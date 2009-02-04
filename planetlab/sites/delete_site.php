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

// Print header
require_once 'plc_drupal.php';
include 'plc_header.php';


// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];


// if no id redirect
if( !$_GET['id'] ) 
  plc_redirect (l_sites());

// set the site_id
$site_id= $_GET['id'];
  
// delete it!
if( $_POST['delete'] ) {
  $api->DeleteSite( intval( $site_id ) );
  drupal_set_title ("Site " . $site_id . " deleted");
  $api_error=$api->error();
  if (!empty($error)) {
    print '<div class="messages error">' . $api_error . '</div>';
  } else {
    print '<div class="messages status">';
    print "Site " . $site_id . " deleted";
    print "</div>";
  }
  echo "<p><a href='index.php'>Back to Sites</a>\n";
 } else {


  // get site info from api
  $site_info= $api->GetSites( array( intval( $site_id ) ), array( "name" ) );
  $name= $site_info[0]['name'];
  drupal_set_title('Confirm site deletion for ' . $name);
  
  // start form
  echo "<form action='delete_site.php?id=$site_id' method=post>\n";
  
  echo "<h2>Delete Site</h2>\n";
  echo "Are you sure you want to delete this site?\n";
  echo "<table><tbody>\n";
  echo "<tr><th>Site Name: </th><td> $name </td></tr>\n";
  echo "<tr><td colspan=2> &nbsp; </td></tr>";
  echo "<tr><td colspan=2 align=center><input type=submit name='delete' value='Delete Site'></td></tr>\n";
  echo "<tr><td colspan=2> &nbsp; </td></tr>";
  echo "</tbody></table>\n";
  
  echo "<p><a href='index.php?id=$site_id'>Back to Site</a>\n";
  
  echo "</form>\n";
 }
  
  

// Print footer
include 'plc_footer.php';

?>
