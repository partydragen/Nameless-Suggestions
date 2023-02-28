<?php 
/*
 *	Made by Partydragen
 *  https://github.com/partydragen/Nameless-Suggestions
 *  https://partydragen.com1
 *
 *  License: MIT
 *
 *  Suggestions module initialisation file
 */

class Suggestions_Module extends Module {

    private DB $_db;
    private $_language;
    private $_suggestions_language;

    public function __construct(Language $language, Language $suggestions_language, Pages $pages, Navigation $navigation, Cache $cache, Endpoints $endpoints) {
        $this->_db = DB::getInstance();
        $this->_language = $language;
        $this->_suggestions_language = $suggestions_language;

        $name = 'Suggestions';
        $author = '<a href="https://partydragen.com" target="_blank" rel="nofollow noopener">Partydragen</a>';
        $module_version = '1.6.0';
        $nameless_version = '2.0.1';

        parent::__construct($this, $name, $author, $module_version, $nameless_version);

        // Define URLs which belong to this module
        $pages->add('Suggestions', '/suggestions', 'pages/index.php');
        $pages->add('Suggestions', '/suggestions/category', 'pages/category.php');
        $pages->add('Suggestions', '/suggestions/new', 'pages/new.php');
        $pages->add('Suggestions', '/suggestions/edit', 'pages/edit.php');
        $pages->add('Suggestions', '/suggestions/view', 'pages/view.php');
        $pages->add('Suggestions', '/suggestions/search_api', 'pages/search_api.php');
        
        $pages->add('Suggestions', '/queries/suggestion', 'queries/suggestion.php');

        $pages->add('Suggestions', '/panel/suggestions/settings', 'pages/panel/settings.php');
        $pages->add('Suggestions', '/panel/suggestions/categories', 'pages/panel/categories.php');
        $pages->add('Suggestions', '/panel/suggestions/statuses', 'pages/panel/statuses.php');

        // Check if module version changed
        $cache->setCache('suggestions_module_cache');
        if (!$cache->isCached('module_version')) {
            $cache->store('module_version', $module_version);
        } else {
            if ($module_version != $cache->retrieve('module_version')) {
                 // Version have changed, Perform actions
                $this->initialiseUpdate($cache->retrieve('module_version'));

                $cache->store('module_version', $module_version);

                if ($cache->isCached('update_check')) {
                    $cache->erase('update_check');
                }
            }
        }

        EventHandler::registerEvent('newSuggestion', $this->_suggestions_language->get('general', 'new_suggestion'));
        EventHandler::registerEvent('newSuggestionComment', $this->_suggestions_language->get('general', 'new_suggestion_comment'));
        EventHandler::registerEvent('userSuggestionVote', $this->_suggestions_language->get('general', 'user_suggestion_vote'));

        EventHandler::registerEvent('preSuggestionPostCreate',
            $this->_suggestions_language->get('admin', 'pre_suggestion_post_create_hook_info'),
            [
                'content' => $this->_language->get('general', 'content')
            ],
            true,
            true
        );

        EventHandler::registerEvent('preSuggestionPostEdit',
            $this->_suggestions_language->get('admin', 'pre_suggestion_post_edit_hook_info'),
            [
                'content' => $this->_language->get('general', 'content')
            ],
            true,
            true
        );

        EventHandler::registerEvent('renderSuggestionPost',
            $this->_suggestions_language->get('admin', 'render_suggestion_post'),
            [
                'content' => $this->_language->get('general', 'content')
            ],
            true,
            true
        );

        EventHandler::registerEvent('renderSuggestionPostEdit',
            $this->_suggestions_language->get('admin', 'render_suggestion_post_edit'),
            [
                'content' => $this->_language->get('general', 'content')
            ],
            true,
            true
        );

        require_once(ROOT_PATH . "/modules/Suggestions/hooks/SuggestionsMentionsHook.php");

        EventHandler::registerListener('renderSuggestionPost', 'ContentHook::purify');
        EventHandler::registerListener('renderSuggestionPost', 'ContentHook::codeTransform', false, 15);
        EventHandler::registerListener('renderSuggestionPost', 'ContentHook::decode', false, 20);
        EventHandler::registerListener('renderSuggestionPost', 'ContentHook::renderEmojis', false, 10);
        EventHandler::registerListener('renderSuggestionPost', 'ContentHook::replaceAnchors', false, 15);

        $endpoints->loadEndpoints(ROOT_PATH . '/modules/Suggestions/includes/endpoints');

        Endpoints::registerTransformer('suggestion', 'Suggestions', static function (Nameless2API $api, string $value) {
            $suggestion = new Suggestion($value);
            if ($suggestion->exists()) {
                return $suggestion;
            }

            $api->throwError(SuggestionsApiErrors::ERROR_SUGGESTION_NOT_FOUND);
        });
    }

