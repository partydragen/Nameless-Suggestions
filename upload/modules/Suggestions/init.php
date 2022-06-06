<?php 
/*
 *	Made by Partydragen
 *  https://github.com/partydragen/Nameless-Suggestions
 *  https://partydragen.com
 *  NamelessMC version 2.0.0-pr10
 *
 *  License: MIT
 *
 *  Suggestions module initialisation file
 */
 
// Initialise Suggestions language
$suggestions_language = new Language(ROOT_PATH . '/modules/Suggestions/language', LANGUAGE);

// Initialise module
require_once(ROOT_PATH . '/modules/Suggestions/module.php');
$module = new Suggestions_Module($language, $suggestions_language, $pages, $navigation, $cache);