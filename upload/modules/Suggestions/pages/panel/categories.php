<?php
/*
 *  Made by Partydragen
 *  https://github.com/partydragen/Nameless-Suggestions
 *  https://partydragen.com/
 *
 *  Suggestions categories page
 */

// Can the user view the panel?
if (!$user->handlePanelPageLoad('suggestions.manage')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'suggestions_configuration');
define('PANEL_PAGE', 'suggestions_categories');
$page_title = $suggestions_language->get('general', 'suggestions');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

if (!isset($_GET['action'])) {

    $categories = DB::getInstance()->query('SELECT * FROM nl2_suggestions_categories WHERE deleted = 0 ORDER BY display_order ASC')->results();
    $categories_array = [];
    if (count($categories)) {
        foreach ($categories as $category) {
            $categories_array[] = [
                'name' => Output::getClean($category->name),
                'edit_link' => URL::build('/panel/suggestions/categories', 'action=edit&id=' . Output::getClean($category->id)),
                'delete_link' => URL::build('/panel/suggestions/categories', 'action=delete&id=' . Output::getClean($category->id))
            ];
        }
    }

    $smarty->assign([
        'CATEGORIES_LIST' => $categories_array,
        'NEW_CATEGORY' => $suggestions_language->get('admin', 'new_category'),
        'NEW_CATEGORY_LINK' => URL::build('/panel/suggestions/categories/', 'action=new'),
        'NONE_CATEGORIES_DEFINED' => $suggestions_language->get('general', 'none_categories_defined'),
        'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
        'CONFIRM_DELETE_CATEGORY' => $suggestions_language->get('admin', 'confirm_delete_category'),
        'YES' => $language->get('general', 'yes'),
        'NO' => $language->get('general', 'no')
    ]);

    $template_file = 'suggestions/categories.tpl';
} else {
    switch ($_GET['action']) {
        case 'new':
            if (Input::exists()) {
                $errors = [];

                if (Token::check(Input::get('token'))) {
                    // Validate input
                    $validation = Validate::check($_POST, [
                        'name' => [
                            Validate::REQUIRED => true,
                            Validate::MIN => 2,
                            Validate::MAX => 32
                        ]
                    ]);

                    if ($validation->passed()) {
                        // Save to database
                        DB::getInstance()->insert('suggestions_categories', [
                            'name' => Output::getClean(Input::get('name')),
                            'display_order' => Output::getClean(Input::get('order')),
                        ]);

                        Session::flash('staff_suggestions', $suggestions_language->get('admin', 'category_created_successfully'));
                        Redirect::to(URL::build('/panel/suggestions/categories'));
                    } else {
                        // Validation Errors
                        $errors = $validation->errors();
                    }
                } else {
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }

            $smarty->assign([
                'CREATING_NEW_CATEGORY' => $suggestions_language->get('admin', 'creating_new_category'),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/suggestions/categories/'),
                'CATEGORY_NAME' => $suggestions_language->get('admin', 'category_name'),
                'CATEGORY_ORDER' => $suggestions_language->get('admin', 'category_order'),
            ]);

            $template_file = 'suggestions/categories_new.tpl';
        break;
        case 'edit':
            // Edit Category
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                Redirect::to(URL::build('/panel/suggestions/categories'));
            }

            $category = DB::getInstance()->query('SELECT * FROM nl2_suggestions_categories WHERE id = ? AND deleted = 0', [$_GET['id']])->results();
            if (!count($category)) {
                Redirect::to(URL::build('/panel/suggestions/categories'));
            }
            $category = $category[0];

            if (Input::exists()) {
                $errors = [];

                if (Token::check(Input::get('token'))) {
                    // Validate input
                    $validation = Validate::check($_POST, [
                        'name' => [
                            Validate::REQUIRED => true,
                            Validate::MIN => 2,
                            Validate::MAX => 32
                        ]
                    ]);

                    if ($validation->passed()) {
                        // Save to database
                        DB::getInstance()->update('suggestions_categories', $category->id, [
                            'name' => Output::getClean(Input::get('name')),
                            'display_order' => Output::getClean(Input::get('order')),
                        ]);

                        Session::flash('staff_suggestions', $suggestions_language->get('admin', 'category_updated_successfully'));
                        Redirect::to(URL::build('/panel/suggestions/categories/', 'action=edit&id=' . Output::getClean($category->id)));
                    } else {
                        // Validation Errors
                        $errors = $validation->errors();
                    }
                } else {
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }

            $smarty->assign([
                'EDITING_CATEGORY' => $suggestions_language->get('admin', 'editing_x', ['name' => Output::getClean($category->name)]),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/suggestions/categories/'),
                'CATEGORY_NAME' => $suggestions_language->get('admin', 'category_name'),
                'CATEGORY_NAME_VALUE' => Output::getClean($category->name),
                'CATEGORY_ORDER' => $suggestions_language->get('admin', 'category_order'),
                'CATEGORY_ORDER_VALUE' => Output::getClean($category->display_order),
            ]);

            $template_file = 'suggestions/categories_edit.tpl';
        break;
        case 'delete':
            // Delete Category
            if  (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                Redirect::to(URL::build('/panel/suggestions/categories'));
            }

            $category = DB::getInstance()->query('SELECT * FROM nl2_suggestions_categories WHERE id = ? AND deleted = 0', [$_GET['id']])->results();
            if (count($category)) {
                DB::getInstance()->update('suggestions_categories', $category[0]->id, [
                    'deleted' => date('U')
                ]);

                Session::flash('staff_suggestions', $suggestions_language->get('admin', 'category_deleted_successfully'));
            }

            Redirect::to(URL::build('/panel/suggestions/categories'));
        break;
        default:
            Redirect::to(URL::build('/panel/suggestions/categories'));
        break;
    }
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Session::exists('staff_suggestions'))
    $success = Session::flash('staff_suggestions');

if (isset($success)) {
    $smarty->assign([
        'SUCCESS_TITLE' => $language->get('general', 'success'),
        'SUCCESS' => $success
    ]);
}

if (isset($errors) && count($errors)) {
    $smarty->assign([
        'ERRORS_TITLE' => $language->get('general', 'error'),
        'ERRORS' => $errors
    ]);
}

$smarty->assign([
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'CATEGORIES' => $suggestions_language->get('admin', 'categories'),
    'SUGGESTIONS' => $suggestions_language->get('general', 'suggestions')
]);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);
