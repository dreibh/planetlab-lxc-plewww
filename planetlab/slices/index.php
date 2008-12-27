<?php

// $Id: index.php 970 2007-11-07 17:18:23Z amine $
// pattern-matching selection not implemented
// due to GetSlices bug, see test.php for details
// in addition that would not make much sense

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
// set default
drupal_set_title('Slices');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

//print_r( $_person );


// if node_host is set then set id to that node's id.
if( $_POST['slicename'] ) {
  $slicename= $_POST['slicename'];

  $slice_info= $api->GetSlices( array( $slicename ), array( "slice_id" ) );

  header( "location: index.php?id=". $slice_info[0]['slice_id'] );
  exit();

}


// if no slice id, display list of slices
if( !$_GET['id'] ) {
  // diplay site select list for admins
  if( in_array( 10, $_roles ) ) {
    // auto complete box for finding a slice
                
    drupal_set_html_head('<script type="text/javascript" src="/planetlab/bsn/bsn.Ajax.js"></script>
    <script type="text/javascript" src="/planetlab/bsn/bsn.DOM.js"></script>
    <script type="text/javascript" src="/planetlab/bsn/bsn.AutoSuggest.js"></script>');

    echo "<div>\n
          <form method=post action='index.php'>\n";
    if( $slicename ) echo "<font color=red>'$slicename' is not a valid slice name.</font>\n";
    echo "<p><label for='testinput'>Enter Slice Name: </label>\n
          <input type='text' id='testinput' name='slicename' size=40 value='' />\n
          <input type=submit value='Select Slice' />\n
          </div>\n
          <br />\n";
     
    // get site info
    $site_info= $api->GetSites( NULL, array( "site_id", "name", "peer_id" ) );
    sort_sites( $site_info );

    // Thierry -- try to select only one entry
    // xxx still not right if _person in several sites, but that is good enough
    //if( $site['site_id'] == $_POST['site_id'] || in_array( $site['site_id'], $_person['site_ids'] ) )
    if ($_POST['site_id'])
      $selected_site_id = $_POST['site_id'];
    else if ($_GET['site_id']) 
      $selected_site_id = $_GET['site_id'];
    else
      $selected_site_id = $_person['site_ids'][0];

    echo "Select a site to view slices from: &nbsp;";
    echo "<select name='site_id' onChange='submit()'>\n";

    foreach( $site_info as $site ) {
      echo "<option value=". $site['site_id'];
      if ( $site['site_id'] == $selected_site_id)
	  echo " selected";
      if ( $site['peer_id'] ) 
	echo " class='plc-foreign'";
      echo ">". $site['name'] ."</option>\n";
      
    }

    echo "</select>\n";
  
  }

  if( $_POST['site_id'] ) {
    $selection="Site";
    $site= array( intval( $_POST['site_id'] ) );
  } elseif( $_GET['site_id'] ) {
    $selection="Site";
    $site= array( intval( $_GET['site_id'] ) );
  } else {
    $selection="Person";
    $site= $_person['site_ids'];
  }
  
  // get site's slices
  $site_info= $api->GetSites( $site, array( "slice_ids","name" ) );

  if ( $selection == "Site" ) 
    drupal_set_title ("Slices for site " . $site_info[0]['name']);
  else
    drupal_set_title ("Slices for " . $_person['email'] . "'s sites");

  // make an array of all slices
  foreach( $site_info as $site ) {
    foreach( $site['slice_ids'] as $slice_id ) {
      $slice_ids[]= $slice_id;
    }
    
  }
  
  if (empty ($slice_ids)) {
    echo "<p><strong>No slice found, or all are expired.</strong>";
  } else {
  
    $slice_info= $api->GetSlices( $slice_ids, array( "slice_id", "name", "site_id", "person_ids", "expires", "peer_id" ) );
    //print '<pre>'; print_r( $api->trace() ) ; print '</pre>';

    if ( ! $slice_info) {
      echo "<p><strong>No Slices on site, or all are expired.</strong>\n";
    } else  {
      echo "<table class='list_set' border=0 cellpadding=2>\n";
      echo "<caption class='list_set'>Slice list</caption>\n";
      echo "<thead><tr class='list_set'><th class='list_set'>Slice Name</th>";
      echo "<th class='list_set'>Users</th>";
      echo "<th class='list_set'>Expiration</th></tr>";
      echo "</thead><tbody>\n";
      
      // create a list of person_ids
      $person_ids = array();
      foreach( $slice_info as $slice ) {
	  if ( !empty($slice['person_ids']) )
	    $person_ids = array_merge($person_ids, $slice['person_ids']);
	}

      // create an associative array of persons with person_id as the key
      $person_list = $api->GetPersons( $person_ids, array("person_id", "email") ); 
      $persons = array();
      foreach( $person_list as $person)
	{
	  $persons[$person['person_id']] = $person;
	}
      
      foreach( $slice_info as $slice ) {
	$slice_id= $slice['slice_id'];
	$slice_name= $slice['name'];
	$slice_expires= date( "M j, Y", $slice['expires'] );
	$peer_id = $slice['peer_id'];
      
	$extraclass="";
	if ( $peer_id ) 
	  $extraclass="plc-foreign";
      
	echo "<tr class='list_set $extraclass'><td><a href='/db/slices/index.php?id=$slice_id'>$slice_name</a></td><td class='list_set'>";
      
	if( !empty( $slice['person_ids'] ) ) {
	  foreach( $slice['person_ids'] as $person_id ) {
	    $person = $persons[$person_id];
	    $id= $person['person_id'];
	    $email= $person['email'];
	    echo "<a href='../persons/index.php?id=$id'>$email</a><br />\n";
	  }
	} else {
	  echo "None";
	}
      
	echo "</td><td class='list_set'>$slice_expires</td></tr>\n";
    
      }
    
      echo "</tbody></table>\n";

    }
  }
  

  echo "</form>\n";

  echo "<script type=\"text/javascript\">
var options = {
	script:\"/planetlab/slices/test.php?\",
	varname:\"input\",
	minchars:1
};
var as = new AutoSuggest('testinput', options);
</script>\n";

}
// if nothing else then show slice info
else {
  $slice_id= intval( $_GET['id'] );

  // GetSlices API call
  $slice_info= $api->GetSlices( array( $slice_id ) );

  if( empty( $slice_info ) ) {
    header( "location: index.php" );
    exit();
  }

  // pull all slice info to vars
  $instantiation= $slice_info[0]['instantiation'];
  $name= $slice_info[0]['name'];
  $url= $slice_info[0]['url'];
  $expires= date( "M j, Y", $slice_info[0]['expires'] );
  $site_id= $slice_info[0]['site_id'];
  $description= $slice_info[0]['description'];
  $max_nodes= $slice_info[0]['max_nodes'];
  $node_ids=$slice_info[0]['node_ids'];
  $person_ids=$slice_info[0]['node_ids'];

  // get peer id
  $peer_id= $slice_info[0]['peer_id'];

  $person_ids= $slice_info[0]['person_ids'];
  $node_ids= $slice_info[0]['node_ids'];
  $slice_tag_ids= $slice_info[0]['slice_tag_ids'];


  // node info
// looks unused
//  if( !empty( $node_ids ) )
//    $nodes= $api->GetNodes( $node_ids, array( "node_id", "hostname" ) );

  // site info
  $site_info= $api->GetSites( array( $site_id ), array( "site_id", "name", "person_ids" ) );

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
	
  if( gmmktime() > $slice_info[0]['expires'] ) { 
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

// Print footer
include 'plc_footer.php';

?>
