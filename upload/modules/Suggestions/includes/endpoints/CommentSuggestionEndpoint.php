<?php
class CommentSuggestionEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'suggestions/{suggestion}/comment';
        $this->_module = 'Suggestions';
        $this->_description = 'Leave a comment on the suggestion';
        $this->_method = 'POST';
    }

    public function execute(Nameless2API $api, Suggestion $suggestion): void {
        $api->validateParams($_POST, ['user', 'content']);

        $user = $this::transformUser($api, $_POST['user']);

        $api->getDb()->insert('suggestions_comments', [
            'suggestion_id' => $suggestion->data()->id,
            'user_id' => $user->data()->id,
            'created' => date('U'),
            'content' => nl2br($_POST['content'])
        ]);
        $comment_id = $api->getDb()->lastId();

        $suggestion->update([
            'updated_by' => $user->data()->id,
            'last_updated' => date('U')
        ]);

        EventHandler::executeEvent(new SuggestionCommentCreatedEvent(
            $user,
            $suggestion,
            $comment_id,
            $_POST['content']
        ));

        $api->returnArray(['comment_id' => (int)$comment_id]);
    }

    private function transformUser(Nameless2API $api, string $value) {
        return Endpoints::getAllTransformers()['user']['transformer']($api, $value);
    }
}