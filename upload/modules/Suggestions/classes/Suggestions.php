<?php
/*
 *  Made by Partydragen
 *  https://github.com/partydragen/Nameless-Suggestions
 *  https://partydragen.com/
 *
 *  Suggestions class
 */
 
class Suggestions {
    private $_db;

    // Constructor, connect to database
    public function __construct() {
        $this->_db = DB::getInstance();
    }
    
    // Get Categories
    public function getCategories() {
        $categories = [];
        $categories_query = $this->_db->query('SELECT * FROM nl2_suggestions_categories WHERE deleted = 0')->results();
        foreach ($categories_query as $category) {
            $categories[] = [
                'id' => Output::getClean($category->id),
                'name' => Output::getClean($category->name),
                'link' => URL::build('/suggestions/category/' . $category->id . '-' . URL::urlSafe($category->name))
            ];
        }

        return $categories;
    }
    
    // Get Statuses
    public function getStatuses() {
        $statuses = [];
        $statuses_query = $this->_db->query('SELECT * FROM nl2_suggestions_statuses WHERE deleted = 0')->results();
        foreach ($statuses_query as $status) {
            $statuses[] = [
                'id' => Output::getClean($status->id),
                'name' => Output::getClean($status->name),
                'html' => Output::getClean($status->html)
            ];
        }
        return $statuses;
    }
    
    // Get Recently updated
    public function getRecentActivity($user, $language, $limit = 10) {
        $timeago = new TimeAgo(TIMEZONE);

        $suggestions = [];
        $suggestions_query = $this->_db->query('SELECT * FROM nl2_suggestions WHERE deleted = 0 ORDER BY last_updated DESC LIMIT ' . $limit)->results();
        foreach ($suggestions_query as $item) {
            $updated_by_user = new User($item->updated_by);
            if (!$updated_by_user->exists()) {
                continue;
            }

            $suggestions[] = [
                'title' => Output::getClean($item->title),
                'link' => URL::build('/suggestions/view/' . $item->id . '-' . URL::urlSafe($item->title)),
                'updated_rough' => $timeago->inWords($item->last_updated, $language),
                'updated' => date(DATE_FORMAT, $item->last_updated),
                'updated_by_avatar' => $updated_by_user->getAvatar(),
                'updated_by_username' => $updated_by_user->getDisplayname(),
                'updated_by_style' => $updated_by_user->getGroupStyle(),
                'updated_by_link' => $updated_by_user->getProfileURL(),
            ];
        }
        return $suggestions;
    }
    
    /*
     *  Check for Module updates
     *  Returns JSON object with information about any updates
     */
    public static function updateCheck() {
        $current_version = Settings::get('nameless_version');
        $uid = Settings::get('unique_id');

        $enabled_modules = Module::getModules();
        foreach ($enabled_modules as $enabled_item) {
            if ($enabled_item->getName() == 'Suggestions') {
                $module = $enabled_item;
                break;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, 'https://api.partydragen.com/stats.php?uid=' . $uid . '&version=' . $current_version . '&module=Suggestions&module_version='.$module->getVersion() . '&domain='. URL::getSelfURL());

        $update_check = curl_exec($ch);
        curl_close($ch);

        $info = json_decode($update_check);
        if (isset($info->message)) {
            die($info->message);
        }

        return $update_check;
    }
}