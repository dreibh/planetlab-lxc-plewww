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
require_once 'details.php';
require_once 'form.php';
require_once 'toggle.php';

// -------------------- 
// recognized URL arguments
$site_id=intval($_GET['id']);
if ( ! $site_id ) { plc_error('Malformed URL - id not set'); return; }

////////////////////
// Get all columns as we focus on only one entry
$sites = $api->GetSites( array($site_id));

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
  
// get peer 
$peer_id= $site['peer_id'];
$peers = new Peers ($api);

$address_ids= $site['address_ids'];
$pcu_ids= $site['pcu_ids'];
$node_ids= $site['node_ids'];
$person_ids= $site['person_ids'];
$slice_ids= $site['slice_ids'];

$api->begin();
// gets address info
$api->GetAddresses( $address_ids );

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

$has_disabled_persons = count ($disabled_persons) !=0;

drupal_set_title("Details for site " . $sitename);
$local_peer = ! $peer_id;
  
// extra privileges to admins, and pi on this site
$privileges = plc_is_admin () || ( plc_in_site($site_id) && plc_is_pi());
  
$tabs=array();

$tabs []= tab_sites_local();

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
  $tabs["Events"]=array_merge (tablook_event(),
			       array('url'=>l_event("Site","site",$site_id),
				     'bubble'=>"Events for site $sitename"));
  $tabs["Comon"]=array_merge(tablook_comon(),
			     array('url'=>l_comon("site_id",$site_id),
				   'bubble'=>"Comon page for $sitename"));

  if (plc_is_admin()) 
    $tabs['Pending'] = array ('url'=>l_sites_pending(),
			      'bubble'=>'Review pending join requests');
 }

plekit_linetabs($tabs);

// show gray background on foreign objects : start a <div> with proper class
$peers->block_start ($peer_id);

if ( ! $enabled ) 
  plc_warning ("This site is not enabled - Please visit " . 
	       href (l_sites_pending(),"this page") . 
	       " to review pending applications.");

$can_update=(plc_is_admin ()  && $local_peer) || ( plc_in_site($site_id) && plc_is_pi());


$toggle = new PlekitToggle ('site',"Details",
			    array('trigger-bubble'=>'Display and modify details for that site'));
$toggle->start();

$details = new PlekitDetails($can_update);

if ( ! $site['is_public']) 
  plc_warning("This site is not public!");

$details->form_start(l_actions(),array('action'=>'update-site','site_id'=>$site_id));

$details->start();

$details->th_td("Full name",$sitename,'name',array('width'=>50));
$details->th_td("Abbreviated name",$abbreviated_name,'abbreviated_name',array('width'=>15));
$details->th_td("URL",$site_url,'url',array('width'=>40));
$details->th_td("Latitude",$site_lat,'latitude');
$details->th_td("Longitude",$site_long,'longitude');

// modifiable by admins only
if (plc_is_admin()) 
  $details->th_td("Login base",$login_base,'login_base',array('width'=>12));
else
  $details->th_td("Login base",$login_base);
if (plc_is_admin())
  $details->th_td("Max slices",$max_slices,'max_slices');
else
  $details->th_td("Max slices",$max_slices);
$details->tr_submit("submit","Update Site");

if ( ! $local_peer) {
  $details->space();
  $details->th_td("Peer",$peers->peer_link($peer_id));
 }
$details->end();
$details->form_end();
$toggle->end();

