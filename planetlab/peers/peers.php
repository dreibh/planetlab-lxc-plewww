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
require_once 'plc_tables.php';
require_once 'plc_minitabs.php';

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
  
  $columns=array( 'Name'=>'string',
		  'SN' =>'string',
		  'HRN' => 'string',
		  'URL'=>'string',
		  'Comon'=>'string');
		  
  $table_options=array('search_area'=>false, 'notes_area'=>false);
  plc_table_start ("peers",$columns,1,$table_options);
  foreach ($peers as $peer) {
    plc_table_row_start();
    plc_table_cell (href(l_peer($peer['peer_id']),$peer['peername']));
    plc_table_cell ($peer['shortname']);
// xxx no HRN yet
    plc_table_cell ('?');
    plc_table_cell ($peer['peer_url']);
    plc_table_cell (href(l_comon("peer_id",$peer['peer_id']),'Comon'));
    plc_table_row_end();
  }
  plc_table_end("peers");
 }
		    
// Print footer
include 'plc_footer.php';

?>
