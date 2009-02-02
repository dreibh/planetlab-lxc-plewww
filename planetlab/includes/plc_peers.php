<?php
  
  // $Id$

require_once 'plc_functions.php';

// all known peers hashed on peer_id
class Peers {
  var $hash;
  
  function Peers ($api) {
    $hash=array();
    // fake entry fot the local myplc
    $local_fake_peer = array ('peername' => PLC_NAME,
			      'shortname' => PLC_SHORTNAME,
			      'peer_id'=>'local');
    $hash['local']=$local_fake_peer;
    // remote
    $peer_columns=array('peer_id','shortname','peername');
    $peer_filter=NULL;
    $peers = $api->GetPeers($peer_filter,$peer_columns);
    if ($peers) foreach ($peers as $peer) {
	$hash[$peer['peer_id']]=$peer;
      }
    $this->hash=$hash;
  }

  public static function is_local ($peer) {
    return $peer['peer_id'] == 'local';
  }

  function peer ($peer_id) {
    // use the fake local entry 
    if (!$peer_id)
      $peer_id='local';
    return $this->hash[$peer_id];
  }

  public function peername ($peer_id) {
    $peer = $this->peer ($peer_id);
    return $peer['peername'];
  }

  public function shortname ($peer_id) {
    $peer = $this->peer ($peer_id);
    return $peer['shortname'];
  }

  public function label ($peer_id) {
    $peer = $this->peer ($peer_id);
    $result = $peer['peername'] . " (" . $peer['shortname'] . ")";
    if (Peers::is_local ($peer))
      $result = "[local] " . $result;
    return $result;
  }
  
  public function link ($peer_id,$text) {
    if (! $peer_id)
      return href("/",$text);
    $peer = $this->peer ($peer_id);
    return l_peer_t($peer['peer_id'],$text);
  }

  public function peer_link ($peer_id) {
    if (! $peer_id)
      return href("/",$this->label($peer_id));
    $peer = $this->peer ($peer_id);
    return l_peer_t($peer['peer_id'],$this->label($peer_id));
  }

  function classname ($peer_id) {
    if ( ! $peer_id) 
      return "";
    $peer = $this->peer ($peer_id);
    $shortname=$peer['shortname'];
    return "plc-$shortname";
  }
  
  function block_start ($peer_id) {
    // to set the background to grey on foreign objects
    // return true if the peer is local 
    if ( ! $peer_id ) {
      print "<div>";
      return true;
    } else {
      $classname=strtolower($this->classname($peer_id));
      // set two classes, one generic to all foreign, and one based on the peer's shortname for finer grain tuning
      printf ("<div class=\"plc-foreign %s\">",$classname);
      return false;
    }
  }

  function block_end ($peer_id) {
    print "</div>\n";
  }
}

class PeerScope {
  var $filter;
  var $label;

  function PeerScope ($api, $peerscope) {
    switch ($peerscope) {
    case '':
      $this->filter=array();
      $this->label="all peers";
      break;
    case 'local':
      $this->filter=array("peer_id"=>NULL);
      $this->label=PLC_SHORTNAME;
      break;
    case 'foreign':
      $this->filter=array("~peer_id"=>NULL);
      $this->label="foreign peers";
      break;
    default:
      if (my_is_int ($peerscope)) {
	$peer_id=intval($peerscope);
	$peers=$api->GetPeers(array("peer_id"=>$peer_id));
      } else {
	$peers=$api->GetPeers(array("shortname"=>$peerscope));
      }
      if ($peers) {
	$peer=$peers[0];
	$peer_id=$peer['peer_id'];
	$this->filter=array("peer_id"=>$peer_id);
	$this->label='peer "' . $peer['shortname'] . '"';
      } else {
	$this->filter=array();
	$this->label="[no such peerscope " . $peerscope . "]";
      }
      break;
    }
  }

  public function filter() {
    return $this->filter;
  }
  public function label() {
    return $this->label;
  }
}

?>
