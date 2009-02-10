<?php

// $Id$

// Require login
require_once 'plc_login.php';

// the choser form is expected to set _GET['type'] among other stuff
if ($_GET['type']) require ('events.php') ;
else             require ('events_choser.php');

?>
