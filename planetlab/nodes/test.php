<?php

// $Id: test.php 152 2007-03-28 13:26:00Z thierry $

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// input 
$input = strtolower( $_GET['input'] );
$len = strlen($input);

// init result
$aResults = array();

// dont query the db on empty input
if ($len) {
  // query db
  $input .= "%";
  $nodes= $adm->GetNodes( array( "hostname" => $input ), array("hostname") );
  if (count($nodes)) {
    foreach ( $nodes as $node ) {
      $aResults[] = $node['hostname'];
    }
  }
}

header("Content-Type: text/xml");

echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>
<results>";
for ($i=0;$i<count($aResults);$i++)
  echo"	<rs>".$aResults[$i]."</rs>";

echo "
</results>
";

?>
