<?php

// $Id$

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// input 
$input = strtolower( get_array($_GET, 'input') );
$len = strlen($input);

// init result
$aResults = array();

// dont query the db on empty input
if ($len) {
  // query db
  $input .= "%";
  $persons= $adm->GetPersons( array( "email" => $input ), array("email") );
  if (count($persons)) {
    foreach ( $persons as $person ) {
      $aResults[] = $person['email'];
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