    public function onInstall() {
        // Initialise
        $this->initialise();
    }

    public function onUninstall() {
        // Not necessary
    }

    public function onEnable() {
        // Check if we need to initialise again
        $this->initialise();
    }

    public function onDisable() {
        // Not necessary
    }

    public function onPageLoad($user, $pages, $cache, $smarty, $navs, $widgets, $template) {
        // navigation link location
        $cache->setCache('suggestions_module_cache');
        if (!$cache->isCached('link_location')) {
            $link_location = 1;
            $cache->store('link_location', 1);
        } else {
            $link_location = $cache->retrieve('link_location');
        }

        // Add link to navbar
        $cache->setCache('navbar_order');
        if (!$cache->isCached('suggestions_order')) {
            $order = 24;
            $cache->store('suggestions_order', 24);
        } else {
            $order = $cache->retrieve('suggestions_order');
        }

        $cache->setCache('navbar_icons');
        if (!$cache->isCached('suggestions_icon')) {
            $icon = '';
        } else {
            $icon = $cache->retrieve('suggestions_icon');
        }

        switch ($link_location) {
            case 1:
                // Navbar
                $navs[0]->add('suggestions', $this->_suggestions_language->get('general', 'suggestions'), URL::build('/suggestions'), 'top', null, $order, $icon);
            break;
            case 2:
                // "More" dropdown
                $navs[0]->addItemToDropdown('more_dropdown', 'suggestions', $this->_suggestions_language->get('general', 'suggestions'), URL::build('/suggestions'), 'top', null, $icon, $order);
            break;
            case 3:
                // Footer
                $navs[0]->add('suggestions', $this->_suggestions_language->get('general', 'suggestions'), URL::build('/suggestions'), 'footer', null, $order, $icon);
            break;
        }

        if (defined('BACK_END')) {
            // Navigation
            $cache->setCache('panel_sidebar');

            PermissionHandler::registerPermissions('Suggestions', [
                'suggestions.manage' => $this->_suggestions_language->get('admin', 'suggestions_manage'),
                'suggestions.create' => $this->_suggestions_language->get('admin', 'suggestions_create_permission'),
                'suggestions.comment' => $this->_suggestions_language->get('admin', 'suggestions_comment_permission')
            ]);

            if ($user->hasPermission('suggestions.manage')) {
                if (!$cache->isCached('suggestions_order')) {
                    $order = 48;
                    $cache->store('suggestions_order', 48);
                } else {
                    $order = $cache->retrieve('suggestions_order');
                }

                if (!$cache->isCached('suggestions_icon')) {
                    $icon = '<i class="nav-icon fas fa-wrench"></i>';
                    $cache->store('suggestions_icon', $icon);
                } else
                    $icon = $cache->retrieve('suggestions_icon');

                $navs[2]->add('suggestions_divider', mb_strtoupper($this->_suggestions_language->get('admin', 'suggestions_module'), 'UTF-8'), 'divider', 'top', null, $order, '');
                $navs[2]->addDropdown('suggestions_configuration', $this->_suggestions_language->get('general', 'suggestions'), 'top', $order, $icon);

                if (!$cache->isCached('suggestions_settings_icon')) {
                    $icon = '<i class="nav-icon fas fa-cogs"></i>';
                    $cache->store('suggestions_settings_icon', $icon);
                } else
                    $icon = $cache->retrieve('suggestions_settings_icon');

                $navs[2]->addItemToDropdown('suggestions_configuration', 'suggestions_settings', $this->_suggestions_language->get('admin', 'settings'), URL::build('/panel/suggestions/settings'), 'top', null, $icon, $order);

                if (!$cache->isCached('suggestions_categories_icon')) {
                    $icon = '<i class="nav-icon fas fa-folder"></i>';
                    $cache->store('suggestions_categories_icon', $icon);
                } else
                    $icon = $cache->retrieve('suggestions_categories_icon');

                $navs[2]->addItemToDropdown('suggestions_configuration', 'suggestions_categories', $this->_suggestions_language->get('admin', 'categories'), URL::build('/panel/suggestions/categories'), 'top', null, $icon, $order);

                if (!$cache->isCached('suggestions_statuses_icon')) {
                    $icon = '<i class="nav-icon fas fa-tags"></i>';
                    $cache->store('suggestions_statuses_icon', $icon);
                } else
                    $icon = $cache->retrieve('suggestions_statuses_icon');

                $navs[2]->addItemToDropdown('suggestions_configuration', 'suggestions_statuses', $this->_suggestions_language->get('admin', 'statuses'), URL::build('/panel/suggestions/statuses'), 'top', null, $icon, $order);
            }
        }

        // Check for module updates
        if (isset($_GET['route']) && $user->isLoggedIn() && $user->hasPermission('admincp.update')) {
            // Page belong to this module?
            $page = $pages->getActivePage();
            if ($page['module'] == 'Suggestions') {

                $cache->setCache('suggestions_module_cache');
                if ($cache->isCached('update_check')) {
                    $update_check = $cache->retrieve('update_check');
                } else {
                    require_once(ROOT_PATH . '/modules/Suggestions/classes/Suggestions.php');
                    $update_check = Suggestions::updateCheck();
                    $cache->store('update_check', $update_check, 3600);
                }

                $update_check = json_decode($update_check);
                if (!isset($update_check->error) && !isset($update_check->no_update) && isset($update_check->new_version)) {  
                    $smarty->assign([
                        'NEW_UPDATE' => (isset($update_check->urgent) && $update_check->urgent == 'true') ? $this->_suggestions_language->get('admin', 'new_urgent_update_available_x', ['module' => $this->getName()]) : $this->_suggestions_language->get('admin', 'new_update_available_x', ['module' => $this->getName()]),
                        'NEW_UPDATE_URGENT' => (isset($update_check->urgent) && $update_check->urgent == 'true'),
                        'CURRENT_VERSION' => $this->_suggestions_language->get('admin', 'current_version_x', ['version' => Output::getClean($this->getVersion())]),
                        'NEW_VERSION' => $this->_suggestions_language->get('admin', 'new_version_x', ['new_version' => Output::getClean($update_check->new_version)]),
                        'NAMELESS_UPDATE' => $this->_suggestions_language->get('admin', 'view_resource'),
                        'NAMELESS_UPDATE_LINK' => Output::getClean($update_check->link)
                    ]);
                }
            }
        }
    }

