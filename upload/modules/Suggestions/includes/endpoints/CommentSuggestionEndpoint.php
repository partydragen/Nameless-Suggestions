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

        $suggestions_language = new Language(ROOT_PATH . '/modules/Suggestions/language', DEFAULT_LANGUAGE);
        EventHandler::executeEvent('newSuggestionComment', [
            'event' => 'newSuggestionComment',
            'suggestion_id' => $suggestion->data()->id,
            'comment_id' => $comment_id,
            'user_id' => $user->data()->id,
            'username' => $user->getDisplayname(),
            'content' => $suggestions_language->get('general', 'hook_new_comment', [
                'user' => $user->getDisplayname(),
                'likes' => Output::getClean($suggestion->data()->likes),
                'dislikes' =>Output::getClean($suggestion->data()->dislikes)
            ]),
            'content_full' => str_replace('&nbsp;', '', strip_tags(htmlspecialchars_decode($_POST['content']))),
            'avatar_url' => $user->getAvatar(128, true),
            'title' => Output::getClean('#' . $suggestion->data()->id . ' - ' . $suggestion->data()->title),
            'url' => rtrim(Util::getSelfURL(), '/') . $suggestion->getURL() . '#comment-' . $comment_id
        ]);

        $api->returnArray(['comment_id' => (int)$comment_id]);
    }

    private function transformUser(Nameless2API $api, string $value) {
        return Endpoints::getAllTransformers()['user']['transformer']($api, $value);
    }
}