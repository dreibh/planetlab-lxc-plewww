<?php
//
// PlanetLab session handling. In a Drupal environment, session
// variables are stored in the database (i.e., the session handling
// functions have been overridden). By default, they are stored on the
// filesystem.
//
// To use, include this file and declare the global variable
// $plc. This object contains the following members:
//
// person: If logged in, the user's GetPersons() details
// api: If logged in, the user's API handle
//
// Mark Huang <mlhuang@cs.princeton.edu>
// Copyright (C) 2006 The Trustees of Princeton University
//
// $Id$ $
//

// Usually in /etc/planetlab/php
require_once 'plc_config.php';

// Usually in /usr/share/plc_api/php
require_once 'plc_api.php';

$cwd = getcwd();
chdir($_SERVER['DOCUMENT_ROOT']);
$included = include_once('./includes/bootstrap.inc');
if ($included === TRUE) {
  // Already included, no need to bootstrap
} elseif ($included) {
  // Not already included, initialize Drupal session handling
  drupal_bootstrap(DRUPAL_BOOTSTRAP_SESSION);
} else {
  // Drupal not available, use regular PHP session handling
  session_start();
}
chdir($cwd);

class PLCSession
{
  var $api;
  var $person;
  var $alt_person;
  var $alt_auth;

  function PLCSession($name = NULL, $pass = NULL)
  {
    $name= strtolower( $name );
    // User API access
    if ($name && $pass) {
      $api = new PLCAPI(array('AuthMethod' => "password",
			      'Username' => $name,
			      'AuthString' => $pass));

      // Authenticate user and get session key
      $session = $api->GetSession();
      if (!$session) {
	return NULL;
      }

      // Change GetSession() at some point to return expires as well
      $expires = time() + (24 * 60 * 60);

      // Change to session authentication
      $api->auth = array('AuthMethod' => "session", 'session' => $session);
      $this->api = $api;

      // Get account details
      list($person) = $api->GetPersons(array('email'=>$name,'peer_id'=>NULL));
      $this->person = $person;

      // Save session variables
      $_SESSION['plc'] = array('auth' => $api->auth,
			       'person' => $person,
			       'expires' => $expires);
     }	
    }

    function BecomePerson($person_id)
    {
	list($person) = $this->api->GetPersons(array($person_id));
	if ($person)
	{
	    //Get this users session if one exists, create 
	    //one otherwise
	    list($session) = $this->api->GetSessions(array('person_id' => $person['person_id']));
	    if (!$session)
	    {
		$session = $this->api->AddSession($person['person_id']);	
	    }
    	    else
	    {
		$session = $session['session_id'];
	    }

	    // Update session authentication info
	    $this->alt_auth = $this->api->auth;
	    $this->api->auth = array('AuthMethod' => "session", 'session' => $session);

	    // su to user
	    $this->alt_person = $this->person;
	    $this->person = $person;

	    // Save session variables
	    $_SESSION['plc']['auth'] = $this->api->auth;
	    $_SESSION['plc']['person'] = $this->person;
	    $_SESSION['plc']['alt_person'] = $this->alt_person;
            $_SESSION['plc']['alt_auth'] = $this->alt_auth;
	    
	}    	
    }

    function BecomeSelf()
    {	
	if($this->alt_auth && $this->alt_person )
	{
	    $this->person = $this->alt_person;
	    $this->api->auth = $this->alt_auth;
	    $this->alt_person = NULL;
	    $this->alt_auth = NULL;

	    $_SESSION['plc']['auth'] = $_SESSION['plc']['alt_auth'];
	    $_SESSION['plc']['person'] = $_SESSION['plc']['alt_person'];
	    unset($_SESSION['plc']['alt_auth']);
            unset($_SESSION['plc']['alt_person']);
	} 
    }
  

  function logout()
  {
    $this->api->DeleteSession();
  }
}

global $plc, $api;

$plc = new PLCSession();

if (!empty($_SESSION['plc'])) {
  if ($_SESSION['plc']['expires'] > time()) {
    $plc->person = $_SESSION['plc']['person'];
    $plc->api = new PLCAPI($_SESSION['plc']['auth']);
    $plc->alt_person = $_SESSION['plc']['alt_person'];
    $plc->alt_auth = $_SESSION['plc']['alt_auth'];
  } else {
    // Destroy PHP session
    session_destroy();
  }
}

// For convenience
$api = $plc->api;

?>
