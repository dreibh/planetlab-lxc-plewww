<?php
// $Id$
//

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;


// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];


// if no site id redirect
if( !$_GET['id'] ) {
  header( "location: index.php" );
  exit();
 }

// get site id
$site_id= $_GET['id'];

// get site info
$site_info= $api->GetSites( array( intval( $site_id ), array( "name", "url", "longitude", "latitude", "login_base", "max_slices", "abbreviated_name", "slice_ids" ) );

// if submitted expire
if( $_POST['action'] ) {
  $expiration= strtotime( $_POST['expires'] );
  
  // loop through all slices for site
  foreach( $site_info[0]['slice_ids'] as $slice_id ) {
    $fields= array( "expires" => $expiration );
    // update all slice's expiration
    $api->UpdateSlice( $slice_id, $fields );
  
  }
  
  // update site to not allow slice creation or renewal
  $site_fields= array( "max_slices" => 0 );
  $api->UpdateSite( $site_id, $site_fields );

}


// Print header
require_once 'plc_drupal.php';
drupal_set_title('Sites');
include 'plc_header.php';

// start form
echo "<form method=post action='expire.php?id=$site_id'>\n";
echo "<h2>Expire ". $site_info[0]['name'] ."</h2>\n";
echo "<b>Expiration:</b>&nbsp;<input name='expires' value='2 days' />\n";
echo "<p>Are you sure you want to set the expiration date for all slices that expire after the above date to the above date as well as disable slice creation and renewal at this site?</p>\n";
echo "<input type=submit name='action' value='Yes' />\n";

echo "<p><a href='index.php?id=$site_id'>Back to Site</a>\n";


echo "</form>\n";


// Print footer
include 'plc_footer.php';

?>
