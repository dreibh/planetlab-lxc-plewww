<?php

// $Id$

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_minitabs.php';
require_once 'plc_tables.php';

// -------------------- 
// recognized URL arguments
$pattern=$_GET['pattern'];

// --- decoration
$title="Nodegroups";
$tabs=array();
$tabs['Tags'] = array('url'=>l_tags(),
		      'bubble'=>'Lists all known tag types');
// -------------------- 
$node_filter=array();


// fetch objs
$nodegroup_columns=array("nodegroup_id","groupname","tagname","value","node_ids");

// server-side filtering - set pattern in $_GET for filtering on hostname
if ($pattern) {
  $nodegroup_filter['groupname']=$pattern;
  $title .= " matching " . $pattern;
 } else {
  $nodegroup_filter['groupname']="*";
 }

// go
$nodegroups=$api->GetNodeGroups($nodegroup_filter,$nodegroup_columns);

// --------------------
drupal_set_title($title);

plc_tabs($tabs);

if ( ! $nodegroups ) {
  drupal_set_message ('No node group found');
  return;
 }
  

$headers = array ( "Name"=>"string",
		   "Tag"=>"string",
		   "Value"=>"string",
		   "Nodes"=>"int");

# initial sort on groupname
plc_table_start("nodegroups",$headers,0);

foreach ($nodegroups as $nodegroup) {
  $table->row_start();
  $table->cell (href(l_nodegroup($nodegroup['nodegroup_id']),$nodegroup['groupname']));
  $table->cell ($nodegroup['tagname']);
  $table->cell ($nodegroup['value']);
  $table->cell (count($nodegroup['node_ids']));
  $table->row_end();
}

plc_table_end("nodegroups");

// Print footer
include 'plc_footer.php';

?>
