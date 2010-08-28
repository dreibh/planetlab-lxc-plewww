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
require_once 'plc_objects.php';
require_once 'plc_peers.php';
require_once 'plc_visibletags.php';
require_once 'linetabs.php';
require_once 'table.php';
require_once 'nifty.php';

ini_set("memory_limit","64M");

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

  $messages=array();
  if ($node['node_type'] != 'regular' && $node['node_type'] != 'reservable' ) 
    $messages []= $node['node_type'];

  // checks on local nodes only
  if ( ( ! $node['peer_id']) ) {
    // has it got interfaces 
    if (count($node['interface_ids']) == 0) 
      $messages []= "No interface";
  }
  return plc_vertical_table($messages,'plc-warning');
}

// fetch nodes 
$node_fixed_columns=array('hostname','node_type','site_id','node_id','boot_state','run_level','last_contact',
			  'interface_ids','peer_id', 'slice_ids');
$visibletags = new VisibleTags ($api, 'node');
$visiblecolumns = $visibletags->column_names();
$node_columns=array_merge($node_fixed_columns,$visiblecolumns);

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
$notes=array();

// fixed columns
if (plc_is_admin()) { 
  $short="I"; $long="node_id"; $type='int'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
  $offset=1; 
 }
$short="P"; $long="Peer"; $type='string'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
$short="D"; $long="toplevel domain name"; $type='string'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
$headers["Site"]="string";
$headers["Hostname"]="string";
$short="IP"; $long="IP Address"; $type='sortIPAddress'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
$short="ST"; $long=Node::status_footnote(); $type='string'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
$short="SL"; $long="Number of slivers"; $type='int'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";

$headers=array_merge($headers,$visibletags->headers());
$notes=array_merge($notes,$visibletags->notes());
$short="?"; $long="extra status info"; $type='string'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";

# initial sort on hostnames
$table=new PlekitTable ("nodes",$headers,3+$offset);
$table->start();

$peers = new Peers ($api);
// write rows
foreach ($nodes as $node) {
  //$node_obj = new Node ($node);
  $hostname=$node['hostname'];
  $node_id=$node['node_id'];
  $site_id=$node['site_id'];
  $site=$site_hash[$site_id];
  $login_base = $site['login_base'];
  $ip=$interface_hash[$node['node_id']]['ip'];
  $interface_id=$interface_hash[$node['node_id']]['interface_id'];
  $peer_id=$node['peer_id'];
  
  $table->row_start();
  if (plc_is_admin()) $table->cell(l_node_t($node_id,$node_id));
  $peers->cell ($table,$peer_id);
  $table->cell (topdomain($hostname));
  $table->cell (l_site_t($site_id,$login_base));
  $table->cell (l_node_t($node_id,$hostname));
  $table->cell (l_interface_t($interface_id,$ip),array('only-if'=> !$peer_id));
  list($label,$class) = Node::status_label_class_($node);
  $table->cell ($label,array('class'=>$class));
  $table->cell (count($node['slice_ids']));
  foreach ($visiblecolumns as $tagname) $table->cell($node[$tagname]);
  $table->cell (node_status($node));
  $table->row_end();
  
}

$table->end(array('notes'=>$notes));
$nifty->end();

//plekit_linetabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>
