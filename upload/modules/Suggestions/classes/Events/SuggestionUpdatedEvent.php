<?php

class SuggestionUpdatedEvent extends AbstractEvent implements HasWebhookParams {

    public User $user;
    public Suggestion $suggestion;

    public function __construct(User $user, Suggestion $suggestion) {
        $this->user = $user;
        $this->suggestion = $suggestion;
    }

    public static function name(): string {
        return 'updateSuggestion';
    }

    public static function description(): string {
        return (new Language(ROOT_PATH . '/modules/Suggestions/language'))->get('general', 'suggestion_updated');
    }

    function webhookParams(): array {
        $status = DB::getInstance()->query('SELECT * FROM nl2_suggestions_statuses WHERE id = ?', [$this->suggestion->data()->status_id]);
        $status = $status->first();

        return [
            'suggestion_id' => $this->suggestion->data()->id,
            'title' => '#' . $this->suggestion->data()->id . ' - ' . $this->suggestion->data()->title,
            'url' => URL::getSelfURL() . ltrim($this->suggestion->getURL(), '/'),
            'status' => [
                'id' => (int)$this->suggestion->data()->status_id,
                'name' => $status ? $status->name : 'Unknown',
                'open' => $status && (($status->open ? true : false)),
                'color' => $status ? $status->color : null,
            ],
            'user' => [
                'id' => $this->user->data()->id,
                'username' => $this->user->getDisplayname(),
                'avatar' => $this->user->getAvatar(128, true)
            ]
        ];
    }
}