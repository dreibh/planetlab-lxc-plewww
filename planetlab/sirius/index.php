<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// Common functions
require_once 'plc_functions.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

//require 'sirius_func.php';

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Sirius');
include 'plc_header.php';

?>

<h2>Sirius Calendar Service</h2>

You can choose to run your experiment at the earliest available
time or give a specific time.  The number of repetitions has
the following meaning: if you want your job re-inserted on the
schedule list (in the earliest slot available) directly after it 
gets a priority increase, you can do so (up to 4 times).  Currently, 
our admission control policy is that we 
allow only one slice per time slot.  <p>

Currently, 
time slots are only allocated on the granularity
of an hour, and
only CPU is increased.

<h3>
Current Schedule
</h3>

<?php

define("SUCCESS", 0);
define("NO_ROOM", 1);
define("TOO_MANY_UNITS", 2);
define("NO_UNITS_LEFT", 3);
define("TIME_ALREADY_OCCURRED", 4);
define("NO_SUCH_SLICE", 5);
define("NOT_SLICE_OWNER", 6);
define("TOO_CLOSE_TO_DEADLINE", 7);

define("DELETE_THRESHOLD", 600);
define("MAX_JOBS", 1);

function authorizeSlice($sn) {
	/*
  $_api = new xmlrpc_client('/PLCAPI/', 'planet-lab.org', 443);

  $username = $_SESSION['username'];
  $password = $_SESSION['password'];
  
  $_api->setDebug(0);

  $_api_auth = new xmlrpcval(array(
				   "AuthMethod" => new xmlrpcval("password"),
				   "Username" => new xmlrpcval($username),
				   "AuthString" => new xmlrpcval($password),
				   "Role" => new xmlrpcval("user")), "struct");
  
  $func = new xmlrpcmsg("SliceInfo");
  $func->addParam($_api_auth);

  $result = $_api->send($func, 15, 'https');

  if( $result == 0 ) {
    //    printf("problem: %s\n", $_api->errstring);
    return 0;
  }
  else if($result->faultCode() != 0) {
    //    printf("server problem: %s\n", $result->errstr);
    return 0;
  }
  else {
    $result_value = $result->value();

    if( !$result_value ) {
      //      printf( "didn't get value back from api\n" );
      return 0;
    }
    else {
      $arr = xmlrpc_decode($result_value);
      //      printf( "return value success, value %s\n", $val);
      //      print_r($val);
      $numElements = count($arr);
      $i = 0;
      while ($i < $numElements) {
	if ($sn == $arr[$i][name])
	  return 1;
	$i++;
      }
      return 0;
    }
  }
  */
	
	global $api;
  
  $slice_list= array();
  $result= $api->GetSlices( $slice_list, array( "name" ) );
  
  foreach ( $result AS $slice )
  {
  	if ( $slice["name"] == $sn )
  		return 1;
	
  }
  
  return 0;
  
}

//can a request be satisfied?  Currently, answer is yes unless
//either this time is taken or you asked for more than one unit
//probably will need to change this.  
function validateRequest ($units, $timesOccupied, $requestedTime, $currentTime) {
  if ($units != 1)
    return TOO_MANY_UNITS;

  // buffer so we aren't too close to deadline, if your request is late
  // OR if it's within 1 minute of deadline, it's too late
  if ($requestedTime - 60 <= $currentTime)
    return TIME_ALREADY_OCCURRED;

  if (array_key_exists($requestedTime, $timesOccupied)) {
    if ($timesOccupied[$requestedTime] == MAX_JOBS)
      return NO_ROOM;
  }
  return SUCCESS;
}

//can a request be satisfied?  Currently, answer is yes unless
//either this time is taken or you asked for more than one unit
//probably will need to change this.  
function validateAndMarkRequest ($units, &$timesOccupied, $requestedTime, $currentTime, $sn, $jobArray) {
  // buffer so we aren't too close to deadline, if your request is late
  // OR if it's within 1 minute of deadline, it's too late
  if ($requestedTime - 60 <= $currentTime)
    return TIME_ALREADY_OCCURRED;

  if (array_key_exists($requestedTime, $timesOccupied)) {
    if ($timesOccupied[$requestedTime] == MAX_JOBS)
      return NO_ROOM;
    else
      $timesOccupied[$requestedTime]++;
  }
  else {
    $timesOccupied[$requestedTime] = 1;
    if (array_key_exists($sn, $jobArray)) {
      $ts = $jobArray[$sn]["timestamp"];
      $timesOccupied[$ts]--;
    }
  }

  if ($units != 1)
    return TOO_MANY_UNITS;

  return SUCCESS;
}

