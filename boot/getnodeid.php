<?php
//
// Returns node ID of requestor
//
// Mark Huang <mlhuang@cs.princeton.edu>
// Copyright (C) 2006 The Trustees of Princeton University
//
// $Id: getnodeid.php 144 2007-03-28 07:52:20Z thierry $ $
//

// Get admin API handle
require_once 'plc_api.php';
global $adm;

if (!empty($_REQUEST['mac_addr'])) {
  $mac_lower = strtolower(trim($_REQUEST['mac_addr']));
  $mac_upper = strtoupper(trim($_REQUEST['mac_addr']));
  $nodenetworks = $adm->GetNodeNetworks(array('mac' => array($mac_lower, $mac_upper)));
} else {
  $nodenetworks = $adm->GetNodeNetworks(array('ip' => $_SERVER['REMOTE_ADDR']));
}

if (!empty($nodenetworks)) {
  print $nodenetworks[0]['node_id'];
} else {
  print "-1";
}

?>
