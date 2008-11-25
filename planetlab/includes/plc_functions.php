<?php

function plc_person_link ($person_id) {
  if ( empty ($person_id)) {
    return "";
  } else {
    return '<a href="/db/persons/index.php?id=' . $person_id . '">' . $person_id . '</a>';
  }
  }

function plc_node_link ($node_id) {
  if ( empty ($node_id)) {
    return "";
  } else {
    return '<a href="/db/nodes/index.php?id=' . $node_id . '">' . $node_id . '</a>';
  }
  }

// pagination function
function paginate( $fn_array, $table_id, $caption, $limit, $main_field, $other_func= NULL, $fid= NULL ) {
  // get vars from call adjust them
  $dir= strtolower( $caption );
  $echo= "";

  $link_page= 'index.php';

  // check for page number
  if( empty( $_GET['page'] ) )
    $page= 1;
  else
    $page= $_GET['page'];

  // reorder array_chunk
  foreach( $fn_array as $arr1 ) {
    unset( $arr2 );
    
    foreach( $arr1 as $key => $val ) {
//      if( substr( $key, -3 ) == "_id" )
      if ( $key == $table_id  ) {
        $id[$key]= $val;
      } else {
        $data[$key]= $val;
      }
    }
    
    foreach( $id as $key => $val )
      $arr2[$key]= $val;

    foreach( $data as $key => $val )
      $arr2[$key]= $val;

    $as_array[]= $arr2;
  }

  $totalrows= count( $as_array );

  // if array has no rows display msg
  if( $totalrows == 0 )
    return "Nothing to display";

  // set key and break up data struct
  $newkey= $page - 1;  
  $newarray= array_chunk( $as_array, $limit );

  // start table output
  $echo.= "<table class='list_set' border=0 cellpadding=2>\n";

  // if there is a caption add it to table
  if( $caption )
    $echo.= "<caption class='list_set'>$caption</caption>\n";

  $echo.= "<thead><tr class='list_set'>";

  // go through keys of one array row for table headers
  foreach( $newarray[$newkey][0] as $key => $val ) {
//    if( substr( $key, -3 ) != "_id" )
    if ( $key != $table_id && $key != 'peer_id' )
      $echo.= "<th class='list_set'>". ucfirst( $key ) ."</th>";
  }

  if( $other_func == 'slivers' )
    $echo.= "<th>Slivers</th>";

  $echo.= "</tr></thead><tbody>\n";

  // go through array row by row to output table rows
  foreach( $newarray[$newkey] as $assoc ) {

    $extraclass="";
    if ($assoc['peer_id']) {
      $extraclass="plc-foreign";
    }
    

    $echo.= "<tr class='list_set'>";

    foreach( $assoc as $key => $val ) {
      // do not rely on keys order
      $id = $assoc[$table_id];
//      if( substr( $key, -3 ) == "_id" )
      if ($key == $table_id) {
//	$id= $val;
	continue;
      } elseif( $key == $main_field ) {
        $echo.= "<td class='list_set $extraclass'><a href='/db/$dir/$link_page?id=$id'>$val</a></td>";
      } elseif ($key != 'peer_id') {
        $echo.= "<td class='list_set $extraclass'>";
        if( is_array( $val ) ) {
          $count= 1;
          $tot= count( $val );
          foreach( $val as $k => $v ) {
            $echo.= $v;
            if( $count != $tot )
              $echo.= ", ";
            $count++;
          }
        }
        else
          $echo.= $val;
        $echo.= "</td>";
      }

    }

    if( $other_func == 'slivers' )
      $echo.= "<td><a href='slivers.php?node=$fid&slice=$id'>view</a></td>";

    $echo.= "</tr>\n";
  }

  // close table
  $echo.= "</tbody></table>\n";
  $echo.= "<hr />\n";

  // find total number of pages
  $numofpages = $totalrows / $limit;

  // start navigation links
  if( $numofpages > 1 ) {
    // if page is not 1 display first and prev links
    if( $page != 1 && $page ) {
        $pageprev= $page - 1;
        $echo.= "<a href=\"". $_SERVER['REQUEST_URI'] ."&page=1\">FIRST</a> &nbsp; ";
        $echo.= " <a href=\"". $_SERVER['REQUEST_URI'] ."&page=$pageprev\">PREV ".$limit."</a> &nbsp; ";
      }
      else
        $echo.= "PREV ". $limit ." ";

    // if less than 30 pages display all
    // otherwise show 30 pages but put ... inbetween
    if( $numofpages < 30 ) {
      $npages= $numofpages;
      $start= 1;
    }
    else {
      $npages= $page + 9;
      if( $npages > $numofpages )
        $npages= $numofpages;
      $start= $page - 10;
      if( $start < 1 )
        $start= 1;
      if( $page != 1 )
        $echo.= " ... ";
    }

    // display pages, no link if current page
    for( $i= $start; $i <= $npages; $i++ ) {
      if( $i == $page )
        $echo.= $i ." ";
      else
        $echo.= "<a href=\"". $_SERVER['REQUEST_URI'] ."&page=$i\">$i</a> ";

    }

    if( ( $totalrows % $limit ) != 0 ) {
      $last= $numofpages + 1;
      if( $i == $page )
        $echo.= $i ." ";
      else
        $echo.= "<a href=\"". $_SERVER['REQUEST_URI'] ."&page=$i\">$i</a> ";
    }
    else
      $last= $numofpages;

    if( $numofpages >= 30 ) {
      if( $page != $numofpages )
        $echo.= " ... ";
    }

    if( ( $totalrows - ($limit * $page) ) > 0 ) {
      $pagenext= $page + 1;
      $echo.= " &nbsp; <a href=\"". $_SERVER['REQUEST_URI'] ."&page=$pagenext\">NEXT ".$limit."</a> &nbsp; ";
    }
    else
      $echo.= "NEXT ". $limit;

    $echo.= " <a href=\"". $_SERVER['REQUEST_URI'] ."&page=". intval( $last ) ."\">LAST</a>\n";

  }

  return $echo;
  
}