function findNextFreeSlot($units, $timesOccupied) {
  $currYear = gmdate("y");
  $currMonth = gmdate("m");
  $currDate = gmdate("d");
  $currHour = gmdate("H") + 1;
  $currentTime = gmmktime();
  $reqTime = gmmktime($currHour, 0, 0, $currMonth, $currDate, $currYear);
  $retVal = 1;
  while ($retVal != SUCCESS) {
    $retVal = validateRequest($units, $timesOccupied, $reqTime, $currentTime);
    if ($retVal == NO_ROOM || $retVal == TIME_ALREADY_OCCURRED) { // advance timestamp one hour (3600 seconds)
      $reqTime = $reqTime + 3600;
    }
  }
  return $reqTime;
}

function dumpToFile($fileName, $buffer, $which, $timesOccupied) {
  //open file, and dump newly list into it (buffer is the list)
  //we're just currently overwriting (fopen with "w" truncates)

  $fileHandle = fopen($fileName, "w");
  //periodically, updateSliceUnits program will update the slices file
  //this function is general, works for schedule or slices file

  //lock in case of concurrent accesses
  flock($fileHandle, LOCK_EX);
  
  if ($which == "schedule") {  // need to write timestamp in this case
    $s = gettimeofday();
    fwrite($fileHandle, $s[sec]);
    fwrite($fileHandle, "\n");
  }

  //do the dump here
  foreach ($buffer as $value) {
    $t = "";
    if ($which == "schedule") {  
      if (strcmp($value["timestamp"], mktime()) > 0) {
	$numReps = $value["reps"];
	$ts = $value["timestamp"];
	$t = $value["sliceName"]." ".$value["id"]." ".$value["timestamp"]." ".$value["units"]." ".$value["reps"]." \n";
      }
      else {  // job expired, does it need be run again?
	if ($value["reps"] > 0) {
	  $ts = findNextFreeSlot($value["units"], $timesOccupied);
	}
	$numReps = $value["reps"] - 1;
      }
      if ($numReps >= 0)
	$t = $value["sliceName"]." ".$value["id"]." ".$ts." ".$value["units"]." ".$numReps." \n";
    }
    else if ($which == "slices") {
      $t = $value["sliceName"]." ".$value["units"]." \n";
    }

    if ($t != "")
      fwrite($fileHandle, $t);

  }

  flock($fileHandle, LOCK_UN);

  fclose($fileHandle);
}

//update the slice file, takes a slice name (name) and number of units
function updateSliceFile($name, $units) {
  $dummyArray = array();

  $sliceFile = fopen("/var/www/html/planetlab/sirius/slices.txt", "rw");
  if (!$sliceFile) {
    echo "<p>Unable to open remote file.</p>"; 
    
  }

  flock($sliceFile, LOCK_EX);

  //we'll construct a new list here, will be current slice file except 
  //the slice in question will have it's units decreased, if there are any...
  while (!feof($sliceFile)) {
    $num = fscanf($sliceFile, "%s %d\n", $sliceName, $unitsAvailable);
    //for some reason feof seems to not quite work
    //precisely, the last entry in the file is read twice (!?!), so hack here
    if ($num == 0)
      break;

    $newArray["sliceName"] = $sliceName;
    if ($name == $sliceName) {
      $newUnits = $unitsAvailable - $units;
      if ($newUnits < 0)  // error, slice has no more units
	return -1;
      else
	$newArray["units"] = $newUnits;
    }
    else
      $newArray["units"] = $unitsAvailable;
    //append this tuple to the entire array
    $sliceArray[] = $newArray;
  }
  flock($sliceFile, LOCK_UN);
  fclose($sliceFile);
  //do the dump to new file
  dumpToFile("/var/www/html/planetlab/sirius/slices.txt", $sliceArray, "slices", dummyArray);
  return 0;
}


