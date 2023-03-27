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
            $likes_list[] = (int)$like->user_id;
        }

        $dislikes_list = [];
        $dislikes = $api->getDb()->query('SELECT user_id FROM nl2_suggestions_votes WHERE suggestion_id = ? AND type = 2', [$suggestion->data()->id])->results();
        foreach ($dislikes as $dislike) {
            $dislikes_list[] = (int)$dislike->user_id;
        }

        $api->returnArray([
            'id' => (int)$suggestion->data()->id,
            'link' => rtrim(URL::getSelfURL(), '/') . $suggestion->getURL(),
            'author' => [
                'id' => (int)$suggestion->data()->user_id,
                'username' => $author->exists() ? $author->getDisplayname(true) : $api->getLanguage()->get('general', 'deleted_user'),
                'nickname' => $author->exists() ? $author->getDisplayname() : $api->getLanguage()->get('general', 'deleted_user')
            ],
            'updated_by' => [
                'id' => (int)$suggestion->data()->updated_by,
                'username' => $updated_by->exists() ? $updated_by->getDisplayname(true) : $api->getLanguage()->get('general', 'deleted_user'),
                'nickname' => $updated_by->exists() ? $updated_by->getDisplayname() : $api->getLanguage()->get('general', 'deleted_user')
            ],
            'status' => [
                'id' => (int)$suggestion->data()->status_id,
                'name' => $status ? $status->name : 'Unknown',
                'open' => $status ? ($status->open ? true : false) : false,
                'color' => $status ? $status->color : null,
            ],
            'category' => [
                'id' => (int)$suggestion->data()->category_id,
                'name' => $category ? $category->name : 'Unknown'
            ],
            'title' => Output::getClean($suggestion->data()->title),
            'content' => Output::getDecoded($suggestion->data()->content),
            'views' => (int)$suggestion->data()->views,
            'created' => (int)$suggestion->data()->created,
            'last_updated' => (int)$suggestion->data()->last_updated,
            'likes_count' => (int)$suggestion->data()->likes,
            'dislikes_count' => (int)$suggestion->data()->dislikes,
            'likes' => $likes_list,
            'dislikes' => $dislikes_list
        ]);
    }
}