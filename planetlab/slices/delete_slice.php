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

//print_r( $_person );

// if no id then go back to slice index
if( !$_GET['id'] )
  header( "index.php" );

// get slice id from get
$slice_id= $_GET['id'];

// delete it!
if( $_POST['delete'] ) {
  $api->DeleteSlice( intval( $slice_id ) );
  
  header( "location: index.php" );
  exit();
}

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Slices');
include 'plc_header.php';


// get slice info from API call
$slice_info= $api->GetSlices( array( intval( $slice_id ) ), array( "name", "expires", "description" ) );

// start form
echo "<form action='delete_slice.php?id=$slice_id' method=post>\n";

// show delete confirmation
echo "<h2>Delete slice ". $slice_info[0]['name'] ."</h2>\n";
echo "<p>Are you sure you want to delete this slice?\n";

echo "<table><tbody>\n";
echo "<tr><th>Name: </th><td> ". $slice_info[0]['name'] ."</td></tr>\n";
echo "<tr><th>Description: </th><td> ". $slice_info[0]['description'] ."</td></tr>\n";
echo "<tr><th>Expiration: </th><td> ". gmstrftime( "%A %b-%d-%y %T %Z", $slice_info[0]['expires'] ) ."</td></tr>\n";
echo "</tbody></table>\n";
echo "<input type=submit value='Delete Slice' name='delete'>\n";
echo "</form\n";

// Print footer
include 'plc_footer.php';

?>
