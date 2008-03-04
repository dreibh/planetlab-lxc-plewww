<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

/*
// Print header
require_once 'plc_drupal.php';
drupal_set_title('Sites');
include 'plc_header.php';
*/

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

// if action exists figure out what to do
if( $_POST['actions'] ) {
  // get slice_id as int
  $site_id= intval( $_POST['site_id'] );

  // depending on action, run function
  switch( $_POST['actions'] ) {
    case "update":
      header( "location: update_site.php?id=$site_id" );
      exit();
      break;
    case "delete":
      header( "location: delete_site.php?id=$site_id" );
      exit();
      break;
    case "expire":
      header( "location: expire.php?id=$site_id" );
      exit();
      break;
     
  }

}


/*
// Print footer
include 'plc_footer.php';
*/

?>
