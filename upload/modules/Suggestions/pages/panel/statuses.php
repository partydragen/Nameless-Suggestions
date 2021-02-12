<?php
/*
 *  Made by Partydragen
 *  https://github.com/partydragen/Nameless-Suggestions
 *  https://partydragen.com/
 *
 *  Suggestions statuses page
 */

// Can the user view the panel?
$user->handlePanelPageLoad('suggestions.manage');

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
            if(Input::exists()){
                $errors = array();
                if(Token::check(Input::get('token'))){
                    // Validate input
                    $validate = new Validate();
                    $validation = $validate->check($_POST, array(
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
                    ));
                    
                    if($validation->passed()){
                        // is status marked as open
                        if(isset($_POST['open']) && $_POST['open'] == 'on') $open = 1;
                        else $open = 0;
                        
                        // Save to database
                        $queries->create('suggestions_statuses', array(
                            'name' => Output::getClean(Input::get('name')),
                            'html' => Input::get('html'),
                            'open' => $open,
                        ));
                        
                        Session::flash('staff_suggestions', $suggestions_language->get('admin', 'status_created_successfully'));
                        Redirect::to(URL::build('/panel/suggestions/statuses'));
                        die();
                    } else {
                        // Errors
                        foreach($validation->errors() as $item){
                        }
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
            if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
                Redirect::to(URL::build('/panel/suggestions/statuses'));
                die();
            }
            
            $status = DB::getInstance()->query('SELECT * FROM nl2_suggestions_statuses WHERE id = ? AND deleted = 0', array($_GET['id']))->results();
            if(!count($status)) {
                Redirect::to(URL::build('/panel/suggestions/statuses'));
                die();
            }
            $status = $status[0];
            
            if(Input::exists()){
                $errors = array();
                if(Token::check(Input::get('token'))){
                    // Validate input
                    $validate = new Validate();
                    $validation = $validate->check($_POST, array(
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
                    ));
                    
                    if($validation->passed()){
                        // is status marked as open
                        if(isset($_POST['open']) && $_POST['open'] == 'on') $open = 1;
                        else $open = 0;
                        
                        // Save to database
                        $queries->update('suggestions_statuses', $status->id, array(
                            'name' => Output::getClean(Input::get('name')),
                            'html' => Input::get('html'),
                            'open' => $open,
                        ));
                        
                        Session::flash('staff_suggestions', $suggestions_language->get('admin', 'status_updated_successfully'));
                        Redirect::to(URL::build('/panel/suggestions/statuses', 'action=edit&id=' . Output::getClean($status->id)));
                        die();
                    } else {
                        // Errors
                        foreach($validation->errors() as $item){
                        }
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
                die();
            }
            
            $status = DB::getInstance()->query('SELECT * FROM nl2_suggestions_statuses WHERE id = ? AND deleted = 0', array($_GET['id']))->results();
            if(count($status)) {
                $queries->update('suggestions_statuses', $status[0]->id, array(
                    'deleted' => date('U')
                ));
                Session::flash('staff_suggestions', $suggestions_language->get('admin', 'status_deleted_successfully'));
            }
            Redirect::to(URL::build('/panel/suggestions/statuses'));
            die();
        break;
        default:
            Redirect::to(URL::build('/panel/suggestions/statuses'));
            die();
        break;
    }
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

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
));

if(isset($_GET['action'])){
    $template->addCSSFiles(array(
        (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.css' => array()
    ));

    $template->addJSFiles(array(
        (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.js' => array()
    ));

    $template->addJSScript('
        var elems = Array.prototype.slice.call(document.querySelectorAll(\'.js-switch\'));
        elems.forEach(function(html) {
            var switchery = new Switchery(html, {color: \'#23923d\', secondaryColor: \'#e56464\'});
        });
    ');
}

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);
