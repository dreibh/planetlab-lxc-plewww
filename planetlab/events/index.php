<?php
// $Id$

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

//// Print header
require_once 'plc_drupal.php';
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_tables.php';
require_once 'plc_minitabs.php';
  
// needs much memory
ini_set("memory_limit","256M");

//set default title
drupal_set_title('Events');

// page size
$page_size=30;

$messages = array ();

//////////////////////////////////////////////////////////// form

// defaults for day ('j'), 3-letter month ('M') or year ('Y')
function the_date ($key,$dateformat) { 
  if ($_GET[$key]) return $_GET[$key];
  else return date($dateformat);
}

// fill out dates from now if not specified
$from_d = the_date('from_d','j');
$from_m = the_date('from_m','M');
$from_y = the_date('from_y','Y');
$until_d = the_date('until_d','j');
$until_m = the_date('until_m','M');
$until_y = the_date('until_y','Y');

// create the options area from a list and the selected entry
function dropdown_options ($array,$selected) {
  $result="";
  foreach ($array as $item) {
    $result.= "<option value=" . $item;
    if ($item == $selected) $result .= ' selected=selected';
    $result .= '>' . $item . '</option>';
  }
  return $result;
}

$days=range(1,31);
$from_d_dropdown_options=dropdown_options($days,$from_d);
$until_d_dropdown_options=dropdown_options($days,$until_d);
$months=array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
$from_m_dropdown_options=dropdown_options($months,$from_m);
$until_m_dropdown_options=dropdown_options($months,$until_m);
// only propose years ranging from now + 3 full years back
$this_year=date('Y');
$years=range($this_year-3,$this_year);
$from_y_dropdown_options=dropdown_options($years,$from_y);
$until_y_dropdown_options=dropdown_options($years,$until_y);
 
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


<tr><th>FROM (inclusive)</th> <th>UNTIL (inclusive)</th> </tr>

<tr>
      <td>    
        <SELECT NAME='from_d'>
$from_d_dropdown_options								 
        </SELECT>
        <SELECT NAME='from_m' >
$from_m_dropdown_options
        </SELECT>
        <SELECT NAME='from_y' >
$from_y_dropdown_options
        </SELECT>
</td>

<TD>
   <SELECT NAME=' until_d' >
$until_d_dropdown_options
    </SELECT>
    <SELECT NAME=' until_m' >
$until_m_dropdown_options
   </SELECT>
    <SELECT NAME=' until_y' >
$until_y_dropdown_options
    </SELECT>
</td></tr>

<TR><TD colspan=2>
<input type='submit' align='middle' value='Show Events'>
</TD></TR>
</table>
</form>

EOF;

//////////////////////////////////////////////////////////// dates
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

//////////////////////////////////////////////////////////// layout
// outline node ids and person ids with a link
function e_node ($node_id) {
  if (! $node_id) return "";
  return l_node_t($node_id,$node_id);
}
function e_person ($person_id) {
  if (! $person_id) return "";
  return l_person_t($person_id,$person_id);
}
// xxx broken
function e_event ($event_id) {
  if (! $event_id) return "";
  return href(l_event("Event","event",$event_id),$event_id);
}

function e_subject ($type,$id) {
  $mess=$type . " " . $id;
  switch ($type) {
  case 'Node': return l_node_t ($id,$mess);
  case 'Site': return l_site_t ($id,$mess);
  case 'Person': return l_person_t ($id,$mess);
  case 'Slice': return l_slice_t ($id,$mess);
  case 'Role': case 'Key': case 'PCU': case 'Interface': case 'NodeGroup': case "Address":
    return "$mess";
  default: return "Unknown $type" . "-" . $id;
  }
}

// synthesize links to the subject objects from types and ids
function e_subjects ($param) {
  $types=$param['object_types'];
  $ids=$param['object_ids'];
  if ( ! $types) return "";
  return plc_vertical_table(array_map ("e_subject",$types,$ids));
}

function e_issuer ($param) {
  if ($param['node_id'])	return e_subject('Node',$param['node_id']);
  if ($param['person_id'])	return e_subject('Person',$param['person_id']);
  return '???';
}

