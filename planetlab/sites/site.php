<?php

  // $Id: index.php 11750 2009-01-29 10:11:53Z thierry $

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
require_once 'plc_minitabs.php';
require_once 'plc_tables.php';
require_once 'plc_details.php';
require_once 'plc_forms.php';

// -------------------- 
// recognized URL arguments
$site_id=intval($_GET['id']);
if ( ! $site_id ) { plc_error('Malformed URL - id not set'); return; }

////////////////////
// Get all columns as we focus on only one entry
$sites= $api->GetSites( array($site_id));

if (empty($sites)) {
  drupal_set_message ("Site " . $site_id . " not found");
  return;
 }

$site=$sites[0];
// var names to api return
$sitename= htmlentities($site['name']);
$abbreviated_name= htmlentities($site['abbreviated_name']);
$site_url= $site['url'];
$login_base= $site['login_base'];
$site_lat= $site['latitude'];
$site_long= $site['longitude'];
$max_slivers= $site['max_slivers'];
$max_slices= $site['max_slices'];

$enabled = $site['enabled'];

// extra privileges to admins, and (pi||tech) on this site
$privileges = plc_is_admin () || ( plc_in_site($site_id) && ( plc_is_pi() || plc_is_tech()));
  
// get peer details
$peer_id= $site['peer_id'];
$peers = new Peers ($api);

$adress_ids= $site['address_ids'];
$pcu_ids= $site['pcu_ids'];
$node_ids= $site['node_ids'];
$person_ids= $site['person_ids'];
$slice_ids= $site['slice_ids'];

$api->begin();
// gets address info
$api->GetAddresses( $adress_ids );

// gets pcu info
// GetPCUs is not accessible to the 'user' role
//$api->GetPCUs( $pcu_ids );

// gets node info
$api->GetNodes( $node_ids, array( "node_id", "hostname", "boot_state" ) );

// gets person info
$api->GetPersons( $person_ids, array( "role_ids", "person_id", "first_name", "last_name", "email", "enabled" ) );

$api->GetSlices ( $slice_ids, array ("slice_id", "name", "instantiation" ) );

//list( $addresses, $pcus, $nodes, $persons, $slices )= $api->commit();
list( $addresses, $nodes, $persons, $slices )= $api->commit();
  
$techs = array();
$pis = array();
$disabled_persons = array();
foreach( $persons as $person ) {
  $role_ids= $person['role_ids'];

  if ( in_array( '20', $role_ids ))	$pis[] = $person;
  if ( in_array( '40', $role_ids ))	$techs[] = $person;
  if ( ! $person['enabled'] )		$disabled_persons[] = $person;
  
}

drupal_set_title("Details for site " . $sitename);
$local_peer = ! $peer_id;
  
// extra privileges to admins, and pi on this site
$privileges = plc_is_admin () || ( plc_in_site($site_id) && plc_is_pi());
  
$tabs=array();
// available actions
if ( $local_peer  && $privileges ) {
  
  $tabs['Expire slices'] = array('url'=>l_actions(),
				 'method'=>'POST',
				 'values'=>array('site_id'=>$site_id,
						 'action'=>'expire-all-slices-in-site'),
				 'bubble'=>"Expire all slices and prevent creation of new slices",
				 'confirm'=>"Suspend all slices in $login_base");
  if (plc_is_admin())
    $tabs['Delete']=array('url'=>l_actions(),
			  'method'=>'POST',
			  'values'=>array('site_id'=>$site_id,
					  'action'=>'delete-site'),
			  'bubble'=>"Delete site $sitename",
			  'confirm'=>"Are you sure you want to delete site $login_base");
  $tabs["Events"]=array_merge (tabs_events(),
			       array('url'=>l_event("Site","site",$site_id),
				     'bubble'=>"Events for site $sitename"));
  $tabs["Comon"]=array_merge(tabs_comon(),
			     array('url'=>l_comon("site_id",$site_id),
				   'bubble'=>"Comon page for $sitename"));

  if (plc_is_admin()) 
    $tabs['Pending'] = array ('url'=>l_sites_pending(),
			      'bubble'=>'Review pending join requests');
 }

