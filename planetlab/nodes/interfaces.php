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

$interface = array();

// If interface_id is specified, load data
if( isset( $_GET['id'] ) ) {
  $id= intval( $_GET['id'] );
  $interfaces= $api->GetInterfaces( array( $id ) );
  if( $interfaces ) {
    $interface= $interfaces[0];
    $node_id= $interface['node_id'];
  }
}

if( $_GET['node_id'] ) 
  $node_id= $_GET['node_id'];

// Override fields with specified data
foreach( array( 'method', 'type', 'ip', 'gateway', 'network', 'broadcast', 'netmask', 'dns1', 'dns2', 'hostname', 'mac', 'bwlimit', 'node_id' ) as $field ) {
  if( isset( $_POST[$field] ) ) {
    if( $_POST[$field] == "" ) {
      $interface[$field]= NULL;
    } else {
      $interface[$field]= $_POST[$field];
      if( in_array( $field, array( 'bwlimit', 'node_id' ) ) ) {
	$interface[$field]= intval( $interface[$field] );
      }
    }
  }
  if( isset( $interface[$field] ) ) {
    // E.g., $method = $interface['method'];
    $$field= $interface[$field];
  }
}

// Either interface_id or node_id must be specified in URL
if( !isset( $_GET['node_id'] ) && !( $nodes= $api->GetNodes( array( intval($node_id) ), array( 'node_id', 'hostname', 'site_id' ) ) ) ) {
  Header( "Location: index.php" );
  exit();
}


$nodes= $api->GetNodes( array( intval($node_id) ), array( 'node_id', 'hostname', 'site_id' ) );
$node= $nodes[0];

$can_update= True;
if( !in_array( 10, $_roles ) ) {
  if ( !( in_array( 20, $_roles ) || in_array( 40, $_roles ) ) || !in_array( $node['site_id'], $_person['site_ids'] ) ) {
    $can_update= False;
  }
}

if( $can_update && (isset( $_POST['submitted'] ) || isset ($_GET['submitted'])) ) {
  if( isset( $_POST['add'] ) ) {
    $api->AddInterface( intval( $node_id ), $interface );
  }
  elseif ( isset( $_POST['delete'] ) || isset( $_GET['delete']) || isset( $_POST['update'] ) ) {
    // interface_id must be specified in URL
    if( !isset( $id ) ) {
      Header( "Location: index.php?id=$node_id" );
      exit();
    }
    if( isset( $_POST['delete'] ) || isset ($_GET['delete']) ) {
      $api->DeleteInterface( $id );
    }
    elseif( isset( $_POST['update'] ) ) {
      $api->UpdateInterface( $id, $interface );
    }
  }

  $error= $api->error();

  if( !empty( $error ) ) {
    echo '<div class="plc-warning">' . $error . '.</div>';
  } else {
    Header( "Location: index.php?id=$node_id" );
    exit();
  }
  
}

// Print header
require_once 'plc_drupal.php';
drupal_set_title($node['hostname']);
include 'plc_header.php';

// Start form
$action= "interfaces.php";
if( isset( $id ) ) {
  $action.= "?id=" . $interface['interface_id'];
} 
elseif( isset($node_id)) {
  $action.= "?node_id=" . $node_id;
}

foreach( array( 'static', 'dhcp', 'proxy', 'tap', 'ipmi' ) as $option ) {
  ${$option . "_selected"} = ( $method == $option ) ? 'selected="selected"' : '';
}

// XXX Query methods and types
echo <<<EOF

<script type="text/javascript">
function updateStaticFields()
{
  var is_static= document.fm.method.options[document.fm.method.selectedIndex].text == 'Static';
  var is_tap= document.fm.method.options[document.fm.method.selectedIndex].text == 'TUN/TAP';

  document.fm.netmask.disabled= !is_static;
  document.fm.network.disabled= !is_static;
  document.fm.gateway.disabled= !is_static && !is_tap;
  document.fm.broadcast.disabled= !is_static;
  document.fm.dns1.disabled= !is_static;
  document.fm.dns2.disabled= !is_static;
}
</script>

<form action="$action" method="post" name="fm">
<table cellpadding="2">
<tbody>

<tr>
  <th>Method: </th>
  <td>
    <select name="method" onchange="updateStaticFields()">
      <option value="static" $static_selected>Static</option>
      <option value="dhcp" $dhcp_selected>DHCP</option>
      <option value="proxy" $proxy_selected>Proxy</option>
      <option value="tap" $tap_selected>TUN/TAP</option>
      <option value="ipmi" $ipmi_selected>IPMI</option>
    </select>
  </td>
