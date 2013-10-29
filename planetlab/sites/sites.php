<?php
  // $Id$
  //

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

// -------------------- 
// recognized URL arguments
$peerscope=$_GET['peerscope'];
$pattern=$_GET['pattern'];

// --- decoration
$title="Sites";
$tabs=array();
$tabs []= tab_sites();
$tabs []= tab_sites_local();
$tabs []= tab_mysite();

// -------------------- 
$site_filter=array();

function site_status ($site) {

  $class=($site['peer_id']) ? 'plc-foreign' : 'plc-warning';

  $messages=array();
  
  if (empty ($site['node_ids'])) 
    $messages [] = "No node";

  // do all this stuff on local sites only
  if ( ! $site['peer_id'] ) {
    
    // check that site is enabled
    if ( ! $site['enabled']) 
      $messages [] = "Not enabled";
    global $PENDING_CONSORTIUM_ID;
    if ( $site['ext_consortium_id'] === $PENDING_CONSORTIUM_ID )
      $messages [] = "Pending registration";
  
    // check that site has at least a PI and a tech
    global $api;
    $persons=$api->GetPersons(array("person_id"=>$site['person_ids']),array("role_ids"));
    $nb_pis=0;
    $nb_tech=0;
    if ( $persons) foreach ($persons as $person) {
	if (in_array( '20', $person['role_ids'])) $nb_pis += 1;
	if (in_array( '40', $person['role_ids'])) $nb_techs += 1;
      }
    if ($nb_pis == 0) $messages [] = "No PI";
    if ($nb_techs == 0) $messages [] = "No Tech";
    
    if (! $site['is_public']) $messages []= "Not public";

    // check number of slices
    if ( $site['max_slices'] == 0) {
      $messages [] = "No slice allowed";
    } else if (count($site["slice_ids"]) >= $site['max_slices']) {
      $messages [] = "No slice left";
    }
  }

  return plc_vertical_table ($messages,$class);
}

////////////////////
// The set of columns to fetch
// and the filter applied for fetching sites
if ($pattern) {
  $site_filter['login_base']=$pattern;
  $title .= " with login_base matching " . $pattern;
 } else {
  $site_filter['login_base']="*";
 }

// server-side selection on peerscope
$peerscope = new PeerScope ($api,$peerscope);
$site_filter=array_merge($site_filter,$peerscope->filter());
$title .= ' - ' . $peerscope->label();

if (! plc_is_admin()) {
  $site_columns = array("site_id", "name", "abbreviated_name", "login_base" , "peer_id" );
  $site_filter = array_merge ($site_filter, array ("enabled" => TRUE));
 } else {
  $site_columns = array("site_id", "name", "abbreviated_name", "login_base" , "peer_id" , 
			"is_public", "enabled", "ext_consortium_id",
			"person_ids", "max_slices", "slice_ids", "node_ids");
 }

if (plc_is_admin()) 
  $tabs['Joining Sites'] = array ('url'=>l_sites_pending(),
				  'bubble'=>'Review pending join requests');

drupal_set_title($title);
plekit_linetabs($tabs);

// go
$sites= $api->GetSites( $site_filter , $site_columns);

$peers=new Peers($api);

$nifty=new PlekitNifty ('','objects-list','big');
$nifty->start();

$headers=array();
if (plc_is_admin()) {
  $headers['I']='int';
}
$headers['Peer']="string";
$headers['Full Name']="string";
$headers['Login']="string";
$headers['Abbrev.']="string";
if (plc_is_admin()) {
  $headers['N']="int";
  $headers['U']="int";
  $headers['S']="int";
  $headers['?']="string";
 }

if (plc_is_admin()) {
  $table=new PlekitTable("sites",$headers,3);
} else {
  $table=new PlekitTable("sites",$headers,2);
}
$table->start();

if ($sites) foreach ($sites as $site) {
  $peer_id=$site['peer_id'];
  $site_id=$site['site_id'];
  $login_base=$site['login_base'];
  $table->row_start();
  if (plc_is_admin()) {
    $table->cell(l_site_t($site_id,$site_id));
  }
  $peers->cell($table,$peer_id);
  $table->cell (l_site_t($site_id,htmlentities(utf8_decode($site['name']))));
  $table->cell (l_site_t($site_id,$login_base));
  $table->cell (htmlentities($site['abbreviated_name']));
  if (plc_is_admin()) {
    $table->cell(href(l_nodes_site($site_id),count($site['node_ids'])));
    $table->cell(href(l_persons_site($site_id),count($site['person_ids'])));
    $table->cell(href(l_slices_site($site_id),count($site['slice_ids'])));
    $table->cell(site_status($site));
  }
  $table->row_end();
}
$notes=array();
if (plc_is_admin()) {
  $notes []= "N = number of nodes";
  $notes []= "U = number of users";
  $notes []= "S = number of slices";
  $notes []= "I = site_id";
  $notes []= "? = status";
 }

$table->end(array('notes'=>$notes));
$nifty->end();

//plekit_linetabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>
