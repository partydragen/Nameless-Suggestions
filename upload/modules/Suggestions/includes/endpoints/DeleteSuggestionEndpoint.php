<?php
class DeleteSuggestionEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'suggestions/{suggestion}';
        $this->_module = 'Suggestions';
        $this->_description = 'Delete suggestion';
        $this->_method = 'DELETE';
    }

    public function execute(Nameless2API $api, Suggestion $suggestion): void {
        $suggestion->update([
            'deleted' => 1
        ]);

        $api->returnArray(['success' => true]);
    }
}