</tr>

<tr>
  <th>Type: </th>
  <td>
    <select name='type' onchange='updateStaticFields()'>
      <option value="ipv4" selected="selected">ipv4</option>
    </select>
  </td>
</tr>

<tr><th>IP: </th><td><input type="text" name="ip" value="$ip" size="30" maxlength="256"/></td></tr>
<tr><th>BW Limit: </th><td> <input type="text" name="bwlimit" value="$bwlimit" size="30" maxlength="256"/></td></tr>
<tr><td colspan=2> <hr> </td></tr>
<tr><th>Gateway: </th><td> <input type="text" name="gateway" value="$gateway" size="30" maxlength="256"/></td></tr>
<tr><th>Network: </th><td> <input type="text" name="network" value="$network" size="30" maxlength="256"/></td></tr>
<tr><th>Broadcast: </th><td> <input type="text" name="broadcast" value="$broadcast" size="30" maxlength="256"/></td></tr>
<tr><th>Netmask: </th><td> <input type="text" name="netmask" value="$netmask" size="30" maxlength="256"/></td></tr>
<tr><th>DNS 1: </th><td> <input type="text" name="dns1" value="$dns1" size="30" maxlength="256"/></td></tr>
<tr><th>DNS 2: </th><td> <input type="text" name="dns2" value="$dns2" size="30" maxlength="256"/></td></tr>
<tr><th>Hostname: </th><td> <input type="text" name="hostname" value="$hostname" size="30" maxlength="256"/></td></tr>
<tr><th>MAC Address: </th><td> <input type="text" name="mac" value="$mac" size="30" maxlength="256"/></td></tr>
<tr><th>BW Limit (bps): </th><td> <input type="text" name="bwlimit" value="$bwlimit" size="30" maxlength="256"/></td></tr>

EOF;

if ($can_update) {
  echo '<tr><td/><td>';
  echo '<input type="hidden" name="submitted" value="1" />';
  if (isset($id)) {
    echo '<input type="submit" name="update" value="Update"/>';
    echo '<input type="submit" name="delete" value="Delete"/>';
  }
  echo '<input type="submit" name="add" value="Add As New"/>';
  echo '</td></tr>';
}

echo <<<EOF
</tbody>
</table>
</form>
EOF;

$is_admin=in_array( 10, $_roles );
$is_pi=in_array( 20, $_roles );
print "<hr />";

if (empty ($interface['interface_tag_ids'])) {
  print "<p> This network interface has no additional setting</p>";
  if( $is_admin || $is_pi )
    echo "<p><a href='settings.php?add=$id'>Add an Interface Setting</a></p>\n";
 } else {
  $interface_tags = $api->GetInterfaceTags($interface['interface_tag_ids']);
  sort_interface_tags ($interface_tags);
  print "<table cellpadding='5' cellspacing='5' class='list_set'><caption class='list_set'>Additional Settings</caption>";
  print "<thead><tr class='list_set'>";
  // the column for the delete button
  if( $is_admin )
    print "<th></th>";
  print "<th class='list_set'>Name</th><th class='list_set'>Category</th><th class='list_set'>Description</th><th class='list_set'>Value</th></tr></thead><tbody>";
  foreach ($interface_tags as $setting) {
    echo "<tr class='list_set'>";
    if ($is_admin) {
      echo("<td>");
      echo plc_delete_link_button('setting_action.php?rem_id=' . $setting['interface_tag_id'],
				  '\\n [ ' . $setting['tagname'] . ' = ' . $setting['value']);
      echo("</td>");
    }
    if ($is_admin || $is_pi) 
      printf ("<td class='list_set'> <a href='settings.php?id=%s'>%s </a></td>",$setting['interface_tag_id'],$setting['tagname']);
    else
      printf ("<td class='list_set'> %s </td>",$setting['tagname']);
    printf ("<td class='list_set'> %s</td><td class='list_set'> %s</td><td class='list_set'> %s </td></tr>",
	    $setting['category'],
	    $setting['description'],
	    $setting['value']);
  }
  if( $is_admin || $is_pi )
    echo "<tr><td colspan=4><a href='settings.php?add=$id'>Add a Network Setting</td</tr>\n";
  
  print "</tbody></table>";
 }

echo <<<EOF
<hr /><a href="index.php?id=$node_id">Back to Node</a>
<script type="text/javascript">
updateStaticFields();
</script>
EOF;

// Print footer
include 'plc_footer.php';

?>
