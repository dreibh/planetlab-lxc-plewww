<?php

// $Id$

// Require login
require_once 'plc_login.php';

if ($_GET['id']) require ('tag.php') ;
else             require ('tags.php');

?>
