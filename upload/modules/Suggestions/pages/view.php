<?php
/*
 *  Made by Partydragen
 *  https://github.com/partydragen/Nameless-Suggestions
 *  https://partydragen.com/
 *
 *  Suggestions page
 */
 
// Always define page name for navbar
define('PAGE', 'suggestions');
$page_title = $suggestions_language->get('general', 'suggestions');
require_once(ROOT_PATH . '/core/templates/frontend_init.php');
$timeago = new Timeago(TIMEZONE);

require_once(ROOT_PATH . '/modules/Suggestions/classes/Suggestions.php');
$suggestions = new Suggestions();

// Get suggestion ID
$sid = explode('/', $route);
$sid = $sid[count($sid) - 1];

if(!isset($sid[count($sid) - 1])){
    Redirect::to(URL::build('/suggestions/'));
    die();
}
$sid = explode('-', $sid);
if(!is_numeric($sid[0])){
    Redirect::to(URL::build('/suggestions/'));
    die();
}

// Get the suggestion information
$suggestion = $queries->getWhere('suggestions', array('id', '=', $sid[0]));
if(!count($suggestion)){
    Redirect::to(URL::build('/suggestions/'));
    die();
}

$suggestion = $suggestion[0];

if($suggestion->deleted == 1){
    Redirect::to(URL::build('/suggestions/'));
    die();
}

// Deal with input
if(Input::exists()){
    if(Token::check(Input::get('token'))){
        if(Input::get('action') == 'vote') {
            // User must be logged in to proceed
            if($user->isLoggedIn()){
                $user_vote = DB::getInstance()->query('SELECT id, type FROM nl2_suggestions_votes WHERE user_id = ? AND suggestion_id = ?', array($user->data()->id, $suggestion->id))->results();
                if(count($user_vote)){
                    $user_vote = $user_vote[0];
                    if($user_vote->type == $_POST['vote']){
                        // Undo vote
                        $queries->delete('suggestions_votes', array('id', '=', $user_vote->id));
                        
                        if($user_vote->type == 1) {
                            $queries->decrement('suggestions', $suggestion->id, 'likes');
                        } else {
                            $queries->decrement('suggestions', $suggestion->id, 'dislikes');
                        }
                        Redirect::to(URL::build('/suggestions/view/' . $suggestion->id . '-' . Util::stringToURL($suggestion->title)));
                        die();
                    } else {
                        // Change existing vote
                        $queries->update('suggestions_votes', $user_vote->id, array(
                            'type' => $_POST['vote']
                        ));
                        
                        if($_POST['vote'] == 1) {
                            $queries->increment('suggestions', $suggestion->id, 'likes');
                            $queries->decrement('suggestions', $suggestion->id, 'dislikes');
                        } else {
                            $queries->increment('suggestions', $suggestion->id, 'dislikes');
                            $queries->decrement('suggestions', $suggestion->id, 'likes');
                        }
                        Redirect::to(URL::build('/suggestions/view/' . $suggestion->id . '-' . Util::stringToURL($suggestion->title)));
                        die();
                    }
                } else {
                    // Input new vote
                    $queries->create('suggestions_votes', array(
                        'user_id' => $user->data()->id,
                        'suggestion_id' => $suggestion->id,
                        'type' => $_POST['vote']
                    ));
                    
                    if($_POST['vote'] == 1) {
                        $queries->increment('suggestions', $suggestion->id, 'likes');
                    } else {
                        $queries->increment('suggestions', $suggestion->id, 'dislikes');
                    }
                    Redirect::to(URL::build('/suggestions/view/' . $suggestion->id . '-' . Util::stringToURL($suggestion->title)));
                    die();
                }
            } else {
                $errors[] = 'You need to be logged in to vote!';
            }
        } else if(Input::get('action') == 'comment') {
            // New Comment
            // Valid token
            $validate = new Validate();

            $validation = $validate->check($_POST, array(
                'content' => array(
                    'required' => true,
                    'min' => 3,
                    'max' => 10000
                )
            ));

            if($validation->passed()){
                $queries->create('suggestions_comments', array(
                    'suggestion_id' => $suggestion->id,
                    'user_id' => $user->data()->id,
                    'created' => date('U'),
                    'content' => Output::getClean(nl2br(Input::get('content')))
                ));

                $queries->update('suggestions', $suggestion->id, array(
                    'updated_by' => $user->data()->id,
                    'last_updated' => date('U')
                ));
                
                $discordAlert = array(
                    'event' => 'newSuggestion',
                    'username' => $user->getDisplayname(),
                    'content' => str_replace(array('{x}', '{y}', '{z}'), array($user->getDisplayname(), Output::getClean($suggestion->likes), Output::getClean($suggestion->dislikes)), $suggestions_language->get('general', 'hook_new_comment')),
                    'content_full' => str_replace('&nbsp;', '', strip_tags(htmlspecialchars_decode(Input::get('content')))),
                    'avatar_url' => $user->getAvatar(null, 128, true),
                    'title' => Output::getClean('#' . $suggestion->id . ' - ' . $suggestion->title),
                    'url' => rtrim(Util::getSelfURL(), '/') . URL::build('/suggestions/view/' . $suggestion->id . '-' . Util::stringToURL(Output::getClean($suggestion->title)))
                );
                
                if($user->canViewStaffCP()){
                    if($suggestion->status_id != htmlspecialchars(Input::get('status'))) {
                        $queries->update('suggestions', $suggestion->id, array(
                            'status_id' => htmlspecialchars(Input::get('status')),
                        ));

                        switch(htmlspecialchars(Input::get('status'))) {
                            case 2:
                                $color = hexdec( "f50606" );
                            break;
                            case 3:
                                $color = hexdec( "11ff00" );
                            break;
                            case 4:
                                $color = hexdec( "ff6100" );
                            break;
                            default:
                                $color = hexdec( "f50606" );
                            break;
                        }
                        $discordAlert['color'] = $color;
                    }
                }
            
                HookHandler::executeEvent('newSuggestion', $discordAlert);
                Redirect::to(URL::build('/suggestions/view/' . $suggestion->id . '-' . Util::stringToURL($suggestion->title)));
                die();
            } else {
                // Display error
                foreach($validation->errors() as $error){
                    if(strpos($error, 'minimum') !== false){
                        switch($error){
                            case (strpos($error, 'content') !== false):
                                $errors[] = $suggestions_language->get('general', 'comment_minimum');
                            break;
                        }
                    } else if(strpos($error, 'maximum') !== false){
                        switch($error){
                            case (strpos($error, 'content') !== false):
                                $errors[] = $suggestions_language->get('general', 'comment_maximum');
                            break;
                        }
                    }
                }
            }
        } else if(Input::get('action') == 'deleteSuggestion') {
            if($user->canViewStaffCP()){
                $queries->update('suggestions', $suggestion->id, array(
                    'deleted' => 1
                ));
            }
                
            Redirect::to(URL::build('/suggestions/'));
            die();
        }
    } else {
        // Invalid token
        $errors[] = 'Invalid token!';
    }
}