    public function getDebugInfo(): array {
        return [];
    }
    
    private function initialiseUpdate($old_version) {
        $old_version = str_replace(array(".", "-"), "", $old_version);

        if ($old_version < 150) {
            try {
                $this->_db->addColumn('suggestions', '`views`', 'int(11) NOT NULL DEFAULT \'0\'');
            } catch (Exception $e) {
                // Error
            }
        }

        if ($old_version < 151) {
            try {
                $groups = $this->_db->query('SELECT id, permissions FROM nl2_groups')->results();
                foreach ($groups as $group) {
                    try {
                        $group_permissions = json_decode($group->permissions, TRUE);
                        $group_permissions['suggestions.create'] = 1;
                        $group_permissions['suggestions.comment'] = 1;

                        $group_permissions = json_encode($group_permissions);
                        $this->_db->update('groups', $group->id, ['permissions' => $group_permissions]);
                    } catch (Exception $e) {
                        // Error
                    }
                }
            } catch (Exception $e) {
                // Error
            }
        }

        if ($old_version < 160) {
            try {
                $this->_db->addColumn('suggestions_statuses', '`color`', "varchar(32) NULL DEFAULT NULL");
            } catch (Exception $e) {
                // Error
            }

            try {
                $this->_db->addColumn('suggestions_comments', '`type`', "int(11) NOT NULL DEFAULT '1'");
            } catch (Exception $e) {
                // Error
            }
        }
    }

