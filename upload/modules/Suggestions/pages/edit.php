<?php
/*
 *  Made by Partydragen
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

if(!$user->canViewStaffCP()){
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
        $errors = array();
        
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
                            $errors[] = $suggestions_language->get('general', 'title_required');
                        break;
                        case (strpos($error, 'content') !== false):
                            $errors[] = $suggestions_language->get('general', 'content_required');
                        break;
                    }
                } else if(strpos($error, 'minimum') !== false){
                    switch($error){
                        case (strpos($error, 'title') !== false):
                            $errors[] = $suggestions_language->get('general', 'title_minimum');
                        break;
                        case (strpos($error, 'content') !== false):
                            $errors[] = $suggestions_language->get('general', 'content_minimum');
                        break;
                    }
                } else if(strpos($error, 'maximum') !== false){
                    switch($error){
                        case (strpos($error, 'title') !== false):
                            $errors[] = $suggestions_language->get('general', 'title_maximum');
                        break;
                    }
                }
            }
        }
    }
}

if(isset($errors) && count($errors))
    $smarty->assign('ERRORS', $errors);

$smarty->assign(array(
    'EDITING_SUGGESTION' => $suggestions_language->get('general', 'editing_suggestion'),
    'SUGGESTION_TITLE' => $suggestions_language->get('general', 'title'),
    'TITLE_VALUE' => Output::getClean($suggestion->title),
    'CONTENT' => $suggestions_language->get('general', 'content'),
    'CONTENT_VALUE' => str_replace("&lt;br /&gt;", "", Output::getClean(Output::getDecoded($suggestion->content))),
    'CATEGORY' => $suggestions_language->get('general', 'category'),
    'CATEGORY_VALUE' => Output::getClean($suggestion->category_id),
    'STATUS' => $suggestions_language->get('general', 'status'),
    'STATUS_VALUE' => Output::getClean($suggestion->status_id),
    'CANCEL' => $language->get('general', 'cancel'),
    'CANCEL_LINK' => URL::build('/suggestions/view/' . $suggestion->id),
    'CATEGORIES' => $suggestions->getCategories(),
    'STATUSES' => $suggestions->getStatuses(),
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit')
));

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');
    
// Display template
$template->displayTemplate('suggestions/edit.tpl', $smarty);