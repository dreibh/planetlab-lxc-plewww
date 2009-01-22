<?php

// $Id: index.php 11603 2009-01-19 16:44:53Z thierry $

// Require login
require_once 'plc_login.php';

if ($_GET['id']) require ('peer.php') ;
else             require ('peers.php');

?>
