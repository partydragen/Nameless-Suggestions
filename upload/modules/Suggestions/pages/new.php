<?php
/*
 *  Made by Partydragen
 *  https://github.com/partydragen/Nameless-Suggestions
 *  https://partydragen.com/
 *
 *  Suggestions page
 */
 
if (!$user->isLoggedIn()) {
    Redirect::to(URL::build('/login'));
    die();
}

if (!$user->hasPermission('suggestions.create')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

// Always define page name for navbar
define('PAGE', 'suggestions');
$page_title = $suggestions_language->get('general', 'suggestions');

require_once(ROOT_PATH . '/core/templates/frontend_init.php');

$suggestions = new Suggestions();

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
            // Check post spam
            $last_post = DB::getInstance()->orderWhere('suggestions', 'user_id = ' . $user->data()->id, 'created', 'DESC LIMIT 1')->results();
            if (count($last_post)) {
                if ($last_post[0]->created > strtotime("-30 seconds")) {
                    $errors[] = $suggestions_language->get('general', 'spam_wait', [
                        'seconds' => strtotime(date('Y-m-d H:i:s', $last_post[0]->created)) - strtotime("-30 seconds")
                    ]);
                }
            }

            // Check if category exists
            $category = DB::getInstance()->query('SELECT id FROM nl2_suggestions_categories WHERE id = ? AND deleted = 0', [Input::get('category')])->results();
            if (!count($category)) {
                $errors[] = 'Invalid Category';
            }

            if (!count($errors)) {
                // Create suggestion
                $suggestion = new Suggestion();
                $suggestion->create($user, Input::get('title'), nl2br(Input::get('content')), $category[0]->id);

                Redirect::to($suggestion->getURL());
            }
        } else {
            // Validation errors
            $errors = $validation->errors();
        }
    } else {
        $errors[] = $language->get('general', 'invalid_token');
    }
}

if (isset($errors) && count($errors))
    $template->getEngine()->addVariable('ERRORS', $errors);

$template->getEngine()->addVariables([
    'SUGGESTIONS' => $suggestions_language->get('general', 'suggestions'),
    'NEW_SUGGESTION' => $suggestions_language->get('general', 'new_suggestion'),
    'BACK' => $language->get('general', 'back'),
    'BACK_LINK' => URL::build('/suggestions/'),
    'SUGGESTION_TITLE' => $suggestions_language->get('general', 'title'),
    'TITLE_VALUE' => ((isset($_POST['title']) && $_POST['title']) ? Output::getPurified(Input::get('title')) : ''),
    'CONTENT' => $suggestions_language->get('general', 'content'),
    'CONTENT_VALUE' => ((isset($_POST['content']) && $_POST['content']) ? Output::getPurified(Input::get('content')) : ''),
    'CATEGORY' => $suggestions_language->get('general', 'category'),
    'CATEGORY_VALUE' => ((isset($_POST['category']) && $_POST['category']) ? Output::getPurified(Input::get('category')) : ''),
    'CATEGORIES' => $suggestions->getCategories(),
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit')
]);

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

$template->addJSScript('$(\'.ui.search\')
  .search({
    type: \'category\',
    apiSettings: {
      url: \''.URL::build('/suggestions/search_api/', 'q=').'{query}\'
    },
    minCharacters: 3
  })
;');

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');
    
// Display template
$template->displayTemplate('suggestions/new');