<?php
class Suggestions_Module extends Module {
	private $_language;
	private $_suggestions_language;
	
	public function __construct($language, $suggestions_language, $pages, $queries, $navigation, $cache){
		$this->_language = $language;
		$this->_suggestions_language = $suggestions_language;
		
		$name = 'Suggestions';
		$author = '<a href="https://partydragen.com" target="_blank" rel="nofollow noopener">Partydragen</a>';
		$module_version = '1.1.1';
		$nameless_version = '2.0.0-pr9';
		
		parent::__construct($this, $name, $author, $module_version, $nameless_version);
		
		// Define URLs which belong to this module
		$pages->add('Suggestions', '/suggestions', 'pages/index.php');
		$pages->add('Suggestions', '/suggestions/category', 'pages/category.php');
		$pages->add('Suggestions', '/suggestions/new', 'pages/new.php');
		$pages->add('Suggestions', '/suggestions/edit', 'pages/edit.php');
		$pages->add('Suggestions', '/suggestions/view', 'pages/view.php');
		$pages->add('Suggestions', '/suggestions/search_api', 'pages/search_api.php');
		
		$pages->add('Suggestions', '/panel/suggestions/settings', 'pages/panel/settings.php');
		$pages->add('Suggestions', '/panel/suggestions/categories', 'pages/panel/categories.php');
		$pages->add('Suggestions', '/panel/suggestions/statuses', 'pages/panel/statuses.php');
		
		HookHandler::registerEvent('newSuggestion', 'New Suggestion');
		
		// Check if module version changed
		$cache->setCache('suggestions_module_cache');
		if(!$cache->isCached('module_version')){
			$cache->store('module_version', $module_version);
		} else {
			if($module_version != $cache->retrieve('module_version')) {
				// Version have changed, Perform actions
				$cache->store('module_version', $module_version);
				$cache->erase('update_check');
			}
		}
	}
	
	public function onInstall(){
		// Initialise
		$this->initialise();
	}

	public function onUninstall(){
		// Not necessary
	}

	public function onEnable(){
		// Check if we need to initialise again
		$this->initialise();
	}

	public function onDisable(){
		// Not necessary
	}

	public function onPageLoad($user, $pages, $cache, $smarty, $navs, $widgets, $template){
		// Add link to navbar
		$cache->setCache('navbar_order');
		if(!$cache->isCached('suggestions_order')){
			$order = 24;
			$cache->store('suggestions_order', 24);
		} else {
			$order = $cache->retrieve('suggestions_order');
		}

		$cache->setCache('navbar_icons');
		if(!$cache->isCached('suggestions_icon')){
			$icon = '';
		} else {
			$icon = $cache->retrieve('suggestions_icon');
		}

		$navs[0]->add('suggestions', 'Suggestions', URL::build('/suggestions'), 'top', null, $order, $icon);
		
		if(defined('BACK_END')){
			// Navigation
			$cache->setCache('panel_sidebar');
			
			PermissionHandler::registerPermissions('Suggestions', array(
				'suggestions.manage' => $this->_suggestions_language->get('admin', 'suggestions_manage')
			));
			
			if($user->hasPermission('suggestions.manage')){
				if(!$cache->isCached('suggestions_order')){
					$order = 48;
					$cache->store('suggestions_order', 48);
				} else {
					$order = $cache->retrieve('suggestions_order');
				}

				if(!$cache->isCached('suggestions_icon')){
					$icon = '<i class="nav-icon fas fa-wrench"></i>';
					$cache->store('suggestions_icon', $icon);
				} else
					$icon = $cache->retrieve('suggestions_icon');

				$navs[2]->add('suggestions_divider', mb_strtoupper($this->_suggestions_language->get('admin', 'suggestions_module'), 'UTF-8'), 'divider', 'top', null, $order, '');
				$navs[2]->addDropdown('suggestions_configuration', $this->_suggestions_language->get('general', 'suggestions'), 'top', $order, $icon);
				
				/*if(!$cache->isCached('suggestions_settings_icon')){
					$icon = '<i class="nav-icon fas fa-cogs"></i>';
					$cache->store('suggestions_settings_icon', $icon);
				} else
					$icon = $cache->retrieve('suggestions_settings_icon');

				$navs[2]->addItemToDropdown('suggestions_configuration', 'suggestions_settings', $this->_suggestions_language->get('admin', 'settings'), URL::build('/panel/suggestions/settings'), 'top', $order, $icon);*/
				
				if(!$cache->isCached('suggestions_categories_icon')){
					$icon = '<i class="nav-icon fas fa-folder"></i>';
					$cache->store('suggestions_categories_icon', $icon);
				} else
					$icon = $cache->retrieve('suggestions_categories_icon');

					
				$navs[2]->addItemToDropdown('suggestions_configuration', 'suggestions_categories', $this->_suggestions_language->get('admin', 'categories'), URL::build('/panel/suggestions/categories'), 'top', $order, $icon);
				
				if(!$cache->isCached('suggestions_statuses_icon')){
					$icon = '<i class="nav-icon fas fa-tags"></i>';
					$cache->store('suggestions_statuses_icon', $icon);
				} else
					$icon = $cache->retrieve('suggestions_statuses_icon');

				$navs[2]->addItemToDropdown('suggestions_configuration', 'suggestions_statuses', $this->_suggestions_language->get('admin', 'statuses'), URL::build('/panel/suggestions/statuses'), 'top', $order, $icon);
			}
		}
		
		// Check for module updates
        if(isset($_GET['route']) && $user->isLoggedIn() && $user->hasPermission('admincp.update')){
            if(rtrim($_GET['route'], '/') == '/suggestions' || rtrim($_GET['route'], '/') == '/panel/suggestions/categories'){

                $cache->setCache('suggestions_module_cache');
                if($cache->isCached('update_check')){
                    $update_check = $cache->retrieve('update_check');
                } else {
					require_once(ROOT_PATH . '/modules/Suggestions/classes/Suggestions.php');
                    $update_check = Suggestions::updateCheck();
                    $cache->store('update_check', $update_check, 3600);
                }

                $update_check = json_decode($update_check);
				if(!isset($update_check->error) && !isset($update_check->no_update) && isset($update_check->new_version)){	
                    $smarty->assign(array(
                        'NEW_UPDATE' => str_replace('{x}', $this->getName(), (isset($update_check->urgent) && $update_check->urgent == 'true') ? $this->_suggestions_language->get('admin', 'new_urgent_update_available_x') : $this->_suggestions_language->get('admin', 'new_update_available_x')),
                        'NEW_UPDATE_URGENT' => (isset($update_check->urgent) && $update_check->urgent == 'true'),
                        'CURRENT_VERSION' => str_replace('{x}', $this->getVersion(), $this->_suggestions_language->get('admin', 'current_version_x')),
                        'NEW_VERSION' => str_replace('{x}', Output::getClean($update_check->new_version), $this->_suggestions_language->get('admin', 'new_version_x')),
                        'UPDATE' => $this->_suggestions_language->get('admin', 'view_resource'),
                        'UPDATE_LINK' => 'https://partydragen.com/resources/resource/4-suggestions-system/'
                    ));
				}
            }
        }
	}
	
