<?php

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
require_once 'table.php';
require_once 'linetabs.php';

drupal_set_title('All Peers');


// GetPeers API call
// xxx no HRN yet
$peers = $api->GetPeers( NULL, array("peer_id","peername","shortname","peer_url"));
    
$tabs=array();
$tabs['Comon for all nodes']=l_comon("peer_id","0");
plc_tabs($tabs);

if ( empty($peers)) {
  drupal_set_message ("You seem to be running a standalone deployment");
  } else {
  
  $headers=array( 'Name'=>'string',
		  'SN' =>'string',
		  'HRN' => 'string',
		  'URL'=>'string',
		  'Comon'=>'string');
		  
  $table_options=array('search_area'=>false, 'notes_area'=>false);
  $table = new PlcTable ("peers",$headers,1,$table_options);
  $table->start();
  foreach ($peers as $peer) {
    $table->row_start();
    $table->cell (href(l_peer($peer['peer_id']),$peer['peername']));
    $table->cell ($peer['shortname']);
// xxx no HRN yet
    $table->cell ('?');
    $table->cell ($peer['peer_url']);
    $table->cell (href(l_comon("peer_id",$peer['peer_id']),'Comon'));
    $table->row_end();
  }
  $table->end();
 }
		    
//plc_tabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>
