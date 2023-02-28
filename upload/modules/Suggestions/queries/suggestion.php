<?php
// Check user ID is specified
if (!isset($_GET['id'])) {
    die(json_encode(['html' => 'Error: Invalid ID']));
}

const PAGE = 'suggestion_query';
$page_title = 'suggestion_query';
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

if (!is_numeric($_GET['id'])) {
    // Suggestion
    die(json_encode(['html' => 'Suggestion not found']));
} else {
    $cache->setCache('suggestion_query');

    if ($cache->isCached($_GET['id'])) {
        [$id, $author_id, $link, $title, $status, $likes, $dislikes, $views] = $cache->retrieve($_GET['id']);

    } else {
        $target_suggestion = new Suggestion($_GET['id']);
        if (!$target_suggestion->exists()) {
            die(json_encode(['html' => 'Suggestion not found']));
        }

        $status = DB::getInstance()->query('SELECT html FROM nl2_suggestions_statuses WHERE id = ?', [$target_suggestion->data()->status_id]);
        $status = $status->count() ? $status->first()->html : '';

        $id = $target_suggestion->data()->id;
        $author_id = $target_suggestion->data()->user_id;
        $link = Output::getClean($target_suggestion->getURL());
        $title = Output::getClean($target_suggestion->data()->title);
        $status = Output::getPurified($status);
        $likes = $target_suggestion->data()->likes;
        $dislikes = $target_suggestion->data()->dislikes;
        $views = $target_suggestion->data()->views;

        $cache->store($id, [$id, $author_id, $link, $title, $status, $likes, $dislikes, $views], 60);
    }
}

$author_user = new User($author_id);
$smarty->assign([
    'ID' => $id,
    'TITLE_VALUE' => $title,
    'LINK_VALUE' => $link,
    'BY' => $suggestions_language->get('general', 'by'),
    'AUTHOR_ID' => $author_user->exists() ? $author_user->data()->id : 0,
    'AUTHOR_USERNAME' => $author_user->exists() ? $author_user->getDisplayname() : $language->get('general', 'deleted_user'),
    'AUTHOR_PROFILE' => $author_user->exists() ? $author_user->getProfileURL() : '#',
    'AUTHOR_STYLE' => $author_user->exists() ? $author_user->getGroupStyle() : '',
    'AUTHOR_AVATAR' => $author_user->exists() ? $author_user->getAvatar() : 'https://avatars.dicebear.com/api/initials/'.$language->get('general', 'deleted_user').'.svg?size=64',
    'VIEWS_TEXT' => $suggestions_language->get('general', 'views'),
    'VIEWS_VALUE' => $views,
    'LIKES_TEXT' => $suggestions_language->get('general', 'likes'),
    'LIKES_VALUE' => $likes,
    'DISLIKES_TEXT' => $suggestions_language->get('general', 'dislikes'),
    'DISLIKES_VALUE' => $dislikes,
    'STATUS_TEXT' => $suggestions_language->get('general', 'status'),
    'STATUS_VALUE' => $status
]);

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

$template->onPageLoad();

echo json_encode([
    'id' => $id,
    'link' => $link,
    'title' => $title,
    'status' => $status,
    'likes' => $likes,
    'dislikes' => $dislikes,
    'views' => $views,
    'html' => $template->getTemplate('suggestions/suggestion_popover.tpl', $smarty)
], JSON_PRETTY_PRINT);
