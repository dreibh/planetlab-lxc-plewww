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

// redirect if no site id is set add instead
if( !$_GET['id'] )
  $do= 'Add';
else {
  $site_id= $_GET['id'];
  $do= 'Update';
}

// if form not submitted get data from API
if( $_POST['submitted'] ) {

  $name= $_POST['name'];
  $abbrev_name= $_POST['abbrev_name'];
  $url= $_POST['url'];
  $login_base= $_POST['login_base'];
  $latitude= $_POST['latitude'];
  $longitude= $_POST['longitude'];
  //$max_slivers= $_POST['max_slivers'];
  $max_slices= $_POST['max_slices'];

  
  if( $name == "" )
    $error['name']= "<font color=red>Name can not be blank.</font>";
  
  if( $abbrev_name == "" )
    $error['abbrev_name']= "<font color=red>Abbreviated Name can not be blank.</font>";
    
  if( $login_base == "" ) 
    $error['login_base']= "<font color=red>Login Base can not be blank.</font>";
  
  if( $url == "" || $url == "http://" )
    $error['url']= "<font color=red>URL can not be blank.</font>";
    
  if( !is_numeric( $latitude ) )
    $error['latitude']= "<font color=red>Latitude must be a number.</font>";
  
  if( !is_numeric( $longitude ) )
    $error['longitude']= "<font color=red>Longitude must be a number.</font>";
    
  if( !is_numeric( $max_slices ) )
    $error['max_slices']= "<font color=red>Max Slices must be a number.</font>";
  
  
  // if no errors add/update site
  if( $do == 'Add' ) {
    $fields= array( "name" => $name, "url" => $url, "longitude" => floatval( $longitude ), "login_base" => $login_base, "latitude" => floatval( $latitude ), "is_public" => true, "abbreviated_name" => $abbrev_name, "max_slices" => 0 );
    $api->AddSite( $fields );
    //echo "<pre>"; print_r( $fields ); echo "</pre>";
  }
  
  if( $do == 'Update' ) {
    $fields= array( "name" => $name, "url" => $url, "longitude" => floatval( $longitude ), "login_base" => $login_base, "latitude" => floatval( $latitude ), "is_public" => true, "abbreviated_name" => $abbrev_name, "max_slices" => intval( $max_slices ) );
    $api->UpdateSite( intval( $site_id ), $fields );
    // Thierry aug 31 07 - redirect to the site's details page
    plc_redirect(l_site($site_id));
  }
  
}

// if its an update get site info$max_slices &&
if( $do == 'Update' && empty( $error ) ) {
  // Get site api call
  $site_info= $api->GetSites( array( intval( $site_id ) ), array( "name", "url", "longitude", "latitude", "login_base", "max_slices", "abbreviated_name" ) );
  
  // var names to api return
  $name= $site_info[0]['name'];
  $abbrev_name= $site_info[0]['abbreviated_name'];
  $url= $site_info[0]['url'];
  $login_base= $site_info[0]['login_base'];
  $latitude= $site_info[0]['latitude'];
  $longitude= $site_info[0]['longitude'];
  //$max_slivers= $site_info[0]['max_slivers'];
  $max_slices= $site_info[0]['max_slices'];

}

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Sites');
include 'plc_header.php';

// set error styles
if( $error['name'] )
  $name_err= " style='border: 1px solid red;'";

if( $error['abbrev_name'] )
  $abbrev_err= " style='border: 1px solid red;'";

if( $error['login_base'] )
  $base_err= " style='border: 1px solid red;'";

if( $error['url'] )
  $url_err= " style='border: 1px solid red;'";

if( $error['latitude'] )
  $lat_err= " style='border: 1px solid red;'";

if( $error['longitude'] )
  $long_err= " style='border: 1px solid red;'";
  
if( $error['max_slices'] )
  $max_err= " style='border: 1px solid red;'";

// start form
echo "<form action='update_site.php?id=$site_id' method='post'>\n";
echo "<h2>$do $name</h2>\n";

echo "<table><tbody>\n";
// Thierry - displays name under double quotes - some site names have single quotes, none have double quotes as of now
echo "<tr><th>Name: </th><td> <input type=text $name_err name='name' value=\"$name\" size=40></td><td>". $error['name'] ."</td></tr>\n";
echo "<tr><th>Abbreviated Name: </th><td> <input type=text $abbrev_err name='abbrev_name' value='$abbrev_name' size=40></td><td>". $error['abbrev_name'] ."</td></tr>\n";
echo "<tr><th>Login Base: </th><td> <input type=text $base_err name='login_base' value='$login_base' size=40></td><td>". $error['login_base'] ."</td></tr>\n";
echo "<tr><th>URL: </th><td> <input type=text $url_err name='url' value='$url' size=40></td><td>". $error['url'] ."</td></tr>\n";
echo "<tr><th>Latitude/Longitude: </th><td> <input type=text $lat_err name='latitude' value='$latitude' size=10> / <input type=text $long_err name='longitude' value='$longitude' size=10></td><td>". $error['latitude'] ." ". $error['longitude'] ."</td></tr>\n";

if( in_array( 10, $_roles ) ) {
	echo "<tr><th>Max Slices: </th><td> <input type=text $max_err name='max_slices' value='$max_slices'"; if( !in_array( '10', $_roles ) ) echo " disabled"; echo " size=10></td><td>". $error['max_slices'] ."</td></tr>\n";
}
else
	echo "<tr><td colspan=3><input type=hidden name='max_slices' value='$max_slices'></td></tr>\n";

echo "<tr><td></td><td colspan=2><input type=submit name='submitted' value='$do Site'></td></tr>\n";
echo "</tbody></table><br />\n";

echo "<p><a href='index.php?id=$site_id'>Back to Site</a>\n";

echo "</form>\n";


// Print footer
include 'plc_footer.php';

?>
