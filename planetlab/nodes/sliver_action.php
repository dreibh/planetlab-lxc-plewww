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
  $tag_type= $_POST['sliver'];
  $value= $_POST['value'];
  $node_id= $_POST['node_id'];
  $slice_id= $_POST['slice_id'];

  $api->AddSliceTag( intval( $slice_id ), intval( $tag_type ), $value, intval( $node_id ) );

  // xxx l_sliver ?
  plc_redirect (l_sliver ($node_id,$slice_id));
  //header( "location: slivers.php?slice=$slice_id&node=$node_id" );
}


// 
if( $_GET['rem_id'] ) {
  $tag_id= $_GET['rem_id'];
  
  // get the slivers for this node
  $slivers= $api->GetSliceTags( array( "slice_tag_id"=>intval( $tag_id ) ), array( "slice_id", "node_id" ) );
  $sliver=$slivers[0];
  
  $api->DeleteSliceTag( intval( $tag_id ) );

  $node_id=$sliver['node_id'];
  $slice_id=$sliver['slice_id'];
  plc_redirect (l_sliver ($node_id,$slice_id));
}

  
/*
// Print footer
include 'plc_footer.php';
*/

?>
