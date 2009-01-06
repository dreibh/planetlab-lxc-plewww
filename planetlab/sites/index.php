<?php
// $Id$
//

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// Print header
require_once 'plc_drupal.php';
// set default
drupal_set_title('Sites');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

////////////////////
// The set of columns to fetch
// and the filter applied for fetching sites
if ( !in_array( '10', $_roles ) ) {
  $columns = array("site_id", "name", "abbreviated_name", "login_base" , "peer_id" );
  $filter = array ("enabled" => TRUE);
 } else {
  $columns = array("site_id", "name", "abbreviated_name", "login_base" , "peer_id" , "enabled", "person_ids", "max_slices", "slice_ids", "node_ids");
  $filter = array ();
 }


//////////////////
// perform post-processing on site objects as returned by GetSites
// performs sanity check and summarize the result in a single column
// performs in-place replacement, so passes a reference
function layout_site ($site) {

  // we need the 'enabled' field to do this
  // so regular users wont run this
  if ( ! array_key_exists ('enabled', $site))
    return $site;
    
  $messages=array();
  
  if (empty ($site['node_ids'])) $messages [] = "Site has no node";
  $class='plc-foreign';

  // do all this stuff on local sites only
  if ( ! $site['peer_id'] ) {
    
    $class='plc-warning';

    // check that site is enabled
    if ( ! $site['enabled']) {
      $messages [] = "Not enabled";
    }
  
    // check that site has at least a PI and a tech
    global $api;
    $persons=$api->GetPersons(array("person_id"=>$site['person_ids']),array("role_ids"));
    $nb_pis=0;
    $nb_tech=0;
    
    if ( ! empty ($persons)) {
      foreach ($persons as $person) {
	if (in_array( '20', $person['role_ids'])) $nb_pis += 1;
	if (in_array( '40', $person['role_ids'])) $nb_techs += 1;
      }
    }
    if ($nb_pis == 0) $messages [] = "Site has no PI";
    if ($nb_techs == 0) $messages [] = "Site has no Tech";
    
    // check number of slices
    if ( $site['max_slices'] == 0) {
      $messages [] = "No slice allowed";
    } else if (count($site["slice_ids"]) >= $site['max_slices']) {
      $messages [] = "Site has no slice left";
    }
    
    // pretty print the cell
  }
  // but always cleanup $site columns
  unset ($site['enabled']);
  unset ($site['person_ids']);
  unset ($site['max_slices']);
  unset ($site['slice_ids']);
  unset ($site['node_ids']);
  $site['sanity check'] = plc_make_table($class,$messages);
  
  return $site;
}


// if sitepattern or peerscope is set : search the sites
// we use GET rather than POST so paginate can display the right contents on subsequent pages
// can be useful for writing bookmarkable URL's as well
if( $_GET['sitepattern'] || $_GET['peerscope']) {
  $sitename= $_GET['sitepattern'];
  if (empty($sitename)) { 
    $sitename="*";
  }
  $filter = array_merge (array( "name"=>$sitename ), $filter);
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

  $sites= $api->GetSites( $filter , $columns);
  $sites = array_map(layout_site,$sites);

  $sites_count = count ($sites);
  if ( $sites_count == 1) {
    header( "location: index.php?id=". $sites[0]['site_id'] );
    exit();
  } else if ( $sites_count == 0) {
    echo "<span class='plc-warning'> No site matching $sitename on " . $peer_label . " </span>";
  } else {
    drupal_set_title ("Sites matching $sitename on " . $peer_label);
    sort_sites ($sites);
    echo paginate( $sites, "site_id", "Sites", 25, "name");
  }
}


