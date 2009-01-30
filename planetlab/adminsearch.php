<?php

  // Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
// set default 
drupal_set_title('DB Search');
include 'plc_header.php'; 

// Common functions
require_once 'plc_functions.php';
require_once 'plc_objects.php';
require_once 'plc_sorts.php';

function is_possible_domainname($token) {
  if ( strpos ( $token, "@" ) === False && substr_count($token, ".") >= 2 ) {
    return true;
  } else {
    return false;
  }
}

function get_and_print_site($site_array) {
  global $api;
  $sites = $api->GetSites( $site_array, array( "name", "site_id", 
					       "url", "enabled", "node_ids", "person_ids", "date_created", 
					       "slice_ids", "max_slivers", "max_slices", "login_base" ) );
  if ( count($sites) > 0 ) {
    foreach( $sites as $site) {
      $name = $site['name'];
      $site_id = $site['site_id'];
      $url =  $site['url'];
      $enabled = $site['enabled'];
      $node_ids =  $site['node_ids'];
      $slice_ids_count =  count($site['slice_ids']);
      $person_ids =  $site['person_ids'];
      $date_created = date("M j G:i Y", $site['date_created']);
      $max_slivers = $site['max_slivers'];
      $max_slices =  $site['max_slices'];
      $login_base =  $site['login_base'];

      echo "<tr>\n";

      echo "<td WIDTH='35%'><a href='/db/sites/index.php?id=$site_id'>$name</a>\n";
      echo "(<a href='$url'>home</a>)</td>\n";
      echo "<td NOWRAP>[ $slice_ids_count of $max_slices ]</td>\n";

      echo "<td NOWRAP>";
      if (!$enabled) echo "<font color=red>Not Enabled</font><br>\n";
      //else echo "<td NOWRAP><i>$date_created</i></td>\n";
      echo "<i>$date_created</i></td>\n";
			
      echo "<td><strong>Nodes:</strong> ";
      foreach ($site['node_ids'] as $node_id) 
	{ 
	  echo " <a href='/db/nodes/index.php?id=$node_id'>$node_id</a>, "; 
	}

      echo "</td></tr>\n";
    }
  }
}

function get_and_print_hostname($host_array) {
  global $api;
  $nodes = $api->GetNodes( $host_array, array( "hostname", "node_id", 
					       "site_id", "date_created", "last_contact", "boot_state" ) );
  if ( count ($nodes) > 0 ) {
    foreach($nodes as $node) {
      $hostname= $node['hostname'];
      $node_id = $node['node_id'];
      $boot_state= $node['boot_state'];
      $last_contact = $node['last_contact'];
      $date_created = date("M j G:i Y", $node['date_created']);
      echo "<tr>";
      echo "<td><a href='/db/nodes/index.php?id=$node_id'>$node_id</a></td> ";
      echo "<td><a href='/db/nodes/index.php?id=$node_id'>$hostname</a></td>";
      echo "<td>$boot_state</td>";
      if( $last_contact != NULL ) {
	$last_contact_str = timeDiff($last_contact);
      } else {
	$last_contact_str = "Never";
      }
      echo "<td NOWRAP>$last_contact_str</td>\n";
      echo "<td NOWRAP>$date_created</td>\n";
      echo "</tr>\n";
    }
  }

  return $nodes;
}

function get_and_print_user ($user_array) {
  global $api;
  $persons= $api->GetPersons( $user_array, array( "person_id", "first_name", 
						  "last_name", "email", "roles", "enabled", "date_created", 
						  "site_ids" ) );

  if ( count($persons) > 0 ) {
    foreach($persons as $person) {
      $first= $person['first_name'];
      $last = $person['last_name'];
      $person_id= $person['person_id'];
      $email= $person['email'];
      $enabled= $person['enabled'];
      $roles = $person['roles'];
      $role = $person['roles'][0];
      $date_created = date("M j G:i Y", $person['date_created']);
      $site_ids= $person['site_ids'];

      echo "<tr><td>\n";

      echo "<a href='/db/persons/index.php?id=$person_id'>";
      echo "$first $last</a></td>";
      echo "<td>($person_id)</td> ";
      echo "<td><a href='mailto:$email'>$email</a> </td>";

      echo "<td NOWRAP>";
      if (!$enabled) echo "<font color=red>Not Enabled</font><br>\n";
      //else echo "<td NOWRAP><i>$date_created</i></td>\n";
      echo "<i>$date_created</i></td>\n";
      //if (!$enabled) echo "<td><font color=red> Not Enabled </font></td>";
      //else echo "<td><i>$date</i></td>";
			
      echo "<td> <strong>roles:</strong> ";
      foreach ($person['roles'] as $role) { echo " $role, "; }

      echo "</td></tr>\n";

    }
		
  }
  return $persons;
}

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];