// function for getting the diff of multi dimention array
function arr_diff( $a1, $a2 ) {
  $diff= array();
  foreach( $a1 as $k=>$v ) {
    unset( $dv );
    for( $x= 0; $x < count( $a2 ); $x++ ) {
      if( is_int( $k ) ) {
        if( array_search( $v, $a2 ) === false )
          $dv=$v;
        else if( is_array( $v ) )
          $dv= arr_diff( $v, $a2[$x] );
        if( $dv && !in_array( $dv, $diff ) )
          $diff[]=$dv;
      }
      else {

        if( !$a2[$k] )
          $dv=$v;
        else if(is_array($v))
          $dv=arr_diff($v,$a2[$x]);
        if($dv)
          $diff[$x]=$dv;
      }
    }
  }
  return $diff;
}


function is_valid_email_addr($email)
{
  if( ereg("^.+@.+\\..+$", $email) )
    return true;
  else
    return false;
}

function is_valid_url($url)
{
  if( ereg("^(http|https)://.+\..+$", strtolower($url) ) )
    return true;
  else
    return false;
}

function is_valid_ip($ip)
{
  if( ereg("^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$", $ip ) )
    {
      // it's at least in the right format, now check to see if
      // each part is equal to less than 255
      $parts= explode( '.', $ip );
      $count= count($parts);

      for( $i= 0; $i < $count; $i++ )
	{
	  if( intval($parts[$i]) > 255 )
	    return false;
	}

      return true;
    }
  else
    return false;
}


function is_valid_network_addr($network_addr,$mask)
{
  $lNetwork= ip2long($network_addr);
  $lMask= ip2long($mask);

  // are they the correct format?
  if( $lNetwork == -1 || $lMask == -1 )
    return false;

  // is network address valid for the mask?
  if( ($lNetwork & $lMask) != $lNetwork )
    return false;

  return true;
}


