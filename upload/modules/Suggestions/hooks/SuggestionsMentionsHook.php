<?php
/*
 *  Made by Samerton and Partydragen
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0 pre-13
 *
 *  Mentions hook for pre-create/edit event for Core module
 */

class SuggestionsMentionsHook extends HookBase {

    private static array $_cache = [];
    private static array $_suggestions_cache = [];

    public static function preCreate(array $params = []): array {
        if (self::validate($params)) {
            $params['content'] = MentionsParser::parse(
                $params['user']->data()->id,
                $params['content'],
                $params['alert_url'] ?: null,
                $params['alert_short'] ?: null,
                $params['alert_full'] ?: null,
            );

            $params['content'] = self::parse(
                $params['user']->data()->id,
                $params['content'],
                $params['suggestion_id']
            );
        }

        return $params;
    }

    public static function preEdit(array $params = []): array {
        if (self::validate($params)) {
            $params['content'] = MentionsParser::parse(
                $params['user']->data()->id,
                $params['content'],
                ''
            );

            $params['content'] = self::parse(
                $params['user']->data()->id,
                $params['content'],
                $params['suggestion_id']
            );
        }

        return $params;
    }

    public static function parsePost(array $params = []): array {
        if (parent::validateParams($params, ['content'])) {
            $params['content'] = preg_replace_callback(
                '/\[user\](.*?)\[\/user\]/ism',
                static function (array $match) {
                    if (isset(SuggestionsMentionsHook::$_cache[$match[1]])) {
                        [$userId, $userStyle, $userNickname, $userProfileUrl] = SuggestionsMentionsHook::$_cache[$match[1]];
                    } else {
                        $user = new User($match[1]);

                        if (!$user->exists()) {
                            return '@' . (new Language('core', LANGUAGE))->get('general', 'deleted_user');
                        }

                        $userId = $user->data()->id;
                        $userStyle = $user->getGroupStyle();
                        $userNickname = $user->data()->nickname;
                        $userProfileUrl = $user->getProfileURL();

                        SuggestionsMentionsHook::$_cache[$match[1]] = [$userId, $userStyle, $userNickname, $userProfileUrl];
                    }

                    return '<a href="' . $userProfileUrl . '" data-poload="' . URL::build('/queries/user/', 'id=' . $userId) . '" class="user-mention" style="' . $userStyle . '">@' . Output::getClean($userNickname) . '</a>';
                },
                $params['content']
            );

            $params['content'] = preg_replace_callback(
                '/\[suggestion\](.*?)\[\/suggestion\]/ism',
                static function (array $match) {
                    if (isset(SuggestionsMentionsHook::$_suggestions_cache[$match[1]])) {
                        [$suggestionId, $suggestionUrl] = SuggestionsMentionsHook::$_suggestions_cache[$match[1]];
                    } else {
                        $suggestion = new Suggestion($match[1]);

                        if (!$suggestion->exists()) {
                            return '#' . (new Language(ROOT_PATH . '/modules/Suggestions/language', LANGUAGE))->get('general', 'deleted_suggestion');
                        }

                        $suggestionId = $suggestion->data()->id;
                        $suggestionUrl = $suggestion->getURL();

                        SuggestionsMentionsHook::$_suggestions_cache[$match[1]] = [$suggestionId, $suggestionUrl];
                    }

                    return '<a href="' . $suggestionUrl . '" data-poload="' . URL::build('/queries/suggestion/', 'id=' . $suggestionId) . '" class="suggestion-mention">#' . Output::getClean($suggestionId) . '</a>';
                },
                $params['content']
            );
        }

        return $params;
    }

    public static function parse(int $author_id, string $value, int $suggestion_id): string {
        if (preg_match_all('/#([0-9\-_!.]+)/', $value, $matches)) {
            $matches = $matches[1];
            $already_mentioned = [];

            foreach ($matches as $possible_suggestion) {
                $suggestion = null;

                while (($possible_suggestion != '') && !$suggestion) {
                    $suggestion = new Suggestion($possible_suggestion);

                    if ($suggestion->exists()) {
                        $value = preg_replace('/' . preg_quote("#$possible_suggestion", '/') . '/', '[suggestion]' . $suggestion->data()->id . '[/suggestion]', $value);

                        if (!in_array($suggestion->data()->id, $already_mentioned)) {
                            DB::getInstance()->insert('suggestions_comments', [
                                'suggestion_id' => $suggestion->data()->id,
                                'user_id' => $author_id,
                                'type' => 2,
                                'content' => $suggestion_id,
                                'created' => date('U')
                            ]);

                            $already_mentioned[] = $suggestion->data()->id;
                        }
                    }

                    // chop last word off of it
                    $new_possible_suggestion = preg_replace('/([^0-9]|[0-9]+)$/', '', $possible_suggestion);
                    if ($new_possible_suggestion !== $possible_suggestion) {
                        $possible_suggestion = $new_possible_suggestion;
                    } else {
                        break;
                    }
                }
            }
        }

        return $value;
    }

    private static function validate(array $params): bool {
        return parent::validateParams($params, ['content', 'user']);
    }
}