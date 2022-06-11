<?php
class CreateSuggestionEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'suggestions/create';
        $this->_module = 'Suggestions';
        $this->_description = 'Create a new suggestion';
        $this->_method = 'POST';
    }

    public function execute(Nameless2API $api): void {
        $api->validateParams($_POST, ['user', 'title', 'content']);

        $user = $this::transformUser($api, $_POST['user']);

        $suggestions_language = new Language(ROOT_PATH . '/modules/Suggestions/language', DEFAULT_LANGUAGE);
        $validation = Validate::check($_POST, [
            'title' => [
                Validate::REQUIRED => true,
                Validate::MIN => 6,
                Validate::MAX => 128,
            ],
            'content' => [
                Validate::REQUIRED => true,
                Validate::MIN => 6,
                Validate::MAX => 50000
            ]
        ])->messages([
            'title' => [
                Validate::REQUIRED => $suggestions_language->get('general', 'title_required'),
                Validate::MIN => $suggestions_language->get('general', 'title_minimum'),
                Validate::MAX => $suggestions_language->get('general', 'title_maximum'),
            ],
            'content' => [
                Validate::REQUIRED => $suggestions_language->get('general', 'content_required'),
                Validate::MIN => $suggestions_language->get('general', 'content_minimum')
            ]
        ]);

        $category_id = 0;
        if (isset($_POST['category'])) {
            $category = DB::getInstance()->query('SELECT id FROM nl2_suggestions_categories WHERE id = ? AND deleted = 0', [$_POST['category']]);
            if (!$category->count()) {
                // Category not found
                $api->throwError(SuggestionsApiErrors::ERROR_CATEGORY_NOT_FOUND);
            }

            $category = $category->first();
            $category_id = $category->id;
        }

        if (!$validation->passed()) {
            // Validation errors
            $api->throwError(SuggestionsApiErrors::ERROR_VALIDATION_ERRORS, $validation->errors());
        }

        $suggestion = new Suggestion();
        $suggestion->create($user, $_POST['title'], nl2br($_POST['content']), $category_id);

        $api->returnArray([
            'suggestion_id' => $suggestion->data()->id,
            'link' => rtrim(Util::getSelfURL(), '/') . URL::build('/user/submissions/', 'view=' . Output::getClean($suggestion->data()->id))
        ]);
    }

    private function transformUser(Nameless2API $api, string $value) {
        return Endpoints::getAllTransformers()['user']['transformer']($api, $value);
    }
}