// returns whether or not a network address is in the reserved space
// in the case of a invalid network address, false will be returned.
function is_reserved_network_addr($network_addr)
{
  $lNetwork= ip2long($network_addr);

  if( $lNetwork == -1 )
    return false;

  // does the network address fall in a reserved block?
  $reserved_ips = array (
			 array('10.0.0.0','10.255.255.255'),
			 array('172.16.0.0','172.31.0.0'),
			 array('192.168.0.0','192.168.255.0')
			 );
  foreach ($reserved_ips as $r)
    {
      $min = ip2long($r[0]);
      $max = ip2long($r[1]);
      
      if (($lNetwork >= $min) && ($lNetwork <= $max))
	  return true;
    }

  return false;
}

// builds a table from an array of strings, with the given class
function plc_make_table ($class, $messages) {
  // pretty print the cell
  $formatted = "";
  if (! empty ($messages)) {
    $formatted="<table class='" . $class . "'>";
    foreach ($messages as $message) {
      $formatted .= "<tr><td>" . $message . "</td></tr>";
    }
    $formatted .= "</table>";
  }
  return $formatted;
}

// shows a php variable verbatim with a heading message
function plc_debug($message,$object) {
  print "<br>" . $message . "<pre>";
  print_r ($object);
  print "</pre>";
}

// attempt to normalize the delete buttons and confirmations
function plc_delete_button($width=15) {
  return '<span title="Delete this entry"><img width=' . $width . ' alt="Delete this entry" src="/planetlab/includes/delete.png"></span>';
}

function plc_js_confirm($message) {
  return "onclick=\"javascript:return confirm('Are you sure you want to delete " . $message . " ?')\"";
}

function plc_delete_link($url,$delete_message,$visible) {
  return "<a href='" . $url . "' " . plc_js_confirm($delete_message) . ">" . $visible . "</a>";
}

function plc_delete_link_button($url,$delete_message,$width=15) {
  return "<a href='" . $url . "' " . plc_js_confirm($delete_message) . ">" . plc_delete_button($width) . "</a>";
}

function plc_event_button($type,$param,$id) {
  return '<a href="/db/events/index.php?type=' . $type . '&' . $param . '=' . $id . '"> <span title="Related events"> <img src="/planetlab/includes/event.png" width=18></span></a>';
}

function plc_comon_button ($field, $value,$target="") {
  $result='<a ';
  if (!empty($target)) {
    $result.='target="' . $target . '" ';
  }
  $result.='href="/db/nodes/comon.php?' . $field . "=" . $value . '">';
  $result.='<span title="Link to Comon"> <img src="/planetlab/includes/comon.png" width="18"></span></a>';
  return $result;
}

function plc_peers_option_list ($api) {

    // get list of peers
    $peers=$api->GetPeers(NULL,array('peer_id','peername'));
    if (count($peers)==0) {
      $predef=array(array("peer_id"=>"","peername"=>"All (no known peers)"));
    } else {
      $predef=array(array("peer_id"=>"","peername"=>"All peers"),
		    array("peer_id"=>"local","peername"=>"Local only"));
      // show a 'foreign' button only if that makes sense
      if (count($peers) >= 2) {
	$predef [] = array("peer_id"=>"foreign","peername"=>"Foreign peers");
      }
    }
    
    $result="";
    foreach ($predef as $a) {
      $peer_line = "<option value='" . $a['peer_id'] . "'>" . $a['peername'] . "</option>\n";
      $result .= $peer_line;
    }

    if (!empty($peers)) {
      foreach ($peers as $a) {
	$peer_line = "<option value='" . $a['peer_id'] . "'>" . $a['peername'] . "</option>\n";
	$result .= $peer_line;
      }
    }

    return $result;
}

?>
