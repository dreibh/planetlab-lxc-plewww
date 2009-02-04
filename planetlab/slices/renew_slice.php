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


// Constants
$week= 7 * 24 * 60 * 60; // seconds
$max_renewal_length= 8; // weeks from today
$max_expiration= mktime() + ($max_renewal_length * $week); // seconds since epoch
$max_expiration_date= gmstrftime("%A %b-%d-%y %T %Z", $max_expiration);

// if submitted validate input
if( $_POST['submitted'] ) {
  // get post vars
  $expire_len= $_POST['expire_len'];
  $expires= $_POST['expires'];
  $slice_id= intval( $_POST['id'] );
  
  // create empty error array
  $error= array(  );
  
  // check input
  
  $url= $_POST['url'];
  if( $url == '' || empty( $url ) )
    $error['url']= "Provide a link to a project website.";
    
  $description= htmlspecialchars( $_POST['description'] );
  if ( $description == '' || empty( $description ) )
    $error['description']= "Provide a short description of the slice.";
    
  // if no errors update slice info 
  if( empty( $error ) ) {
    // set new expiration
    
    $expires+= ( $expire_len * $week );
    
    // make slice field array
    $slice_fields= array( "url" => $url, "description" => $description, "expires" => $expires );
    
    // Update it!
    $api->UpdateSlice( $slice_id, $slice_fields );
    
    plc_redirect( l_slice($slice_id));
    
  }
  
}

// if no id is set redirect back to slice index
if( !$_POST['id'] && !$_GET['id'] ) {
  plc_redirect( l_slices());
 }

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Slice Renewal');
include 'plc_header.php';




// get id
if( $_GET['id'] )
  $slice_id= intval( $_GET['id'] );
if( $_POST['id'] )
  $slice_id= intval( $_POST['id'] );

// get slice info
$slice_info= $api->GetSlices( array( $slice_id ), array( "expires", "name", "site_id", "description", "url" ) );

echo "<h2>Slice ". $slice_info[0]['name'] ." Renewal</h2>\n";

// get site info
if( !empty( $slice_info[0]['site_id'] ) ) {
  // get sliver/slice site info
  $site_info= $api->GetSites( array( $slice_info[0]['site_id'] ), array( "max_slivers", "max_slices" ) );
  
  // do not allow renew if max_slices are 0
  if( $site_info[0]['max_slices'] <= 0 ) {
    $support= '';
    $site_id= $slice_info[0]['site_id'];
    
    echo "<p>Slice creation and renewal have been temporarily disabled for your site. This may have occurred because your site's nodes have been down or unreachable for several weeks, and multiple attempts to contact your site's PI(s) and Technical Contact(s) have all failed. If so, contact your site's PI(s) and Technical Contact(s) and ask them to bring up your site's nodes. If you believe that your site's nodes are up and reachable. Visit your site's <a href='/db/sites/index.php?id=$site_id'>Site Details</a> page to find out more about your site's nodes, and how to contact your site's PI(s) and Technical Contact(s).</p>";

  }
  // else start renewal form
  else {
    // Calculate possible extension lengths
    $renewal_lengths = array();
    foreach ( array( 1 => "One more week", 2 => "Two more weeks", 4 => "One more month" ) as $weeks => $text ) {
      if ( ( $slice_info[0]['expires'] + ( $weeks * $week ) ) < $max_expiration ) {
        $renewal_lengths[$weeks]= "$text (". gmstrftime( "%A %b-%d-%y %T %Z", $slice_info[0]['expires'] + ( $weeks * $week ) ) .")";
      }
    }
    
        
    if ( empty( $renewal_lengths ) ) {
      echo "<font color='red'>Slice cannot be renewed any further into the future, try again closer to expiration date.</font> Go <a href='index.php?id=$slice_id'>back</a> to ". $slice_info[0]['name'] .".\n";
    }
    else {
      // clean vars
      $expiration_date = gmstrftime( "%A %b-%d-%y %T %Z", $slice_info[0]['expires'] );
      
      // display form
      echo "<form action='/db/slices/renew_slice.php' method='post'>\n";
      echo "<input type=hidden name='id' value='$slice_id'><input type=hidden name='expires' value='". $slice_info[0]['expires'] ."'>\n";
    
      echo "<p>You must provide a short description as well as a link to a project website before renewing it. Do <b>not</b> provide bogus information; if a complaint is lodged against your slice and PlanetLab Operations is unable to determine what the normal behavior of your slice is, your slice may be deleted to resolve the complaint.</p>\n";
      
      echo "<p><b>NOTE:</b> Slices cannot be renewed beyond $max_renewal_length weeks of today ($max_expiration_date).</p>\n";
      
      echo "<table cellpadding=2><tbody>\n";
      
      echo "<tr><th>Name:</th><td colspan=2>". $slice_info[0]['name'] ."</td></tr>\n";
      
      if( $error['url'] ) 
        $url_style= " style='border: 1px solid red;'";
      echo "<tr><th$url_style>URL: </th><td$url_style><input size=50 name='url' value='". $slice_info[0]['url'] ."' /></td><td$url_style><font color=red>". $error['url'] ."</font></td></tr>\n";
      
      if( $error['description'] ) 
        $desc_style= " style='border: 1px solid red;'";
      echo "<tr><th$desc_style>Description: </th><td$desc_style><textarea name='description' rows=5 cols=40>". $slice_info[0]['description'] ."</textarea></td><td$desc_style><font color=red>". $error['description'] ."</font></td></tr>\n";
      
      echo "<tr><th>Expiration Date: </th><td colspan=2>$expiration_date</td></tr>\n";
      
      echo "<tr><th>Renewal Length: </th><td colspan=2><select name='expire_len'>";
      
      // create drop down of lengths to choose
      foreach ($renewal_lengths as $weeks => $text) {
        echo "<option value='$weeks'";
        if( $weeks == $expire_len )
          echo " selected";
        echo ">$text</option>\n";
      }
      
      echo "</select></td></tr>\n<tr><td colspan=3 align=center><input type=submit value='Renew Slice' name='submitted'></td></tr>\n</tbody></table>\n";
    
    }
    
  }

}
else 
  echo "No data for this slice ID.  Go <a href='index.php'>back</a> to slices.\n";
  








// Print footer
include 'plc_footer.php';

?>
