<?php

// $Id: index.php 11645 2009-01-21 23:09:49Z thierry $

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
// set default 
drupal_set_title('People');
include 'plc_header.php'; 

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

//fix the memory limit for this page
ini_set("memory_limit","48M");

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];


////////////////////
// The set of columns to fetch
// and the filter applied for fetching sites
if ( !in_array( '10', $_roles ) ) {
  $columns = array("person_id", "first_name", "last_name", "email", "roles" , "peer_id");
  // PIs can see users not yet enabled
  if ( ! in_array ('20', $_roles) ) {
    $filter = array ("enabled" => TRUE);
  } else {
    $filter = array();
  }
 } else {
  $columns = array("person_id", "first_name", "last_name", "email", "roles" , "peer_id", "key_ids", "enabled","slice_ids" );
  $filter = array ();
 }

//////////////////
// perform post-processing on site objects as returned by GetSites
// performs sanity check and summarize the result in a single column
// performs in-place replacement, so passes a reference
function layout_person ($person) {

  // we need the 'key_ids' field to do this
  // so regular users wont run this
  if ( ! array_key_exists ('key_ids', $person))
    return $person ;
    
  $messages=array();
  
  // do all this stuff on local persons only
  if ( $person['peer_id'] ) {
    $class='plc-foreign';
  } else {
    $class='plc-warning';
    // check that the person has keys, but dont do that for admins
    if ( ! in_array ('admin',$person['roles']) && count($person['key_ids']) == 0)
      $messages [] = "No Key";
  }
  if ( ! $person['enabled'] ) 
    $messages[] = "Disabled";
  // but always cleanup $person columns
  unset ($person['key_ids']);
  unset ($person['enabled']);
  //detect tech already involved on some slices. 
  if( ( count($person['roles'])==1 ) && ( in_array('tech',$person['roles']) )  && (! empty($person["slice_ids"])) ) {
    $messages[]="Tech involved in a Slice";  
  }
  // cleanup $person columns
  unset ($person['slice_ids']);
  //display the extra status column
  $person['status'] = plc_vertical_table($messages,$class);
  return $person;
}

// if emailpattern or peerscope is set then search for Persons.
// we use GET rather than POST so paginate can display the right contents on subsequent pages
// can be useful for writing bookmarkable URL's as well
if( $_GET['emailpattern'] || $_GET['peerscope']) {
  $emailpattern= $_GET['emailpattern'];
   if (empty($emailpattern)) { 
    $emailpattern="*";
  }
   $filter = array_merge (array( "email"=>$emailpattern ), $filter);
   switch ($_GET['peerscope']) {
   case '':
     $peer_label="all peers";
     break;
   case 'local':
     $filter=array_merge(array("peer_id"=>NULL),$filter);
     $peer_label="local peer";
     break;
   case 'foreign':
     $filter=array_merge(array("~peer_id"=>NULL),$filter);
     $peer_label="foreign peers";
     break;
   default:
    $peer_id=intval($_GET['peerscope']);
    $filter=array_merge(array("peer_id"=>$peer_id),$filter);
    $peer=$api->GetPeers(array("peer_id"=>$peer_id));
    $peer_label='peer "' . $peer[0]['peername'] . '"';
    break;
   }
   // need to use a hash filter for patterns to be properly handled
  $persons= $api->GetPersons($filter , $columns );
  $persons= array_map(layout_person,$persons);
  $person_count = count ($persons);
  if ( $person_count == 1) {
    header( "location: index.php?id=". $persons[0]['person_id'] );
    exit();
  } else if ( $person_count == 0) {
    echo "<span class='plc-warning'> No person whose email matches $emailpattern </span>";
  } else {
    drupal_set_title ("Users matching $emailpattern on ". $peer_label);
    sort_persons ($persons);
    echo paginate( $persons, "person_id", "Persons", 25, "email");
  }
 }
