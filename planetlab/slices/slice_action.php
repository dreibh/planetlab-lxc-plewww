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



// echo "<pre>"; print_r( $_POST ); echo "</pre>";
// SLICES ------------------------------------------------------

// if action exists figure out what to do
if( $_POST['actions'] ) {
  // get slice_id as int
  $slice_id= intval( $_POST['slice_id'] );

  // depending on action, run function
  switch( $_POST['actions'] ) {
    case "renew":
      header( "location: renew_slice.php?id=$slice_id" );
      exit();
      break;
    case "delete":
      header( "location: delete_slice.php?id=$slice_id" );
      exit();
      break;
    case "nodes":
      header( "location: slice_nodes.php?id=$slice_id" );
      exit();
      break;
    case "users":
      header( "location: slice_users.php?id=$slice_id" );
      exit();
      break;
     
  }

}

  
/*
// Print footer
include 'plc_footer.php';
*/

?>
