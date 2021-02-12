<?php
/*
 *	Made by Partydragen
 *  https://github.com/partydragen/Nameless-Suggestions
 *  https://partydragen.com/
 *
 *  Suggestions settings page
 */

// Can the user view the panel?
if($user->isLoggedIn()){
	if(!$user->canViewACP()){
		// No
		Redirect::to(URL::build('/'));
		die();
	}
	if(!$user->isAdmLoggedIn()){
		// Needs to authenticate
		Redirect::to(URL::build('/panel/auth'));
		die();
	} else {
		if(!$user->hasPermission('suggestions.manage')){
			require_once(ROOT_PATH . '/403.php');
			die();
		}
	}
} else {
	// Not logged in
	Redirect::to(URL::build('/login'));
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

	} else {
		// Invalid token
		$errors = array($language->get('general', 'invalid_token'));
	}
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

if(Session::exists('general_language'))
	$success = Session::flash('general_language');

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
	'TOKEN' => Token::get(),
	'SUBMIT' => $language->get('general', 'submit'),
	'SUGGESTIONS' => $suggestions_language->get('suggestions', 'suggestions'),
));

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate('suggestions/settings.tpl', $smarty);