	private function initialise(){
		// Generate tables
		try {
			$engine = Config::get('mysql/engine');
			$charset = Config::get('mysql/charset');
		} catch(Exception $e){
			$engine = 'InnoDB';
			$charset = 'utf8mb4';
		}

		if(!$engine || is_array($engine))
			$engine = 'InnoDB';

		if(!$charset || is_array($charset))
			$charset = 'latin1';

		$queries = new Queries();
		
		if(!$queries->tableExists('suggestions')) {
			try {
				$queries->createTable('suggestions', ' `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `updated_by` int(11) NOT NULL, `category_id` int(11) NOT NULL, `status_id` int(11) NOT NULL DEFAULT \'1\', `created` int(11) NOT NULL, `last_updated` int(11) NOT NULL, `title` varchar(150) NOT NULL, `content` mediumtext, `likes` int(11) NOT NULL DEFAULT \'0\', `dislikes` int(11) NOT NULL DEFAULT \'0\', `deleted` int(11) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
	
		if(!$queries->tableExists('suggestions_categories')) {
			try {
				$queries->createTable('suggestions_categories', ' `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(32) NOT NULL, `display_order` int(11) NOT NULL, `deleted` int(11) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
		
		if(!$queries->tableExists('suggestions_comments')) {
			try {
				$queries->createTable('suggestions_comments', ' `id` int(11) NOT NULL AUTO_INCREMENT, `suggestion_id` int(11) NOT NULL, `user_id` int(11) NOT NULL, `created` int(11) NOT NULL, `content` mediumtext, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
		
		if(!$queries->tableExists('suggestions_statuses')) {
			try {
				$queries->createTable('suggestions_statuses', ' `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(32) NOT NULL, `html` varchar(1024) NOT NULL, `open` tinyint(1) NOT NULL DEFAULT \'1\', `deleted` int(11) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
				
				$queries->create('suggestions_statuses', array(
					'name' => 'Open',
					'html' => '<span class="badge badge-success">Open</span>',
					'open' => 1
				));
				
				$queries->create('suggestions_statuses', array(
					'name' => 'Closed',
					'html' => '<span class="badge badge-danger">Closed</span>',
					'open' => 0
				));
				
				$queries->create('suggestions_statuses', array(
					'name' => 'Complete',
					'html' => '<span class="badge badge-success">Complete</span>',
					'open' => 1
				));
				
				$queries->create('suggestions_statuses', array(
					'name' => 'In progress',
					'html' => '<span class="badge badge-warning">In progress</span>',
					'open' => 1
				));
			} catch(Exception $e){
				// Error
			}
		}
		
		if(!$queries->tableExists('suggestions_votes')) {
			try {
				$queries->createTable('suggestions_votes', ' `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `suggestion_id` int(11) NOT NULL, `type` tinyint(1) NOT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
		
		if(!$queries->tableExists('suggestions_settings')) {
			try {
				$queries->createTable('suggestions_settings', ' `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(64) NOT NULL, `value` varchar(2048) DEFAULT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
		
		try {
			// Update main admin group permissions
			$group = $queries->getWhere('groups', array('id', '=', 2));
			$group = $group[0];
			
			$group_permissions = json_decode($group->permissions, TRUE);
			$group_permissions['suggestions.manage'] = 1;
			
			$group_permissions = json_encode($group_permissions);
			$queries->update('groups', 2, array('permissions' => $group_permissions));
		} catch(Exception $e){
			// Error
		}
	}
}