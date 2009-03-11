<?php

// $Id: index.php 12104 2009-02-19 18:41:19Z thierry $

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_peers.php';
require_once 'linetabs.php';
require_once 'table.php';
require_once 'details.php';
require_once 'toggle.php';

// -------------------- 
// recognized URL arguments
$slice_id=intval($_GET['id']);
if ( ! $slice_id ) { plc_error('Malformed URL - id not set'); return; }

function renew_area ($slice,$site) {
 
  // Constants
  $week= 7 * 24 * 60 * 60; // seconds
  $max_renewal_length= 8; // weeks from today
  $max_expiration= mktime() + ($max_renewal_length * $week); // seconds since epoch
  $max_expiration_date= gmstrftime("%A %b-%d-%y %T %Z", $max_expiration);
  
  // the renew area
  // xxx some extra code needed to enable this area only if the slice description is OK:
  // description and url must be non void
  $toggle=new PlekitToggle('renew',"Renew this slice",
 			   array("trigger-bubble"=>"Enter this zone if you wish to renew your slice",
 				 'start-visible'=>true));
  $toggle->start();

  // xxx message could take roles into account
  if ($site['max_slices']<=0) {
     $message= <<< EOF
<p>Slice creation and renewal have been temporarily disabled for your
site. This may have occurred because your site's nodes have been down
or unreachable for several weeks, and multiple attempts to contact
your site's PI(s) and Technical Contact(s) have all failed. If so,
contact your site's PI(s) and Technical Contact(s) and ask them to
bring up your site's nodes. Please visit your <a
href='/db/sites/index.php?id=$site_id'>site details</a> page to find
out more about your site's nodes, and how to contact your site's PI(s)
and Technical Contact(s).</p>
EOF;
     echo $message;
 
  } else {
    // xxx this is a rough cut and paste from the former UI
    // Showing a datepicker view could be considered as well with some extra work
    // Calculate possible extension lengths
    $renewal_lengths = array();
    foreach ( array ( 1 => "One more week", 
 		      2 => "Two more weeks", 
 		      4 => "One more month" ) as $weeks => $text ) {
       if (($slice [ 'expires' ] + ($weeks * $week)) < $max_expiration) {
	 $renewal_lengths [ $weeks ] = "$text (" . 
	   gmstrftime( "%A %b-%d-%y %T %Z", $slice [ 'expires' ] + ( $weeks * $week ) ) 
	   . ")";
       }
    }

    if ( empty( $renewal_lengths ) ) {
      plc_warning("Slice cannot be renewed any further into the future, try again closer to expiration date.");
     } else {
      // clean vars
       $expiration_date = gmstrftime( "%A %b-%d-%y %T %Z", $slice [ 'expires' ] );
       echo '<p> area under construction </a>';
       
       //       // display form
       //       echo "<form action='/db/slices/renew_slice.php' method='post'>\n";
       //       echo "<input type=hidden name='id' value='$slice_id'><input type=hidden name='expires' value='". $slice['expires'] ."'>\n";
     
// $message = <<< EOF
// <p>You must provide a short description as well as a link to a project website before renewing it.
// Do <span class='bold'>not</span> provide bogus information; if a complaint is lodged against your slice 
// and PlanetLab Operations is unable to determine what the normal behavior of your slice is, 
// your slice may be deleted to resolve the complaint.</p>
// EOF;
// echo $message;
//       
//       echo "<p><span class='bold'>NOTE:</span> 
// Slices cannot be renewed beyond $max_renewal_length weeks of today ($max_expiration_date).</p>\n";
//       
//       echo "<table cellpadding=2><tbody>\n";
//       
//       echo "<tr><th>Name:</th><td colspan=2>". $slice['name'] ."</td></tr>\n";
//       
//       if( $error['url'] ) 
//         $url_style= " style='border: 1px solid red;'";
//       echo "<tr><th$url_style>URL: </th><td$url_style><input size=50 name='url' value='". $slice['url'] ."' /></td><td$url_style><font color=red>". $error['url'] ."</font></td></tr>\n";
//       
//       if( $error['description'] ) 
//         $desc_style= " style='border: 1px solid red;'";
//       echo "<tr><th$desc_style>Description: </th><td$desc_style><textarea name='description' rows=5 cols=40>". $slice['description'] ."</textarea></td><td$desc_style><font color=red>". $error['description'] ."</font></td></tr>\n";
//       
//       echo "<tr><th>Expiration Date: </th><td colspan=2>$expiration_date</td></tr>\n";
//       
//       echo "<tr><th>Renewal Length: </th><td colspan=2><select name='expire_len'>";
//       
//       // create drop down of lengths to choose
//       foreach ($renewal_lengths as $weeks => $text) {
//         echo "<option value='$weeks'";
//         if( $weeks == $expire_len )
//           echo " selected";
//         echo ">$text</option>\n";
       }
//       
//       echo "</select></td></tr>\n<tr><td colspan=3 align=center><input type=submit value='Renew Slice' name='submitted'></td></tr>\n</tbody></table>\n";
//     
//     }
//     
  }
 
  $toggle->end();
}

