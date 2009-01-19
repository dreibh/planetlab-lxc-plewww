<?php

// $Id$

// Require login
require_once 'plc_login.php';

if ($_GET['id']) require ('node.php') ;
else             require ('nodes.php');

?>