///////////////////////////////////////////////////////////////////////
// Initially, we need a search form, and blank information after that.  
// Based on the fields of the search form, we should be able to populate a lot
// of information, related to user, site, keys, 
// 
//	from a simple domain name search, it would display the site, 
//		nodes -> pcu
//		users grouped by role, 
//  from a user search, it would find,
//		user info link, site, roles, etc.
//
//  userquery will be one of these types:
//		part of a user name, email, user_id
//		part of a site name, site alias, site_id
// 		part of a node name, domain, node_id
//
//  we should be able to return results based on these guesses.  If they're
//  good, we'll get hits, if nothing comes back, we won't display anything.


echo "<div>\n
        <form method=post action='/db/adminsearch.php'>\n";

if( $_POST['userquery'] or $_GET['userquery']) {
  if ( $_POST['userquery'] ) {
    $query = $_POST['userquery'];
  } else {
    $query = $_GET['userquery'];
  }
 }

echo "<p><label for='testinput'>User or Site Name/loginbase/Email/User or Site ID:</label> (Separate with commas for multiple queries.)\n
        <input type='text' id='testinput' name='userquery' size=50 value='$query' />\n
        <input type=submit value='Search' />\n
		</form>\n
        </div>\n
        <br />\n";

// if userquery then search based on string
if( $_POST['userquery'] or $_GET['userquery']) {
  if ( $_POST['userquery'] ) {
    $query = $_POST['userquery'];
  } else {
    $query = $_GET['userquery'];
  }
  echo "<table cellspacing=2 cellpadding=1 width=100%><thead><caption><b>Search Results:</b><caption></thead><tbody>\n";

  $f_commas = explode(",", $query);
  // PHASE 1: query contains email, user_id, or part of a user name
  foreach($f_commas as $tok) {
    $tok = trim($tok);	// strip white space.

    $e = false;
    //find user by email
    if ( is_valid_email_addr($tok) ){
      $u = get_and_print_user(array("email"=>$tok));
      $e = true;
      if( count( $u ) == 0 ) $none = 1;
    }
    $n = false;
    // find user by user_id
    if ( is_numeric($tok) ){
      $u = get_and_print_user(array("person_id"=>intval($tok)));
      $n = true;
      if( count( $u ) == 0 ) $none = 1;
    }
    // neither of the above, 
    if (!$n && !$e) {
      // split on spaces, and search each part.
      // TODO: search upper and lower-case
      $f_spaces = explode(" ", $tok);
      foreach($f_spaces as $stok) {
	// assume $tok is part of a name
	// get_user
	$a = get_and_print_user(array("first_name"=>$stok));
	$b = get_and_print_user(array("last_name"=>$stok));
	// c = intersect_users(a, b)
	// if c 
	//     print_user(c), 
	// else 
	//     print_user(a,b)
      }
      if( count( $a ) == 0 && count( $b ) == 0 ) $none = 1;
    }

    // PHASE 2: query contains login_base, site_id, or part of a site name

    // find site by login_base
    $lb = false;
    $s = get_and_print_site(array("login_base"=>strtolower($tok)));
    if( count( $s ) == 0 ) $none = 1;
    else $lb = true;

    $n = false;
    // find site by site_id
    if ( is_numeric($tok) ){
      $s = get_and_print_site(array("site_id"=>intval($tok)));
      $n = true;
      if( count( $s ) == 0 ) $none = 1;
    }

    if( !$lb && !$n ) {
      $f_spaces = explode(" ", $tok);
      foreach($f_spaces as $stok) {
	$a = get_and_print_site(array("name"=>$stok));
      }
      if( count($a) == 0 ) $none = 1;
    }

    // PHASE 3:	query contains part of a node name, domain, node_id

    //if( $none == 1 ) echo "<tr><td>No Results. </td></tr>\n";
    if ( is_possible_domainname($tok) ) {
      $n = get_and_print_hostname(array("hostname"=>$tok));
    }
  }
  echo "</tbody></table>\n";
 }

