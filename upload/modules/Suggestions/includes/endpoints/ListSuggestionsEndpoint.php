<?php
class ListSuggestionsEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'suggestions';
        $this->_module = 'Suggestions';
        $this->_description = 'List all suggestions';
        $this->_method = 'GET';
    }

    public function execute(Nameless2API $api): void {
        $query = 'SELECT nl2_suggestions.*, name, open, color FROM nl2_suggestions LEFT JOIN nl2_suggestions_statuses ON nl2_suggestions_statuses.id=nl2_suggestions.status_id';
        $where = ' WHERE nl2_suggestions.deleted = 0';
        $order = ' ORDER BY `created` DESC';
        $limit = '';
        $params = [];

        if (isset($_GET['open'])) {
            $where .= ' AND `nl2_suggestions_statuses`.`open` = ' . ($_GET['open'] == 'true' ? '1' : '0');
        } else {
            $where .= ' AND `nl2_suggestions_statuses`.`open` = 1';
        }

        if (isset($_GET['category'])) {
            $where .= ' AND category_id = ?';
            array_push($params, $_GET['category']);
        }

        if (isset($_GET['status'])) {
            $where .= ' AND status_id = ?';
            array_push($params, $_GET['status']);
        }

        if (isset($_GET['user'])) {
            $where .= ' AND user_id = ?';
            array_push($params, $_GET['user']);
        }

        if (isset($_GET['updated_by'])) {
            $where .= ' AND updated_by = ?';
            array_push($params, $_GET['updated_by']);
        }

        if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
            $limit .= ' LIMIT '. $_GET['limit'];
        }

        $suggestions = [];
        $suggestions_query = $api->getDb()->query($query . $where . $order . $limit, $params)->results();
        foreach ($suggestions_query as $suggestion) {
            $suggestions[] = [
                'id' => (int)$suggestion->id,
                'title' => $suggestion->title,
                'status' => [
                    'id' => (int)$suggestion->status_id,
                    'name' => $suggestion->name ? $suggestion->name : 'Unknown',
                    'open' => $suggestion->open ? ($suggestion->open ? true : false) : false,
                    'color' => $suggestion->color ? $suggestion->color : null,
                ]
            ];
        }

        $api->returnArray(['suggestions' => $suggestions]);
    }
}