$voted = 0;
if($user->isLoggedIn()){
    $smarty->assign('CAN_COMMENT', true);
    
    $user_voted = DB::getInstance()->query('SELECT id, type FROM nl2_suggestions_votes WHERE user_id = ? AND suggestion_id = ?', array($user->data()->id, $suggestion->id))->results();
    if(count($user_voted)){
        $voted = $user_voted[0]->type;
    }
    
    if($user->canViewStaffCP()){
        $smarty->assign(array(
            'CAN_MODERATE' => true,
            'STATUSES' => $suggestions->getStatuses()
        ));
    }
}

// Get comments
$comments = $queries->getWhere('suggestions_comments', array('suggestion_id', '=', $suggestion->id));
$smarty_comments = array();
foreach($comments as $comment){
    $comment_user = new User($comment->user_id);
    
    $smarty_comments[] = array(
        'username' => $comment_user->getDisplayname(),
        'profile' => $comment_user->getProfileURL(),
        'style' => $comment_user->getGroupClass(),
        'avatar' => $comment_user->getAvatar(),
        'content' => Output::getPurified(Output::getDecoded($comment->content)),
        'date' => date('d M Y, H:i', $comment->created),
        'date_friendly' => $timeago->inWords(date('Y-m-d H:i:s', $comment->created), $language->getTimeLanguage())
    );
}

if(isset($errors) && count($errors))
    $smarty->assign('ERRORS', $errors);

$author_user = new User($suggestion->user_id);
$smarty->assign(array(
    'ID' => Output::getClean($suggestion->id),
    'SUGGESTIONS' => $suggestions_language->get('general', 'suggestions'),
    'BACK' => $language->get('general', 'back'),
    'BACK_LINK' => URL::build('/suggestions/'),
    'EDIT_LINK' => URL::build('/suggestions/edit/', 'sid=' . $suggestion->id),
    'TITLE' => Output::getClean($suggestion->title),
    'POSTER_USERNAME' => $author_user->getDisplayname(),
    'POSTER_PROFILE' => $author_user->getProfileURL(),
    'POSTER_STYLE' => $author_user->getGroupClass(),
    'POSTER_AVATAR' => $author_user->getAvatar(),
    'POSTER_DATE' => date('d M Y, H:i', $suggestion->created),
    'POSTER_DATE_FRIENDLY' => $timeago->inWords(date('Y-m-d H:i:s', $suggestion->created), $language->getTimeLanguage()),
    'CONTENT' => Output::getPurified(Output::getDecoded($suggestion->content)),
    'LIKES' => Output::getClean($suggestion->likes),
    'DISLIKES' => Output::getClean($suggestion->dislikes),
    'VOTED' => $voted,
    'TOKEN' => Token::get(),
    'SEARCH_KEYWORD' => $suggestions_language->get('general', 'search_keyword'),
    'RECENT_ACTIVITY' => $suggestions_language->get('general', 'recent_activity'),
    'RECENT_ACTIVITY_LIST' => $suggestions->getRecentActivity($user, $timeago, $language, 6),
    'COMMENTS_TEXT' => $language->get('moderator', 'comments'),
    'NEW_COMMENT' => $language->get('moderator', 'new_comment'),
    'NO_COMMENTS' => $language->get('moderator', 'no_comments'),
    'COMMENTS_LIST' => $smarty_comments,
    'SUBMIT' => $language->get('general', 'submit'),
    'STATUS' => Output::getClean($suggestion->status_id),
    'BY' => $language->get('user', 'by')
));

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

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
$template->displayTemplate('suggestions/view.tpl', $smarty);