if (false) {
  // if a site_id is given, display the site nodes only
  if( $_GET['site_id'] ) {
    $site_id= $_GET['site_id'];

    // Get site info
    $site_info= $api->GetSites( array( intval( $site_id ) ), array( "name", "person_ids" ) );

    // Get site nodes
    $persons= $api->GetPersons( $site_info[0]['person_ids'], array( "person_id", "first_name", "last_name", "email", "roles", "peer_id", "enabled" ) );
	  
    drupal_set_title("People with " . $site_info[0]['name']);

    sort_persons( $persons );

    echo paginate_trash ( $persons, "person_id", "Persons", 25, "email" );

  }
  // if no person id, display list of persons to choose
  elseif( !$_GET['id'] ) {

    // GetPersons API call
    $persons= $api->GetPersons( NULL, array( "person_id", "first_name", "last_name", "email", "roles" , "peer_id", "enabled" ) );

    sort_persons( $persons );

    echo "<div>\n
			<form method=post action='/db/persons/allinfo.php'>\n";
    if( $pers_email ) echo "<font color=red>'$pers_email' is not a valid person email.</font>\n";
    echo "<p><label for='testinput'>Query: </label>\n
			<input type='text' id='testinput' name='userquery' size=50 value='' />\n
			<input type=submit value='Search' />\n
			</div>\n
			<br />\n";
  } else {
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
	echo "<form action='/db/persons/person_action.php' method='post'>\n";
      } else {
	echo "<div class='plc-foreign'>";
      }
      echo "<input type=hidden name='person_id' value='$person_id'>\n";
		
      if ( ! $peer_id ) {
# if ( Admin, PI, or user ) allow deletion
	if( in_array( 10, $_roles ) || 
	    ( in_array( 20, $_roles ) && in_array( $site_ids[0], $_person['site_ids'] ) )  ||
	    $_person['person_id'] == $person_id) {

	  // list to take person action
	  echo "<p><select name='actions' onChange=\"submit();\">\n";
				
	  $actions= array( ''=>'Choose Action', 'delete'=>"Delete $first_name" );
	  $select_end = "</select>\n";

# if ( Admin or PI ) check whether to allow # 'enabling/disabling'.
	  if( in_array( 10, $_roles ) || 
	      ( in_array( 20, $_roles ) && in_array( $site_ids[0], $_person['site_ids'] ) ) ) { 

	    if( $enabled == true ) {
	      $actions['disable']= "Disable $first_name";
	    } else {
	      $actions['enable']= "Enable $first_name";
	      $select_end = $select_end . " &nbsp; <font color=red size=-1>".
		"<- This user is not enabled. Choose here to enable or delete.</font>";
	    }
	    if ( in_array( 10, $_roles )) {
	      $actions['su'] = "Become $first_name";
	    }
	  } 
				
# for all cases, list each 'select' key
	  foreach( $actions as $key => $val ) {
	    echo "<option value='$key'";
		
	    if( $key == $_POST['actions'] )
	      echo " selected";
		
	    echo ">$val\n";
	  }
		
	  echo $select_end;
	}
      } 
		
      // basic person info
      echo "<p><table border=0>\n";
      echo "<tr><td>First Name: </td><td> $first_name</td></tr>\n";
      echo "<tr><td>Last Name: </td><td> $last_name</td></tr>\n";
      echo "<tr><td>Title: </td><td> $title</td></tr>\n";
      echo "<tr><td>Email: </td><td><a href='mailto:$email'>$email</a></td></tr>\n";
      echo "<tr><td>Password: </td><td>***********</td></tr>\n";
      echo "<tr><td>Phone: </td><td>$phone</td></tr>\n";
      echo "<tr><td>URL: </td><td>$url</td></tr>\n";
      echo "</table>\n";
		
      if( in_array( 10, $_roles ) || $_person['person_id'] == $person_id )
	echo "<br /><a href='/db/persons/update.php?id=$person_id'>Update account information</a>\n";

      echo "<hr />\n";
			
      // keys
      $can_manage_keys = ( ( ! $peer_id ) && (in_array( "10", $_roles ) || $person_id == $_person['person_id']));
      echo "<h3>Keys</h3>\n";
      if( !empty( $key_ids ) ) {
	echo "<p><table border=0 width=450>\n";
	echo "<thead><tr><th>Type</th><th>Key</th></tr></thead><tbody>\n";
				
	foreach( $key_info as $key ) {
	  $key_type= $key['key_type'];
	  $key_id= $key['key_id'];
	  $key_text= wordwrap( $key['key'], 70, "<br />\n", 1 );
	  echo "<tr><td>$key_type</td><td>$key_text</td></tr>\n";
	}
		
	echo "</tbody></table>\n";

		
      } else {
	echo "<span class='plc-warning'> This user has no known key</span>";
      }
			
      if( $can_manage_keys )
	echo "<p><a href='/db/persons/keys.php?id=$person_id'>Manage Keys</a>\n";
      echo "<hr />";
		
		
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
	echo "<input type=submit value='Remove Sites'>\n";
		
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
			

      if ( !empty ($roles) ) {
	foreach( $proles as $role ) {
	  $role_name= $role['name'];
	  $role_id= $role['role_id'];
				
	  echo "<tr><td>$role_name";
				

	  if( in_array( 10, $_roles ) ) {
	    echo "</td><td><input type=checkbox name='rem_role[]' value='$role_id'>";

	  }
					
	  echo "</td></tr>\n";
	}
      } else {
	echo "<span class='plc-warning'> This user has no known role !!</span>";
      }
			
      echo "</tbody></table>\n";

      if ( in_array( 10, $_roles ) )
	echo "<input type=submit value='Update Roles'><br />\n";


		
      // if admin show roles to add
      if( in_array( 10, $_roles ) ) {
	$all_roles= $api->GetRoles();
	$addable_roles= arr_diff( $all_roles, $proles );
		
	if( !empty( $addable_roles ) ) {
	  echo "<p><p>Add role: <select name='add_role' onChange='submit()'>\n<option value=''>Choose a Role to add:</option>\n";
			
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
    echo "<br /><hr /><p><a href='/db/persons/index.php'>Back to person list</a></div>";

  }
 }


// Print footer
include 'plc_footer.php';

?>
