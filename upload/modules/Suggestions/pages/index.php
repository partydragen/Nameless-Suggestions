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
 
// Always define page name for navbar
define('PAGE', 'suggestions');
$page_title = $suggestions_language->get('general', 'suggestions');
require_once(ROOT_PATH . '/core/templates/frontend_init.php');
$timeago = new TimeAgo(TIMEZONE);

$suggestions = new Suggestions();

if (isset($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'recent-activity':
            $sort = 'last_updated';
            $sort_by = $suggestions_language->get('general', 'recent_activity');
            $url = URL::build('/suggestions/', 'sort=recent-activity');
        break;
        case 'newest':
            $sort = 'created';
            $sort_by = $suggestions_language->get('general', 'newest');
            $url = URL::build('/suggestions/', 'sort=newest');
        break;
        case 'likes':
            $sort = 'likes';
            $sort_by = $suggestions_language->get('general', 'likes');
            $url = URL::build('/suggestions/', 'sort=likes');
        break;
        default:
            $sort = 'created';
            $sort_by = $suggestions_language->get('general', 'newest');
            $url = URL::build('/suggestions/');
        break;
    }
} else {
    $sort = 'created';
    $sort_by = $suggestions_language->get('general', 'newest');
    $url = URL::build('/suggestions/');
}
 
$suggestions_query = DB::getInstance()->query('SELECT nl2_suggestions.*, html FROM nl2_suggestions LEFT JOIN nl2_suggestions_statuses ON nl2_suggestions_statuses.id=nl2_suggestions.status_id WHERE nl2_suggestions.deleted = 0 AND nl2_suggestions_statuses.open = 1 ORDER BY '.$sort.' DESC')->results();

if (count($suggestions_query)) {
    // Get page
    if (isset($_GET['p'])) {
        if (!is_numeric($_GET['p'])) {
            Redirect::to($url);
            die();
        } else {
            if ($_GET['p'] == 1) {
                // Avoid bug in pagination class
                Redirect::to($url);
            }
            $p = $_GET['p'];
        }
    } else {
        $p = 1;
    }

    // Pagination
    $paginator = new Paginator(
        $template_pagination ?? null,
        $template_pagination_left ?? null,
        $template_pagination_right ?? null
    );
    $results = $paginator->getLimited($suggestions_query, 16, $p, count($suggestions_query));
    $pagination = $paginator->generate(7, $url);

    $template->getEngine()->addVariable('PAGINATION', $pagination);

    $suggestions_array = [];
    foreach ($results->data as $item) {
        $author_user = new User($item->user_id);
        $updated_by_user = new User($item->updated_by);
        
        $suggestions_array[] = [
            'title' => Output::getClean($item->title),
            'status' => $item->html,
            'link' => URL::build('/suggestions/view/' . $item->id . '-' . URL::urlSafe($item->title)),
            'created_rough' => $timeago->inWords($item->created, $language),
            'created' => date(DATE_FORMAT, $item->created),
            'author_id' => $author_user->exists() ? $author_user->data()->id : 0,
            'author_username' => $author_user->exists() ? $author_user->getDisplayname() : $language->get('general', 'deleted_user'),
            'author_style' => $author_user->exists() ? $author_user->getGroupStyle() : '',
            'author_link' => $author_user->exists() ? $author_user->getProfileURL() : '#',
            'likes' => Output::getClean($item->likes),
            'dislikes' => Output::getClean($item->dislikes),
            'updated_rough' => $timeago->inWords($item->last_updated, $language),
            'updated' => date(DATE_FORMAT, $item->last_updated),
            'updated_by_id' => $updated_by_user->exists() ? $updated_by_user->data()->id : 0,
            'updated_by_username' => $updated_by_user->exists() ? $updated_by_user->getDisplayname() : $language->get('general', 'deleted_user'),
            'updated_by_style' => $updated_by_user->exists() ? $updated_by_user->getGroupStyle() : '',
            'updated_by_link' => $updated_by_user->exists() ? $updated_by_user->getProfileURL() : '#',
        ];
    }
    
    $template->getEngine()->addVariables([
        'SUGGESTIONS_LIST' => $suggestions_array
    ]);
}

if ($user->hasPermission('suggestions.create')) {
    $template->getEngine()->addVariables([
        'CAN_CREATE' => true,
        'NEW_SUGGESTION' => $suggestions_language->get('general', 'new_suggestion'),
        'NEW_SUGGESTION_LINK' => URL::build('/suggestions/new')
    ]);
}

$template->getEngine()->addVariables([
    'SUGGESTIONS' => $suggestions_language->get('general', 'suggestions'),
    'NO_SUGGESTIONS' => $suggestions_language->get('general', 'no_suggestions'),
    'CATEGORIES' => $suggestions_language->get('general', 'categories'),
    'CATEGORIES_LIST' => $suggestions->getCategories(),
    'SORT_BY' => $suggestions_language->get('general', 'sort_by'),
    'SORT_BY_VALUE' => $sort_by,
    'BY' => $suggestions_language->get('general', 'by'),
    'SUGGESTION_TITLE' => $suggestions_language->get('general', 'title'),
    'STATS' => $suggestions_language->get('general', 'stats'),
    'LAST_REPLY' => $suggestions_language->get('general', 'last_reply'),
    'LIKES' => $suggestions_language->get('general', 'likes'),
    'DISLIKES' => $suggestions_language->get('general', 'dislikes'),
    'NEWEST' => $suggestions_language->get('general', 'newest'),
    'SEARCH_KEYWORD' => $suggestions_language->get('general', 'search_keyword'),
    'RECENT_ACTIVITY' => $suggestions_language->get('general', 'recent_activity'),
    'RECENT_ACTIVITY_LIST' => $suggestions->getRecentActivity($user, $language),
    'SORT_NEWEST_LINK' => URL::build('/suggestions/', 'sort=newest'),
    'SORT_LIKES_LINK' => URL::build('/suggestions/', 'sort=likes'),
    'SORT_RECENT_ACTIVITY_LINK' => URL::build('/suggestions/', 'sort=recent-activity')
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
    
$template->getEngine()->addVariable('WIDGETS', $widgets->getWidgets('right'));
    
require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');
    
// Display template
$template->displayTemplate('suggestions/index');