$tabs["All sites"]=l_sites();

plc_tabs($tabs);

// show gray background on foreign objects : start a <div> with proper class
$peers->block_start ($peer_id);

if ( ! $enabled ) 
  plc_warning ("This site is not enabled - Please visit " . 
	       href (l_sites_pending(),"this page") . 
	       " to review pending applications.");

$can_update=plc_is_admin () || ( plc_in_site($site_id) && plc_is_pi());
$details = new PlcDetails($can_update);

if ( ! $site['is_public']) 
  plc_warning("This site is not public!");

$details->start();
$details->line("Peer",$peers->peer_link($peer_id));
$details->space();

$details->form_start(l_actions(),array('action'=>'update-site','site_id'=>$site_id));
$save_w=$details->set_field_width(30);
$details->line("Full name",$sitename,'name');
$details->set_field_width($save_w);
$details->line("Abbreviated name",$abbreviated_name,'abbreviated_name');
$details->line("URL",$site_url,'url');
$details->line("Latitude",$site_lat,'latitude');
$details->line("Longitude",$site_long,'longitude');
if (plc_is_admin()) 
  $details->line("Login base",$login_base,'login_base');
else
  $details->line("Login base",$login_base);
if (plc_is_admin())
  $details->line("Max slices",$max_slices,'max_slices');
else
  $details->line("Max slices",$max_slices);
$details->line("",$details->submit_html("submit","Update Site"));
$details->form_end();

if ( $local_peer ) {

  // Nodes
  $details->space();
  $nb_boot = 0;
  if ($nodes) foreach ($nodes as $node) if ($node['boot_state'] == 'boot') $nb_boot ++;
  $node_label = $nb_boot . " boot / " .  count($nodes) . " total";
  $details->line("# Nodes", href(l_nodes_site($site_id),$node_label));
  function n_link ($n) { return l_node_t($n['node_id'],$n['hostname'] . " (" . $n['boot_state'] . ")");}
  $nodes_label= plc_vertical_table(array_map ("n_link",$nodes));
  $details->line ("Hostnames",$nodes_label);
  $button=new PlcFormButton (l_node_add(),"add_node","Add node","POST");
  $details->line("",$button->html());

  // Users
  $details->space();
  $user_label = count($person_ids) . " Total / " .
    count ($pis) . " PIs / " .
    count ($techs) . " Techs";
  if ( (count ($pis) == 0) || (count ($techs) == 0) || (count($person_ids) >=50)) 
    $user_label = plc_warning_html ($user_label);
  $details->line ("# Users",href(l_persons_site($site_id),$user_label));
  function p_link ($p) { return l_person_t($p['person_id'],$p['email']); }
  // PIs
  $details->line("PI's",plc_vertical_table (array_map ("p_link",$pis)));
  // techs
  $details->line("Techs's",plc_vertical_table (array_map ("p_link",$techs)));
  if (count ($disabled_persons)) 
    $details->line("Disabled",plc_vertical_table (array_map ("p_link",$disabled_persons)));

  // Slices
  $details->space();
  // summary on slices
  $slice_label = count($slice_ids) . " running / " . $max_slices . " max";
  if (count($slice_ids) >= $max_slices) 
    $slice_label = plc_warning_html ($slice_label);
  $details->line("# Slices", href(l_slices_site($site_id),$slice_label));
  if ($slices) foreach ($slices as $slice)
     $details->line($slice['instantiation'],l_slice_obj($slice));
  $button=new PlcFormButton (l_slice_add(),"slice_add","Add slice","POST");
  $details->line("",$button->html());

  // Addresses
  if ($addresses) {
    $details->space();
    $details->line("Addresses","");
    foreach ($addresses as $address) {
      $details->line(plc_vertical_table($address['address_types']),
		       plc_vertical_table(array($address['line1'],
						$address['line2'],
						$address['line3'],
						$address['city'],
						$address['state'],
						$address['postalcode'],
						$address['country'])));
    }
  }

 }

$details->end();

////////////////////////////////////////
$peers->block_end($peer_id);

// Print footer
include 'plc_footer.php';

?>
