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
$timeago = new TimeAgo(TIMEZONE);

require_once(ROOT_PATH . '/modules/Suggestions/classes/Suggestions.php');
$suggestions = new Suggestions();

// Get suggestion ID
$sid = explode('/', $route);
$sid = $sid[count($sid) - 1];

if (!strlen($sid)) {
    require_once(ROOT_PATH . '/404.php');
    die();
}

$sid = explode('-', $sid);
if (!is_numeric($sid[0])) {
    require_once(ROOT_PATH . '/404.php');
    die();
}

// Get the suggestion information
$suggestion = DB::getInstance()->get('suggestions', ['id', '=', $sid[0]])->results();
if (!count($suggestion)) {
    require_once(ROOT_PATH . '/404.php');
    die();
}

$suggestion = $suggestion[0];

if ($suggestion->deleted == 1) {
    require_once(ROOT_PATH . '/404.php');
    die();
}

// Deal with input
if (Input::exists()) {
    if (Token::check(Input::get('token'))) {
        $errors = [];
        
        if (Input::get('action') == 'vote') {
            // User must be logged in to proceed
            if ($user->isLoggedIn()) {
                $user_vote = DB::getInstance()->query('SELECT id, type FROM nl2_suggestions_votes WHERE user_id = ? AND suggestion_id = ?', [$user->data()->id, $suggestion->id])->results();

                if (count($user_vote)) {
                    $user_vote = $user_vote[0];

                    if ($user_vote->type == $_POST['vote']) {
                        // Undo vote
                        DB::getInstance()->delete('suggestions_votes', ['id', '=', $user_vote->id]);

                        if ($user_vote->type == 1) {
                            DB::getInstance()->query('UPDATE nl2_suggestions SET likes = likes - 1 WHERE id = ?', [$suggestion->id]);

                        } else {
                            DB::getInstance()->query('UPDATE nl2_suggestions SET dislikes = dislikes - 1 WHERE id = ?', [$suggestion->id]);
                        }
                        Redirect::to(URL::build('/suggestions/view/' . $suggestion->id . '-' . Util::stringToURL($suggestion->title)));
                    } else {
                        // Change existing vote
                        DB::getInstance()->update('suggestions_votes', $user_vote->id, [
                            'type' => $_POST['vote']
                        ]);

                        if ($_POST['vote'] == 1) {
                            DB::getInstance()->query('UPDATE nl2_suggestions SET likes = likes + 1, dislikes = dislikes - 1 WHERE id = ?', [$suggestion->id]);

                        } else {
                            DB::getInstance()->query('UPDATE nl2_suggestions SET dislikes = dislikes + 1, likes = likes - 1 WHERE id = ?', [$suggestion->id]);
                        }
                        Redirect::to(URL::build('/suggestions/view/' . $suggestion->id . '-' . Util::stringToURL($suggestion->title)));
                    }
                } else {
                    // Input new vote
                    DB::getInstance()->insert('suggestions_votes', [
                        'user_id' => $user->data()->id,
                        'suggestion_id' => $suggestion->id,
                        'type' => $_POST['vote']
                    ]);

                    if ($_POST['vote'] == 1) {
                        DB::getInstance()->increment('suggestions', $suggestion->id, 'likes');
                    } else {
                        DB::getInstance()->increment('suggestions', $suggestion->id, 'dislikes');
                    }
                    Redirect::to(URL::build('/suggestions/view/' . $suggestion->id . '-' . Util::stringToURL($suggestion->title)));
                }
            } else {
                $errors[] = $suggestions_language->get('general', 'login_to_vote');
            }
        } else if (Input::get('action') == 'comment') {
            // New Comment
            $validation = Validate::check($_POST, [
                'content' => [
                    Validate::MIN => 3,
                    Validate::MAX => 10000
                ]
            ])->messages([
                'content' => [
                    Validate::MIN => $suggestions_language->get('general', 'comment_minimum'),
                    Validate::MAX => $suggestions_language->get('general', 'comment_maximum')
                ]
            ]);

            if ($validation->passed()) {
                $discordAlert = [];

                if (!empty(Input::get('content'))) {
                    // New comment

                    // Check post spam
                    $last_post = DB::getInstance()->orderWhere('suggestions_comments', 'user_id = ' . $user->data()->id, 'created', 'DESC LIMIT 1')->results();
                    if (count($last_post)) {
                        if ($last_post[0]->created > strtotime("-10 seconds")) {
                            $errors[] = $suggestions_language->get('general', 'spam_wait', [
                                'seconds' => strtotime(date('Y-m-d H:i:s', $last_post[0]->created)) - strtotime("-10 seconds")
                            ]);
                        }
                    }

                    if (!count($errors)) {
                        DB::getInstance()->insert('suggestions_comments', [
                            'suggestion_id' => $suggestion->id,
                            'user_id' => $user->data()->id,
                            'created' => date('U'),
                            'content' => Output::getClean(nl2br(Input::get('content')))
                        ]);

                        DB::getInstance()->update('suggestions', $suggestion->id, [
                            'updated_by' => $user->data()->id,
                            'last_updated' => date('U')
                        ]);
                        
                        $discordAlert = [
                            'event' => 'newSuggestionComment',
                            'username' => $user->getDisplayname(),
                            'content' => $suggestions_language->get('general', 'hook_new_comment', [
                                'user' => $user->getDisplayname(),
                                'likes' => Output::getClean($suggestion->likes),
                                'dislikes' =>Output::getClean($suggestion->dislikes)
                            ]),
                            'content_full' => str_replace('&nbsp;', '', strip_tags(htmlspecialchars_decode(Input::get('content')))),
                            'avatar_url' => $user->getAvatar(128, true),
                            'title' => Output::getClean('#' . $suggestion->id . ' - ' . $suggestion->title),
                            'url' => rtrim(Util::getSelfURL(), '/') . URL::build('/suggestions/view/' . $suggestion->id . '-' . Util::stringToURL(Output::getClean($suggestion->title)))
                        ];
                    }
                }
                
                if ($user->canViewStaffCP()) {
                    if ($suggestion->status_id != htmlspecialchars(Input::get('status'))) {
                        DB::getInstance()->update('suggestions', $suggestion->id, [
                            'status_id' => Input::get('status'),
                        ]);

                        switch (Input::get('status')) {
                            case 2:
                                $color = "f50606";
                            break;
                            case 3:
                                $color = "11ff00";
                            break;
                            case 4:
                                $color = "ff6100";
                            break;
                            default:
                                $color = "f50606";
                            break;
                        }
                        $discordAlert['color'] = $color;
                    }
                }
            
                if (!empty(Input::get('content'))) {
                    EventHandler::executeEvent('newSuggestionComment', $discordAlert);
                }
                
                if (!count($errors)) {
                    Redirect::to(URL::build('/suggestions/view/' . $suggestion->id . '-' . Util::stringToURL($suggestion->title)));
                }
            } else {
                // Validation Errors
                $errors = $validation->errors();
            }
        } else if (Input::get('action') == 'deleteSuggestion') {
            if ($user->canViewStaffCP()) {
                DB::getInstance()->update('suggestions', $suggestion->id, [
                    'deleted' => 1
                ]);
            }

            Redirect::to(URL::build('/suggestions/'));
        } else if (Input::get('action') == 'deleteComment') {
            if ($user->canViewStaffCP() && is_numeric(Input::get('cid'))) {
                DB::getInstance()->delete('suggestions_comments', ['id', '=', Input::get('cid')]);
            }

            Redirect::to(URL::build('/suggestions/view/' . $suggestion->id . '-' . Util::stringToURL($suggestion->title)));
        }
    } else {
        // Invalid token
        $errors[] = $language->get('general', 'invalid_token');
    }
}

