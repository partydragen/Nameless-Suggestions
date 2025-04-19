<?php
class ListSuggestionsCategoriesEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'suggestions/categories';
        $this->_module = 'Suggestions';
        $this->_description = 'List suggestions categories';
        $this->_method = 'GET';
    }

    public function execute(Nameless2API $api): void {
        $categories_list = [];
        $categories_query = $api->getDb()->query('SELECT * FROM nl2_suggestions_categories WHERE deleted = 0 ORDER BY display_order ASC');
        foreach ($categories_query->results() as $category) {
            $categories_list[] = [
                'id' => $category->id,
                'name' => $category->name,
            ];
        }

        $api->returnArray(['categories' => $categories_list]);
    }
}