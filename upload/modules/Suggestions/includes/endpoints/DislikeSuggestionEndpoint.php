<?php
class DislikeSuggestionEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'suggestions/{suggestion}/dislike';
        $this->_module = 'Suggestions';
        $this->_description = 'Like suggestion';
        $this->_method = 'POST';
    }

    public function execute(Nameless2API $api, Suggestion $suggestion): void {
        $api->validateParams($_POST, ['user']);

        $user = $this::transformUser($api, $_POST['user']);

        if (isset($_POST['dislike']) && $_POST['dislike'] == false) {
            $suggestion->removeVote($user);
            $api->returnArray(['message' => 'Removed any votes from this user']);
        }

        $suggestion->setVote($user, 2, false);
        $api->returnArray(['message' => 'User successfully disliked the suggestion']);
    }

    private function transformUser(Nameless2API $api, string $value) {
        return Endpoints::getAllTransformers()['user']['transformer']($api, $value);
    }
}