// if no site id, display list of sites to choose
elseif( !$_GET['id'] ) {

  // GetSites API call
  // careful, need to pass NULL and *not* array() if no filter is given
  $sites= $api->GetSites( empty($filter) ? NULL : $filter, $columns );
  $sites = array_map(layout_site,$sites);
  if ( empty ($sites)) {
    echo "No site to display";
  } else {
    sort_sites( $sites );

    drupal_set_html_head('<script type="text/javascript" src="/planetlab/bsn/bsn.Ajax.js"></script>
    <script type="text/javascript" src="/planetlab/bsn/bsn.DOM.js"></script>
    <script type="text/javascript" src="/planetlab/bsn/bsn.AutoSuggest.js"></script>');
    
    echo "<div>\n
        <form method=get action='index.php'>\n";
    echo "<table><tr>\n
<th><label for='testinput'>Enter Site Name or pattern: </label></th>\n
<td><input type='text' id='testinput' name='sitepattern' size=40 value='' /></td>\n
<td rowspan=2><input type=submit value='Search Sites' /></td>\n
</tr> <tr>
<th><label for='peerscope'>Federation scope: </label></th>\n
<td><select id='peerscope' name='peerscope' onChange='submit()'>\n
";
    echo plc_peers_option_list($api);
    echo "</select></td>\n
</tr></table></form></div>\n
<br />\n";
    
    echo paginate( $sites, "site_id", "Sites", 25, "name" );

    echo "<script type=\"text/javascript\">\n
var options = {\n
	script:\"/planetlab/sites/test.php?\",\n
	varname:\"input\",\n
	minchars:1\n
};\n
var as = new AutoSuggest('testinput', options);\n
</script>\n";
  }
}
else {
  // stores get variable and casts it as int
  $site_id= intval( $_GET['id'] );

  // api call GetSites
  $site_info= $adm->GetSites( array( $site_id ) );

  // var names to api return
  $sitename= $site_info[0]['name'];
  $abbrev_name= $site_info[0]['abbreviated_name'];
  $site_url= $site_info[0]['url'];
  $login_base= $site_info[0]['login_base'];
  $site_lat= $site_info[0]['latitude'];
  $site_long= $site_info[0]['longitude'];
  $max_slivers= $site_info[0]['max_slivers'];
  $max_slices= $site_info[0]['max_slices'];

  $enabled = $site_info[0]['enabled'];

  // peer id
  $peer_id= $site_info[0]['peer_id'];

  $site_addresses= $site_info[0]['address_ids'];
  $site_pcus= $site_info[0]['pcu_ids'];
  $site_nodes= $site_info[0]['node_ids'];
  $site_persons= $site_info[0]['person_ids'];
  $site_slices= $site_info[0]['slice_ids'];

  $adm->begin();
  // gets address info
  $adm->GetAddresses( $site_addresses );

  // gets pcu info
  $adm->GetPCUs( $site_pcus );

  // gets node info
  $adm->GetNodes( $site_nodes, array( "node_id", "hostname", "boot_state" ) );

  // gets person info
  $adm->GetPersons( $site_persons, array( "role_ids", "person_id", "first_name", "last_name", "email", "enabled" ) );

  $adm->GetSlices ( $site_slices, array ("slice_id", "name", "instantiation" ) );

  list( $addresses, $pcus, $nodes, $persons, $slices )= $adm->commit();
  
  $techs = array();
  $pis = array();
  foreach( $persons as $person ) {
    $role_ids= $person['role_ids'];
    if( in_array( '40', $role_ids ))
      $techs[] = $person;

    if( in_array( '20', $role_ids ))
      $pis[] = $person;

  }

  if( $peer_id ) {
    echo "<div class='plc-foreign'>\n";
  }

  // start form
  drupal_set_title("Site $sitename details");
  //  echo "<h3>$sitename Site details</h3>\n";

  if( !$peer_id ) {
    $actions= array( ''=>'Choose Action' );
    
    if( in_array( 10, $_roles ) 
	|| ( in_array( 20, $_roles ) && in_array( $site_id, $_person['site_ids'] ) ) ) {
      $actions['update']= 'Update Site';
    }
    
    if( in_array( 10, $_roles ) ) {
      $actions['delete']= 'Delete Site';
      $actions['expire']= 'Expire All Slices';
    }
    
    echo "<table> <tr><td>";
    if( in_array( 10, $_roles ) ) {
      echo plc_event_button("Site","site",$site_id);
      echo "</td><td>\n";
    }
    echo plc_comon_button("site_id",$site_id);
    // comon link
    echo "</td><td>";

    // list to take site action
    echo "<form action='/db/sites/site_action.php' method='post'>\n";
    echo "<input type=hidden name=site_id value=$site_id>\n";
    echo "<select name='actions' onChange=\"submit();\">\n";
    foreach( $actions as $key => $val ) {
      echo "<option value='$key'";
      if( $key == $_POST['actions'] ) {
	echo " selected";
      }
      echo ">$val\n";
    }
    echo "</select>\n";
    echo "</form>";
    
    echo "</td></tr></table>\n";
  }
	

  if ( ! $enabled ) {
    echo "<p class='plc-warning'> This site is not enabled - Please visit <a href='/db/sites/join_request.php'> this page </a> to review pending applications. </p>";
  }

  // basic site info
  echo "<p><table class='list_set' border=0>\n
  <tr class='list_set'><th>Full name: </th><td class='list_set'> $sitename</td></tr>\n
  <tr class='list_set'><th>Login base: </th><td class='list_set'> $login_base</td></tr>\n
  <tr class='list_set'><th>Abbreviated Name: </th><td class='list_set'> $abbrev_name</td></tr>\n
  <tr class='list_set'><th>URL: </th><td class='list_set'> <a href='$site_url'>$site_url</a></td></tr>\n
  <tr class='list_set'><th>Latitude: </th><td class='list_set'> $site_lat</td></tr>\n
  <tr class='list_set'><th>Longitude: </th><td class='list_set'> $site_long</td></tr>\n";
	
  if( $peer_id ) {
    // display peer name
    echo "<tr><th></th><td></td></tr>\n";
    $peer = $api->GetPeers(array('peer_id'=>$peer_id));
    echo "<tr><th>Managed at foreign peer:</th><td>" . $peer[0]['peername'] . "</td></tr>";
    // we wrap up everything here
    // the local version closes the table in the middle of the page...
    echo "</table>";
    echo "<br /></div>";
  
  } else {

    // Slices
    echo "<tr><th></th><td></td></tr>\n";
    $href="'/db/slices/index.php?site_id=" . $site_id . "'";
    $slice_text="" . count($site_slices) . "/" . $max_slices;
    if (count($site_slices) >= $max_slices) {
      $slice_text .= " <span class='plc-warning'>Maximum number of slices reached !</span>";
    }
    printf("<tr><th><a href=%s># Slices Used/Allocated:</a> </th><td> <a href=%s>%s</a></td></tr>\n",$href,$href,$slice_text);
    if ( ! empty ($site_slices)) {
      foreach ($slices as $slice) {
	$href="'/db/slices/index.php?id=" . $slice['slice_id'] . "'";
	printf ("<tr><td><a href=%s> %s</a></td><td> <a href=%d> %s </a> </td> </tr>\n",
		$href,$slice['name'],$href,$slice['instantiation']);
      }
    }

    // Users
    echo "<tr><th></th><td></td></tr>\n";
    $href="'/db/persons/index.php?site_id=" . $site_id . "'";
    printf ("<tr><th> <a href=%s># Users</a></th><td><a href=%s>Total %d users</a></td>\n",$href,$href,count($site_persons));

    echo "<tr><th>PI(s)</th><td>";
    if ( ! $pis) {
      echo "<span class='plc-warning'>Site has no PI !!</span>";
    } else {
      echo "<table border=0>";
      foreach( $pis as $person ) {
	echo "<tr><td>";
	if ( ! $person['enabled'] ) {
	  printf("<span class='plc-warning'> <a href='/db/persons/index.php?id=%d'>%s</a> (not enabled yet)</span><br />\n",$person['person_id'],$person['email']);
	} else {
	  printf("<a href='/db/persons/index.php?id=%d'>%s</a><br />\n",$person['person_id'],$person['email']);
	}
	echo "</td></tr>\n";
      }
      echo "</table>\n";
    }
    echo "</td></tr>\n";

    echo "<tr><th>Tech(s)</th><td>";
    if ( ! $techs) {
      echo "<span class='plc-warning'>Site has no Technical contact !!</span>";
    } else {
      echo "<table border=0>";
      foreach( $techs as $person ) {
	echo "<tr><td>";
	if ( ! $person['enabled'] ) {
	  printf("<span class='plc-warning'> <a href='/db/persons/index.php?id=%d'>%s</a> (not enabled yet)</span><br />\n",$person['person_id'],$person['email']);
	} else {
	  printf("<a href='/db/persons/index.php?id=%d'>%s</a><br />\n",$person['person_id'],$person['email']);
	}
	echo "</td></tr>\n";
      }
      echo "</table>\n";
    }
    echo "</td></tr>\n";

    // Nodes 
    echo "<tr><th></th><td></td></tr>\n";
    $href="'/db/nodes/index.php?site_id=" . $site_id . "'";
    printf ("<tr><th><a href=%s># Nodes</a></th>",$href);
    if (empty ($site_nodes)) {
      $right_site = in_array($site_id,$_person['site_ids']);
      $right_role = in_array(30,$_roles) || in_array(40,$_roles);
      $can_add = $right_site && $right_role;
      if ($can_add) {
	$href_add_node = "'/db/nodes/add_node.php'";
	printf ("<td><a href=%s>0 <span class='plc-warning'>Site has no node, please add one</span></a></td>",$href_add_node); 
      } else {
	printf ("<td><a href=%s>0 <span class='plc-warning'>Site has no node</span></a></td>",$href);
      }
    } else {
      printf ("<td><a href=%s>%d</a></td>",$href,count($nodes));
    }
    echo "</tr>";

    if( !empty( $site_nodes ) ) {
      foreach( $nodes as $node ) {
	echo "<tr><td><a href='/db/nodes/index.php?id=". $node['node_id'] ."'>". $node['hostname'] ."</a></td><td class='list_set'>". $node['boot_state'] ."</td></tr>\n";
      }
      
    }
    
    echo "</table>\n";

    // Addresses
    echo "<hr />\n";
    
    // if there is an address list it.
    if( !empty( $addresses ) ) {
      echo "<h4>Addresses</h4>\n";
      
      foreach( $addresses as $address ) {
	echo "<p><table cellpadding=2><tbody><tr><td><a href='/db/addresses/index.php?id=". $address['address_id'] ."'>";
	
	$comma= count( $address['address_types'] );
	$count= 0;
	foreach( $address['address_types'] as $add_type ) {
	  echo $add_type;
	  $count++;
	  if ( $comma > 0 && $count != $comma )
	    echo ", ";
	}
	
	echo "</a></td></tr>\n<tr><td>". $address['line1'] ."</td></tr>\n</tr><td>". $address['line2'] ."</td></tr>\n<tr><td>". $address['line3'] ."</td></tr>\n<tr><td>". $address['city'] .", ". $address['state'] ." ". $address['postalcode'] ."</td></tr>\n<tr><td>". $address['country'] ."</td></tr></tbody></table></p>\n";
      }
      
    }
    else {
      echo "Site has no addresses. \n";
    }
    
    // if eligable display add address
    /*if( in_array( '10', $_roles ) || in_array( '20', $_roles ) )
     echo "<br /><p><a href='/db/addresses/add_address.php'>Add an address</a>\n";*/
    
  }
  
  echo "<br /><hr /><p><a href='/db/sites/index.php'>Back to site list</a>";

}


// Print footer
include 'plc_footer.php';

?>
