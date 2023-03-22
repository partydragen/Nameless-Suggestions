<?php

class SuggestionCreatedEvent extends AbstractEvent implements HasWebhookParams, DiscordDispatchable {

    public User $user;
    public Suggestion $suggestion;

    public function __construct(User $user, Suggestion $suggestion) {
        $this->user = $user;
        $this->suggestion = $suggestion;
    }

    public static function name(): string {
        return 'newSuggestion';
    }

    public static function description(): string {
        return (new Language(ROOT_PATH . '/modules/Suggestions/language'))->get('general', 'new_suggestion');
    }

    function webhookParams(): array {
        return [
            'user_id' => $this->user->data()->id,
            'username' => $this->user->getDisplayname(),
            'suggestion_id' => $this->suggestion->data()->id,
            'title' => '#' . $this->suggestion->data()->id . ' - ' . $this->suggestion->data()->title,
            'content_full' => Text::embedSafe(strip_tags($this->suggestion->data()->content)),
            'url' => URL::getSelfURL() . ltrim($this->suggestion->getURL(), '/')
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
                    ->setDescription(Text::embedSafe(strip_tags($this->suggestion->data()->content)))
                    ->setFooter($language->get('general', 'hook_new_suggestion', [
                        'user' => $this->user->getDisplayname()
                    ]))
                    ->setUrl(URL::getSelfURL() . ltrim($this->suggestion->getURL(), '/'));
            });
    }
}