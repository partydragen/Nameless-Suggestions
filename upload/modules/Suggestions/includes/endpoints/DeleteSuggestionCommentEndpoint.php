<?php
class DeleteSuggestionCommentEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'suggestions/{suggestion}/comment';
        $this->_module = 'Suggestions';
        $this->_description = 'Delete a suggestion comment';
        $this->_method = 'DELETE';
    }

    public function execute(Nameless2API $api, Suggestion $suggestion): void {
        $api->validateParams($_POST, ['comment_id']);

        DB::getInstance()->delete('suggestions_comments', ['id', '=', Input::get('comment_id')]);

        $api->returnArray(['success' => true]);
    }
}