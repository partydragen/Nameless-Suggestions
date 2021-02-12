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
    public function getCategories(){
        $categories = DB::getInstance()->query('SELECT * FROM nl2_suggestions_categories WHERE deleted = 0')->results();
        $category_array = array();
        
        foreach($categories as $category){
            $category_array[] = array(
                'id' => Output::getClean($category->id),
                'name' => Output::getClean($category->name),
                'link' => URL::build('/suggestions/category/' . $category->id . '-' . Util::stringToURL($category->name))
            );
        }
        return $category_array;
    }
    
    // Get Statuses
    public function getStatuses(){
        $statuses = DB::getInstance()->query('SELECT * FROM nl2_suggestions_statuses WHERE deleted = 0')->results();
        $status_array = array();
        
        foreach($statuses as $status){
            $status_array[] = array(
                'id' => Output::getClean($status->id),
                'name' => Output::getClean($status->name),
                'html' => Output::getClean($status->html)
            );
        }
        return $status_array;
    }
    
    // Get Recently updated
    public function getRecentActivity($user, $timeago, $language, $limit = 10){
        $suggestions_query = $this->_db->query('SELECT * FROM nl2_suggestions WHERE deleted = 0 ORDER BY last_updated DESC LIMIT ' . $limit)->results();
        $suggestions_array = array();
        
        foreach($suggestions_query as $item){
            $updated_by_user = new User($item->updated_by);
            
            $suggestions_array[] = array(
                'title' => Output::getClean($item->title),
                'link' => URL::build('/suggestions/view/' . $item->id . '-' . Util::stringToURL($item->title)),
                'updated_rough' => $timeago->inWords(date('d M Y, H:i', $item->last_updated), $language->getTimeLanguage()),
                'updated' => date('d M Y, H:i', $item->last_updated),
                'updated_by_avatar' => $updated_by_user->getAvatar(),
                'updated_by_username' => $updated_by_user->getDisplayname(),
                'updated_by_style' => $updated_by_user->getGroupClass(),
                'updated_by_link' => $updated_by_user->getProfileURL(),
            );
        }
        return $suggestions_array;
    }
    
    /*
     *  Check for Module updates
     *  Returns JSON object with information about any updates
     */
    public static function updateCheck($current_version = null) {
        $queries = new Queries();

        // Check for updates
        if (!$current_version) {
            $current_version = $queries->getWhere('settings', array('name', '=', 'nameless_version'));
            $current_version = $current_version[0]->value;
        }

        $uid = $queries->getWhere('settings', array('name', '=', 'unique_id'));
        $uid = $uid[0]->value;
        
        $enabled_modules = Module::getModules();
        foreach($enabled_modules as $enabled_item){
            if($enabled_item->getName() == 'Suggestions'){
                $module = $enabled_item;
                break;
            }
        }
        

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, 'https://api.partydragen.com/stats.php?uid=' . $uid . '&version=' . $current_version . '&module=Suggestions&module_version='.$module->getVersion() . '&domain='. Util::getSelfURL());

        $update_check = curl_exec($ch);
        curl_close($ch);

        $info = json_decode($update_check);
        if (isset($info->message)) {
            die($info->message);
        }
        
        return $update_check;
    }
}