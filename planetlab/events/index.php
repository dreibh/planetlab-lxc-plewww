<?php
// $Id$
  //

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

//// Print header
require_once 'plc_drupal.php';
//set default title
drupal_set_title('Events');

include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';
  
// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

// paginate unit
$page_size=30;

$messages = array ();

$event_form = <<< EOF
<form method=get name='F' action='/db/events/index.php' >

<table align='bottom'>
<tr><td colspan=2>
<table> <TR><TD>
<input type='radio' name='type' id='events' value='Event' checked='checked'>&nbsp;Events: 
</TD><TD>
<input type='text' onSelect="submit();" onFocus='events.checked=true' name='event' size=20>
</TD></TR><TR><TD>
<input type='radio' name='type' id='persons' value='Person'>&nbsp;Persons:
</TD><TD>
<input type='text' onSelect="submit();" onFocus='persons.checked=true' name='person' size=20>
</TD></TR><TR><TD>  
<input type='radio' name='type' id='nodes' value='Node'>&nbsp;Nodes:
</TD><TD>
<input type='text' onSelect="submit();" onFocus='nodes.checked=true' name='node' size=20>
</TD></TR><TR><TD>
<input type='radio' name='type' id='sites' value='Site'>&nbsp;Sites:
</TD><TD>
<input type='text' onSelect="submit();" onFocus='sites.checked=true' name='site' size=20>
</TD></TR><TR><TD>
<input type='radio' name='type' id='slices' value='Slice'>&nbsp;Slices:
</TD><TD>
<input type='text' onSelect="submit();" onFocus='slices.checked=true' name='slice' size=20>
</TD></TR></table>
</td></tr>


<tr><th>FROM</th> <th>UNTIL</th> </tr>

<tr>
      <td>    
        <SELECT NAME='from_d' >
	<OPTION>
	<OPTION VALUE=' 1' >1
	<OPTION VALUE=' 2' >2
	<OPTION VALUE=' 3' >3
	<OPTION VALUE=' 4' >4
	<OPTION VALUE=' 5' >5
	<OPTION VALUE=' 6' >6
	<OPTION VALUE=' 7' >7
	<OPTION VALUE=' 8' >8
	<OPTION VALUE=' 9' >9
	<OPTION VALUE=' 10' >10
	<OPTION VALUE=' 11' >11
	<OPTION VALUE=' 12' >12
	<OPTION VALUE=' 13' >13
	<OPTION VALUE=' 14' >14
	<OPTION VALUE=' 15' >15
	<OPTION VALUE=' 16' >16
	<OPTION VALUE=' 17' >17
	<OPTION VALUE=' 18' >18
	<OPTION VALUE=' 19' >19
	<OPTION VALUE=' 20' >20
	<OPTION VALUE=' 21' >21
	<OPTION VALUE=' 22' >22
	<OPTION VALUE=' 23' >23
	<OPTION VALUE=' 24' >24
	<OPTION VALUE=' 25' >25
	<OPTION VALUE=' 26' >26
	<OPTION VALUE=' 27' >27
	<OPTION VALUE=' 28' >28
	<OPTION VALUE=' 29' >29
	<OPTION VALUE=' 30' >30
	<OPTION VALUE=' 31' >31
        </SELECT>

        <SELECT NAME='from_m' >
	<OPTION>
	<OPTION VALUE=' Jan' >January
	<OPTION VALUE=' Feb' >February
	<OPTION VALUE=' Mar' >March
	<OPTION VALUE=' Apr' >April
	<OPTION VALUE=' May' >May
	<OPTION VALUE=' Jun' >June
	<OPTION VALUE=' Jul' >July
	<OPTION VALUE=' Aug' >August
	<OPTION VALUE=' Sep' >September
	<OPTION VALUE=' Oct' >October
	<OPTION VALUE=' Nov' >November
	<OPTION VALUE=' Dec' >December
        </SELECT>

        <SELECT NAME='from_y' >
	<OPTION>
	<OPTION VALUE='2006' >2006
	<OPTION VALUE='2007' >2007
	<OPTION VALUE='2008' >2008
	<OPTION VALUE='2009' >2009
	<OPTION VALUE='2010' >2010
	<OPTION VALUE='2011' >2011
        </SELECT>

</td>

