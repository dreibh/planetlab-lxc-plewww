<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Slivers');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

//print_r( $_person );


// if add is set, diplay add sliver form
if( $_GET['add'] ) {
  $node_id= $_GET['add'];
  $slice_id= $_GET['slice'];

  // slice info
  $slice_info= $api->GetSlices( array( intval( $slice_id ) ), array( "name" ) );

  // node info
  $node_info= $api->GetNodes( array( intval( $node_id ) ), array( "hostname" ) );

  // get attribute types
  $type_info= $api->GetSliceAttributeTypes( NULL, array( "attribute_type_id", "name" ) );
  
  // get the slivers for this node
  $sliver_info= $api->GetSliceAttributes( array( "node_id"=>intval( $node_id ), "slice_id"=>intval( $slice_id ) ), array( "attribute_type_id", "name" ) );

    $types_left= $type_info;


  // start form
  echo "<form action='sliver_action.php' method=post>\n
  <h2>Add a Sliver Attribute to ". $slice_info[0]['name'] ." on node ". $node_info[0]['hostname'] ."</h2>\n
  <table class='list_set' border=0 cellpadding=2><tbody>\n
  <tr class='list_set'><th class='list_set'>Attribute: </th><td class='list_set'><select name='sliver'>\n";

  foreach( $types_left as $type ) {
    echo "<option value='". $type['attribute_type_id'] ."'>". $type['name'] ."</option>\n";

  }

  echo "</td><td class='list_set'></td></tr>\n
  <tr class='list_set'><th class='list_set'>Value: </th><td class='list_set'><input type=text name='value'></td></tr>\n
  </tbody></table>\n
  <input type=hidden name='node_id' value='$node_id'><input type=hidden name='slice_id' value='$slice_id'>
  <p><input type=submit name='add_sub' value='Add Sliver Attribute'>\n";

  echo "<p><a href='index.php?id=$node_id'>Back to Node</a>\n</form>\n";
  
}


// if slice and node ids are passed display slivers and attribs
if( $_GET['slice'] && $_GET['node'] ) {
  $slice_id= $_GET['slice'];
  $node_id= $_GET['node'];

  // slice info
  $slice_info= $api->GetSlices( array( intval( $slice_id ) ), array( "name" ) );

  // node info
  $node_info= $api->GetNodes( array( intval( $node_id ) ), array( "hostname" ) );

  // get the slivers for this node
  $sliver_info= $api->GetSliceAttributes( array( "node_id"=>intval( $node_id ), "slice_id"=>intval( $slice_id ) ), array( "slice_attribute_id", "name", "value", "min_role_id", "description" ) );

  // get the attrbibutes for this slice
  $attrib_info= $api->GetSliceAttributes( array( intval( $slice_id ) ), array( "slice_attribute_id", "name", "value", "min_role_id", "description" ) );


  // start form
  echo "<form action='slivers.php' method=post>\n<h2>Sliver Details for slice ". $slice_info[0]['name'] ." on node ". $node_info[0]['hostname'] ."</h2>\n";

  // sliver attributes of slice
  if( empty( $sliver_info ) )
    // if no sliver exists tell user
    echo "No sliver attribute for this node/slice sliver combination.\n";
  else {
    echo "<p><table class='list_set' border=0 cellpadding=2>\n<caption class='list_set'>Slivers</caption>\n<thead><tr class='list_set'><th class='list_set'>Name</th><th class='list_set'>Value</th><th class='list_set'>Min Roll</th><th class='list_set'>Description</th>";
    if ( in_array( 10, $_roles ) || ( in_array( 20, $_roles ) && in_array( $node_info, $_person['site_ids'] ) ) ) echo "<th></th><th></th>";
    echo "</tr></thead><tbody>\n";

    foreach( $sliver_info AS $sliver ) {
      echo "<tr><td>". $sliver['name'] ."</td><td>". $sliver['value'] ."</td><td>". $sliver['min_role_id'] ."</td><td>". $sliver['description'] ."</td>";
      if ( in_array( 10, $_roles ) || ( in_array( 20, $_roles ) && in_array( $node_info, $_person['site_ids'] ) ) ) echo "<td><a href='/db/slices/attributes.php?id=". $sliver['slice_attribute_id'] ."'>Edit</a></td><td><a href='sliver_action.php?rem_id=". $sliver['slice_attribute_id'] ."' onclick=\"javascript:return confirm('Are you sure you want to remove ". $sliver['name'] ." from node ". $node_info[0]['hostname'] ."?')\">Remove</a></td>";
      echo "</tr>\n";


    }

    echo "</tbody></table>\n";

  }
  
  if ( in_array( 10, $_roles ) || ( in_array( 20, $_roles ) && in_array( $node_info, $_person['site_ids'] ) ) ) echo "<p><a href='slivers.php?add=$node_id&slice=$slice_id'>Add Sliver Attribute</a>\n";

  echo "<br /><hr />";

  // regular attributes of slice
  if( empty( $attrib_info ) )
    // if no attributes exist tell user
    echo "No Attributes for this slice.\n";
  else {
    echo "<p><table class='list_set' border=0 cellpadding=2>\n<caption class='list_set'>Attributes</caption>\n<thead><tr class='list_set'><th class='list_set'>Name</th><th class='list_set'>Value</th><th class='list_set'>Min Roll</th><th class='list_set'>Description</th>";
    if ( in_array( 10, $_roles ) || ( in_array( 20, $_roles ) && in_array( $node_info, $_person['site_ids'] ) ) ) echo "<th></th>";
    echo "</tr></thead><tbody>\n";

    foreach( $attrib_info AS $attrib ) {
      echo "<tr><td>". $attrib['name'] ."</td><td>". $attrib['value'] ."</td><td>". $attrib['min_role_id'] ."</td><td>". $attrib['description'] ."</td>";
      if ( in_array( 10, $_roles ) || ( in_array( 20, $_roles ) && in_array( $node_info, $_person['site_ids'] ) ) ) echo "<td><a href='attributes.php?id=". $attrib['slice_attribute_id'] ."'>Edit</a></td>";
      echo "</tr>\n";


    }

    echo "</tbody></table>\n";

  }


  echo "<p><a href='index.php?id=$node_id'>Back to Node</a>\n</form>\n";

}


// Print footer
include 'plc_footer.php';

?>
