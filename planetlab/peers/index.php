<?php

// $Id$

// Require login
require_once 'plc_login.php';
require_once 'plekit-utils.php';

if (get_array($_GET, 'id')) require ('peer.php') ;
else             require ('peers.php');

?>