<TD>
   <SELECT NAME=' until_d' >
	<OPTION>
	<OPTION VALUE='1' >1
	<OPTION VALUE='2' >2
	<OPTION VALUE='3' >3
	<OPTION VALUE='4' >4
	<OPTION VALUE='5' >5
	<OPTION VALUE='6' >6
	<OPTION VALUE='7' >7
	<OPTION VALUE='8' >8
	<OPTION VALUE='9' >9
	<OPTION VALUE='10' >10
	<OPTION VALUE='11' >11
	<OPTION VALUE='12' >12
	<OPTION VALUE='13' >13
	<OPTION VALUE='14' >14
	<OPTION VALUE='15' >15
	<OPTION VALUE='16' >16
	<OPTION VALUE='17' >17
	<OPTION VALUE='18' >18
	<OPTION VALUE='19' >19
	<OPTION VALUE='20' >20
	<OPTION VALUE='21' >21
	<OPTION VALUE='22' >22
	<OPTION VALUE='23' >23
	<OPTION VALUE='24' >24
	<OPTION VALUE='25' >25
	<OPTION VALUE='26' >26
	<OPTION VALUE='27' >27
	<OPTION VALUE='28' >28
	<OPTION VALUE='29' >29
	<OPTION VALUE='30' >30
	<OPTION VALUE='31' >31
    </SELECT>
    <SELECT NAME=' until_m' >
	<OPTION>
	<OPTION VALUE='Jan' >January
	<OPTION VALUE='Feb' >February
	<OPTION VALUE='Mar' >March
	<OPTION VALUE='Apr' >April
	<OPTION VALUE='May' >May
	<OPTION VALUE='Jun' >June
	<OPTION VALUE='Jul' >July
	<OPTION VALUE='Aug' >August
	<OPTION VALUE='Sep' >September
	<OPTION VALUE='Oct' >October
	<OPTION VALUE='Nov' >November
	<OPTION VALUE='Dec' >December
   </SELECT>
 
    <SELECT NAME=' until_y' >
	<OPTION>
	<OPTION VALUE='2006' >2006
	<OPTION VALUE='2007' >2007
	<OPTION VALUE='2008' >2008
	<OPTION VALUE='2009' >2009
	<OPTION VALUE='2010' >2010
	<OPTION VALUE='2011' >2011
    </SELECT>
</td></tr>

<TR><TD colspan=2>
<input type='submit' align='middle' value='Show Events'>
</TD></TR>
</table>
</form>

EOF;

function parse_date ($day,$month,$year) {
  // if everything empty -> unspecified date, return 0
  if ( empty($day) && empty($month) && empty($year)) {
    return array ("xxx",0);
  } else {
    // fill missing fields with current value
    if (empty($day)) $day=date('d');
    if (empty($month)) $month=date('M');
    if (empty($year)) $year=date('Y');
    $date=sprintf("%s %s %s",$day,$month,$year);
    $time=strtotime($date);
    return array($date,$time);
  }
}

function parse_dates () {
  list($from_date,$from_time) = parse_date($_GET['from_d'],$_GET['from_m'],$_GET['from_y']);
  list($until_date,$until_time) = parse_date($_GET['until_d'],$_GET['until_m'],$_GET['until_y']);
  return array($from_date,$from_time,$until_date,$until_time);
}

function my_is_int ($x) {
    return (is_numeric($x) ? intval($x) == $x : false);
}

function truncate ($text,$numb,$etc = "...") {
  if (strlen($text) > $numb) {
    $text = substr($text, 0, $numb);
    $text = $text.$etc;
  }
  return $text;
}

// layout function to refine a row's content
function layout ($param){
 
  // format time
  $time=$param['time'];
  $date= date('d M Y H:i' ,$time);
  $param['time']=$date;

  // the call button
  $message=htmlentities($param['message'], ENT_QUOTES);
  $call=htmlentities($param['call'], ENT_QUOTES);
  $detail_text=sprintf("message=<<%s>>\\n\\ncall=<<%s>>\\n\\nruntime=<<%f>>\\n",$message,$call,$param['runtime']);
  $detail="<input type=button name='call' value='" . $param['call_name'] ."' onclick='alert(\"" . $detail_text . "\")'";
  $detail=sprintf('<span title="%s">%s</span>',$call,$detail);
  $param['call_name']=$detail;
  unset ($param['call']);

  // the message button
  $trunc_mess=htmlentities(truncate($param['message'],40),ENT_QUOTES);
  $detail="<input type=button name='message' value='" . $trunc_mess ."' onclick='alert(\"" . $detail_text . "\")'";
  $detail=sprintf('<span title="%s">%s</span>',$message,$detail);
  $param['message']=$detail;

  // shrink column name : event_id -> id - paginate_id used in paginate and does not show up
  $param['<span title="event_id">id</span>']=$param['event_id'] ; 
  // so that event_id shows up
  $param['paginate_id']=$param['event_id']; unset($param['event_id']);

  //// shrink column names 
  $param['<span title="fault_code">fault</span>']=$param['fault_code'] ; unset($param['fault_code']);
  // seem empty on all rows - probably something that I screwed when importing tony's stuff
  //  $param['<span title="object_type">oty</span>']=$param['object_type'] ; unset($param['object_type']);
  //  $param['<span title="object_id">oid</span>']=$param['object_id'] ; unset($param['object_id']);
  $param['<span title="object_types">otys</span>']=$param['object_types'] ; unset($param['object_types']);
  $param['<span title="object_ids">oids</span>']=$param['object_ids'] ; unset($param['object_ids']);
  $param['<span title="node_id">nid</span>']=plc_node_link($param['node_id']) ; unset($param['node_id']);
  $param['<span title="person_id">pid</span>']= plc_person_link($param['person_id']) ; unset($param['person_id']);
  if (array_key_exists('auth_type',$param)) {
    $param['<span title="auth_type">at</span>']=$param['auth_type'] ; unset($param['auth_type']);
  }

  // clears
  unset($param['object_type']);
  unset($param['object_id']);
  unset($param['runtime']);
  return $param;
}