$voted = 0;
if ($user->isLoggedIn()) {
    $smarty->assign('CAN_COMMENT', true);

    $user_voted = DB::getInstance()->query('SELECT id, type FROM nl2_suggestions_votes WHERE user_id = ? AND suggestion_id = ?', [$user->data()->id, $suggestion->id])->results();
    if (count($user_voted)) {
        $voted = $user_voted[0]->type;
    }

    if ($user->canViewStaffCP()) {
        $smarty->assign([
            'CAN_MODERATE' => true,
            'STATUSES' => $suggestions->getStatuses()
        ]);
    }
}

// Get comments
$comments = DB::getInstance()->get('suggestions_comments', ['suggestion_id', '=', $suggestion->id])->results();
$smarty_comments = [];
foreach ($comments as $comment) {
    $comment_user = new User($comment->user_id);

    if ($comment_user->exists()) {
        $smarty_comments[] = [
            'id' => $comment->id,
            'username' => $comment_user->getDisplayname(),
            'profile' => $comment_user->getProfileURL(),
            'style' => $comment_user->getGroupClass(),
            'avatar' => $comment_user->getAvatar(),
            'content' => Output::getPurified(Output::getDecoded($comment->content)),
            'date' => date(DATE_FORMAT, $comment->created),
            'date_friendly' => $timeago->inWords($comment->created, $language)
        ];
    }
}

if (Session::exists('suggestions_success'))
    $success = Session::flash('suggestions_success');

if (isset($success))
    $smarty->assign([
        'SUCCESS' => $success,
        'SUCCESS_TITLE' => $language->get('general', 'success')
    ]);

if (isset($errors) && count($errors))
    $smarty->assign([
        'ERRORS' => $errors,
        'ERRORS_TITLE' => $language->get('general', 'error')
    ]);

$author_user = new User($suggestion->user_id);
$smarty->assign([
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
    'POSTER_DATE' => date(DATE_FORMAT, $suggestion->created),
    'POSTER_DATE_FRIENDLY' => $timeago->inWords($suggestion->created, $language),
    'CONTENT' => Output::getPurified(Output::getDecoded($suggestion->content)),
    'LIKES' => Output::getClean($suggestion->likes),
    'DISLIKES' => Output::getClean($suggestion->dislikes),
    'VOTED' => $voted,
    'TOKEN' => Token::get(),
    'SEARCH_KEYWORD' => $suggestions_language->get('general', 'search_keyword'),
    'RECENT_ACTIVITY' => $suggestions_language->get('general', 'recent_activity'),
    'RECENT_ACTIVITY_LIST' => $suggestions->getRecentActivity($user, $language, 6),
    'COMMENTS_TEXT' => $language->get('moderator', 'comments'),
    'NEW_COMMENT' => $language->get('moderator', 'new_comment'),
    'NO_COMMENTS' => $language->get('moderator', 'no_comments'),
    'COMMENTS_LIST' => $smarty_comments,
    'SUBMIT' => $language->get('general', 'submit'),
    'STATUS' => Output::getClean($suggestion->status_id),
    'BY' => $language->get('user', 'by'),
    'CONFIRM_DELETE' => $language->get('general', 'confirm_delete'),
    'CONFIRM_DELETE_SUGGESTION' => $language->get('general', 'confirm_deletion'),
    'CONFIRM_DELETE_COMMENT' => $language->get('general', 'confirm_deletion'),
    'CANCEL' => $language->get('general', 'cancel'),
    'DELETE' => $language->get('general', 'delete'),
    'EDIT' => $language->get('general', 'edit'),
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
    
$smarty->assign('WIDGETS', $widgets->getWidgets());
    
require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');
    
// Display template
$template->displayTemplate('suggestions/view.tpl', $smarty);