// if a site_id is given, display the site persons only
else if( $_GET['site_id'] ) {
  $site_id= $_GET['site_id'];
  // Get site info
  $site_info= $api->GetSites( array( intval( $site_id ) ), array( "name", "person_ids" ) );
  drupal_set_title("People with " . $site_info[0]['name']);
  // Get site nodes
  $persons= $api->GetPersons( array_merge (array("person_id"=>$site_info[0]['person_ids']),$filter), $columns );
  $persons= array_map(layout_person,$persons);
  sort_persons( $persons );

  echo paginate( $persons, "person_id", "Persons", 25, "email" );

}
// if a slice_id is given,display the persons involved in this slice
else if( $_GET['slice_id'] ) {
  $slice_id= $_GET['slice_id'];
  // Get slice infos
  $slice_info= $api->GetSlices( array( intval( $slice_id ) ), array( "name", "person_ids" ) );
  drupal_set_title("People In " . $slice_info[0]['name']);
  // Get slice persons
  $persons= $api->GetPersons( array_merge (array("person_id"=>$slice_info[0]['person_ids']),$filter), $columns );
  if ( empty ($persons) ) {
    echo "No persons to display";
  } else {

  $persons= array_map(layout_person,$persons);
  sort_persons( $persons );

  echo paginate( $persons, "person_id", "Persons", 25, "email" );
  echo "<br /><p><a href='/db/slices/index.php?id=".$slice_id.">Back to slice page</a></div>";  
  }
  
 }
