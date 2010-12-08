
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

require_once 'columns.php';


$slice_id=$_GET["slice_id"];
$tagN=$_GET["tagName"];
$data_source=$_GET["data_source"];
$data_type=$_GET["data_type"];


$nodetags = array('node_id');
$extratags = explode("|", $tagN);

if ($slice_id == "nodes") {

if ($data_source == "comon") {

$comontags = $extratags;
$extratags = array ('hostname');
$nodes=$api->GetNodes(NULL, array_merge($nodetags, $extratags));
$ColumnsConfigure = new PlekitColumns(NULL, NULL, NULL);
$comon_data = $ColumnsConfigure->comon_query_nodes(",".$tagN);

//print ("comon tags = ".$comontags);

echo "---attached---";
if ($nodes) foreach ($nodes as $node) {
	echo "|".$node['node_id'];
	foreach ($comontags as $t)
		echo ":".$ColumnsConfigure->convert_data($comon_data[$node['hostname']][$t], $data_type);
}
}
else if ($data_source == "tophat") {
$extratags = array ('hostname');
$nodes=$api->GetNodes(NULL, array_merge($nodetags, $extratags));
echo "---attached---";
if ($nodes) foreach ($nodes as $node) {
	echo "|".$node['node_id'];
	echo ":n/a";
}
}
else
{

echo "---attached---";
$nodes=$api->GetNodes(NULL, array_merge($nodetags, $extratags));
//echo $nodes;

if ($nodes) foreach ($nodes as $node) {
	echo "|".$node['node_id'];
	foreach ($extratags as $t)
		echo ":".$node[$t];
}
}



}
else
{

$slices= $api->GetSlices( array(intval($slice_id)));
if (empty($slices)) {
  drupal_set_message ("Slice " . $slice_id . " not found");
}

$slice=$slices[0];

if ($data_source == "comon") {

$comontags = $extratags;
$extratags = array ('hostname');

$nodes=$api->GetNodes(array('node_id'=>$slice['node_ids']),array_merge($nodetags, $extratags));
$potential_nodes=$api->GetNodes(array('~node_id'=>$slice['node_ids']),array_merge($nodetags, $extratags));

$ColumnsConfigure = new PlekitColumns(NULL, NULL, NULL);
$comon_data = $ColumnsConfigure->comon_query_nodes(",".$tagN);

//print ("comon tags = ".$comontags);

echo "---attached---";
if ($nodes) foreach ($nodes as $node) {
	echo "|".$node['node_id'];
	foreach ($comontags as $t)
		echo ":".$ColumnsConfigure->convert_data($comon_data[$node['hostname']][$t], $data_type);
}
echo "|---potential---";
if ($potential_nodes) foreach ($potential_nodes as $potential_node) {
	echo "|".$potential_node['node_id'];
	foreach ($comontags as $t)
		echo ":".$ColumnsConfigure->convert_data($comon_data[$potential_node['hostname']][$t], $data_type);
}
}
else if ($data_source == "tophat") {
$extratags = array ('hostname');
$nodes=$api->GetNodes(array('node_id'=>$slice['node_ids']),array_merge($nodetags, $extratags));
$potential_nodes=$api->GetNodes(array('~node_id'=>$slice['node_ids']),array_merge($nodetags, $extratags));
echo "---attached---";
if ($nodes) foreach ($nodes as $node) {
	echo "|".$node['node_id'];
	echo ":n/a";
}
echo "|---potential---";
if ($potential_nodes) foreach ($potential_nodes as $potential_node) {
	echo "|".$potential_node['node_id'];
	echo ":n/a";
}
}
else
{

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
}
}
?> 

