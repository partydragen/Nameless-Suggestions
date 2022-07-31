<?php
/**
 * Suggestion class.
 *
 * @package Modules\Suggestions
 * @author Partydragen
 * @version 2.0.0-pr13
 * @license MIT
 */
class Suggestion {

    private DB $_db;
    private $_data;

    public function __construct($value = null, $field = 'id', $query_data = null) {
        $this->_db = DB::getInstance();

        if (!$query_data && $value) {
            $data = $this->_db->get('suggestions', [$field, '=', $value]);
            if ($data->count()) {
                $this->_data = $data->first();
            }
        } else if ($query_data) {
            // Load data from existing query.
            $this->_data = $query_data;
        }
    }

    /**
     * Update a suggestion data in the database.
     *
     * @param array $fields Column names and values to update.
     */
    public function update(array $fields = []): void {
        if (!$this->_db->update('suggestions', $this->data()->id, $fields)) {
            throw new Exception('There was a problem updating suggestion');
        }
    }

    /**
     * Create a new suggestion.
     *
     * @param User $user The user who post this suggestion
     * @param string $title Suggestion title.
     * @param string $content Suggestion content.
     * @param int $user The user who post this suggestion
     */
    public function create(User $user, string $title, string $content, int $category_id = 0): bool {
        $this->_db->insert('suggestions', [
            'user_id' => $user->data()->id,
            'updated_by' => $user->data()->id,
            'category_id' => $category_id,
            'created' => date('U'),
            'last_updated' => date('U'),
            'title' => $title,
            'content' => $content,
        ]);
        $suggestion_id = $this->_db->lastId();

        $data = $this->_db->get('suggestions', ['id', '=', $suggestion_id]);
        if ($data->count()) {
            $this->_data = $data->first();

            $suggestions_language = new Language(ROOT_PATH . '/modules/Suggestions/language', DEFAULT_LANGUAGE);
            EventHandler::executeEvent('newSuggestion', [
                'event' => 'newSuggestion',
                'suggestion_id' => $suggestion_id,
                'user_id' => $user->data()->id,
                'username' => $user->getDisplayname(),
                'content' => $suggestions_language->get('general', 'hook_new_suggestion', [
                    'user' => $user->getDisplayname()
                ]),
                'content_full' => strip_tags($content),
                'avatar_url' => $user->getAvatar(128, true),
                'title' => Output::getClean('#' . $suggestion_id . ' - ' . $title),
                'url' => rtrim(Util::getSelfURL(), '/') . $this->getURL()
            ]);

            return true;
        }

        return false;
    }

    /**
     * Does this payment exist?
     *
     * @return bool Whether the payment exists (has data) or not.
     */
    public function exists(): bool {
        return (!empty($this->_data));
    }

    /**
     * Get the payment data.
     *
     * @return object This payment data.
     */
    public function data() {
        return $this->_data;
    }
    
    /**
     * Build this suggestion link.
     *
     * @return object Compiled suggestion URL.
     */
    public function getURL(): string {
        return URL::build('/suggestions/view/' . $this->data()->id . '-' . Util::stringToURL($this->data()->title));
    }
    
    public function userVote(User $user) {
        $user_vote = $this->_db->query('SELECT id, type FROM nl2_suggestions_votes WHERE user_id = ? AND suggestion_id = ?', [$user->data()->id, $suggestion->data()->id])->results();

        if (count($user_vote)) {
            $user_vote = $user_vote[0];

            if ($user_vote->type == $_POST['vote']) {
                // Undo vote
                $this->_db->delete('suggestions_votes', ['id', '=', $user_vote->id]);

                if ($user_vote->type == 1) {
                    $this->_db->query('UPDATE nl2_suggestions SET likes = likes - 1 WHERE id = ?', [$suggestion->data()->id]);
                } else {
                    $this->_db->query('UPDATE nl2_suggestions SET dislikes = dislikes - 1 WHERE id = ?', [$suggestion->data()->id]);
                }

                EventHandler::executeEvent('userSuggestionVote', [
                    'suggestion_id' => $suggestion->data()->id,
                    'user_id' => $user->data()->id,
                    'vote_type' => 'undo'
                ]);
            } else {
                // Change existing vote
                $this->_db->update('suggestions_votes', $user_vote->id, [
                    'type' => $_POST['vote']
                ]);

                if ($_POST['vote'] == 1) {
                    $this->_db->query('UPDATE nl2_suggestions SET likes = likes + 1, dislikes = dislikes - 1 WHERE id = ?', [$suggestion->data()->id]);
                } else {
                    $this->_db->query('UPDATE nl2_suggestions SET dislikes = dislikes + 1, likes = likes - 1 WHERE id = ?', [$suggestion->data()->id]);
                }

                EventHandler::executeEvent('userSuggestionVote', [
                    'suggestion_id' => $suggestion->data()->id,
                    'user_id' => $user->data()->id,
                    'vote_type' => $_POST['vote'] == 1 ? 'like' : 'dislike'
                ]);
            }
        } else {
            // Input new vote
            $this->_db->insert('suggestions_votes', [
                'user_id' => $user->data()->id,
                'suggestion_id' => $suggestion->data()->id,
                'type' => $_POST['vote']
            ]);

            if ($_POST['vote'] == 1) {
                $this->_db->increment('suggestions', $suggestion->data()->id, 'likes');
            } else {
                $this->_db->increment('suggestions', $suggestion->data()->id, 'dislikes');
            }

            EventHandler::executeEvent('userSuggestionVote', [
                'suggestion_id' => $suggestion->data()->id,
                'user_id' => $user->data()->id,
                'vote_type' => $_POST['vote'] == 1 ? 'like' : 'dislike'
            ]);
        }
    }

    public function setVote(User $user, int $reaction, bool $can_remove = true) {
        $existing_vote = $this->_db->query('SELECT id, type FROM nl2_suggestions_votes WHERE user_id = ? AND suggestion_id = ?', [$user->data()->id, $this->data()->id]);
        if ($existing_vote->count()) {
            $existing_vote = $existing_vote->first();

            // Update or remove existing vote
            if ($existing_vote->type == $reaction && $can_remove) {
                $this->removeVote($user);

            } else {
                // Change existing vote
                $this->_db->update('suggestions_votes', $existing_vote->id, [
                    'type' => $reaction
                ]);
            }
        } else {
            $this->_db->insert('suggestions_votes', [
                'user_id' => $user->data()->id,
                'suggestion_id' => $this->data()->id,
                'type' => $reaction
            ]);
        }

        $this->updateVotes();
    }

    public function removeVote(User $user) {
        $this->_db->query('DELETE FROM nl2_suggestions_votes WHERE suggestion_id = ? AND user_id = ?', [$this->data()->id, $user->data()->id]);

        $this->updateVotes();
    }

    public function updateVotes(): void {
        $this->update([
            'likes' => $this->_db->query('SELECT COUNT(*) AS c FROM nl2_suggestions_votes WHERE suggestion_id = ? AND type = 1', [$this->data()->id])->first()->c,
            'dislikes' => $this->_db->query('SELECT COUNT(*) AS c FROM nl2_suggestions_votes WHERE suggestion_id = ? AND type = 2', [$this->data()->id])->first()->c
        ]);
    }
}