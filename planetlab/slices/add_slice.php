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

// if not a PI or admin then redirect to slice index
if( !in_array( '10', $_roles ) && !in_array( '20', $_roles ) )
  header( "index.php" );

if( !$_POST['name'] ) {
  // get default site base
  $site_info= $api->GetSites( array( $_person['site_ids'][0] ), array( "login_base" ) );
  $base= $site_info[0]['login_base'] ."_";
}

// add it
if( $_POST['add'] ) {
  // get post vars
  $url= $_POST['url'];
  $instantiation= $_POST['instantiation'];
  $name= $_POST['name'];
  $description= $_POST['description'];

  // validate input
  if( $name == $base )
    $error['name']= "<font color=red>You must enter a name for your slice.</font>";
  else {
    $slice_name= $name;
    // make sure slice name doesnt exist
    $slice_info= $api->GetSlices( array( $slice_name ), array( "slice_id" ) );
    
    if( !empty( $slice_info ) ) {
      $error['name']= "<font color=red>Slice name already in use.  Please choose another.</font>";
      $name= "";
    }
    
  }
  
  if( $url == "http://" || "" )
    $error['url']= "<font color=red>You must enter a URL for your slice's info.</font>";
      
  if( $description == "" )
    $error['description']= "<font color=red>Your must enter a description for you slice.</font>";
  
  // if no errors then add
  if( !$error ) {
    $fields= array( "url" => $url, "instantiation" => $instantiation, "name" => $slice_name, "description" => $description );
    echo "added: <pre>"; print_r( $fields ); echo "</pre>\n";
    // add it!
    $slice_new_id= $api->AddSlice( $fields );

    if( $slice_new_id ) {
      header( "location: index.php?id=$slice_new_id" );
      exit();
    } else {
      $error['api']= $api->error();
    }
  }

}

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Slices');
include 'plc_header.php';


if( !$url )  
  $url= "http://";

// check for errors and set error styles
if( $error['name'] )
  $name_error= " class='plc-warning'";
  
if( $error['url'] )
  $url_error= " class='plc-warning'";
  
if( $error['description'] )
  $desc_error= " class='plc-warning'";


// add javaScript code
echo "<script type='text/javascript'>
      function update(str1) {
        var temp= new Array()
        temp= str1.split('->');
        var c= ( temp[1] + '_' )
        document.getElementById('textbox').value = c;
      }
</script>\n";


// start form
echo "<form action='add_slice.php' method=post>\n";

if( $error['api'] )
  echo "<font class='plc-warning'>". $error['api'] ."</font>\n";

echo "<h2>Create Slice</h2>\n";

echo "<p>You must provide a short description of the new slice as well as a link to a project website before creating it. Do <strong>not</strong> provide bogus information; if a complaint is lodged against your slice and PlanetLab Operations is unable to determine what the normal behavior of your slice is, your slice may be deleted to resolve the complaint.\n";
echo "<p>There are three possible \"instantiation\" states for a slice. <strong>PLC</strong> creates a slice with default settings. <strong>Delegated</strong> creates a ticket to use on each node. <strong>None</strong> allows you to reserve a slice name; you may instantiate the slice later.\n";
echo "<p><strong>NOTE</strong>: All PlanetLab users are <strong>strongly</strong> encouraged to join the PlanetLab <a href='https://lists.planet-lab.org/mailman/listinfo/users'>Users</a> mailing list. Most questions about running software on PlanetLab can be answered by posting to this list. Site administrators often use this list to post announcements about service outages. New software releases and available services are announced here as well.\n";

echo "<p><table><tbody>\n";

// displays a site select list for admins and multi-site users
if( count( $_person['site_ids'] ) > 1 || in_array( 10, $_roles ) ) {
  // get sites depending on role and sites associated.
  if( in_array( 10, $_roles ) )
    $site_info= $api->GetSites( NULL, array( "name", "site_id", "login_base" ) );
  elseif( count( $_person['site_ids'] ) > 1 )
    $site_info= $api->GetSites( $_person['site_ids'], array( "name", "site_id", "login_base" ) );

  echo "<tr><th>Site: </th><td><select onchange='update(this[selectedIndex].text)' name='site_id'>\n";

  sort_sites( $site_info );
  foreach( $site_info as $site ) {
    echo "<option value=". $site['site_id'];
    if( $site['site_id'] == $_person['site_ids'][0] )
      echo " selected";
    echo ">". $site['name'] ."->". $site['login_base'] ."</option>\n";
  }

  echo "</select></td><td></td></tr>\n";

}

echo "<tr><th$name_error>Name: </th><td><input type=text id='textbox' name='name' size=40 value='";
if( $name ) 
  echo $name;
else
  echo $base;
echo "'></td><td>". $error['name'] ."</td></tr>\n";
echo "<tr><th$url_error>URL: </th><td> <input type=text name='url' size=50 value='$url'></td><td>". $error['url'] ."</td></tr>\n";
echo "<tr><th$desc_error>Description: </th><td> <textarea name='description' rows=5 cols=45>$description</textarea></td><td>". $error['description'] ."</td></tr>\n";
echo "<tr><th>Instantiation: </th><td> <select name='instantiation'>\n";
echo "<option value='plc-instantiated'"; if( $instantiation == 'plc-instantiated' ) echo " selected"; echo ">PLC</option>\n";
echo "<option value='delegated'"; if( $instantiation == 'delegated' ) echo " selected"; echo ">Delegated</option>\n";
echo "<option value='not-instantiated'"; if( $instantiation == 'not-instantiated' ) echo " selected"; echo ">None</option>\n";
echo "</select></td><td></td></tr>\n";

echo "</tbody></table>\n";

echo "<p><input type=submit name='add' value='Add Slice'>\n";

echo "</form>\n";




// Print footer
include 'plc_footer.php';

?>
