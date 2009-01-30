<?php

// $Id$

// Require login
require_once 'plc_login.php';

if ($_GET['id']) require ('site.php') ;
else             require ('sites.php');

?>
