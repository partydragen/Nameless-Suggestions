<?php
/*
 *  Made by Partydragen
 *  https://github.com/partydragen/Nameless-Suggestions
 *  https://partydragen.com/
 *
 *  Suggestions page
 */

header('Content-Type: application/json');

// Get search query
if(!isset($_GET['q'])){
	die(json_encode(array('error' => 'No search query set')));
}

$query = '%' . htmlspecialchars($_GET['q']) . '%';

// Query database and get results
$results = array('results' => array(), 'action' => array('url' => URL::build('/suggestions'), 'text' => 'Full Search'));

$search = DB::getInstance()->query("SELECT * FROM nl2_suggestions WHERE deleted = 0 AND status_id != 2 AND title LIKE = '$query'")->results();

if(count($search)){
	foreach($search as $item){
		$results['results']['communities']['results'][] = array('title' => htmlspecialchars('#'.$item->id. ' - ' . $item->title), 'url' => URL::build('/suggestions/view/' . $item->id));
	}
}

die(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));