//pretty obvious what this does; basically, does the slice exist in
//the slice file yet?  (New user of calendar service user may not have 
//an entry)
function isFirstSliceRequest($name) {
  $sliceFile = fopen("/var/www/html/planetlab/sirius/slices.txt", "r");
  if (!$sliceFile) {
    echo "<p>Unable to open remote file.</p>"; 
    
  }

  flock($sliceFile, LOCK_EX);

  while (!feof($sliceFile)) {
    $num = fscanf($sliceFile, "%s %d\n", $sliceName, $unitsAvailable);
    //for some reason feof seems to not quite work
    //precisely, the last entry in the file is read twice (!?!), so hack here
    if ($num == 0)
      break;

    if ($name == $sliceName) {
      flock($sliceFile, LOCK_UN);
      fclose($sliceFile);
      return 0;
    }
  }

  flock($sliceFile, LOCK_UN);
  fclose($sliceFile);
  return 1;
}


function cmp ($a, $b) {
  if ($a["timestamp"] == $b["timestamp"])
    return 0;
  else
    return ($a["timestamp"] < $b["timestamp"]) ? -1 : 1;
}

function checkForErrors($requestStatus) {
  if ($requestStatus == NO_ROOM) {
    printf("<b> Error: Cannot add your request; that time slot is currently full. </b> <p>");
  }
  else if ($requestStatus == TOO_MANY_UNITS) {
    printf("<b> Error: Cannot add your request; only 1 extra unit is allowed.</b> <p>");
  }
  else if ($requestStatus == NO_UNITS_LEFT) {
    printf("<b> Error: Cannot add your request; no more units remaining.</b> <p>");
  }
  else if ($requestStatus == TIME_ALREADY_OCCURRED) {
    printf("<b> Error: Cannot add your request; that time has already occurred, or is too close to the current time.</b> <p>");
  }
  else if ($requestStatus == NO_SUCH_SLICE) {
    printf("<b> Error: Cannot delete nonexistent slice.</b> <p>");
  }
  else if ($requestStatus == NOT_SLICE_OWNER) {
    printf("<b> Error: Only authorized user can manipulate slice.</b> <p>");
  }
  else if ($requestStatus == TOO_CLOSE_TO_DEADLINE) {
    printf("<b> Error: Cannot delete your request; it is too close to
the time that your slice will receive its priority increase.</b> <p>");
  }
}

function getCurrentSchedule (&$jobArray, &$timesOccupied, &$maxId) {

  $schedFile = fopen("/var/www/html/planetlab/sirius/schedule.txt", "r");
  if (!$schedFile) {
    echo "<p>Unable to open remote file.</p>"; 
    
  }

  flock($schedFile, LOCK_EX);

  //first line is timestamp, throw it away.
  fscanf($schedFile, "%s\n", $str);

  //read in current file into array
  $jobArray = array();
  $newArray = array();
  $timesOccupied = array();
  $maxId = 0;
  while (!feof($schedFile)) {
    $num = fscanf($schedFile, "%s %d %s %d %d\n", $sliceName, $id, $timestamp, $units, $reps);

    if ($id > $maxId)
      $maxId = $id;

    //for some reason feof seems to not quite work
    //precisely, the last entry in the file is read twice (!?!), so hack here
    if ($num == 0)
      break;
    $newArray["sliceName"] = $sliceName;
    $newArray["id"] = $id;
    $newArray["units"] = $units;
    $newArray["timestamp"] = $timestamp;
    $newArray["reps"] = $reps;
    $jobArray[$sliceName] = $newArray;

    if (array_key_exists($timestamp, $timesOccupied)) {
      $timesOccupied[$timestamp]++;
    }
    else {
      $timesOccupied[$timestamp] = 1;
    }

  }

  flock($schedFile, LOCK_UN);
  fclose($schedFile);

}

// Reid: after below function call, you have the current schedule.
// It is stored in $jobArray, which is an array of arrays.  
// Layout: each element of $jobArray is an array with
//         the following fields.
//   "sliceName": the name of the slice that occupies the slot
//   "id": the id of the slice, currently not used; do not display
//   "units": another field that will be used eventually...but not yet
//   "reps": indicates how many repetitions slice has specified; for
//           each repetition, the slice is automatically rescheduled
//           after running for the earliest available slot
// I don't know if you want to pring the schedule out as part of the
// queue here, or wait to see if there was a submitted job.
// See my comments below for more details.

getCurrentSchedule ($jobArray, $timesOccupied, $maxId);

$changeMade = 0;

// Reid: here, we see if a new request is submitted (which would
//       be done now as: did the user click into the queue and
//       select a slice to get the next slot.  I think you can just
//       skip to the end of this if statement (see comment below)

// Reid: The problem here is, this is based on the current submission
//       procedure, which is: click the submit button (or the delete
//       button).  I'm not sure how to change or modularize this
//       function because I don't know how the queue would precisely
//       be implemented.  I've commented the code below to try to help.

//if form was submitted with new job, process it
if (isset($_POST['action']) && $_POST['action'] == 'submitted') {
  $sname = $_POST['sliceName'];

  if (!authorizeSlice($sname)) {
    $requestStatus = NOT_SLICE_OWNER;
  }
  else if ($_POST['add_delete'] == "delete") {
    // delete request.  Make sure it exists and is early enough to delete,
    // then delete it.
    if (array_key_exists($sname, $jobArray)) {
      $changeMade = 1;
      $ts = $jobArray[$sname]["timestamp"];
      if ($ts - mktime() < DELETE_THRESHOLD)
	$requestStatus = TOO_CLOSE_TO_DEADLINE;
      else {
	$timesOccupied[$ts]--;
	unset($jobArray[$sname]);
      }
    }
    else 
      $requestStatus = NO_SUCH_SLICE;
  }

  else {
    // it's an add request
    // grab all the data from the user.
    $changeMade = 1;
    //    $minute = $_POST['minute'];
    $reps = $_POST['reps'];
    //  $u = $_POST['units'];
    $u = 1;
    
    $currentTime = mktime();

    if ($_POST['whenToRun'] == "asap") {
      $requestedTime = findNextFreeSlot($u, $timesOccupied);
    }
    else {
      //      $requestedTime = gmmktime($hour, $minute, 0, $month, $date, $year);
      if (!isset( $_POST['queue_time'] )) {
	$year = $_POST['year'];
	$month = $_POST['month'];
	$date = $_POST['date'];
	$hour = $_POST['hour'];
	$requestedTime = gmmktime($hour, 0, 0, $month, $date, $year);
      }
      else {
	$currYear = gmdate("y");
	$currMonth = gmdate("m");
	$currDate = gmdate("d");
	$currHour = gmdate("H");
	$hour = $_POST['queue_time'];
	if ($hour < $currHour) {
	  $requestedTime = gmmktime($hour, 0, 0, $currMonth, $currDate+1, $currYear);
	}
	else
	  $requestedTime = gmmktime($hour, 0, 0, $currMonth, $currDate, $currYear);
      }
    }
    
    $id = $maxId + 1;
    
    $requestStatus = validateAndMarkRequest($u, $timesOccupied, $requestedTime, $currentTime, $sname, $jobArray);
    if ($requestStatus == SUCCESS) {
      // ignore below, it is for future work anyways.
      if (isFirstSliceRequest($sname)) {
	$sliceFile = fopen("/var/www/html/planetlab/sirius/slices.txt", "a");
	if ($sliceFile == 0) {
	  echo "<p>Unable to open file.</p>"; 
	  
	}
	flock($sliceFile, LOCK_EX);
	
	// should be max number of units, not 5
	// why is this 6?
	fwrite($sliceFile, $sname." "."6");
	flock($sliceFile, LOCK_UN);
	fclose($sliceFile);
      }
      // if (updateSliceFile($sname, 1) < 0)
      // temporarily not looking at units...
      if (0)
	$requestStatus = NO_UNITS_LEFT;  
      else {
	// here, pretty simple, just stick all data into 
	// array element, then stick array into $jobArray.
	$newArray["sliceName"] = $sname;
	$newArray["id"] = $id;
	$newArray["timestamp"] = $requestedTime;
	$newArray["units"] = $u;
	$newArray["reps"] = $reps;
	$jobArray[$sname] = $newArray;
      }
    }
  }
  //  header("Location: planetcal.php");
}

//sort job array by earliest time first ("cmp" function does this)
usort($jobArray, "cmp");  

// Reid: after this above line, $jobArray holds a sorted list that
//       you can output as the queue.

// Reid: below is the current printing of the schedule, which would
//       certainly be deleted when you have the better representation
//       of the schedule (the visual queue).  It starts here and ends
//       where I've marked below.

//print current job list as table on screen
printf("<table cellspacing=0 cellpadding=2>");
if (count($jobArray) > 0) {
  printf("<tr>");
  printf("<th style='border: 1px black solid'> Slice name </th> <th style='border: 1px black solid'> Repetitions </th><th style='border: 1px black solid'> Time of priority </th>");
  printf("</tr>");
}
else {
  printf("<tr>");
  printf("<td>No jobs currently on queue </td><td></td><td></td>");
  printf("</tr>");
}

$deletedExpiredJob = 0;

$arr= array();
$n= 0;
foreach ($jobArray as $value) {
  if (strcmp($value["timestamp"], mktime()) > 0) {
    printf("<tr>\n");
    printf("<td> %s </td><td align=center> %d </td><td> %s </td>\n", $value["sliceName"], $value["reps"], gmdate("r", $value["timestamp"]));
    $arr[$n]= $value["sliceName"];
    $n++;
    printf("</tr>\n");
  }
  else {
    $deletedExpiredJob = 1;
  }
}
printf("</table>\n");
echo "<br>\n";
// Reid: end of current printing of the schedule.

// Reid: here is where we put the data back to the schedule file.
//       It's already a function, 

function findNextQueue($units, $timesOccupied, $arr) {

  $currYear = gmdate("y");
  $currMonth = gmdate("m");
  $currDate = gmdate("d");
  $currHour = gmdate("H") + 1;
  $currentTime = gmmktime();
  $reqTime = gmmktime($currHour, 0, 0, $currMonth, $currDate, $currYear);
  $retVal = 1;
  $i = 0;
	

	// DAVE
	// outputting table to display the queue
	// green background will mean slot is open, and red will mean the slot is used
	// 
  echo "<table cellspacing=\"2\" cellpadding=\"1\" border=\"0\" width=550>\n";
  echo "<tr><td colspan=\"3\"><strong>24 hour Queue:</strong> Choose the GMT time slot you desire (<font color=\"#339933\">green</font> slots are open, <font color=\"#CC3333\">red</font> are taken) <p></td></tr>\n";
  echo "<tr><td width=\"47%\" align=\"right\"><table cellspacing=1 cellpadding=1 border=0 width=130>\n";

  // here's what this does below: it goes through each hour, and sees if the slot is occupied
  // if so, it outputs in red, w/ slice name ($arr[$x], where $x is the number request, i.e.
  // earlier when we dump out the list of slices on the schedule, we do $arr[$x++] = $slicename
  $x= 0;
  //  while ($reqTime < ( $reqTime + ( 24 * 3600 ) ) ) {
  while ($i < 12) {
    $retVal = validateRequest($units, $timesOccupied, $reqTime, $currentTime);
    if ($retVal == SUCCESS) { // advance timestamp one hour (3600 seconds)
	  
	echo "<tr bgcolor=\"#339933\"><td><input type=\"radio\" name=\"queue_time\" value=\"" . gmdate("H:i:s", $reqTime) . "\"> " . gmdate("H:i:s", $reqTime) . " &nbsp;  </td></tr>\n";
    }
    else {
	echo"<tr bgcolor=\"#CC3333\"><td align=center> " . $arr[$x] . " </td></tr>\n";
	$x++;
    }

    $reqTime = $reqTime + 3600;
    $i++;
  }
  echo "</table></td><td width=\"6%\"> &nbsp; </td><td><table cellspacing=1 cellpadding=1 border=0 width=130>\n";

  while ($i < 24 && $i > 11) {
    $retVal = validateRequest($units, $timesOccupied, $reqTime, $currentTime);
    if ($retVal == SUCCESS) { // advance timestamp one hour (3600 seconds)

        echo "<tr bgcolor=\"#339933\"><td><input type=\"radio\" name=\"queue_time\" value=\"" . gmdate("H:i:s", $reqTime) . "\"> " . gmdate("H:i:s", $reqTime) . " &nbsp;  </td></tr>\n";     }
    else {
        echo"<tr bgcolor=\"#CC3333\"><td align=center> " . $arr[$x] . " </td></tr>\n";
	$x++;
    }

    $reqTime = $reqTime + 3600;
    $i++;
  }
  echo "</table></td></tr>\n";

  echo "</table>\n";

}

function sliceDropDown() {
  /*
	$_api = new xmlrpc_client('/PLCAPI/', 'planet-lab.org', 443);

  $username = $_SESSION['username'];
  $password = $_SESSION['password'];
  
  $_api->setDebug(0);

  $_api_auth = new xmlrpcval(array(
				   "AuthMethod" => new xmlrpcval("password"),
				   "Username" => new xmlrpcval($username),
				   "AuthString" => new xmlrpcval($password),
				   "Role" => new xmlrpcval("user")), "struct");
  
  $func = new xmlrpcmsg("SliceInfo");
  $func->addParam($_api_auth);

  $result = $_api->send($func, 15, 'https');
  
  if( $result == 0 ) {
    //    printf("problem: %s\n", $_api->errstring);
    return 0;
  }
  else if($result->faultCode() != 0) {
    //    printf("server problem: %s\n", $result->errstr);
    return 0;
  }
  else {
    $result_value = $result->value();

    if( !$result_value ) {
      //      printf( "didn't get value back from api\n" );
      return 0;
    }
    else {
      $arr = xmlrpc_decode($result_value);
      //      printf( "return value success, value %s\n", $val);
      //      print_r($val);
      $numElements = count($arr);
      $i = 0;
      while ($i < $numElements) {
		echo "<option value='" . $arr[$i][name] . "'>" . $arr[$i][name] . "</option>\n";
		
		$i++;
      }
      return 0;
    }
  }
  */
	global $api;
  
  $slice_list= array();
  $result= $api->GetSlices( Null, array( "name" ) );
  sort_slices( $result );
  foreach ( $result AS $slice )
  {
  	echo "<option value='" . $slice["name"] . "'>" . $slice["name"] . "\n";
  	
  }
  
}
//reopen schedule file, and dump newly sorted job list into it
//note that current timestamp is put in at beginning
//note also: only do this dump if a change has been made

if ($deletedExpiredJob || ($changeMade && $requestStatus == SUCCESS)) {
  dumpToFile("/var/www/html/planetlab/sirius/schedule.txt", $jobArray, "schedule", $timesOccupied);

  // hack here...the problem is that the file might not be sorted
  // when it should, because of the stupid way it was designed.  this
  // happens when reps is not 0, and the next entry should go after 
  // another entry.  what does happen is that it goes before, which is
  // fine for displaying, but the sirius service code expects it to
  // always be sorted, "it" being the schedule file

  $hackArray = array();
  getCurrentSchedule ($hackArray, $timesOccupied, $maxId);
  usort($hackArray, "cmp");  
  dumpToFile("/var/www/html/planetlab/sirius/schedule.txt", $hackArray, "schedule", $timesOccupied);
}

checkForErrors($requestStatus);
?>

<h3>
Priority Queue
</h3>

<form action="/db/sirius/index.php" method="post">
<p>Choose your slice name:
<select name="sliceName">
<?php sliceDropDown(); ?>
</select>

<p>
Either Add a new time slot or remove a previously taken slot:
<br>
<b>Add</b> <input type=radio name="add_delete" value="add" checked/>
<b>Delete</b> <input type=radio name="add_delete" value="delete" />
<p>
ASAP will just select the next availible time, choose specific time if you want to specify a slot in the queue:
<br>
<b>ASAP</b> <input type=radio name="whenToRun" value="asap" checked/>
<b>Specific Time</b> <input type=radio name="whenToRun" value="specific" />
<p>
Choose a number of times you need CPU priority:
<br>
<b>Number of Repetitions</b>:
0 <input type=radio name="reps" value="0" checked/>
1 <input type=radio name="reps" value="1"/>
2 <input type=radio name="reps" value="2"/>
3 <input type=radio name="reps" value="3"/>
4 <input type=radio name="reps" value="4"/>

<p>

<?php findNextQueue( 1, $timesOccupied, $arr ); ?>

<p>
Only enter a time/date here if your request is for a time more than 24 hours from now.<br>
Year (two digits) <input type=text maxlength=2 size=2 name="year"/> 
Month (1-12) <input type=text maxlength=2 size=2 name="month"/> 
Date (1-31) <input type=text maxlength=2 size=2 name="date"/> 
Hour (0-23) <input type=text maxlength=2 size=2 name="hour"/> 
<p>
<!--Units <input type=text maxlength=2 size=2 name="units"/>-->
<!--<p>-->
<input type="hidden" name="action" value="submitted" />
<input type="submit" name="submit" value="Submit" />
<input type="reset" name="reset" value="Reset" />
</form>

<p>
