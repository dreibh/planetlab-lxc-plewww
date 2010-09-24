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
$slice_id=intval($_GET["slice_id"]);
$tagN=$_GET["tagName"];

$fields= array( "$tagN"=>$value);
$api->UpdateSlice( $slice_id , $fields );

$myFile = "/var/log/myslice-log";
$fh = fopen($myFile, 'a') or die("can't open file");
$stringData = "\n".date('Ymd-H:i')."|".$slice_id.":".$value;
fwrite($fh, $stringData);
fclose($fh);

?> 
