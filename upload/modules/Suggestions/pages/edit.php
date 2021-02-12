<?php
/*
 *	Made by Partydragen
 *  https://github.com/partydragen/Nameless-Suggestions
 *  https://partydragen.com/
 *  NamelessMC version 2.0.0-pr8
 *
 *  License: MIT
 *
 *  Suggestions page
 */
 
if(!$user->isLoggedIn()){
    Redirect::to(URL::build('/login'));
    die();
}

if(!$user->canViewACP()){
	require('404.php');
	die();
}
 
// Always define page name for navbar
define('PAGE', 'suggestions');
$page_title = $suggestions_language->get('general', 'suggestions');
require_once(ROOT_PATH . '/core/templates/frontend_init.php');
$timeago = new Timeago(TIMEZONE);

require_once(ROOT_PATH . '/modules/Suggestions/classes/Suggestions.php');
$suggestions = new Suggestions();

if(isset($_GET['sid'])){
	if(is_numeric($_GET['sid'])) {
		$sid = $_GET['sid'];
	} else {
		Redirect::to(URL::build('/suggestions/'));
		die();
	}
} else {
	Redirect::to(URL::build('/suggestions/'));
	die();
}

$suggestion = $queries->getWhere('suggestions', array('id', '=', $sid));
if(!count($suggestion)){
	Redirect::to(URL::build('/suggestions/'));
	die();
}
$suggestion = $suggestion[0];

if(Input::exists()){
	if(Token::check(Input::get('token'))){
		$validate = new Validate();
		$validation = $validate->check($_POST, array(
			'title' => array(
				'required' => true,
				'min' => 5,
				'max' => 128,
			),
			'content' => array(
				'required' => true,
				'min' => 5,
			)
		));
					
		if($validation->passed()){
			$queries->update("suggestions", $suggestion->id, array(
				'category_id' => htmlspecialchars(Input::get('category')),
				'status_id' => htmlspecialchars(Input::get('status')),
				'title' => htmlspecialchars(Input::get('title')),
				'content' => htmlspecialchars(nl2br(Input::get('content'))),
			));
						
			Redirect::to(URL::build('/suggestions/view/' . $suggestion->id));
			die();
		} else {
			foreach($validation->errors() as $error){
				if(strpos($error, 'is required') !== false){
					switch($error){
						case (strpos($error, 'title') !== false):
							$errors[] = 'You must enter a title';
						break;
						case (strpos($error, 'content') !== false):
							$errors[] = 'You must enter a content';
						break;
					}
				} else if(strpos($error, 'minimum') !== false){
					switch($error){
						case (strpos($error, 'title') !== false):
							$errors[] = 'The title must be a minimum of 6 characters';
						break;
						case (strpos($error, 'content') !== false):
							$errors[] = 'The content must be a minimum of 6 characters';
						break;
					}
				} else if(strpos($error, 'maximum') !== false){
					switch($error){
						case (strpos($error, 'title') !== false):
							$errors[] = 'The title must be a maximum of 128 characters';
						break;
					}
				}
			}
		}
	}
}

$smarty->assign(array(
	'TITLE' => Output::getClean($suggestion->title),
	'CONTENT' => str_replace("&lt;br /&gt;", "", Output::getClean(Output::getDecoded($suggestion->content))),
	'CATEGORY' => Output::getClean($suggestion->category_id),
	'STATUS' => Output::getClean($suggestion->status_id),
	'CANCEL' => $language->get('general', 'cancel'),
	'CANCEL_LINK' => URL::build('/suggestions/view/' . $suggestion->id),
	'CATEGORIES' => $suggestions->getCategories(),
	'STATUSES' => $suggestions->getStatuses(),
	'TOKEN' => Token::get(),
));


$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');
	
// Display template
$template->displayTemplate('suggestions/edit.tpl', $smarty);