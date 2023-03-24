<?php
class SuggestionDeletedEvent extends AbstractEvent implements HasWebhookParams, DiscordDispatchable {

    public User $user;
    public Suggestion $suggestion;

    public function __construct(User $user, Suggestion $suggestion) {
        $this->user = $user;
        $this->suggestion = $suggestion;
    }

    public static function name(): string {
        return 'deleteSuggestion';
    }

    public static function description(): string {
        return (new Language(ROOT_PATH . '/modules/Suggestions/language'))->get('general', 'suggestion_deletion');
    }

    function webhookParams(): array {
        return [
            'user_id' => $this->user->data()->id,
            'username' => $this->user->getDisplayname(),
            'suggestion_id' => $this->suggestion->data()->id,
            'title' => '#' . $this->suggestion->data()->id . ' - ' . $this->suggestion->data()->title
        ];
    }

    public function toDiscordWebhook(): DiscordWebhookBuilder {
        $language = new Language(ROOT_PATH . '/modules/Suggestions/language', DEFAULT_LANGUAGE);

        return DiscordWebhookBuilder::make()
            ->setUsername($this->user->getDisplayname())
            ->setAvatarUrl($this->user->getAvatar(128, true))
            ->addEmbed(function (DiscordEmbed $embed) use ($language) {
                return $embed
                    ->setTitle('#' . $this->suggestion->data()->id . ' - ' . $this->suggestion->data()->title)
                    ->setDescription($language->get('general', 'suggestion_deleted_by_x', [
                        'user' => $this->user->getDisplayname()
                    ]));
            });
    }
}