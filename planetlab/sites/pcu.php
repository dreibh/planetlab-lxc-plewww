<?php

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

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];


// if no id: add, else: display(update)
if( !$_GET['id'] ) {
  if( $_POST['submitted'] ) {
    // get person's site id
    $site_id= $_person['site_ids'][0];
    
    // build dict
    $fields= array( 'protocol'=>$_POST['protocol'], 'hostname'=>$_POST['hostname'], 'model'=>$_POST['model'], 'username'=>$_POST['username'], 'password'=>$_POST['password'], 'notes'=>$_POST['notes'], 'ip'=>$_POST['ip'] );
    
    $pcu_id= $api->AddPCU( $site_id, $fields );
    
    if( $pcu_id != 0 ) {
      // xxx is l_pcu defined & effective ?
      plc_redirect( l_pcu($pcu_id));
      exit();
    } else {
      $error= $api->error();
    }
  }
  
  if( !empty( $error ) )
    echo '<div class="plc-warning">' . $error . '.</div>';
  
  echo "<h3>Add a PCU</h3>\n
<form action='/db/sites/pcu.php' method=post>\n
<p><table border=0 cellpadding=3><tbody>\n
<tr><th>Protocol: </th><td><input type=text name='protocol' value=''></td></tr>\n
<tr><th>Hostname: </th><td><input type=text name='hostname' size=40 value=''></td></tr>\n
<tr><th>IP Address: </th><td><input type=text name='ip' size=40 value=''></td></tr>\n
<tr><th>Model: </th><td><input type=text name='model' value=''></td></tr>\n
<tr><th>Username: </th><td><input type=text name='username' value=''></td></tr>\n
<tr><th>Password: </th><td><input type=text name='password' value=''></td></tr>\n
<tr><th>Notes: </th><td><input type=text name='notes' size=40 value=''></td></tr>\n
</tbody></table>\n
<br /><p><input type=submit name='submitted' value='Add New PCU'>\n";
} else {
  // get PCU info
  $pcu_id= intval( $_GET['id'] );
  $pcu_info= $api->GetPCUs( array( intval( $pcu_id ) ) );
  
  // if remove is set remove the node from the pcu
  if( $_GET['remove'] ) {
    $rem_id= $_GET['remove'];
    
    $api->DeleteNodeFromPCU( intval( $rem_id ), $pcu_id );
    
    plc_redirect (l_pcu ($pcu_id));
    
  }
  
  //if submitted, update node info
  if( $_POST['submitted'] ) {
    $protocol= $_POST['protocol'];
    $username= $_POST['username'];
    $hostname= $_POST['hostname'];
    $ipaddress= $_POST['ip'];
    $model= $_POST['model'];
    $password= $_POST['password'];
    $notes= $_POST['notes'];
    
    $api->UpdatePCU( $pcu_id, array( "protocol"=>$protocol, "hostname"=>$hostname, "model"=>$model, "password"=>$password, "notes"=>$notes, "ip"=>$ipaddress ) );
		
    plc_redirect (l_pcu($pcu_id));
		
  }
	
  if( in_array( 10, $_roles) ) {
      $is_admin= true;
  } else {
      $is_admin = false;
  }

  if( in_array( 10, $_roles ) || ( in_array( 20, $_roles ) && in_array( $pcu_info[0]['site_id'], $_person['site_ids'] ) ) || ( in_array( 40, $_roles ) && in_array( $pcu_info[0]['site_id'], $_person['site_ids'] ) ) )
    $pcu_controller= true;

  // get PCU node info
  $node_info= $api->GetNodes( $pcu_info[0]['node_ids'], array( "hostname", "node_id", "boot_state" ) );
  echo "<form action='/db/sites/pcu.php?id=$pcu_id' method=post>\n
<h3>PCU: ". $pcu_info[0]['hostname'] ."</h3>\n
<p><table border=0 cellpadding=3><tbody>\n
<tr><th>Protocol: </th><td>";

  if( $pcu_controller )
    echo "<input type=text name='protocol' value='";
  
  echo $pcu_info[0]['protocol'];
  
  if( $pcu_controller )
    echo "'>";
  
  echo "</td></tr>\n
        <tr><th>Hostname: </th><td>";
  
  if( $pcu_controller )
    echo "<input type=text name='hostname' size=40 value='";
  
  echo $pcu_info[0]['hostname'];
  
  echo "'>";
  
  echo "</td></tr>\n
	<tr><th>IP Address: </th><td>";
  if( $pcu_controller )
    echo "<input type=text name='ip' value='";
  
  echo $pcu_info[0]['ip'];
  
  echo "'>";
  
  if( $pcu_controller )
    echo "</td></tr>\n
	  <tr><th>Model: </th><td>";
  
  // NOTE: in general, this value should not be edited, so only allow admins.
  if( $pcu_controller && $is_admin )
    echo "<input type=text name='model' value='";
		
  echo $pcu_info[0]['model'];
  
  if( $pcu_controller && $is_admin )
    echo "'>";
  
  echo "</td></tr>\n
	<tr><th>Username: </th><td>";
	
  if( $pcu_controller )
    echo "<input type=text name='username' value='";
  
  echo $pcu_info[0]['username'];
  
  if( $pcu_controller )
    echo "'>";
  
  echo "</td></tr>\n
	<tr><th>Password: </th><td>";
	
  if( $pcu_controller )
    echo "<input type=text name='password' value='";
		
  echo $pcu_info[0]['password'];
  
  if( $pcu_controller )
    echo "'>";
  
  echo "</td></tr>\n
	<tr><th>Notes: </th><td>";
  
  if( $pcu_controller )
    echo "<input type=text name='notes' size=40 value='";
		
  echo $pcu_info[0]['notes'];
  
  if( $pcu_controller )
    echo "'>";
		
  echo "</td></tr>\n
	</tbody></table>\n";
		
  if( $pcu_controller )
    echo "<br /><p><input type=submit name='submitted' value='Update PCU'>\n";
	
  if( !empty( $node_info ) ) {
    echo "<p><table border=0 cellpadding=3>\n<caption>Nodes</caption>\n<thead><tr><th></th><th>Hostname</td><th>State</th><th></th>";
    
    // if user can control PCU add table cells
    if( $pcu_controller )
      echo "<th></th>\n";
    
    echo "</tr></thead><tbody>\n";
    
    // for port numbers
    $count= 0;
    
    foreach( $node_info as $node ) {
      echo "<tr><td>". $pcu_info[0]['ports'][$count] ."</td><td><a href='/db/nodes/index.php?id=". $node['node_id'] ."'>". $node['hostname'] ."</a></td><td class='list_set'>". $node['boot_state'] ."</td>";

      if( $pcu_controller )
	echo "<td><a href='/db/sites/pcu.php?id=$pcu_id&remove=". $node['node_id'] ."' onclick=\"javascript:return confirm('Are you sure you want to remove node ". $node['hostname'] ." from this PCU?')\">remove</a></td>\n";

      echo "</tr>\n";
			
      $count++;
	
    }
    echo "</tbody></table><br />\n";
    
  } else {
    echo "<p>No nodes on PCU.";
  }

  
  echo "<br /><p><a href='/db/sites/index.php?id=". $pcu_info[0]['site_id'] ."'>Back to Site</a>\n";

}



// Print footer
include 'plc_footer.php';

?>
