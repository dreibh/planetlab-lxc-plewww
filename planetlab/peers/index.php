<?php

// $Id$

// Require login
require_once 'plc_login.php';

if (get_array($_GET, 'id')) require ('peer.php') ;
else             require ('peers.php');

?>
