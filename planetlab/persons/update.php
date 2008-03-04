<?

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];


$is_submitted= isset($_POST['submitted']) ? $_POST['submitted'] : 0;

// show details for the current user.
if( isset($_GET['id']) && is_numeric($_GET['id']) ) {
  $person_id= intval($_GET['id']);
 } else {
  header( "location: index.php" );
  exit();
 }

$errors= array();

if( $is_submitted ) {
  // attempt to update this person
  $first_name= $_POST['first_name'];
  $last_name= $_POST['last_name'];
  $title= $_POST['title'];
  $email= $_POST['email'];
  $phone= $_POST['phone'];
  $url= $_POST['url'];
  $bio= str_replace("\r", "", $_POST['bio']);
  $password1= $_POST['password1'];
  $password2= $_POST['password2'];

  if( $password1 != $password2 ) {
    $errors[]= "The passwords do not match";
  }

  if( count($errors) == 0 ) {   
    $update_vals= array();
    $update_vals['first_name']= $first_name;
    $update_vals['last_name']= $last_name;
    $update_vals['title']= $title;
    $update_vals['email']= $email;
    $update_vals['phone']= $phone;
    $update_vals['url']= $url;
    $update_vals['bio']= $bio;
		
    if( $password1 != "" )
      $update_vals['password']= $password1;
    
    $rc= $api->UpdatePerson( intval( $person_id ), $update_vals);
    
    if( $rc == 1 ) {
      Header( "Location: /db/persons/index.php?id=$person_id" );
      exit();
    }
    elseif ($rc === NULL) {
      $errors[] = $api->error();
    }
  }
 } else {
  // get details for the user
  $person_details= $api->GetPersons( array( intval( $person_id ) ), array( "person_id", "first_name", "last_name", "title", "email", "phone", "url", "bio" ) );
  if ( $person_details === NULL ) {
    $errors[] = $api->error();
  } else {
    $person_detail= $person_details[0];
  
    $first_name= $person_detail['first_name'];
    $last_name= $person_detail['last_name'];
    $title= $person_detail['title'];
    $email= $person_detail['email'];
    $phone= $person_detail['phone'];
    $url= $person_detail['url'];
    $bio= $person_detail['bio'];
  }
}

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Update Person');
include 'plc_header.php';

?>

<h2>Update Account</h2>

<?
if( count($errors) > 0 )
{
  print( "<p><strong>The following errors occured:</strong>" );
  print( "<font color='red' size='-1'><ul>\n" );
  foreach( $errors as $err )
    {
      print( "<li>$err\n" );
    }
  print( "</ul></font>\n" );
}
?>

<h3>Personal Information</h3>

<form method="post" action="update.php?id=<?php print($person_id); ?>">
<input type="hidden" name="submitted" value="1">

<table width="100%" cellspacing="0" cellpadding="4" border="0">

<tr>
<td>First Name:</td>
<td><input type="text" name="first_name"
value="<?php print($first_name); ?>" size="30" maxlength="256"></td>
</tr>

<tr>
<td>Last Name:</td>
<td><input type="text" name="last_name"
value="<?php print($last_name); ?>" size="30" maxlength="256"></td>
</tr>

<tr>
<td>Title:</td>
<td><input type="text" name="title"
value="<?php print($title); ?>" size="30" maxlength="256"></td>
</tr>

<tr>
<td>Email:</td>
<td><input type="text" name="email"
value="<?php print($email); ?>" size="30" maxlength="256"></td>
</tr>

<tr>
<td>Phone:</td>
<td><input type="text" name="phone"
value="<?php print($phone); ?>" size="30" maxlength="32"></td>
</tr>

<tr>
<td>URL:</td>
<td><input type="text" name="url"
value="<?php print($url); ?>" size="30" maxlength="200"></td>
</tr>

<tr>
<td valign=top>Bio:</td>
<td><textarea name="bio" cols="40" rows="5" wrap>
<?php print($bio); ?>
</textarea></td>
</tr>

<tr>
<td>Password (blank for no change):</td>
<td><input type="password" name="password1" size="30" maxlength="256"></td>
</tr>

<tr>
<td>Repeat Password:</td>
<td><input type="password" name="password2" size="30" maxlength="256"></td>
</tr>

</table>

<input type="submit" name="Submit" value="Update">

</form>

<?

// Print footer
include 'plc_footer.php';

?>