function e_auth ($event) {
  if (array_key_exists('auth_type',$event)) 
    return $event['auth_type'];
    else
      return "";
}

function e_fault ($event) {
  $f=$event['fault_code'];
  if ($f==0) return "OK";
  else return $f;
}

////////////////////////////////////////////////////////////
// for convenience, add 1 day to the 'until' date as otherwise this corresponds to 0:00
$STEP=24*60*60;

if ( ! plc_is_admin()) {
  plc_warning("You need admin role to see this page.");

 } else if (! $_GET['type']) {
  echo "<h2>Select the events to focus on :</h2>";
  // print the selection frame
  echo $event_form;
  
 } else {

  $tabs=array();
  $tabs['Back to events form']=l_events();
  plc_tabs($tabs);

  // handle dates
  list($from_date,$from_time,$until_date,$until_time) = parse_dates ();
  // add one day to until_time - otherwise this corresponds to 0:0
  $until_time += $STEP;
  if ( ($from_time != 0) && ($until_time != $STEP) && ($from_time > $until_time) ) {
    $messages[] = "Warning - <from> is after <until>";
  }
  
  $filter=array();
  // sort events by time is not good enough, let's use event_id
  $filter['-SORT']='-event_id';
  if ($from_time != 0) {
    $filter[']time']=$from_time;
  }
  if ($until_time != $STEP) {
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
    $title="Events matching " . ($user_desc ? $user_desc : "everything");
    if ($from_time != 0) 
      $title .= " From " . $from_date;
    if ($until_time != $STEP) 
      $title .= " Until " . $until_date;

    // see actual display of $title and $events below
    
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

    $event_objs = $api->GetEventObjects(array('object_id'=>$object_ids,'object_type'=>$object_type),array('event_id'));
    // get set of event_ids
    $event_ids = array_map ( create_function ('$eo','return $eo["event_id"];') , $event_objs);
    
    $events = $api->GetEvents (array('event_id'=>$event_ids));

    // see actual display of $title and $events below

  }

  drupal_set_title ($title);
  // Show messages
  if (!empty($messages)) 
    foreach ($messages as $line) 
      drupal_set_message($line);

  $headers=array("Id"=>"int",
		 "Time"=>"EnglishDateTime",
		 "Method"=>"string",
		 "Message"=>"string",
		 "Subjects"=>"string",
		 "Issuer"=>"string",
		 "Auth"=>"string",
		 "R"=>"string",
		 "D"=>"none",
		 );

  $table = new PlcTable ("events",$headers,"0r");
  $table->set_options (array ('max_pages'=>20));
  $table->start ();
  foreach ($events as $event) {

    // the call button
    $message = htmlentities($event['message'], ENT_QUOTES);
    $call = htmlentities($event['call'], ENT_QUOTES);
    $text = sprintf("message=<<%s>>\\n\\ncall=<<%s>>\\n\\nruntime=<<%f>>\\n",$message,$call,$event['runtime']);
    $method = "<input type=button name='call' value='" . $event['call_name'] ."' onclick='alert(\"" . $text . "\")'";
    //    $method = sprintf('<span title="%s">%s</span>',$call,$method);

  // the message button
    $trunc_mess=htmlentities(truncate($event['message'],40),ENT_QUOTES);
    $message="<input type=button name='message' value='" . $trunc_mess ."' onclick='alert(\"" . $text . "\")'";
    $details="<input type=button name='message' value='X' onclick='alert(\"" . $text . "\")'";
    //    $message=sprintf('<span title="%s">%s</span>',$message,$message);

    $message=truncate($event['message'],40);
    $table->row_start();
    $table->cell(e_event($event['event_id']));
    $table->cell(date('M/d/Y H:i', $event['time']));
    $table->cell($event['call_name']);
    $table->cell($message);
    $table->cell(e_subjects($event));
    $table->cell(e_issuer($event));
    $table->cell(e_auth($event));
    $table->cell(e_fault($event));
    $table->cell($details);
    $table->row_end();
  }
  $table->set_options(array('notes'=>array("The R column shows the call result value, a.k.a. fault_code",
					   "Click the button in the D(etails) column to get more details")));
  $table->end();
  
 }


  // Print footer
include 'plc_footer.php';

?>

