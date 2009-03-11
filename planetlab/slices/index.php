<?php

// $Id$

// Require login
require_once 'plc_login.php';

if ($_GET['id']) require ('slice.php') ;
else             require ('slices.php');

?>
