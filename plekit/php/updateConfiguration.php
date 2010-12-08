<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

//print header
require_once 'plc_drupal.php';

// Common functions
require_once 'plc_functions.php';

$value=$_GET["value"];
$person_id=intval($_GET["person_id"]);
$slice_id=$_GET["slice_id"];
$tag_id=intval($_GET["tag_id"]);
$tag_name=$_GET["tag_name"];

#$res = $api->UpdatePersonTag( $tag_id, $value );
if ($tag_name == "columnconf")
$res = $api->SetPersonColumnconf( $person_id, $value );
else if ($tag_name == "showconf")
$res = $api->SetPersonShowconf( $person_id, $value );

$myFile = "/var/log/myslice.log";
if (file_exists($myFile))
	$fh = fopen($myFile, 'a') or die("can't open file");

$stringData = "\n".date('Ymd-H:i')."|".$person_id.":".$slice_id.":".$value;
fwrite($fh, $stringData);
fclose($fh);

?> 