//plc_debug('GET',$_GET);

if ( !in_array ('10', $_roles)) {
  echo "<div class='plc-warning'> You need admin role to see this page. </div>";

 } else if (!$_GET['type']) {

  echo "<h2>What events would you like to consult :</h2>";
  // print the selection frame
  echo $event_form;
  
 } else {

  // handle dates
  list($from_date,$from_time,$until_date,$until_time) = parse_dates ();
  if ( ($from_time != 0) && ($until_time != 0) && ($from_time > $until_time) ) {
    $messages[] = "Warning - wrong date selection";
  }
  
  $filter=array();
  if ($from_time != 0) {
    $filter[']time']=$from_time;
  }
  if ($until_time != 0) {
    $filter['[time']=$until_time;
  }

  //////////////////////////////////////// Events
  $type=$_GET['type'];
  if ($type == 'Event') {

   // and the filter applied for fetching events using GetEvent
    $user_desc=$_GET['event'];
    if ( ! empty($user_desc)) {
      // should parse stuff like 45-90,230-3000 - some other day
      $filter['event_id']=intval($user_desc);
    }
    // the filter might be void here - in python we need an empty dict but that's not what we get so
    if (empty($filter)) {
      $filter[']time']=0;
    }
    $events = $api->GetEvents($filter); 
    if (empty($events)) {
      $messages[] = "No event found - user input was [" . $user_desc . "]";
    } else {
      $title="Events matching " . ($user_desc ? $user_desc : "everything");
      if ($from_time != 0) 
	$title .= " From " . $from_date;
      if ($until_time != 0) 
	$title .= " Until " . $until_date;
      drupal_set_title ($title);
    }

    // Show messages
    if (!empty($messages)) {
      print '<div class="messages plc-warning"><ul>';
      foreach ($messages as $line) {
	print "<li> $line";
      }
      print "</ul></div>";
    }
	
    if ( ! empty ($events)) {
      $events= array_map(layout,$events);
      echo paginate( $events, "paginate_id", "Events", $page_size, "event_id");
    }
  } else {

    switch ($type) {
    case 'Person': 
      $primary_key='person_id';
      $string_key='email';
      $user_input=$_GET['person'];
      $method="GetPersons";
      $object_type='Person';
      break;

    case 'Node': 
      $primary_key='node_id';
      $string_key='hostname';
      $user_input=$_GET['node'];
      $method="GetNodes";
      $object_type='Node';
      break;
      
    case 'Site': 
      $primary_key='site_id';
      $string_key='login_base';
      $user_input=$_GET['site'];
      $method="GetSites";
      $object_type='Site';
      break;

    case 'Slice': 
      $primary_key='slice_id';
      $string_key='name';
      $user_input=$_GET['slice'];
      $method="GetSlices";
      $object_type='Slice';
      break;
    }

    $object_ids=array();
    $title=sprintf('Events for type %s:',$object_type);
    foreach ( split(",",$user_input) as $user_desc) {
      # numeric 
      if (my_is_int($user_desc)) {
	$obj_check = call_user_func(array($api,$method),array(intval($user_desc)),array($primary_key));
	if (empty ($obj_check)) {
	  $messages[] = "No such " . $primary_key . ": " . $user_desc;
	} else {
	  $object_ids[] = $obj_check[0][$primary_key];
	  $title .= $user_desc . ", " ;
	}
      } else {
	# string
	$new_object_ids=call_user_func (array($api,$method), array($string_key=>$user_desc),array($primary_key,$string_key));
	if (empty($new_object_ids)) {
	  $messages[] = "No " . $string_key . " matching " . $user_desc;
	} else {
	  foreach ($new_object_ids as $new_obj_id) {
	    $object_ids[] = $new_obj_id[$primary_key];
	    $title .= $new_obj_id[$primary_key] . ", ";
	  }
	}
      }
    }
      
    // Show messages
    if (!empty($messages)) {
      print '<div class="messages plc-warning"><ul>';
      foreach ($messages as $line) {
	print "<li> $line";
      }
      print "</ul></div>";
    }
	
    drupal_set_title($title);
    $events = $api->GetEventObjects(array('object_id'=>$object_ids,'object_type'=>$object_type));

    $events=array_map(layout,$events);
    echo paginate( $events, "paginate_id", "--------" . $type . " EVENTS---------", $page_size, "hostname");
  }
 }

echo "<br /><p><a href='/db/events/index.php'>Back to Events</a>";

  // Print footer
include 'plc_footer.php';

?>

