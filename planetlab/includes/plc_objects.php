<?php

  // $Id$

function timeDiff ($timestamp,$detailed=false,$n = 0) {
  $now = time();

#If the difference is positive "ago" - negative "away"
  ($timestamp >= $now) ? $action = 'away' : $action = 'ago';
  //echo "Away: $action<br>\n";
  //if ( $timestamp >= $now //)
  //{
  //	echo "Val: greater $timestamp : $now<br>\n";
  //} else{
  //	echo "Val: less than $timestamp : $now<br>\n";
  //}


  $diff = ($action == 'away' ? $timestamp - $now : $now - $timestamp);

# Set the periods of time
  $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
  $lengths = array(1, 60, 3600, 86400, 604800, 2630880, 31570560, 315705600);

# Go from decades backwards to seconds
  $i = sizeof($lengths) - 1;				# Size of the lengths / periods in case you change them
  $time = "";						# The string we will hold our times in
  while($i >= $n) {
# if the difference is greater than the length we are checking... continue
    if ($diff > $lengths[$i-1]) {				
# 65 / 60 = 1.	That means one minute.	130 / 60 = 2. Two minutes.. etc
      $val = floor($diff / $lengths[$i-1]);		
# The value, then the name associated, then add 's' if plural
      $time .= $val ." ". $periods[$i-1].($val > 1 ? 's ' : ' ');	
# subtract the values we just used from the overall diff so we can 
# find the rest of the information
      $diff -= ($val * $lengths[$i-1]);		
# if detailed is turn off (default) only show the first set found, 
# else show all information
      if(!$detailed) { $i = 0; }		
    }
    $i--;
  }
	
# Basic error checking.
  if ($time == "") {
    return "error: bad time";
  } else {
    return $time.$action;
  }
}

class PlcObject {
  public static function constructList($cname, $list) {
    $ret_list = array();
    foreach ($list as $item) {
      $ret_list[] = new $cname ($item);
    }
    return $ret_list;
  }
}


class Person {
  var $roles;
  var $person_id;
  var $first_name; 
  var $last_name;
  var $email;
  var $enabled;

  function Person($person) {
    $this->roles = $person['role_ids'];
    $this->person_id = $person['person_id'];
    $this->first_name = $person['first_name'];
    $this->last_name = $person['last_name'];
    $this->email = $person['email'];
    $this->enabled = $person['enabled'];
    $this->data = $person;
  }

  public static function getPIs($persons) {
    $pis = array();
    foreach( $persons as $person ) {
      $role_ids= $person->roles;

      if ( in_array( '20', $role_ids ) && $person->enabled )
	$pis[$person->person_id]= $person->email;
    }
    return $pis;
  }

  public static function getTechs($persons) {
    $techs = array();
    foreach( $persons as $person ) {
      $role_ids= $person->roles;
      if( in_array( '40', $role_ids ) && $person->enabled )
	$techs[$person->person_id]= $person->email;
    }
    return $techs;
  }

  function getSites() {
    return $this->data['site_ids'];
  }
  function isMember($site_id) {
    return in_array($site_id, $this->data['site_ids']);
  }

  function isAdmin() {
    return in_array( '10', $this->roles);
  }
  function isPI() {
    return in_array( '20', $this->roles);
  }
  function isUser() {
    return in_array( '30', $this->roles);
  }
  function isTech() {
    return in_array( '40', $this->roles);
  }

  function link($str) {
    return "<a href='/db/persons/index.php?id=" . $this->person_id . "'>" . $str . "</a>";
  }

  function display() {
    $person = array();
    $person[] = $this->first_name . " " . $this->last_name;
    $person[] = $this->link($this->email);
    return $person;
  }
}


class PCU {
  var $data;

  function PCU($pcu) {
    $this->data = $pcu;
  }

  function deletePCUlink($node) {
    return "<a href='/db/sites/index.php?id=" . $node->site_id . 
      "&delete_node_from_pcu=" . $node->node_id . 
      "&pcu_id=" . $this->data['pcu_id'] . "'>&nbsp;Remove from PCU</a>";
  }
  function pcu_name() {
    if ( $this->data['hostname'] != NULL and $this->data['hostname'] != "" ):
      return $this->data['hostname'];
    else: 
      if ( $this->data['ip'] != NULL and $this->data['ip'] != "" ):
	return $this->data['ip'];
      else:
	return "NO-HOSTNAME-OR-IP";
    endif;
    endif;
  }

  function link($str) {
    return "<a href='/db/sites/pcu.php?id=" . $this->data['pcu_id'] . "'>" . $str . "</a>";
  }

  function host() {
    return substr($this->data['hostname'], 0, strpos($this->data['hostname'], '.'));
  }
}

class Address {
  var $data;

  function Address($address) {
    $this->data = $address;
  }

  function link($str) {
    return "<a href='/db/addresses/index.php?id=" . $this->data['address_id'] . "'>" . $str . "</a>";
  }
	
  function label() {
    $label = "";
    $comma= sizeof( $this->data['address_types'] );
    $count= 0;
    foreach( $this->data['address_types'] as $add_type ) {
      $label .= $add_type;
      $count++;
      if ( $comma > 0 && $count != $comma )
	$label .= ", ";
    }
    return $label;
  }

}


