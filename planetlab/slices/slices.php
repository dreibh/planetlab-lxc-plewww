<?php

// $Id$
// pattern-matching selection not implemented
// due to GetSlices bug, see test.php for details
// in addition that would not make much sense

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

ini_set('memory_limit','48M');

// -------------------- 
// recognized URL arguments
$peerscope=$_GET['peerscope'];
$pattern=$_GET['pattern'];
$site_id=intval($_GET['site_id']);
$person_id=intval($_GET['person_id']);

// --- decoration
$title="Slices";
$tabs=array();
$tabs []= tab_slices_mysite();
if (plc_is_admin()) $tabs []= tab_slices_local();

// ----------
$slice_filter=array();


// fetch slices
$slice_columns=array('slice_id','name','expires','person_ids','peer_id','node_ids');
// server-side filtering - set pattern in $_GET for filtering on hostname
if ($pattern) {
  $slice_filter['name']=$pattern;
  $title .= " matching " . $pattern;
 } else {
  $slice_filter['name']="*";
 }

// server-side selection on peerscope
$peerscope=new PeerScope($api,$_GET['peerscope']);
$slice_filter=array_merge($slice_filter,$peerscope->filter());
$title .= ' - ' . $peerscope->label();


if ($site_id) {
  $sites=$api->GetSites(array($site_id));
  $site=$sites[0];
  $name=$site['name'];
  $login_base=$site['login_base'];
  $title .= t_site($site);
  $tabs []= tab_site($site);
  $slice_filter['site_id']=array($site_id);
}

if ($person_id) {
  $persons=$api->GetPersons(array('person_id'=>$person_id,array('email','person_id','slice_ids')));
  $person=$persons[0];
  $title .= t_person($person);
  $tabs .= tab_person($person);
  $slice_filter['slice_id']=$person['slice_ids'];
 }

// go
$slices=$api->GetSlices($slice_filter,$slice_columns);

// build person_hash
$person_ids=array();
if ($slices) foreach ($slices as $slice) {
  $person_ids = array_merge ($person_ids,$slice['person_ids']);
}
$persons=$api->GetPersons($person_ids,array('person_id','email'));
global $person_hash;
$person_hash=array();
if ($persons) foreach ($persons as $person) $person_hash[$person['person_id']]=$person;

function email_link_from_hash($person_id) { 
  global $person_hash; 
  return l_person_obj($person_hash[$person_id]);
}

// --------------------
drupal_set_title($title);

plekit_linetabs($tabs);

if ( ! $slices ) {
  drupal_set_message ('No slice found');
  return;
 }
  
$nifty=new PlekitNifty ('','objects-list','big');
$nifty->start();
if (plc_is_admin()) $headers["I"]="int";
$headers["Peer"]="string";
$headers["Name"]="string";
$headers["Users"]="string";
$headers["U"]="int";
$headers["N"]="int";
$headers["Exp. d/m/y"]="date-dmy";

# initial sort on hostnames
if (plc_is_admin()) $slices_sort_column = 3;
else $slices_sort_column = 2;
    
$table=new PlekitTable ("slices",$headers,$slices_sort_column,
			array('search_width'=>20));
$table->start();

$peers = new Peers ($api);
// write rows
foreach ($slices as $slice) {
  $slice_id=$slice['slice_id'];
  $peer_id=$slice['peer_id'];
  $users=plc_vertical_table (array_map ("email_link_from_hash",$slice['person_ids']));
  $expires= date( "d/m/Y", $slice['expires'] );

  $table->row_start();
  if (plc_is_admin()) $table->cell (l_slice_t($slice_id,$slice_id));
  $peers->cell($table,$peer_id);
  $table->cell (l_slice_obj($slice));
  $table->cell ($users);
  $table->cell(href(l_persons_slice($slice_id),count($slice['person_ids'])));
  $table->cell (href(l_nodes_slice($slice_id),count($slice['node_ids'])));
  $table->cell ($expires);
  $table->row_end();
}

$notes=array();
$notes []= "U = number of users";
$notes []= "N = number of nodes";
$table->end(array('notes'=>$notes));
$nifty->end();

//plekit_linetabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>
