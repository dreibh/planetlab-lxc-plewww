<?php

// $Id$

// Require login
require_once 'plc_login.php';

if ($_GET['id']) require ('person.php') ;
else             require ('persons.php');

?>
