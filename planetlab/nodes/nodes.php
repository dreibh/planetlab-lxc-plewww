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
$peerscope=$_GET['peerscope'];
$pattern=$_GET['pattern'];
$site_id=intval($_GET['site_id']);
$slice_id=intval($_GET['slice_id']);

// --- decoration
$title="Nodes";
$tabs=array();
$mysite_id=plc_my_site_id();
$tabs['My nodes'] = array('url'=>l_nodes(),
			  'values'=>array('site_id'=>plc_my_site_id()),
			  'bubble'=>'Lists nodes on site ' . $mysite_id);
// -------------------- 
$peer_filter=array();
$node_filter=array();

//////////////////
// performs sanity check and summarize the result in a single column
function node_status ($node) {

  $messages=array();
  
  // do all this stuff on local nodes only
  if ( ! $node['peer_id'] ) {
    // check that the node has keys
    if (count($node['interface_ids']) == 0) {
      $messages [] = "No interface";
    }
  }
  return plc_vertical_table($messages,'plc-warning');
}


// fetch nodes 
$node_columns=array('hostname','node_type','site_id','node_id','boot_state','interface_ids','peer_id');
// server-side filtering - set pattern in $_GET for filtering on hostname
if ($pattern) {
  $node_filter['hostname']=$pattern;
  $title .= " matching " . $pattern;
 } else {
  $node_filter['hostname']="*";
 }

// server-side selection on peerscope
list ( $peer_filter, $peer_label) = plc_peer_info($api,$_GET['peerscope']);
$node_filter=array_merge($node_filter,$peer_filter);

if ($site_id) {
  $sites=$api->GetSites(array($site_id),array("name","login_base"));
  $site=$sites[0];
  $name=$site['name'];
  $login_base=$site['login_base'];
  $title .= t_site($site);
  $tabs = array_merge($tabs,tabs_site($site));
  $node_filter['site_id']=array($site_id);
}

if ($slice_id) {
  $slices=$api->GetSlices(array($slice_id),array('node_ids','name'));
  $slice=$slices[0];
  $title .= t_slice($slice);
  $tabs = array_merge($tabs,tabs_slice($slice));
  $node_filter['node_id'] = $slice['node_ids'];
 }

// go
$nodes=$api->GetNodes($node_filter,$node_columns);

// build site_ids - interface_ids
$site_ids=array();
$interface_ids=array();
if ($nodes) foreach ($nodes as $node) {
  $site_ids []= $node['site_id'];
  $interface_ids = array_merge ($interface_ids,$node['interface_ids']);
}

// fetch related interfaces
$interface_columns=array('ip','node_id','interface_id');
$interface_filter=array('is_primary'=>TRUE,'interface_id'=>$interface_ids);
$interfaces=$api->GetInterfaces($interface_filter,$interface_columns);

$interface_hash=array();
foreach ($interfaces as $interface) {
    $interface_hash[$interface['node_id']]=$interface;
}

// fetch related sites
$site_columns=array('site_id','login_base');
$site_filter=array('site_id'=>$site_ids);
$sites=$api->GetSites($site_filter,$site_columns);

$site_hash=array();
foreach ($sites as $site) {
    $site_hash[$site['site_id']]=$site;
}

// --------------------
drupal_set_title($title);

plc_tabs($tabs);

if ( ! $nodes ) {
  drupal_set_message ('No node found');
  return;
 }
  
$columns = array ("Peer"=>"string",
		  "Region"=>"string",
		  "Site"=>"string",
		  "State"=>"string",
		  "Hostname"=>"string",
		  "IP"=>"IPAddress",
		  "Type"=>"string",
		  "?"=>"string",
		  "Int"=>"int",
		  "Float"=>"float");

# initial sort on hostnames
plc_table_start("nodes",$columns,4);

$peer_hash = plc_peer_get_hash ($api);
// write rows
$fake1=1; $fake2=3.14; $fake_i=0;
foreach ($nodes as $node) {
    $hostname=$node['hostname'];
    $node_id=$node['node_id'];
    $site_id=$node['site_id'];
    $site=$site_hash[$site_id];
    $login_base = $site['login_base'];
    $node_id=$node['node_id'];
    $ip=$interface_hash[$node['node_id']]['ip'];
    $interface_id=$interface_hash[$node['node_id']]['interface_id'];
    $shortname = plc_peer_shortname ($peer_hash,$node['peer_id']);
    $node_type = $node['node_type'];

    plc_table_row_start($hostname);
    plc_table_cell ($shortname);
    plc_table_cell (topdomain($hostname));
    plc_table_cell (l_site_t($site_id,$login_base));
    plc_table_cell ($node['boot_state']);
    plc_table_cell (l_node_t($node_id,$hostname));
    plc_table_cell (l_interface_t($interface_id,$ip));
    plc_table_cell ($node_type);
    plc_table_cell (node_status($node));
    plc_table_cell ($fake1);
    plc_table_cell ($fake2);
    plc_table_row_end();
				 
    if ($fake_i % 5 == 0) $fake1 += 3; 
    if ($fake_i % 3 == 0) $fake2 +=5; else $fake2 -= $fake_i;
    $fake_i += 1;
}

plc_table_end("nodes");

// Print footer
include 'plc_footer.php';

?>
