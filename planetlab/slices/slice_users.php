<?php

// Thierry on 2007-02-20
// There's no reason why we should see this page with a foreign slice, at least
// so long as the UI is used in a natural way, given the UI's logic as of now
// however it's always possible that someone forges her own url like
// http://one-lab.org/db/slices/slice_users?id=176
// So just to be consistent, we protect ourselves against such a usage

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Slices');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';
  
// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

//print_r( $_person );

// if no id ... redirect to slice index
if( !$_GET['id'] && !$_POST['id'] ) {
  header( "location: index.php" );
  exit();
 }

  
// get slice id from GET or POST
if( $_GET['id'] )
  $slice_id= intval( $_GET['id'] );
elseif ( $_POST['id'] )
  $slice_id= intval( $_POST['id'] );
else
  echo "no slice_id<br />\n";

// if add node submitted add the nodes to slice
if( $_POST['add'] ) {
  $add_user= $_POST['add_user'];
  
  foreach( $add_user as $user) {
    $api->AddPersonToSlice( intval( $user ), $slice_id );
  }

  $added= "<font color=blue>People Added.</font><br />";
}

// if rem node submitted remove the nodes from slice
if( $_POST['remove'] ) {
  $rem_user= $_POST['rem_user'];

  foreach( $rem_user as $user) {
    $api->DeletePersonFromSlice( intval( $user ), $slice_id );
  }
  
  $removed= "<font color=blue>People Removed.</font><br />";
}

// get slice info
$slice_info= $api->GetSlices( array( $slice_id ), array( "name", "person_ids" , "peer_id") );
$slice_readonly = $slice_info[0]['peer_id'];
drupal_set_title("Slice " . $slice_info[0]['name'] . " - Users");

// get person info
if( !empty( $slice_info[0]['person_ids'] ) ) {
  $person_info= $adm->GetPersons( $slice_info[0]['person_ids'], array( "first_name", "last_name", "email", "person_id","roles" ) );
  sort_persons( $person_info );
}

// if site_id is in post use it, if not use the user's primary
if( $_POST['site_id'] )
  $site_id= $_POST['site_id'];
else
  $site_id= $_person['site_ids'][0];
  
// get site nodes for $site_id
$sid= intval( $site_id );
$site_user_info= $adm->GetSites( array( $sid ), array( "person_ids" ) );
$site_user= $site_user_info[0]['person_ids'];


// gets all person_ids from site that arent already associated with the slice
foreach( $site_user as $suser) {
  if( !in_array( $suser, $slice_info[0]['person_ids'] ) )
    $susers[]= $suser;

}

// Get person info from new list
if( !empty( $susers ) ) {
  $all_suser_info= $adm->GetPersons( $susers, array( "email", "first_name", "last_name", "person_id", "role_ids", 'roles' ) );
//Filter the new list of user info to omit the tech user  
  foreach( $all_suser_info as $user_info) {
    if ( (count($user_info["role_ids"])==1 ) && ( in_array(40,  $user_info["role_ids"]) )) {
      continue;
    }
    $suser_info[]= $user_info;
  }
  if ( ! empty($suser_info) ) {
    sort_persons( $suser_info );
  }
 }


// start form   
if ( $slice_readonly) 
  echo "<div class='plc-foreign'>";
else
  echo "<form action='slice_users.php?id=$slice_id' method=post>\n";

// section for adding people : for local slices only
if ( ! $slice_readonly ) {
  echo "<hr />";
  echo "<h5>Select a site to add People from.</h5>\n";
  echo "<select name='site_id' onChange='submit()'>\n";

  // get site names and ids
  $site_info= $adm->GetSites( NULL, array( "site_id", "name" ) );
  sort_sites( $site_info );

  foreach( $site_info as $site ) {
    echo "<option value=". $site['site_id'];
    if( $site['site_id'] == $site_id )
      echo " selected";
    echo ">". $site['name'] ."</option>\n";
    
  }

  echo "</select>\n";

  
  if( $suser_info ) {
    echo $added;
    echo "<table cellpadding=2><tbody >\n<tr>";
    echo "<th></th> <th> Email </th><th> First Name </th><th> Last Name </th><th> Roles </th>
        </tr>";
    $proles="";
    foreach( $suser_info as $susers ) {
      foreach ( $susers['roles'] as $prole)
	$proles.=" ".$prole;
      echo "<tr><td><input type=checkbox name='add_user[]' value=". $susers['person_id'] ."> </td><td> ". $susers['email'] ."  </td><td align='center'> ". $susers['first_name'] ."</td><td align='center'> ". $susers['last_name'] ."</td><td align='center'> ".$proles."</td></tr>\n";
      unset($proles);
    }
  
    echo "</tbody></table>\n";
    echo "<p><input type=submit value='Add People' name='add'>\n";
  } else {
    echo "<p>All People on site already added.\n";
  }
 }


echo "<hr />\n";

// show all people currently associated
echo $removed;
echo "<h5>People currently associated with slice</h5>\n";
if( $person_info ) {
  if ( ! $slice_readonly ) {
    echo "<u>Check boxes of people to remove:</u>\n";
    echo "<table cellpadding=2><tbody >\n<tr>";
    echo "<th></th><th> Email </th><th> First Name</th><th> Last Name</th><th> Roles </th>
        </tr>";
  } else {
    echo "<table cellpadding=2><tbody>\n";
    echo "<tr><th> E-mail <th> First name <th> Last name<th> Roles </th> </tr>";
  }

  foreach( $person_info as $person ) {
    foreach ( $person['roles'] as $prole)
      $proles.=" ".$prole;
    if ( ! $slice_readonly ) 
      echo "<tr><td><input type=checkbox name='rem_user[]' value=". $person['person_id'] ."> </td><td> ". $person['email'] ." </td><td align='center'> ". $person['first_name']." </td><td align='center'>".$person['last_name'] ." </td><td align='center'>".$proles."</td></tr>\n";
    else 
      echo "<tr><td align='center'>" . $person['email'] . "</td><td align='center'>" . $person['first_name'] . "</td><td align='center'>" . $person['last_name'] ." </td><td align='center'>".$proles."</td></tr>"; 
    unset($proles);
  }
  
  echo "</tbody></table>\n";
  if ( ! $slice_readonly )
    echo "<p><input type=submit value='Remove People' name='remove'>\n";
  
} else {
  echo "<p>No People associated with slice.\n";
}

if ($slice_readonly)
  echo "</div>";
 else 
   echo "</form>";

echo "<p><a href='index.php?id=$slice_id'>Back to Slice</a>\n";

// Print footer
include 'plc_footer.php';

?>

