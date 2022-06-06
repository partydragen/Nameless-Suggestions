<?php
/*
 *  Made by Partydragen
 *  https://github.com/partydragen/Nameless-Suggestions
 *  https://partydragen.com/
 *
 *  Suggestions page
 */
 
if(!$user->isLoggedIn()){
    Redirect::to(URL::build('/login'));
    die();
}
 
// Always define page name for navbar
define('PAGE', 'suggestions');
$page_title = $suggestions_language->get('general', 'suggestions');

require_once(ROOT_PATH . '/core/templates/frontend_init.php');

require_once(ROOT_PATH . '/modules/Suggestions/classes/Suggestions.php');
$suggestions = new Suggestions();

if(Input::exists()){
    if(Token::check(Input::get('token'))){
        $errors = array();
        
        $validation = Validate::check($_POST, [
            'title' => array(
                'required' => true,
                'min' => 6,
                'max' => 128,
            ),
            'content' => array(
                'required' => true,
                'min' => 6,
            )
        ]);
                    
        if($validation->passed()){
            // Check post spam
            $last_post = DB::getInstance()->orderWhere('suggestions', 'user_id = ' . $user->data()->id, 'created', 'DESC LIMIT 1')->results();
            if (count($last_post)) {
                if ($last_post[0]->created > strtotime("-30 seconds")) {
                    $errors[] = str_replace('{x}', (strtotime(date('Y-m-d H:i:s', $last_post[0]->created)) - strtotime("-30 seconds")), $suggestions_language->get('general', 'spam_wait'));
                }
            }
        
            // Check if category exists
            $category = DB::getInstance()->query('SELECT id FROM nl2_suggestions_categories WHERE id = ? AND deleted = 0', array(htmlspecialchars(Input::get('category'))))->results();
            if(!count($category)) {
                $errors[] = 'Invalid Category';
            }
            
            if(!count($errors)) {
                $DB::getInstance()->insert('suggestions', array(
                    'user_id' => $user->data()->id,
                    'updated_by' => $user->data()->id,
                    'category_id' => $category[0]->id,
                    'created' => date('U'),
                    'last_updated' => date('U'),
                    'title' => htmlspecialchars(Input::get('title')),
                    'content' => htmlspecialchars(nl2br(Input::get('content'))),
                ));
                
                $suggestion_id = DB::getInstance()->lastId();
                
                EventHandler::executeEvent('newSuggestion', array(
                    'event' => 'newSuggestion',
                    'username' => $user->getDisplayname(),
                    'content' => str_replace(array('{x}'), array($user->getDisplayname()), $suggestions_language->get('general', 'hook_new_suggestion')),
                    'content_full' => str_replace('&nbsp;', '', strip_tags(htmlspecialchars_decode(Input::get('content')))),
                    'avatar_url' => $user->getAvatar(128, true),
                    'title' => Output::getClean('#' . $suggestion_id . ' - ' . Input::get('title')),
                    'url' => rtrim(Util::getSelfURL(), '/') . URL::build('/suggestions/view/' . $suggestion_id . '-' . Util::stringToURL(Output::getClean(Input::get('title'))))
                ));
                
                Redirect::to(URL::build('/suggestions/view/' . $suggestion_id));
                die();
            }
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
    } else {
        $errors[] = $language->get('general', 'invalid_token');
    }
}

if(isset($errors) && count($errors))
    $smarty->assign('ERRORS', $errors);

$smarty->assign(array(
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
));

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
    
$smarty->assign('WIDGETS', $widgets->getWidgets());
    
require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');
    
// Display template
$template->displayTemplate('suggestions/new.tpl', $smarty);