// if no person id, display list of persons to choose
elseif( !$_GET['id'] ) {

  // GetPersons API call
  $persons= $api->GetPersons( empty($filter) ? NULL : $filter, $columns );
  $persons= array_map(layout_person,$persons);
  sort_persons( $persons );

  drupal_set_html_head('<script type="text/javascript" src="/planetlab/bsn/bsn.Ajax.js"></script>
    <script type="text/javascript" src="/planetlab/bsn/bsn.DOM.js"></script>
    <script type="text/javascript" src="/planetlab/bsn/bsn.AutoSuggest.js"></script>');

  echo "<div>\n
        <form method=get action='index.php'>\n";
  //if( $emailpattern ) echo "<font color=red>'$emailpattern' is not a valid person email.</font>\n";
  echo "<table><tr>\n
<th><label for='testinput'>Enter Person Email or pattern: : </label></th>\n
<td><input type='text' id='testinput' name='emailpattern' size=40 value='' /></td>\n
<td rowspan=2><input type=submit value='Select Person' /></td>\n
</tr> <tr>
<th><label for='peerscope'>Federation scope: </label></th>\n
<td><select id='peerscope' name='peerscope' onChange='submit()'>\n
";
    echo plc_peers_option_list($api);
    echo "</select></td>\n
</tr></table></form></div>\n
<br />\n";
  echo paginate( $persons, "person_id" , "Persons", 25, "email");
  echo "<script type=\"text/javascript\">\n
var options = {\n
	script:\"/planetlab/persons/test.php?\",\n
	varname:\"input\",\n
	minchars:1\n
};\n
var as = new AutoSuggest('testinput', options);\n
</script>\n";
}
else {
  // get the person_id from the URL
  $person_id= intval( $_GET['id'] );
  // GetPersons API call for this person
  $person_info= $api->GetPersons( array( $person_id ) );
  if( empty( $person_info ) ) {
    echo "No such person.";   
  } else {
    // vars from api
    $first_name= $person_info[0]['first_name'];
    $last_name= $person_info[0]['last_name'];
    $title= $person_info[0]['title'];
    $url= $person_info[0]['url'];
    $phone= $person_info[0]['phone'];
    $email= $person_info[0]['email'];
    $enabled= $person_info[0]['enabled'];
    $peer_id=$person_info[0]['peer_id'];
    
    // arrays from api
    $role_ids= $person_info[0]['role_ids'];
    $roles= $person_info[0]['roles'];
    $site_ids= $person_info[0]['site_ids'];
    $slice_ids= $person_info[0]['slice_ids'];
    $key_ids= $person_info[0]['key_ids'];
    
    // gets more data from API calls
    $site_info= $api->GetSites( $site_ids, array( "site_id", "name" ) );
    $slice_info= $api->GetSlices( $slice_ids, array( "slice_id", "name" ) );
    $key_info= $api->GetKeys( $key_ids );
    
    drupal_set_title("$first_name $last_name Account Information");

    // start form
    if ( ! $peer_id ) {
      echo "<form action='/db/persons/person_actions.php' enctype='multipart/form-data'  method='post'>\n";
    } else {
      echo "<div class='plc-foreign'>";
    }
    echo "<input type=hidden name='person_id' value='$person_id'>\n";
    
    if ( ! $peer_id ) {
      if( in_array( 10, $_roles ) || ( in_array( 20, $_roles ) && in_array( $site_ids[0], $_person['site_ids'] ) ) ) {
	// list to take person action

	echo "<table><tr><td>";

	if (in_array( 10, $_roles )) {
	  echo plc_event_button("Person","person",$person_id); 
	  echo "</td><td>";
	}

	echo "<select name='actions' onChange=\"submit();\">\n";
			
	$actions= array( ''=>'Choose Action', 'delete'=>"Delete $first_name" );
	
	if( $enabled == true )
	  $actions['disable']= "Disable $first_name";
	else
	  $actions['enable']= "Enable $first_name";

	if ( in_array(10,$_roles)) {
	    $actions['su'] = "Become $first_name";
	  }
	      
	foreach( $actions as $key => $val ) {
	  echo "<option value='$key'";
		
	  if( $key == $_POST['actions'] )
	    echo " selected";
		
	  echo ">$val</option>\n";
	}
	      
	echo "</select>\n";

	if( $enabled == false ) {
	  echo "</td><td>";
	  echo " &nbsp; <font color=red size=-1><- This user is not enabled. Choose here to enable or delete.</font>";
	}
	echo "</td></tr></table>";
      }

    } 
	
    // basic person info
    echo "<hr />";
    echo "<table border=0>\n";
    echo "<tr><th>First Name: </th><td> $first_name</td></tr>\n";
    echo "<tr><th>Last Name: </th><td> $last_name</td></tr>\n";
    echo "<tr><th>Title: </th><td> $title</td></tr>\n";
    echo "<tr><th>Email: </th><td><a href='mailto:$email'>$email</a></td></tr>\n";
    echo "<tr><th>Phone: </th><td>$phone</td></tr>\n";
    echo "<tr><th>URL: </th><td>$url</td></tr>\n";
    echo "</table>\n";
	  
    if( in_array( 10, $_roles ) || $_person['person_id'] == $person_id )
      echo "<br /><a href='/db/persons/update.php?id=$person_id'>Update info</a>\n";
	  
    echo "<hr />\n";
		
    // keys
    $can_manage_keys = ( ( ! $peer_id ) && (in_array( "10", $_roles ) || $person_id == $_person['person_id']));
    echo "<h3>Keys</h3>\n";
    if( !empty( $key_ids ) ) {
      echo "<p><table border=0 width=450>\n";
      echo "<thead><tr><th>Type</th><th>Key</th>";
      if ( $can_manage_keys )
	echo "<th>Remove</th>";
      echo "</tr></thead><tbody>\n";
			
      foreach( $key_info as $key ) {
	$key_type= $key['key_type'];
	$key_id= $key['key_id'];
	$key_text= wordwrap( $key['key'], 70, "<br />\n", 1 );
	echo "<tr><td>$key_type</td><td>$key_text";
	      
	if ( $can_manage_keys ) 
	  echo "</td><td><input type=checkbox name='rem_key[]' value='$key_id'>";
	      
	echo "</td></tr>\n";
      }
	
      echo "</tbody></table>\n";
      if ($can_manage_keys)
	echo "<p><input type=submit name='Remove_keys' value='Remove keys'><br />\n";
	    
    } else {
      echo "<span class='plc-warning'> This user has no known key</span>";
    }
		
    if( $can_manage_keys ){
      echo "<br /> Upload new key: <input type='file' name='key' size=30>\n
        <input type='submit' name='Upload' value='Upload'>\n
        <br /><hr />\n";
    }
    // sites
    echo "<h3>Sites</h3>\n";
    if( !empty( $site_info ) ) {
      echo "<table cellpadding=3><tbody>\n";
	
      foreach( $site_info as $site ) {
	$site_name= $site['name'];
	$site_id= $site['site_id'];
	      
	echo "<tr><td><a href='/db/sites/index.php?id=$site_id'>$site_name</a> </td><td> (<input type=checkbox name='rem_site[]' value='$site_id'> remove)</td></tr>\n";
      }
      echo "</tbody></table>\n";
      echo "<input type=submit name='Remove_Sites' value='Remove Sites'>\n";
	
    } else {
      echo "<span class='plc-warning'> This user is not affiliated with a site !!</span>";
    }
	
    // diplay site select list to add another site for user
    if( ! $peer_id && in_array( 10, $_roles ) ) {
      // get site info
      $full_site_info= $api->GetSites( NULL, array( "site_id", "name" ) );
	    
      if( $site_info )
	$person_site= arr_diff( $full_site_info, $site_info );
      else
	$person_site= $full_site_info;
	    
      sort_sites( $person_site );
	    
      echo "<p>Select a site to add this user to: ";
      echo "<select name='site_add' onChange='submit()'>\n<option value=''>Choose a site to add:</option>\n";
	    
      foreach( $person_site as $site ) {
	echo "<option value=". $site['site_id'] .">". $site['name'] ."</option>\n";
	      
      }
	    
      echo "</select>";
	    
    }
    echo "<hr />\n";

  // roles
    echo "<h3>Roles</h3>\n";
    echo "<p><table>\n";
    echo "<thead><tr><th>Role</th>";
    if( in_array( "10", $_roles ) )
      echo "<th>Remove</th>";
    echo "</tr></thead><tbody>\n";
	  
    // construct role array
    for( $n=0; $n<count($roles); $n++ ) {
      $proles[]= array( 'role_id'=>$role_ids[$n], 'name'=>$roles[$n] );
    }
    
    $button_shown=0;
    if ( !empty ($roles) ) {
      foreach( $proles as $role ) {
	$role_name= $role['name'];
	$role_id= $role['role_id'];
	      
	echo "<tr><td>$role_name";
	      

	if( in_array( 10, $_roles ) ) {
	  echo "</td><td><input type=checkbox name='rem_role[]' value='$role_id'>";
	  if ( ! $button_shown ) {
	    $rowspan=count($roles);
	    echo "</td><td rowspan=$rowspan valign=center><input type=submit name='Remove_Roles' value='Remove Roles'></td></tr>\n";
	    $button_shown=1;
	  }
	}
	      
	echo "</td></tr>\n";
      }
    } else {
      echo "<span class='plc-warning'> This user has no known role !!</span>";
    }
    echo "</tbody></table>\n";
	  
    // if admin show roles to add
    if( in_array( 10, $_roles ) ) {
      $all_roles= $api->GetRoles();
      $addable_roles= arr_diff( $all_roles, $proles );
      ##when the proles array is empty strangely the method arr_diff($all_roles, $proles )
      ##return an empty array and the scrolling roles list is not displayed in this case
      ##assign to addablerole all the roles
      if (count($proles)==0)
	$addable_roles=$all_roles;
      
      if( !empty( $addable_roles ) ) {
	echo "<p>Add role: <select name='add_role' onChange='submit()'>\n<option value=''>Choose a Role to add:</option>\n";
	
	foreach( $addable_roles as $arole ) {
	  echo "<option value=". $arole['role_id'] .">". $arole['name'] ."</option>\n";
		
	}
	      
	echo "</select>\n";
	      
      }
    }
	  
    echo "<hr />\n";
	  
    // slices
    echo "<h3>Slices</h3>\n";
    if( !empty( $slice_info ) ) {
	    
      foreach( $slice_info as $slice ) {
	$slice_name= $slice['name'];
	$slice_id= $slice['slice_id'];
	      
	echo "<a href='/db/slices/index.php?id=$slice_id'>$slice_name</a><br />\n";
      }
	    
    } else {
      echo "No slices found for that user";
    }
	  
    if ( ! $peer_id ) {
      echo "</form>\n";
    } else {
      echo "</div>\n";
    }
	  
  }
  if( $peer_id )
    echo "<br /></div>";
  
  echo "<br /><hr /><p><a href='/db/persons/index.php'>Back to persons list</a></div>";
  
 }


// Print footer
include 'plc_footer.php';


?>