////////////////////
// Get all columns as we focus on only one entry
$slices= $api->GetSlices( array($slice_id));

if (empty($slices)) {
  drupal_set_message ("Slice " . $slice_id . " not found");
  return;
 }

$slice=$slices[0];

// pull all node info to vars
$name= $slice['name'];
$expires = date( "d/m/Y", $slice['expires'] );
$site_id= $slice['site_id'];

//$node_ids=$slice['node_ids'];
$person_ids=$slice['person_ids'];
//$slice_tag_ids= $slice['slice_tag_ids'];

// get peers
$peer_id= $slice['peer_id'];
$peers=new Peers ($api);

// gets site info
$sites= $api->GetSites( array( $site_id ) );
$site=$sites[0];
$site_name= $site['name'];
$max_slices = $site['max_slices'];

// get all persons info
if (!empty($person_ids))
  $persons=$api->GetPersons($person_ids,array('email','enabled'));

////////// 
drupal_set_title("Details for slice " . $name);
$local_peer= ! $peer_id;

$am_in_slice = in_array(plc_my_person_id(),$person_ids);

$privileges = (plc_is_admin()  || $am_in_slice);

$tabs=array();
$tabs [] = tab_nodes_slice($slice_id);
$tabs [] = tab_site($site_id);

// are these the right privileges for deletion ?
if ($privileges) {
  $tabs ['Delete']= array('url'=>l_actions(),
			  'method'=>'post',
			  'values'=>array('action'=>'delete-slice','slice_id'=>$slice_id),
			  'bubble'=>"Delete slice $name",
			  'confirm'=>'Are you sure to delete $name');

  $tabs["Events"]=array_merge(tablook_event(),
			      array('url'=>l_event("Slice","slice",$slice_id),
				    'bubble'=>"Events for slice $name"));
  $tabs["Comon"]=array_merge(tablook_comon(),
			     array('url'=>l_comon("slice_id",$slice_id),
				   'bubble'=>"Comon page about slice $name"));
}

plekit_linetabs($tabs);

////////////////////////////////////////
$peers->block_start($peer_id);

$toggle = new PlekitToggle ('slice',"Details",
			    array('trigger-bubble'=>'Display and modify details for that slice'));
$toggle->start();

$details=new PlekitDetails($privileges);
$details->form_start(l_actions(),array('action'=>'update-slice','slice_id'=>$slice_id));

$details->start();
if (! $local_peer) {
  $details->th_td("Peer",$peers->peer_link($peer_id));
  $details->space();
 }


$details->th_td('Name',$slice['name']);
$details->th_td('Description',$slice['description'],'description',
		array('input_type'=>'textarea',
		      'width'=>50,'height'=>5));
$details->th_td('URL',$slice['url'],'url',array('width'=>50));
$details->th_td('Expires',$expires);
$details->th_td('Instantiation',$slice['instantiation']);
$details->th_td('Site',l_site_obj($site));
$details->end();

$details->form_end();
$toggle->end();

renew_area ($slice,$site);

$peers->block_end($peer_id);

//////////////////// users

//////////////////// nodes

//////////////////// tags

// Print footer
include 'plc_footer.php';

return;