    private function initialise() {
        // Generate tables
        if (!$this->_db->showTables('suggestions')) {
            try {
                $this->_db->createTable('suggestions', ' `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `updated_by` int(11) NOT NULL, `category_id` int(11) NOT NULL, `status_id` int(11) NOT NULL DEFAULT \'1\', `created` int(11) NOT NULL, `last_updated` int(11) NOT NULL, `title` varchar(150) NOT NULL, `content` mediumtext, `likes` int(11) NOT NULL DEFAULT \'0\', `dislikes` int(11) NOT NULL DEFAULT \'0\', `views` int(11) NOT NULL DEFAULT \'0\', `deleted` int(11) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)');
            } catch(Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('suggestions_categories')) {
            try {
                $this->_db->createTable('suggestions_categories', ' `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(32) NOT NULL, `display_order` int(11) NOT NULL, `deleted` int(11) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)');
            } catch(Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('suggestions_comments')) {
            try {
                $this->_db->createTable('suggestions_comments', ' `id` int(11) NOT NULL AUTO_INCREMENT, `suggestion_id` int(11) NOT NULL, `user_id` int(11) NOT NULL, `type` int(11) NOT NULL DEFAULT \'1\', `created` int(11) NOT NULL, `content` mediumtext, PRIMARY KEY (`id`)');
            } catch(Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('suggestions_statuses')) {
            try {
                $this->_db->createTable('suggestions_statuses', ' `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(32) NOT NULL, `html` varchar(1024) NOT NULL, `open` tinyint(1) NOT NULL DEFAULT \'1\', `color` varchar(32) NULL DEFAULT NULL, `deleted` int(11) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)');

                $this->_db->insert('suggestions_statuses', [
                    'name' => 'Open',
                    'html' => '<span class="badge badge-success">Open</span>',
                    'open' => 1
                ]);

                $this->_db->insert('suggestions_statuses', [
                    'name' => 'Closed',
                    'html' => '<span class="badge badge-danger">Closed</span>',
                    'open' => 0
                ]);

                $this->_db->insert('suggestions_statuses', [
                    'name' => 'Complete',
                    'html' => '<span class="badge badge-success">Complete</span>',
                    'open' => 1
                ]);

                $this->_db->insert('suggestions_statuses', [
                    'name' => 'In progress',
                    'html' => '<span class="badge badge-warning">In progress</span>',
                    'open' => 1
                ]);
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('suggestions_votes')) {
            try {
                $this->_db->createTable('suggestions_votes', ' `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `suggestion_id` int(11) NOT NULL, `type` tinyint(1) NOT NULL, PRIMARY KEY (`id`)');
            } catch(Exception $e) {
                // Error
            }
        }

        try {
            // Update main admin group permissions
            $group = $this->_db->get('groups', ['id', '=', 2])->results();
            $group = $group[0];

            $group_permissions = json_decode($group->permissions, TRUE);
            $group_permissions['suggestions.manage'] = 1;

            $group_permissions = json_encode($group_permissions);
            $this->_db->update('groups', 2, ['permissions' => $group_permissions]);
        } catch (Exception $e) {
            // Error
        }
    }
}