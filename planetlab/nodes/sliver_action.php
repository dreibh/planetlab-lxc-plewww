<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

/*
// Print header
require_once 'plc_drupal.php';
drupal_set_title('Slices');
include 'plc_header.php';
*/

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];


if( !empty( $_POST['add_sub'] ) ) {
  $attrib_type= $_POST['sliver'];
  $value= $_POST['value'];
  $node_id= $_POST['node_id'];
  $slice_id= $_POST['slice_id'];

  $api->AddSliceAttribute( intval( $slice_id ), intval( $attrib_type ), $value, intval( $node_id ) );

  header( "location: slivers.php?slice=$slice_id&node=$node_id" );
  exit();
  
}


// 
if( $_GET['rem_id'] ) {
  $attrib_id= $_GET['rem_id'];
  
  // get the slivers for this node
  $sliver_info= $api->GetSliceAttributes( array( "slice_attribute_id"=>intval( $attrib_id ) ), array( "slice_id", "node_id" ) );
  
  $api->DeleteSliceAttribute( intval( $attrib_id ) );

  header( "location: slivers.php?slice=". $sliver_info[0]['slice_id'] ."&node=". $sliver_info[0]['node_id'] );
  exit();
  
}

  
/*
// Print footer
include 'plc_footer.php';
*/

?>