class Node extends PlcObject {
  var $node_id;
  var $hostname;
  var $boot_state;
  var $date_created;
  var $last_updated;
  var $last_contact;
  var $site_id;
  var $pcu_ids;
  var $data;

  function Node($node) {
    global $plc, $api, $adm;
    $this->data = $node;
    $this->model = $node['model'];
    $this->node_id = $node['node_id'];
    $this->hostname = $node['hostname'];
    $this->boot_state = $node['boot_state'];
    $this->date_created = $node['date_created'];
    $this->last_updated = $node['last_updated'];
    $this->last_contact = $node['last_contact'];
    $this->site_id = $node['site_id'];
    $this->pcu_ids = $node['pcu_ids'];
    $this->nn = $api->GetNodeNetworks($node['nodenetwork_ids']);
    foreach ($this->nn as $nnet)
      {
	if ( $nnet['is_primary'] == true )
	  {
	    $this->ip = $nnet['ip'];
	    $this->netmask = $nnet['netmask'];
	    $this->network = $nnet['network'];
	    $this->gateway= $nnet['gateway'];
	    $this->broadcast = $nnet['broadcast'];
	    $this->dns1 = $nnet['dns1'];
	    $this->dns2 = $nnet['dns2'];
	    $this->method = $nnet['method'];
	  }
      }
  }

  public static function filter($nodes, $nodes_listed) {
    $ret = array();
    foreach ($nodes as $node) {
      if ( ! in_array($node, $nodes_listed) ) {
	$ret[] = $node;
      }
    }
    return $ret;
  }

  function link($str) {
    return "<a href='/db/nodes/index.php?id=" . $this->node_id . "'>" . $str . "</a>";
  }
  function pcuport($pcu) {
    $count = 0;
    foreach ( $pcu->data['node_ids'] as $node_id ) {
      if ( $node_id == $this->node_id ) {
	return $pcu->data['ports'][$count];
      }
      $count += 1;
    }
    return 0;
  }

  function hasPCU($pcu) {
    $pcu_id = $pcu->data['pcu_id'];
    return in_array( $pcu_id, $this->pcu_ids );
  }
  function dateCreated() {
    $date_created = date("M j, Y", $this->date_created);
    return $date_created;
  }
  function lastUpdated() {
    return $this->timeaway($this->last_updated);
  }
  function lastContact() {
    return $this->timeaway($this->last_contact);
  }
  function stale() {
    $now = time();
	$STALE_LENGTH = 60*60;	/* TODO: set by some policy */
    if ( $this->last_contact + $STALE_LENGTH > $now ) {
	    return False;
	} else {
	    return True;
	}
  }
  function timeaway($val) {
    if ( $val != NULL ) {
      $ret = timeDiff(intval($val));
    } else {
      $ret = "Never";
    }
    return $ret;
    
  }
}

class Slice {
  var $data;

  function Slice($val) {
    $this->data = $val;
  }

  //		<!--sort_slices( $slices ); -->
  function dateCreated() {
    $date_created = date("M j, Y", $this->data['created']);
    return $date_created;
  }

  function expires() {
    if ( $this->data['expires'] != 0 ) {
      $expires = timeDiff(intval($this->data['expires']));
    } else {
      $expires = "Never";
    }
    return $expires;
  }
}

class Site extends PlcObject {
  var $address_ids;
  var $pcu_ids;
  var $node_ids;
  var $person_ids;
  var $slice_ids;
  var $enabled;
  var $peer_id;
  var $site_id;
  var $data;

  function Site($site_id) {
    global $plc, $api, $adm;
    $site_info= $adm->GetSites( array( intval($site_id) ) );
    $this->data = $site_info[0];

    $this->site_id = intval($site_id);
    $this->site_name = $site_info[0]['name'];
    $this->address_ids = $site_info[0]['address_ids'];
    $this->pcu_ids = $site_info[0]['pcu_ids'];
    $this->node_ids = $site_info[0]['node_ids'];
    $this->person_ids = $site_info[0]['person_ids'];
    $this->slice_ids = $site_info[0]['slice_ids'];
    $this->enabled = $site_info[0]['enabled'];
    $this->peer_id = $site_info[0]['peer_id'];
  }

  function getSiteObjects() {
    global $plc, $api, $adm;
    $adm->begin();
    $adm->GetAddresses( $this->address_ids );
    $adm->GetPCUs( $this->pcu_ids );
    $adm->GetNodes( $this->node_ids, array( "node_id", "hostname", "boot_state",
					    "date_created", "last_updated", "last_contact", "site_id", "pcu_ids" ) );
    $adm->GetPersons( $this->person_ids, array( "role_ids", "person_id", "first_name", 
						"last_name", "email", "enabled" ) );
    $adm->GetSlices( $this->slice_ids, array( "name", "slice_id", "instantiation", "created", "expires" ) );
    return $adm->commit();
  }
}

/* class Blue extends PlcObject
 {
 var $val;
 function Blue($arg)
 {
 $this->val = $arg;
 }
 }

 $cl = PlcObject::constructList('Blue', array('this', 'is', 'a', 'test'));
 echo sizeof($cl) . "\n";
 foreach ($cl as $obj)
 {
 echo get_class( $obj) . "\n";
 echo $obj->val . "\n";
 }*/


?>
