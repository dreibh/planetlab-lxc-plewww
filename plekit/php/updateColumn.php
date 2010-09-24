
<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

//print header
require_once 'plc_drupal.php';

// Common functions
require_once 'plc_functions.php';

$slice_id=intval($_GET["slice_id"]);
$tagN=$_GET["tagName"];

$slices= $api->GetSlices( array($slice_id));

if (empty($slices)) {
  drupal_set_message ("Slice " . $slice_id . " not found");
  return;
 }

$slice=$slices[0];

$nodetags = array('node_id');
$extratags = explode("|", $tagN);

$nodes=$api->GetNodes(array('node_id'=>$slice['node_ids']),array_merge($nodetags, $extratags));
$potential_nodes=$api->GetNodes(array('~node_id'=>$slice['node_ids']),array_merge($nodetags, $extratags));

echo "---attached---";
if ($nodes) foreach ($nodes as $node) {
	echo "|".$node['node_id'];
	foreach ($extratags as $t)
		echo ":".$node[$t];
}
echo "|---potential---";
if ($potential_nodes) foreach ($potential_nodes as $potential_node) {
	echo "|".$potential_node['node_id'];
	foreach ($extratags as $t)
		echo ":".$potential_node[$t];
}

?> 

