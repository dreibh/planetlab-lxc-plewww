<?php

// $Id$

// Require login
require_once 'plc_login.php';

if (get_array($_GET, 'id')) require ('tag.php') ;
else             require ('tags.php');

?>
