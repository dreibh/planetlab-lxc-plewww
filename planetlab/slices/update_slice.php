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


if( !empty( $_GET['id'] ) ) {
  $slice_id= $_GET['id'];
  // Fetch slice information
  $slices= $api->GetSlices( array( intval( $slice_id ) ) );
  if( !empty( $slices ) ) {
    $slice= $slices[0];
  }
}

// Invalid slice name
if( !isset( $slice ) ) {
  Header( "Location: index.php" );
  exit();
}

// Defaults
$url_error = "";
$description_error = "";

if( isset( $_POST['submitted'] ) ) {
  if( !empty($_POST['url'] ) )
    $slice['url']= $_POST['url'];
  else
    $url_error= "Provide a link to a project website.";

  if( !empty($_POST['desc'] ) )
    $slice['description'] = $_POST['desc'];
  else
    $description_error= "Provide a short description of the slice.";

  if( empty( $url_error ) && empty( $description_error ) ) {
    // Update the slice URL and description
    $fields= array( "description"=>$slice['description'], "url"=>$slice['url'] );
    $api->UpdateSlice( intval( $slice_id ), $fields );
    Header( "Location: index.php?id=$slice_id" );
    exit();
  }
}

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Slices');
include 'plc_header.php';

$slice_name= $slice['name'];

print "<h2>Update Slice ". $slice['name'] ."</h2>";

//echo "<pre>"; print_r( $slice ); echo "</pre>";
$url =  $slice['url'] ;
$description =  $slice['description'] ;

echo <<<EOF

<p>You must provide a short description as well as a
link to a project website. Do <b>not</b> provide
bogus information; if a complaint is lodged against your slice and
PlanetLab Operations is unable to determine what the normal behavior
of your slice is, your slice may be deleted to resolve the
complaint.</p>

<form method="post" action="update_slice.php?id=$slice_id">

<table>
  <tbody>
    <tr>
      <th>Name:</th>
      <td>$slice_name</td>
      <td></td>
    </tr>
    <tr>
      <th>URL:</th>
      <td><input size="50" name="url" value="$url" /></td>
      <td><font color="red"><strong>$url_error</strong></font></td>
    </tr>
    <tr>
      <th>Description:</th>
      <td><textarea name="desc" rows="5" cols="40">$description</textarea></td>
      <td><font color="red"><strong>$description_error</strong></font></td>
    </tr>
  </tbody>
</table>

<input type="submit" name="submitted" value="Update" />

</form>

EOF;

// Print footer
include 'plc_footer.php';

?>