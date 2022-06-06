<?php
/*
 *  Made by Partydragen
 *  https://github.com/partydragen/Nameless-Suggestions
 *  https://partydragen.com/
 *
 *  Suggestions statuses page
 */

// Can the user view the panel?
if(!$user->handlePanelPageLoad('suggestions.manage')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'suggestions_configuration');
define('PANEL_PAGE', 'suggestions_statuses');
$page_title = $suggestions_language->get('general', 'suggestions');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

if(!isset($_GET['action'])){
    
    $statuses = DB::getInstance()->query('SELECT * FROM nl2_suggestions_statuses WHERE deleted = 0')->results();
    $statuses_array = array();
    if(count($statuses)){
        foreach($statuses as $status){
            $statuses_array[] = array(
                'html' => $status->html,
                'open' => $status->open,
                'edit_link' => URL::build('/panel/suggestions/statuses', 'action=edit&id=' . Output::getClean($status->id)),
                'delete_link' => URL::build('/panel/suggestions/statuses', 'action=delete&id=' . Output::getClean($status->id))
            );
        }
    }
    
    $smarty->assign(array(
        'STATUSES' => $suggestions_language->get('admin', 'statuses'),
        'STATUS' => $suggestions_language->get('admin', 'status'),
        'STATUSES_LIST' => $statuses_array,
        'NEW_STATUS' => $suggestions_language->get('admin', 'new_status'),
        'NEW_STATUS_LINK' => URL::build('/panel/suggestions/statuses/', 'action=new'),
        'MARKED_AS_OPEN' => $suggestions_language->get('admin', 'marked_as_open'),
        'ACTIONS' => $suggestions_language->get('admin', 'actions'),
        'NONE_STATUSES_DEFINED' => $suggestions_language->get('general', 'none_statuses_defined'),
        'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
        'CONFIRM_DELETE_STATUS' => $suggestions_language->get('admin', 'confirm_delete_status'),
        'YES' => $language->get('general', 'yes'),
        'NO' => $language->get('general', 'no')
    ));
            
    $template_file = 'suggestions/statuses.tpl';
} else {
    switch($_GET['action']){
        case 'new':
            if (Input::exists()) {
                $errors = array();

                if (Token::check(Input::get('token'))) {
                    // Validate input
                    $validation = Validate::check($_POST, [
                        'name' => array(
                            'required' => true,
                            'min' => 2,
                            'max' => 32
                        ),
                        'html' => array(
                            'required' => true,
                            'min' => 2,
                            'max' => 1024
                        )
                    ]);

                    if ($validation->passed()) {
                        // is status marked as open
                        if(isset($_POST['open']) && $_POST['open'] == 'on') $open = 1;
                        else $open = 0;

                        // Save to database
                        $DB::getInstance()->insert('suggestions_statuses', array(
                            'name' => Output::getClean(Input::get('name')),
                            'html' => Input::get('html'),
                            'open' => $open,
                        ));

                        Session::flash('staff_suggestions', $suggestions_language->get('admin', 'status_created_successfully'));
                        Redirect::to(URL::build('/panel/suggestions/statuses'));
                    } else {
                        // Validation Errors
                        $errors = $validation->errors();
                    }
                } else {
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }

            $smarty->assign(array(
                'CREATING_NEW_STATUS' => $suggestions_language->get('admin', 'creating_new_status'),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/suggestions/statuses/'),
                'STATUS_NAME' => $suggestions_language->get('admin', 'status_name'),
                'STATUS_HTML' => $suggestions_language->get('admin', 'status_html'),
                'MARKED_AS_OPEN' => $suggestions_language->get('admin', 'marked_as_open'),
            ));

            $template_file = 'suggestions/statuses_new.tpl';
        break;
        case 'edit':
            // Edit Status
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                Redirect::to(URL::build('/panel/suggestions/statuses'));
            }

            $status = DB::getInstance()->query('SELECT * FROM nl2_suggestions_statuses WHERE id = ? AND deleted = 0', array($_GET['id']))->results();
            if (!count($status)) {
                Redirect::to(URL::build('/panel/suggestions/statuses'));
            }
            $status = $status[0];

            if (Input::exists()) {
                $errors = [];

                if (Token::check(Input::get('token'))) {
                    // Validate input
                    $validation = Validate::check($_POST, [
                        'name' => array(
                            'required' => true,
                            'min' => 2,
                            'max' => 32
                        ),
                        'html' => array(
                            'required' => true,
                            'min' => 2,
                            'max' => 1024
                        )
                    ]);

                    if ($validation->passed()) {
                        // is status marked as open
                        if(isset($_POST['open']) && $_POST['open'] == 'on') $open = 1;
                        else $open = 0;

                        // Save to database
                        $DB::getInstance()->update('suggestions_statuses', $status->id, array(
                            'name' => Output::getClean(Input::get('name')),
                            'html' => Input::get('html'),
                            'open' => $open,
                        ));

                        Session::flash('staff_suggestions', $suggestions_language->get('admin', 'status_updated_successfully'));
                        Redirect::to(URL::build('/panel/suggestions/statuses', 'action=edit&id=' . Output::getClean($status->id)));
                    } else {
                        // Validation Errors
                        $errors = $validation->errors();
                    }
                } else {
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }

            $smarty->assign(array(
                'EDITING_STATUS' => str_replace('{x}', Output::getClean($status->name), $suggestions_language->get('admin', 'editing_x')),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/suggestions/statuses/'),
                'STATUS_NAME' => $suggestions_language->get('admin', 'status_name'),
                'STATUS_NAME_VALUE' => Output::getClean($status->name),
                'STATUS_HTML' => $suggestions_language->get('admin', 'status_html'),
                'STATUS_HTML_VALUE' => Output::getClean($status->html),
                'MARKED_AS_OPEN' => $suggestions_language->get('admin', 'marked_as_open'),
                'MARKED_AS_OPEN_VALUE' => Output::getClean($status->open),
            ));

            $template_file = 'suggestions/statuses_edit.tpl';
        break;
        case 'delete':
            // Edit Status
            if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
                Redirect::to(URL::build('/panel/suggestions/statuses'));
            }

            $status = DB::getInstance()->query('SELECT * FROM nl2_suggestions_statuses WHERE id = ? AND deleted = 0', array($_GET['id']))->results();
            if(count($status)) {
                $DB::getInstance()->update('suggestions_statuses', $status[0]->id, array(
                    'deleted' => date('U')
                ));
                Session::flash('staff_suggestions', $suggestions_language->get('admin', 'status_deleted_successfully'));
            }
            Redirect::to(URL::build('/panel/suggestions/statuses'));
        break;
        default:
            Redirect::to(URL::build('/panel/suggestions/statuses'));
        break;
    }
}

$premium = false;
$cache->setCache('partydragen');
if($cache->isCached('premium')){
    $premium = $cache->retrieve('premium');
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if(Session::exists('staff_suggestions'))
    $success = Session::flash('staff_suggestions');

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
    'SUGGESTIONS' => $suggestions_language->get('general', 'suggestions'),
    'PREMIUM' => $premium
));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);
