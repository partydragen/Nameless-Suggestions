<?php
class SuggestionCommentsEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'suggestions/{suggestion}/comments';
        $this->_module = 'Suggestions';
        $this->_description = 'Get suggestion comments';
        $this->_method = 'GET';
    }

    public function execute(Nameless2API $api, Suggestion $suggestion): void {
        $query = 'SELECT * FROM nl2_suggestions_comments';
        $where = ' WHERE suggestion_id = ?';
        $params = [$suggestion->data()->id];

        if (isset($_GET['comment']) && is_numeric($_GET['comment'])) {
            $where .= ' AND `id` = ?';
            $params[] = $_GET['comment'];
        }

        if (isset($_GET['user']) && is_numeric($_GET['user'])) {
            $where .= ' AND `user_id` = ?';
            $params[] = $_GET['user'];
        }

        $comments = [];
        $comments_query = $api->getDb()->query($query . $where, $params)->results();
        foreach ($comments_query as $comment) {
            $target_user = new User($comment->user_id);

            $comments[] = [
                'id' => (int)$comment->id,
                'user' => [
                    'id' => (int)$comment->user_id,
                    'username' => $target_user->exists() ? $target_user->getDisplayname(true) : $api->getLanguage()->get('general', 'deleted_user'),
                    'nickname' => $target_user->exists() ? $target_user->getDisplayname() : $api->getLanguage()->get('general', 'deleted_user')
                ],
                'created' => (int)$comment->created,
                'content' => Output::getDecoded($comment->content)
            ];
        }

        $api->returnArray([
            'suggestion' => [
                'id' => $suggestion->data()->id
            ],
            'comments' => $comments
        ]);
    }
}