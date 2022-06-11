<?php
/**
 * Contains namespaced API error messages for the Suggestions module.
 * These have no versioning, and are not meant to be used by any other modules.
 *
 * @package Modules\Suggestions
 * @author Partydragen
 * @version 2.0.0-pr13
 * @license MIT
 */
class SuggestionsApiErrors {
    public const ERROR_SUGGESTION_NOT_FOUND = 'suggestions:suggestion_not_found';
    public const ERROR_CATEGORY_NOT_FOUND = 'suggestions:category_not_found';
    public const ERROR_VALIDATION_ERRORS = 'suggestions:validation_errors';
    public const ERROR_UNKNOWN_ERROR = 'suggestions:unknown_error';
}