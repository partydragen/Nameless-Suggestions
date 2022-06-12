<?php
class SuggestionInfoEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'suggestions/{suggestion}';
        $this->_module = 'Suggestions';
        $this->_description = 'Get suggestion info';
        $this->_method = 'GET';
    }

    public function execute(Nameless2API $api, Suggestion $suggestion): void {
        $author = new User($suggestion->data()->user_id);
        $updated_by = new User($suggestion->data()->updated_by);

        $status = $api->getDb()->query('SELECT * FROM nl2_suggestions_statuses WHERE id = ?', [$suggestion->data()->status_id]);
        $status = $status->first();

        $category = $api->getDb()->query('SELECT * FROM nl2_suggestions_categories WHERE id = ?', [$suggestion->data()->category_id]);
        $category = $category->first();
        
        $likes_list = [];
        $likes = $api->getDb()->query('SELECT user_id FROM nl2_suggestions_votes WHERE suggestion_id = ? AND type = 1', [$suggestion->data()->id])->results();
        foreach ($likes as $like) {
            $likes_list[] = $like->user_id;
        }

        $dislikes_list = [];
        $dislikes = $api->getDb()->query('SELECT user_id FROM nl2_suggestions_votes WHERE suggestion_id = ? AND type = 2', [$suggestion->data()->id])->results();
        foreach ($dislikes as $dislike) {
            $dislikes_list[] = $dislike->user_id;
        }

        $api->returnArray([
            'id' => $suggestion->data()->id,
            'author' => [
                'id' => $suggestion->data()->user_id,
                'username' => $author->exists() ? $author->getDisplayname(true) : $api->getLanguage()->get('general', 'deleted_user')
            ],
            'updated_by' => [
                'id' => $suggestion->data()->updated_by,
                'username' => $updated_by->exists() ? $updated_by->getDisplayname(true) : $api->getLanguage()->get('general', 'deleted_user')
            ],
            'status' => [
                'id' => $suggestion->data()->status_id,
                'name' => $status ? $status->name : 'Unknown',
                'open' => $status ? ($status->open ? true : false) : false
            ],
            'category' => [
                'id' => $suggestion->data()->category_id,
                'name' => $category ? $category->name : 'Unknown'
            ],
            'title' => Output::getClean($suggestion->data()->title),
            'content' => Output::getDecoded($suggestion->data()->content),
            'views' => $suggestion->data()->views,
            'created' => $suggestion->data()->created,
            'last_updated' => $suggestion->data()->last_updated,
            'likes_count' => $suggestion->data()->likes,
            'dislikes_count' => $suggestion->data()->dislikes,
            'likes' => $likes_list,
            'dislikes' => $dislikes_list
        ]);
    }
}