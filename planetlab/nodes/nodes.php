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
require_once 'nifty.php';

ini_set("memory_limit","48M");

// -------------------- 
// recognized URL arguments
$peerscope=$_GET['peerscope'];
$pattern=$_GET['pattern'];
$site_id=intval($_GET['site_id']);
$slice_id=intval($_GET['slice_id']);
$person_id=intval($_GET['person_id']);

// --- decoration
$title="Nodes";
$tabs=array();
$tabs []= tab_nodes();
if (count (plc_my_site_ids()) == 1) {
    $tabs []= tab_nodes_mysite();
} else {
    $tabs []= tab_nodes_all_mysite();
}
$tabs []= tab_nodes_local();

// -------------------- 
$node_filter=array();

//////////////////
// performs sanity check and summarize the result in a single column
function node_status ($node) {

  // do all this stuff on local nodes only
  if ( $node['peer_id'] )
    return "n/a";

  $messages=array();
  // check that the node has interfaces
  if (count($node['interface_ids']) == 0) {
    $messages [] = "No interface";
  }
  return plc_vertical_table($messages,'plc-warning');
}

// fetch nodes 
$node_columns=array('hostname','node_type','site_id','node_id','boot_state','interface_ids','peer_id', 'arch','slice_ids');
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

// person_id is set : this is mostly oriented towards people managing several sites
if ($person_id) {
  // avoid doing a useless call to GetPersons if the person_id is already known though $plc,
  // as this is mostly done for the 'all my sites nodes' link
  if ($person_id == plc_my_person_id()) { 
    $person=plc_my_person();
    $site_ids = plc_my_site_ids();
  } else {
    // fetch the person's site_ids
    $persons = $api->GetPersons(array('person_id'=>$person_id),array('person_id','email','site_ids'));
    $person=$persons[0];
    $site_ids=$person['site_ids'];
  }
  $title .= t_person($person);
  $node_filter['site_id']=$site_ids;
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

plekit_linetabs($tabs);

if ( ! $nodes ) {
  drupal_set_message ('No node found');
  return;
 }
  
$nifty=new PlekitNifty ('','objects-list','big');
$nifty->start();
$headers = array (); $offset=0;
if (plc_is_admin()) { $headers["I"]="int"; $offset=1; }
$headers["P"]="string";
$headers["R"]="string";
$headers["Site"]="string";
$headers["State"]="string";
$headers["Hostname"]="string";
$headers["Type"]="string";
$headers["IP"]="sortIPAddress";
$headers["A"]="string";
$headers["S"]='int';
$headers["?"]="string";

# initial sort on hostnames
$table=new PlekitTable ("nodes",$headers,4+$offset);
$table->start();

$peers = new Peers ($api);
// write rows
foreach ($nodes as $node) {
  $hostname=$node['hostname'];
  $node_id=$node['node_id'];
  $site_id=$node['site_id'];
  $site=$site_hash[$site_id];
  $login_base = $site['login_base'];
  $ip=$interface_hash[$node['node_id']]['ip'];
  $interface_id=$interface_hash[$node['node_id']]['interface_id'];
  $peer_id=$node['peer_id'];
  $node_type = $node['node_type'];
  
  $table->row_start();
  if (plc_is_admin()) $table->cell(l_node_t($node_id,$node_id));
  $peers->cell ($table,$peer_id);
  $table->cell (topdomain($hostname));
  $table->cell (l_site_t($site_id,$login_base));
  if ($node['run_level']) {
      $table->cell($node['run_level']);
  } else {
      $table->cell ($node['boot_state'] . '*');
  }
  $table->cell (l_node_t($node_id,$hostname));
  $table->cell ($node_type);
  $table->cell (l_interface_t($interface_id,$ip),array('only-if'=> !$peer_id));
  $table->cell ($node['arch'],array('only-if'=> !$peer_id));
  $table->cell (count($node['slice_ids']));
  $table->cell (node_status($node));
  $table->row_end();
  
}

$notes=array();
if (plc_is_admin()) $notes []= "I = node_id";
$notes []= "R = region";
$notes []= "A = arch";
$notes []= "S = number of slivers";
$notes []= "? = status";
$table->end(array('notes'=>$notes));
$nifty->end();

//plekit_linetabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>
