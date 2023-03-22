<?php
class UserSuggestionVoteEvent extends AbstractEvent implements HasWebhookParams, DiscordDispatchable {

    public User $user;
    public Suggestion $suggestion;
    public string $vote_type;

    public function __construct(User $user, Suggestion $suggestion, string $vote_type) {
        $this->user = $user;
        $this->suggestion = $suggestion;
        $this->vote_type = $vote_type;
    }

    public static function name(): string {
        return 'userSuggestionVote';
    }

    public static function description(): string {
        return (new Language(ROOT_PATH . '/modules/Suggestions/language'))->get('general', 'user_suggestion_vote');
    }

    function webhookParams(): array {
        return [
            'user_id' => $this->user->data()->id,
            'username' => $this->user->getDisplayname(),
            'suggestion_id' => $this->suggestion->data()->id,
            'vote_type' => $this->vote_type,
            'url' => URL::getSelfURL() . ltrim($this->suggestion->getURL(), '/')
        ];
    }

    public function toDiscordWebhook(): DiscordWebhookBuilder {
        $language = new Language(ROOT_PATH . '/modules/Suggestions/language', DEFAULT_LANGUAGE);

        $term = null;
        if ($this->vote_type == 'like') {
            $term = 'user_liked_suggestion';
        } else if ($this->vote_type == 'dislike') {
            $term = 'user_disliked_suggestion';
        } else if ($this->vote_type == 'undo') {
            $term = 'user_removed_vote';
        }

        return DiscordWebhookBuilder::make()
            ->setUsername($this->user->getDisplayname())
            ->setAvatarUrl($this->user->getAvatar(128, true))
            ->addEmbed(function (DiscordEmbed $embed) use ($language, $term) {
                return $embed
                    ->setTitle('#' . $this->suggestion->data()->id . ' - ' . $this->suggestion->data()->title)
                    ->setDescription($language->get('general', $term, [
                        'user' => $this->user->getDisplayname()
                    ]))
                    ->setUrl(URL::getSelfURL() . ltrim($this->suggestion->getURL(), '/'));
            });
    }
}