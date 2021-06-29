<?php
/*
 *  Made by Partydragen
 *  https://github.com/partydragen/Nameless-Suggestions
 *  https://partydragen.com/
 *
 *  Suggestions settings page
 */

// Can the user view the panel?
if(!$user->handlePanelPageLoad('suggestions.manage')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'suggestions_configuration');
define('PANEL_PAGE', 'suggestions_settings');
$page_title = $language->get('admin', 'general_settings');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

// Deal with input
if (Input::exists()) {
    if (Token::check(Input::get('token'))) {
                // Get link location
                if(isset($_POST['link_location'])){
                    switch($_POST['link_location']){
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                            $location = $_POST['link_location'];
                            break;
                        default:
                            $location = 1;
                    }
                } else
                    $location = 1;
                
                // Update Icon cache
                $cache->setCache('navbar_icons');
                $cache->store('suggestions_icon', Input::get('icon'));
                
                // Update Link location cache
                $cache->setCache('suggestions_module_cache');
                $cache->store('link_location', $location);

                Session::flash('suggestions_success', $suggestions_language->get('admin', 'settings_updated_successfully'));
                Redirect::to(URL::build('/panel/suggestions/settings'));
                die();
    } else {
        // Invalid token
        $errors = array($language->get('general', 'invalid_token'));
    }
}

// Retrieve Icon from cache
$cache->setCache('navbar_icons');
$icon = $cache->retrieve('suggestions_icon');

// Retrieve link_location from cache
$cache->setCache('suggestions_module_cache');
$link_location = $cache->retrieve('link_location');

$premium = false;
$cache->setCache('partydragen');
if($cache->isCached('premium')){
    $premium = $cache->retrieve('premium');
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

if(Session::exists('suggestions_success'))
    $success = Session::flash('suggestions_success');

if(isset($success)){
    $smarty->assign(array(
        'SUCCESS_TITLE' => $language->get('general', 'success'),
        'SUCCESS' => $success
    ));
}

if(isset($errors) && count($errors)){
    $smarty->assign(array(
        'ERRORS_TITLE' => $language->get('general', 'error'),
        'ERRORS' => $errors
    ));
}

$smarty->assign(array(
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'PAGE' => PANEL_PAGE,
    'SUGGESTIONS' => $suggestions_language->get('general', 'suggestions'),
    'SETTINGS' => $suggestions_language->get('admin', 'settings'),
    'LINK_LOCATION' => $suggestions_language->get('admin', 'link_location'),
    'LINK_LOCATION_VALUE' => $link_location,
    'LINK_NAVBAR' => $language->get('admin', 'page_link_navbar'),
    'LINK_MORE' => $language->get('admin', 'page_link_more'),
    'LINK_FOOTER' => $language->get('admin', 'page_link_footer'),
    'LINK_NONE' => $language->get('admin', 'page_link_none'),
    'ICON' => $suggestions_language->get('admin', 'icon'),
    'ICON_EXAMPLE' => htmlspecialchars($suggestions_language->get('admin', 'icon_example')),
    'ICON_VALUE' => Output::getClean(htmlspecialchars_decode($icon)),
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'PREMIUM' => $premium
));

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate('suggestions/settings.tpl', $smarty);
