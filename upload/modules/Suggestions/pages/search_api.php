<?php
/*
 *  Nameless Hosting - knowledgebase search API
 */

header('Content-Type: application/json');

// Get search query
if(!isset($_GET['q'])){
	die(json_encode(array('error' => 'No search query set')));
}

$query = '%' . htmlspecialchars($_GET['q']) . '%';

// Query database and get results
$results = array('results' => array(), 'action' => array('url' => URL::build('/suggestions'), 'text' => 'Full Search'));

$knowledgebase = $queries->getLike('suggestions', 'deleted = 0 AND status_id != 2 AND title', '%' . $query . '%');

if(count($knowledgebase)){
	foreach($knowledgebase as $item){
		$results['results']['communities']['results'][] = array('title' => htmlspecialchars('#'.$item->id. ' - ' . $item->title), 'url' => URL::build('/suggestions/view/' . $item->id));
	}
}

die(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));