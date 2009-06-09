<?php
  
  // $Id$

require_once 'plc_functions.php';

drupal_set_html_head('<link href="/planetlab/css/plc_peers.css" rel="stylesheet" type="text/css"/>');

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
    $shortname=strtolower($this->shortname($peer_id));
    return "peer-$shortname";
  }
  
  function block_start ($peer_id) {
    // start a <div> element with 2 classes:
    // (1) generic:  is either peer-local or peer-foreign
    // (2) specific: is peer-<shortname> based on the plc's shortname
    // e.g. at PLE we'd get <div class='peer-local peer-ple'>
    // or		    <div class='peer-local peer-plc'>
    // see plc_styles.css for how to have the more specific ones override the generic one
    if ( ! $peer_id ) 
      $generic='peer-local';
    else
      $generic='peer-foreign';
    $specific=$this->classname($peer_id);
    // add nifty-big for the rounded corner
    printf ("<div class='$generic $specific nifty-big'>");
  }

  function block_end ($peer_id) {
    print "</div>\n";
  }

  // writes a cell in the table with the peer's shortname, link to the peer page, 
  // and classname set for proper color
  function cell ($table, $peer_id) {
    $shortname=$this->shortname($peer_id);
    $table->cell ($this->link($peer_id,$shortname),
		  array('class'=>$this->classname($peer_id)));
  }
  
}

////////////////////////////////////////////////////////////
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
