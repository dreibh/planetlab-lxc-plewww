<?php

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

// get site_id
$site_id= $_GET['id'];

// if submitted
if( $_POST['submitted'] ) {
  $new_site= $_POST['new_site'];
  
  // no primary site anymore..........

}


// Print header
require_once 'plc_drupal.php';
drupal_set_title('Sites');
include 'plc_header.php';


// if admin list all sites, else list just persons sites
if( in_array( '10', $_roles ) ) 
  $site_info= $api->GetSites( NULL, array( "site_id", "name" ) );
else 
  $site_info= $api->GetSites( $_person['site_ids'], array( "site_id", "name" ) );


sort_sites( $site_info );

// start form
echo "<from method=post action='switch_site.php?id=$site_id'>\n";
echo "<h2>Switch Site</h2>\n";
echo "Change active site to: \n";
echo "<p><select name='new_site'>\n";

// out puts site names and ids
foreach( $site_info as $site ) {
  echo "<option value='". $site['site_id'] ."'>". $site['name'] ."</option>\n";

}

echo "</select>\n";
echo "<p>This will change your Primary Site.\n";
echo "<p><input type=submit name='submitted' value='Switch Site'>\n";

echo "<p><a href='index.php?id=$site_id'>Back to Site</a>\n";

echo "</form>\n";



// Print footer
include 'plc_footer.php';

?>
