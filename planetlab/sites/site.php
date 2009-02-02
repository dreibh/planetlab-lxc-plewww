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
$abbrev_name= htmlentities($site['abbreviated_name']);
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
foreach( $persons as $person ) {
  $role_ids= $person['role_ids'];
  if( in_array( '40', $role_ids ))
    $techs[] = $person;
  
  if( in_array( '20', $role_ids ))
    $pis[] = $person;
  
}

// fetches peers and initialize hash peer_id->peer
drupal_set_title("Details for site " . $sitename);
  
// extra privileges to admins, and pi on this site
$privileges = plc_is_admin () || ( plc_in_site($site_id) && plc_is_pi());
  
$tabs=array();
// available actions
if ( ! $peer_id  && $privileges ) {
  
  $tabs['Update']=array('url'=>l_site_update($site_id),
			'bubble'=>"Update details of $sitename");
  // not avail to PI
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

plc_details_start();
plc_details_line("Full name",$sitename);
plc_details_line("Login base",$login_base);
plc_details_line("Abbreviated name",$abbrev_name);
plc_details_line("URL",$site_url);
plc_details_line("Latitude",$site_lat);
plc_details_line("Longitude",$site_long);
plc_details_line("Peer",$peers->peer_link($peer_id));

if ( ! $peer_id ) {

  // Addresses
  if ($addresses) {
    plc_details_space_line();
    plc_details_line("Addresses","");
    foreach ($addresses as $address) {
      plc_details_line(plc_vertical_table($address['address_types']),
		       plc_vertical_table(array($address['line1'],
						$address['line2'],
						$address['line3'],
						$address['city'],
						$address['state'],
						$address['postalcode'],
						$address['country'])));
    }
  }

  // Nodes
  plc_details_space_line();
  $nb_boot = 0;
  if ($nodes) foreach ($nodes as $node) if ($node['boot_state'] == 'boot') $nb_boot ++;
  $node_text = $nb_boot . " boot / " .  count($nodes) . " total";
  plc_details_line("# Nodes", href(l_nodes_site($site_id),$node_text));
  function n_link ($n) { return l_node_t($n['node_id'],$n['hostname'] . " (" . $n['boot_state'] . ")");}
  $nodes_text= plc_vertical_table(array_map ("n_link",$nodes));
  plc_details_line ("hostnames",$nodes_text);
		   
  // Users
  plc_details_space_line();
  $user_text = count($person_ids) . " total / " .
    count ($pis) . " PIs / " .
    count ($techs) . " techs";
  if ( (count ($pis) == 0) || (count ($techs) == 0) || (count($person_ids) >=50)) 
    $user_text = plc_warning_text ($user_text);
  plc_details_line ("# Users",href(l_persons_site($site_id),$user_text));
  function p_link ($p) { return l_person_t($p['person_id'],$p['email']); }
  // PIs
  $pi_text = plc_vertical_table (array_map ("p_link",$pis));
  plc_details_line("PI's",$pi_text);
  // PIs
  $tech_text = plc_vertical_table (array_map ("p_link",$techs));
  plc_details_line("techs's",$tech_text);

  // Slices
  plc_details_space_line();
  // summary on # slices
  $slice_text = count($slice_ids) . " running / " . $max_slices . " max";
  if (count($slice_ids) >= $max_slices) $slice_text = plc_warning_text ($slice_text);
  plc_details_line("# Slices", href(l_slices_site($site_id),$slice_text));
  if ($slices) foreach ($slices as $slice)
     plc_details_line($slice['instantiation'],l_slice_text($slice));


 }

plc_details_end();

////////////////////////////////////////
$peers->block_end($peer_id);

// Print footer
include 'plc_footer.php';

?>
