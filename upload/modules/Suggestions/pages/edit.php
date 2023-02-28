<?php
/*
 *  Made by Partydragen
 *  https://github.com/partydragen/Nameless-Suggestions
 *  https://partydragen.com/
 *  NamelessMC version 2.0.0-pr11
 *
 *  License: MIT
 *
 *  Suggestions page
 */
 
if (!$user->isLoggedIn()) {
    Redirect::to(URL::build('/login'));
    die();
}

if (!$user->canViewStaffCP()) {
    require('404.php');
    die();
}
 
// Always define page name for navbar
define('PAGE', 'suggestions');
$page_title = $suggestions_language->get('general', 'suggestions');
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

require_once(ROOT_PATH . '/modules/Suggestions/classes/Suggestions.php');
$suggestions = new Suggestions();

if (isset($_GET['sid'])) {
    if (is_numeric($_GET['sid'])) {
        $sid = $_GET['sid'];
    } else {
        Redirect::to(URL::build('/suggestions/'));
    }
} else {
    Redirect::to(URL::build('/suggestions/'));
}

$suggestion = new Suggestion($sid);
if (!$suggestion->exists()) {
    Redirect::to(URL::build('/suggestions/'));
}

if (Input::exists()) {
    if (Token::check(Input::get('token'))) {
        $errors = [];

        $validation = Validate::check($_POST, [
            'title' => [
                Validate::REQUIRED => true,
                Validate::MIN => 6,
                Validate::MAX => 128,
            ],
            'content' => [
                Validate::REQUIRED => true,
                Validate::MIN => 6,
                Validate::MAX => 50000
            ]
        ])->messages([
            'title' => [
                Validate::REQUIRED => $suggestions_language->get('general', 'title_required'),
                Validate::MIN => $suggestions_language->get('general', 'title_minimum'),
                Validate::MAX => $suggestions_language->get('general', 'title_maximum'),
            ],
            'content' => [
                Validate::REQUIRED => $suggestions_language->get('general', 'content_required'),
                Validate::MIN => $suggestions_language->get('general', 'content_minimum')
            ]
        ]);

        if ($validation->passed()) {
            // Check if category exists
            $category = DB::getInstance()->query('SELECT id FROM nl2_suggestions_categories WHERE id = ? AND deleted = 0', [Input::get('category')])->results();
            if (!count($category)) {
                $errors[] = 'Invalid Category';
            }

            if (!count($errors)) {
                $event_data = EventHandler::executeEvent('preSuggestionPostEdit', [
                    'suggestion_id' => $suggestion->data()->id,
                    'title' => Input::get('title'),
                    'content' => nl2br(Input::get('content')),
                    'user' => $user
                ]);

                $suggestion->update([
                    'category_id' => Input::get('category'),
                    'status_id' => Input::get('status'),
                    'title' => $event_data['title'],
                    'content' => $event_data['content']
                ]);

                Redirect::to($suggestion->getURL());
            }
        } else {
            // Validation errors
            $errors = $validate->errors();
        }
    } else {
        $errors[] = $language->get('general', 'invalid_token');
    }
}

if (isset($errors) && count($errors))
    $smarty->assign('ERRORS', $errors);

$smarty->assign([
    'EDITING_SUGGESTION' => $suggestions_language->get('general', 'editing_suggestion'),
    'SUGGESTION_TITLE' => $suggestions_language->get('general', 'title'),
    'TITLE_VALUE' => Output::getClean($suggestion->data()->title),
    'CONTENT' => $suggestions_language->get('general', 'content'),
    'CONTENT_VALUE' => str_replace("&lt;br /&gt;", "", Output::getClean(Output::getDecoded($suggestion->data()->content))),
    'CATEGORY' => $suggestions_language->get('general', 'category'),
    'CATEGORY_VALUE' => Output::getClean($suggestion->data()->category_id),
    'STATUS' => $suggestions_language->get('general', 'status'),
    'STATUS_VALUE' => Output::getClean($suggestion->data()->status_id),
    'CANCEL' => $language->get('general', 'cancel'),
    'CANCEL_LINK' => $suggestion->getURL(),
    'CATEGORIES' => $suggestions->getCategories(),
    'STATUSES' => $suggestions->getStatuses(),
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit')
]);

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');
    
// Display template
$template->displayTemplate('suggestions/edit.tpl', $smarty);