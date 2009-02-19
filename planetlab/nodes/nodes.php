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
require_once 'plc_peers.php';
require_once 'linetabs.php';
require_once 'table.php';

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
$tabs []= tab_nodes_mysite();
$tabs []= tab_nodes_local();

// -------------------- 
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
$node_columns=array('hostname','node_type','site_id','node_id','boot_state','interface_ids','peer_id', "arch");
// server-side filtering - set pattern in $_GET for filtering on hostname
if ($pattern) {
  $node_filter['hostname']=$pattern;
  $title .= " matching " . $pattern;
 } else {
  $node_filter['hostname']="*";
 }

// server-side selection on peerscope
$peerscope=new PeerScope($api,$_GET['peerscope']);
$node_filter=array_merge($node_filter,$peerscope->filter());
$title .= ' - ' . $peerscope->label();

if ($site_id) {
  $sites=$api->GetSites(array($site_id));
  $site=$sites[0];
  $name=$site['name'];
  $login_base=$site['login_base'];
  $title .= t_site($site);
  $tabs []= tab_site($site);
  $node_filter['site_id']=array($site_id);
}

if ($slice_id) {
  $slices=$api->GetSlices(array($slice_id),array('node_ids','name'));
  $slice=$slices[0];
  $title .= t_slice($slice);
  $tabs []= tab_slice($slice);
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
foreach ($interfaces as $interface) $interface_hash[$interface['node_id']]=$interface;

// fetch related sites
$site_columns=array('site_id','login_base');
$site_filter=array('site_id'=>$site_ids);
$sites=$api->GetSites($site_filter,$site_columns);

$site_hash=array();
foreach ($sites as $site) $site_hash[$site['site_id']]=$site;

// --------------------
drupal_set_title($title);

plc_tabs($tabs);

if ( ! $nodes ) {
  drupal_set_message ('No node found');
  return;
 }
  
$headers = array ("Peer"=>"string",
		  "Region"=>"string",
		  "Site"=>"string",
		  "State"=>"string",
		  "Hostname"=>"string",
		  "IP"=>"IPAddress",
		  "Type"=>"string",
		  "Arch"=>"string",
		  "?"=>"string",
		  );

# initial sort on hostnames
$table=new PlcTable ("nodes",$headers,4);
$table->start();

$peers = new Peers ($api);
// write rows
foreach ($nodes as $node) {
    $hostname=$node['hostname'];
    $node_id=$node['node_id'];
    $site_id=$node['site_id'];
    $site=$site_hash[$site_id];
    $login_base = $site['login_base'];
    $node_id=$node['node_id'];
    $ip=$interface_hash[$node['node_id']]['ip'];
    $interface_id=$interface_hash[$node['node_id']]['interface_id'];
    $peer_id=$node['peer_id'];
    $shortname = $peers->shortname($peer_id);
    $node_type = $node['node_type'];

    $table->row_start();
    $table->cell ($peers->link($peer_id,$shortname));
    $table->cell (topdomain($hostname));
    $table->cell (l_site_t($site_id,$login_base));
    $table->cell ($node['boot_state']);
    $table->cell (l_node_t($node_id,$hostname));
    $table->cell (l_interface_t($interface_id,$ip));
    $table->cell ($node_type);
    $table->cell ($node['arch']);
    $table->cell (node_status($node));
    $table->row_end();
				 
}

$table->end();

//plc_tabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>
