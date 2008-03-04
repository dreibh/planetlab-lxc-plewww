<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
drupal_set_title('All Peers');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

// layout : add a comon link
function layout_peer ($peer) {
  $peer['comon'] = plc_comon_button("peer_id",$peer['peer_id']);
  return $peer;
}


// if peer_host is set then set id to that peer's id.
if( $_POST['peername'] ) {
  $peername= $_POST['peername'];

  $peer_info= $api->GetPeers( array( $peername ), array( "peer_id" ) );

  header( "location: index.php?id=". $peer_info[0]['peer_id'] );
  exit();

}

if( !$_GET['id'] ) {

  // GetPeers API call
  $peers = $api->GetPeers( NULL, array("peer_id","peername","peer_url"));
    
  $local_peer_comon = plc_comon_button("peer_id","0");
  echo "<p> See all local nodes through comon " . $local_peer_comon . " </p>";

  if ( empty($peers)) {
    echo "No known peer - standalone deployment";
  } else {

    $peers = array_map(layout_peer,$peers);
    sort_peers( $peers );

    echo "<div>";
    // xxx Thierry : mimicking what was done for nodes - not sure that makes sense here
    if( $peername )
      echo "<span class='plc-warning'> $peername is not a valid peer.</span>\n";

    echo paginate( $peers, "peer_id", "Peers", 10, "peername" );
  }

} else {
  // get the peer id from the URL
  $peer_id= intval( $_GET['id'] );

  // make the api call to pull that peers DATA
  $peer_info= $api->GetPeers( array( $peer_id ) );

  // peer info
  $peername= $peer_info[0]['peername'];
  $peer_url= $peer_info[0]['peer_url'];
  // arrays of ids of peer info
  $number_nodes= sizeof($peer_info[0]['node_ids']);
  $number_slices= sizeof($peer_info[0]['slice_ids']);
  $number_persons= sizeof($peer_info[0]['person_ids']);
  $number_sites= sizeof($peer_info[0]['site_ids']);

  // get peer id
  $peer_id= $peer_info[0]['peer_id'];

  drupal_set_title("Details for Peer " . $peername);

  echo "<table><tbody>\n";
  echo "<tr><th>Peer name </th>";
  echo "<td> $peername </td></tr>";
  echo "<tr><th>API url </th>";
  echo "<td> $peer_url </td></tr>";
  echo "<tr><th> # nodes </th>";
  echo "<td> $number_nodes </td></tr>";
  echo "<tr><th> # slices </th>";
  echo "<td> $number_slices </td></tr>";
  echo "<tr><th> # sites </th>";
  echo "<td> $number_sites </td></tr>";
  echo "<tr><th> # persons </th>";
  echo "<td> $number_persons </td></tr>";
  echo "</tbody></table>\n";
}

echo "<br /><p><a href='index.php'>Back to peer list</a>";
echo "</div>";


// Print footer
include 'plc_footer.php';

?>
