<?php

// $Id: test.php 162 2007-03-29 07:18:49Z thierry $

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// input 
$input = $_GET['input'];
$len = strlen($input);

// init result
$aResults = array();

// dont query the db on empty input
if ($len) {
  // query db
  $input .= "%";
  $sites= $adm->GetSites( array( "name" => $input ), array("name") );
  if (count($sites)) {
    foreach ( $sites as $site ) {
      $aResults[] = $site['name'];
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