//////////////////// mode details - for local object
if ( $local_peer ) {

  //////////////////// nodes
  // xxx missing : would need to add columns on attached PCU name and port if avail
  $nb_boot = 0;
  if ($nodes) foreach ($nodes as $node) if ($node['boot_state'] == 'boot') $nb_boot ++;

  $nodes_title = "Nodes : ";
  $nodes_title .= count($nodes) . " total";
  $nodes_title .= " / " . $nb_boot . " boot";
  if ($nb_boot < 2 ) 
    $nodes_title = plc_warning_html ($nodes_title);
  $nodes_title .= href(l_nodes_site($site_id)," (See as nodes)");

  $toggle=new PlekitToggle ('nodes',$nodes_title);
  $toggle->start();

  $headers=array();
  $headers['hostname']='string';
  $headers['state']='string';

  $table = new PlekitTable ('nodes',$headers,'0',array('search_area'=>false,
						    'notes_area'=>false,
						    'pagesize_area'=>false));
  $table->start();
  foreach ($nodes as $node) {
    $table->row_start();
    $table->cell (l_node_obj($node));
    $table->cell ($node['boot_state']);
    $table->row_end();
  }
  $table->tfoot_start();
  $table->row_start();
  $button=new PlekitFormButton (l_node_add(),"node_add","Add node","POST");
  $table->cell($button->html(),$table->columns(),"right");
  $table->row_end();
  $table->end();
  $toggle->end();
    
  //////////////////// Users
  $persons_title = "Users : ";
  $persons_title .= count($person_ids) . " total";
  $persons_title .= " / " . count ($pis) . " PIs";
  $persons_title .= " / " . count ($techs) . " Techs";
  if ($has_disabled_persons) 
    $persons_title .= " / " . count($disabled_persons) . " Disabled";
  if ( (count ($pis) == 0) || (count ($techs) == 0) || (count($person_ids) >= 30) || count($disabled_persons) != 0 ) 
    $persons_title = plc_warning_html ($persons_title);
  $persons_title .= href(l_persons_site($site_id)," (See as users)");

  $toggle=new PlekitToggle ('persons',$persons_title);
  $toggle->start();

  $headers = array ();
  $headers["email"]='string';
  $headers["PI"]='string';
  $headers['User']='string';
  $headers["Tech"]='string';
  if ($has_disabled_persons) $headers["Disabled"]='string';
  $table=new PlekitTable('persons',$headers,'1r-3r-0',array('search_area'=>false,
							 'notes_area'=>false,
							 'pagesize_area'=>false));
  $table->start();
  foreach ($persons as $person) {
    $table->row_start();
    $table->cell(l_person_obj($person));
    $table->cell( in_array ('20',$person['role_ids']) ? "yes" : "no");
    $table->cell( in_array ('30',$person['role_ids']) ? "yes" : "no");
    $table->cell( in_array ('40',$person['role_ids']) ? "yes" : "no");
    if ($has_disabled_persons) $table->cell( $person['enabled'] ? "no" : "yes");
    $table->row_end();
  }
  $table->end();
  $toggle->end();

  //////////////////// Slices
  // xxx to review after slices gets reworked
  $toggle=new PlekitToggle ('slices',"Slices");
  $toggle->start();
  $details=new PlekitDetails (false);
  $details->start();
  // summary on slices
  $slice_label = count($slice_ids) . " running / " . $max_slices . " max";
  if (count($slice_ids) >= $max_slices) 
    $slice_label = plc_warning_html ($slice_label);
  $details->th_td("# Slices", href(l_slices_site($site_id),$slice_label));
  if ($slices) foreach ($slices as $slice)
     $details->th_td($slice['instantiation'],l_slice_obj($slice));
  $button=new PlekitFormButton (l_slice_add(),"slice_add","Add slice","POST");
  $details->tr($button->html(),"right");
  $details->end();
  $toggle->end();

  // Addresses
  $toggle=new PlekitToggle ('addresses',"Addresses",array('start-visible'=>false));
  $toggle->start();
  if ( ! $addresses) {
    print "<p class='addresses'>No known address for this site</p>";
  } else {
    $details=new PlekitDetails (false);
    $details->start();
    $details->th_td("Addresses","");
    foreach ($addresses as $address) {
      $details->th_td(plc_vertical_table($address['address_types']),
		       plc_vertical_table(array($address['line1'],
						$address['line2'],
						$address['line3'],
						$address['city'],
						$address['state'],
						$address['postalcode'],
						$address['country'])));
    }
    $details->end();
  }
  $toggle->end();

 }

////////////////////////////////////////
$peers->block_end($peer_id);

//plekit_linetabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>
