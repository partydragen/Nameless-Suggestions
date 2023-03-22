<?php

class SuggestionCommentCreatedEvent extends AbstractEvent implements HasWebhookParams, DiscordDispatchable {

    public User $user;
    public Suggestion $suggestion;
    public int $comment_id;
    public string $content;

    public function __construct(User $user, Suggestion $suggestion, int $comment_id, string $content) {
        $this->user = $user;
        $this->suggestion = $suggestion;
        $this->comment_id = $comment_id;
        $this->content = $content;
    }

    public static function name(): string {
        return 'newSuggestionComment';
    }

    public static function description(): string {
        return (new Language(ROOT_PATH . '/modules/Suggestions/language'))->get('general', 'new_suggestion');
    }

    function webhookParams(): array {
        return [
            'user_id' => $this->user->data()->id,
            'username' => $this->user->getDisplayname(),
            'suggestion_id' => $this->suggestion->data()->id,
            'comment_id' => $this->comment_id,
            'title' => '#' . $this->suggestion->data()->id . ' - ' . $this->suggestion->data()->title,
            'content_full' => Text::embedSafe(strip_tags($this->content)),
            'url' => URL::getSelfURL() . ltrim($this->suggestion->getURL() . '#comment-' . $comment_id, '/')
        ];
    }

    public function toDiscordWebhook(): DiscordWebhookBuilder {
        $language = new Language(ROOT_PATH . '/modules/Suggestions/language', DEFAULT_LANGUAGE);
        $status = DB::getInstance()->query('SELECT * FROM nl2_suggestions_statuses WHERE id = ?', [$this->suggestion->data()->status_id]);
        $color = $status->count() ? $status->first()->color : null;

        return DiscordWebhookBuilder::make()
            ->setUsername($this->user->getDisplayname())
            ->setAvatarUrl($this->user->getAvatar(128, true))
            ->addEmbed(function (DiscordEmbed $embed) use ($language, $color) {
                return $embed
                    ->setTitle('#' . $this->suggestion->data()->id . ' - ' . $this->suggestion->data()->title)
                    ->setDescription(Text::embedSafe(strip_tags($this->content)))
                    ->setFooter($language->get('general', 'hook_new_comment', [
                        'user' => $this->user->getDisplayname(),
                        'likes' => $this->suggestion->data()->likes,
                        'dislikes' => $this->suggestion->data()->dislikes
                    ]))
                    ->setUrl(URL::getSelfURL() . ltrim($this->suggestion->getURL() . '#comment-' . $comment_id, '/'))
                    ->setColor($color);
            });
    }
}