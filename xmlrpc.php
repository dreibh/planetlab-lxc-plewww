<?php
// $Id: xmlrpc.php 144 2007-03-28 07:52:20Z thierry $

/**
 * @file
 * PHP page for handling incoming XML-RPC requests from clients.
 */

include_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
include_once './includes/xmlrpc.inc';
include_once './includes/xmlrpcs.inc';

xmlrpc_server(module_invoke_all('xmlrpc'));