?>














  // gets all persons from site_id
    // person info
  if( !empty( $person_ids ) ) 
    $persons= $api->GetPersons( $site_info[0]['person_ids'] , array( "person_id", "role_ids", "first_name", "last_name", "email" ) );

  if( $persons ) {
    // gets site contacts pis stores in dict
    foreach( $persons as $person )
      if( in_array( "20", $person['role_ids'] ) ) {
	$pis[]= array( "email" => $person['email'], "first_name" => $person['first_name'], "last_name" => $person['last_name'], "person_id" => $person['person_id'] );
	
      }
    if ($pis) {
      sort_persons( $pis );
    }
  }

  // slice tag info
  if( !empty( $slice_tag_ids ) )
    $slice_attibs= $api->GetSliceTags( $slice_tag_ids, 
				       array( "slice_tag_id", "tag_type_id", "value", "description", "min_role_id", "node_id" ) );

  // gets tag type info and combines it to form all tag info array
  if( $slice_attibs ) {
    foreach( $slice_attibs as $slice_attib ) {
      $tag_type= $api->GetTagTypes( array( $slice_attib['tag_type_id'] ), 
				    array( "tag_type_id", "tagname", "description" ) );
      
      $tags[]= array( "slice_tag_id" => $slice_attib['slice_tag_id'], 
		      "tag_type_id" => $slice_attib['tag_type_id'], 
		      "tagname" => $tag_type[0]['tagname'], 
		      "value" => $slice_attib['value'], 
		      "description" => $slice_attib['description'], 
		      "min_role_id" => $slice_attib['min_role_id'], 
		      "node_id" => $slice_attib['node_id'] );
    }

  }

  drupal_set_title("Slice details for " . $name);
  // start form

  if( $peer_id ) {
    echo "<div class='plc-foreign'>\n";
  }

  // basic slice menu
  if( ! $peer_id ) {

    $actions= array( ''=>'Choose Action' );
    
    if( in_array( 10, $_roles ) 
	|| ( in_array( 20, $_roles ) && in_array( $site_id, $_person['site_ids'] ) ) 
	|| in_array( $slice_id, $_person['slice_ids'] ) ) {
      $actions['renew']= "Renew $name";
      $actions['nodes']= "Manage Nodes";
    }
    if ( in_array( 10, $_roles )
	 || ( in_array( 20, $_roles ) && in_array( $site_id, $_person['site_ids'] ) ) ) {
      $actions['users']= "Manage Users";
      $actions['delete']= "Delete $name";
    }
    
    echo "<table><tr><td>\n";
    if (in_array( 10, $_roles )) {
      echo plc_event_button("slices","slice",$slice_id);
      echo "</td><td>";
    }
    echo plc_comon_button("slice_id",$slice_id);
    echo "</td><td>\n";

    echo "<form action='/db/slices/slice_action.php' method='post'>\n";
    echo "<input type=hidden name=slice_id value=$slice_id>\n";

    echo "<select name='actions' onChange=\"submit();\">\n";
    foreach( $actions as $key => $val ) {
      echo "<option value='$key'";
      
      if( $key == $_POST['actions'] )
	echo " selected";
      
      echo ">$val\n";
    }
    
    echo "</select><br />\n";
    echo "</form>\n";

    echo "</td></tr></table>\n";
  }

  echo "<table cellpadding=3><tbody>\n
	<tr><th>Slice Name: </th><td> $name </td></tr>\n
	<tr><th>Description: </th><td> $description </td></tr>\n
        <tr><th>URL: </th><td> <a href='$url'>$url</a> </td></tr>\n";
	
  if( gmmktime() > $slice['expires'] ) { 
    $class1= ' style="color:red;"'; 
    $msg1= '(slice is expired)'; 
  }
  echo "<tr><th$class1>Expiration: </th><td$class1> $expires &nbsp; $msg1</td></tr>\n";
  echo "<tr><th>Instantiation: </th><td><select name='instantiation' onChange=\"submit();\"\n";
  echo "<option value='delegated'"; 
  if( $instantiation == 'delegated' ) echo " selected"; 
  echo ">delegated</option>";
  echo "<option value='plc-instantiated'"; 
  if( $instantiation == 'plc-instantiated' ) echo " selected"; 
  echo ">plc-instantiated</option>";
  echo "<option value='not-instantiated'"; 
  if( $instantiation == 'not-instantiated' ) echo " selected"; 
  echo ">not-instantiated</option>";
  echo "</select>"; 

  echo "</td></tr>\n";
  echo "<tr><th>Site: </th><td> <a href='/db/sites/index.php?id=$site_id'>". $site_info[0]['name'] ."</a></td></tr>\n";
  $href="'/db/nodes/index.php?slice_id=" . $slice_id . "'";
  printf ("<tr><th> <a href=%s># Nodes</a></th><td><a href=%s>Total %d nodes</a></td>\n",$href,$href,count($node_ids));
  $href="'/db/persons/index.php?slice_id=" . $slice_id . "'";
  printf ("<tr><th> <a href=%s># Users</a></th><td><a href=%s>Total %d users</a></td>\n",$href,$href,count($person_ids));
  echo "</tbody></table>\n";

  if ( (!$class1) && in_array( $slice_id, $_person['slice_ids'] ) && (! $peer_id) ) 
    echo "<p><a href='update_slice.php?id=$slice_id'>Update Information</a>\n";

  echo "<br /><hr />\n";


  // slice tags
  if( $tags ) {

    // builds 2 arrays, one for tags, one for slivers
    foreach( $tags as $tag ) {
      if( empty( $tag['node_id'] ) ) {
        $slice_tag[]= $tag;
      }
      else {
        $sliver_tag[]= $tag;
        $sliver_nodes[]= $tag['node_id'];
      }
    }
  }

  // Get node info for those slivers
  $sliver_node_info= $api->GetNodes( $sliver_nodes, array( "node_id", "hostname" ) );

  if( $sliver_node_info ) {
    foreach( $sliver_node_info as $sliv_node ) {
      $new_sliver_node_info[$sliv_node['node_id']]= $sliv_node;
    }
  }

 if( $peer_id ) {
   echo "<br /></div>\n";
  }
 
  // slice tags
  $is_admin=in_array( 10, $_roles );
  $is_in_slice=in_array( $slice_id, $_person['slice_ids'] );
  $is_pi=in_array( 20, $_roles );
  if( $slice_tag ) {
    echo "<table cellpadding=3><caption class='list_set'>Slice Tags</caption>";
    echo "<thead><tr>";
    if( $is_admin )
      echo "<th></th>";
    echo "<th>Tag</th><th>Value</th><th>Description</th>";
    echo "</tr></thead><tbody>\n";

    foreach( $tags as $tag ) {
      // ignore sliver tags at this stage
      if( empty( $tag['node_id'] ) ) {
        echo("<tr>");
        if( $is_admin ) {
	  printf("<td>");
	  sprintf($label,"\\n [ %s = %s] \\n from %s",$tag['tagname'],$tag['value'],$name);
	  // xxx this is deprecated
	  echo plc_delete_link_button ('tag_action.php?rem_id=' . $tag['slice_tag_id'],
				       $label);
	  echo "</td>";
	}
	if( $is_admin || ($is_pi && $is_in_slice) ) {
          printf ("<td><a href='tags.php?type=slice?id=%s'>%s</a></td>",
		  $tag['slice_tag_id'],$tag['tagname']);
	} else {
	  printf("<td>%s</td>",$tag['tagname']);
	}
	printf("<td align=center>%s</td><td>%s</td>",
	       $tag['value'],$tag['description']);
        echo "</tr>";
      }
    }

    
    echo "</tbody></table>\n";

  }
  if( $is_admin || ($is_pi && $is_in_slice) )
    echo "<a href='tags.php?type=slice&add=$slice_id'>Add a Slice Tag</a>\n";    



  // sliver tags
  if( $sliver_tag ) {
    echo "<table cellpadding=3><caption class='list_set'>Sliver Tags</caption>";
    echo "<thead><tr>";
    if( $is_admin )
      echo "<th></th>";
    echo "<th>Tag</th><th>Value</th><th>Description</th><th>Node</th>";
    echo "</tr></thead><tbody>\n";

    foreach( $tags as $tag ) {
      $nodename=$new_sliver_node_info[$tag['node_id']]['hostname'];
      // consider only sliver tags at this stage
      if( !empty( $tag['node_id'] ) ) {
        echo("<tr>");
        if( $is_admin ) {
	  echo("<td>");
	  $label=sprintf("\\n [ %s = %s ] \\n from %s \\n on node %s",
			 $tag['tagname'],$tag['value'],$name,$nodename);
	  echo plc_delete_link_label('/db/nodes/sliver_action.php?rem_id=' . $tag['slice_tag_id'], 
				     $label);
	  echo "</td>";
	}
        if( $is_admin ) {
          printf("<td><a href='tags.php?type=slice&id=%s'>%s</a></td>",$tag['slice_tag_id'],$tag['tagname']);
	} else {
	  printf("<td>%s</td>",$tag['tagname']);
	}
	printf("<td align=center>%s</td><td>%s</td><td><a href='/db/nodes/index.php?id=%s'>%s</a></td>",
	       $tag['value'],$tag['description'],$tag['node_id'],$nodename);
	
        echo "</tr>";
      }
    }

    echo "</tbody></table>\n";
    
  }
  
  echo "<br /><hr />\n";
  
  if( $pis && !$peer_id ) {
    // site contacts
    echo "<h5>Contacts</h5>\n";
		
    $pi_rows= count( $pis );
    $tech_rows= count( $techs );
    $table_row= 0;
	
    echo "<table cellpadding=2><tbody>";
    if( $pis ) {
      echo "<tr><td rowspan=$pi_rows><strong>PI's:</strong> &nbsp; </td>\n";
      
      foreach( $pis as $pi ) {
	if( $table_row != 0 )
	  echo "<tr>";
	printf("<td><a href='/db/persons/index.php?id=%s'>%s %s</td><td><a href='mailto:%s'>%s</a></td></tr>\n",
	       $pi['person_id'],$pi['first_name'],$pi['last_name'],$pi['email'],$pi['email']);
	$table_row++;
      }
      
    }
    
    echo "</table>\n<br /><hr />\n";
    
  }
  
  
  echo "<p><a href='index.php'>Back to slice list</a></div>\n";
 }

