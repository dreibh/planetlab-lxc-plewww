<?php

// $Id: index.php 11645 2009-01-21 23:09:49Z thierry $

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

//fix the memory limit for this page
ini_set("memory_limit","48M");

// -------------------- 
// recognized URL arguments
$peerscope=$_GET['peerscope'];
$pattern=$_GET['pattern'];
$site_id=intval($_GET['site_id']);
$slice_id=intval($_GET['slice_id']);

// --- decoration
$title="Accounts";
$tabs=array();
$mysite_id=plc_my_site_id();
$tabs['My accounts'] = array('url'=>l_persons(),
			     'values'=>array('site_id'=>plc_my_site_id()),
			     'bubble'=>'Lists accounts on site ' . $mysite_id);
// -------------------- 
$peer_filter=array();
$person_filter=array();

////////////////////
function person_status ($person) {

  $messages=array();
  
  if ( $person['peer_id'] ) {
    $class='plc-foreign';
  } else {
    $class='plc-warning';
  }
  // check that the person has keys
  if ( count($person['key_ids']) == 0)
    $messages [] = "No Key";
  if ( ! $person['enabled'] ) 
    $messages[] = "Disabled";
  //detect tech-only people involved in slices. 
  if ( ( count($person['roles'])==1 ) && 
       ( in_array('tech',$person['roles']) )  && 
       (! empty($person["slice_ids"])) ) 
    $messages[]="Tech involved in a Slice";  
  return plc_vertical_table($messages,$class);
}


// fetch persons 
$person_columns=array('person_id','first_name','last_name','email','roles','peer_id','key_ids','site_ids');
// PIs and admins can see users not yet enabled
$privileges=plc_is_admin() || plc_is_pi();
if ( ! $privileges ) 
  $person_filter['enabled']=true;
// server-side filtering - set pattern in $_GET for filtering on email
if ($pattern) {
  $person_filter['email']=$pattern;
  $title .= " matching " . $pattern;
 } else {
  $person_filter['email']="*";
 }

// server-side selection on peerscope
list ( $peer_filter, $peer_label) = plc_peer_info($api,$_GET['peerscope']);
$person_filter=array_merge($person_filter,$peer_filter);

if ($site_id) {
  $sites=$api->GetSites(array($site_id),array("name","login_base","person_ids"));
  $site=$sites[0];
  $name=$site['name'];
  $login_base=$site['login_base'];
  $title .= t_site($site);
  $tabs = array_merge($tabs,tabs_site($site));
  $person_filter['person_id']=$site['person_ids'];
  if ($site_id == plc_my_site_id()) 
    unset($tabs['My accounts']);
}

if ($slice_id) {
  $slices=$api->GetSlices(array($slice_id),array('person_ids','name'));
  $slice=$slices[0];
  $title .= t_slice($slice);
  $tabs = array_merge($tabs,tabs_slice($slice));
  $person_filter['person_id'] = $slice['person_ids'];
 }

// go
$persons=$api->GetPersons($person_filter,$person_columns);

// build site_ids 
$site_ids=array();
if ($persons) foreach ($persons as $person) 
		if ($person['site_ids'][0])
		  $site_ids []= $person['site_ids'][0];

// fetch related sites
$site_columns=array('site_id','login_base');
$site_filter=array('site_id'=>$site_ids);
$sites=$api->GetSites($site_filter,$site_columns);

// hash on site_id
$site_hash=array();
foreach ($sites as $site) {
    $site_hash[$site['site_id']]=$site;
}

// --------------------
drupal_set_title($title);

plc_tabs($tabs);

if ( ! $persons ) {
  drupal_set_message ('No account found');
  return;
 }
  
$columns = array ("Peer"=>"string",
		  "Roles"=>"string",
		  "First"=>"string",
		  "Last"=>"string",
		  "Email"=>"string",
		  "Site" => "string",
		  "Status"=>"string",
		  );

// initial sort on email
$table_options=array();
plc_table_start("persons",$columns,4,$table_options);

$peer_hash = plc_peer_get_hash ($api);
// write rows

foreach ($persons as $person) {
    $person_id=$person['person_id'];
    $email=$person['email'];
    $shortname = plc_peer_shortname ($peer_hash,$person['peer_id']);
    $site_id=$person['site_ids'][0];
    $site=$site_hash[$site_id];
    $login_base = $site['login_base'];
    $roles = plc_vertical_table ($person['roles']);

    plc_table_row_start($email);
    
    plc_table_cell($shortname);
    plc_table_cell($roles);
    plc_table_cell ($person['first_name']);
    plc_table_cell ($person['last_name']);
    plc_table_cell(l_person_t($person_id,$email));
    plc_table_cell($login_base);
    plc_table_cell(person_status($person));
    plc_table_row_end();
				 
}

plc_table_end($table_options);

// Print footer
include 'plc_footer.php';


?>
