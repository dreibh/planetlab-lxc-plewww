<?php
// $Id$

// Drupalish, but does not use Drupal itself to generate the form

require_once 'plc_functions.php';

function build_site_form ($register_mode) {
  $form = array();
  $form['site:name'] = array('title' => 'Site name', 'required' => TRUE,
			     'maxlength' => 40, 'size' => 20,
			     'comment' => '<span class='bold'>Site Information</span>');
  $form['site:login_base'] = array('title' => 'Login base', 'required' => TRUE,
				   'maxlength' => 16, 'size' => 10);
  $form['site:abbreviated_name'] = array('title' => 'Abbreviated name', 'required' => TRUE,
					 'maxlength' => 40, 'size' => 20);
  $form['site:url'] = array('title' => 'URL', 'required' => TRUE,
			    'maxlength' => 128, 'size' => 30);
  $form['site:latitude'] = array('title' => 'Latitude', 'required' => TRUE,
				 'maxlength' => 10, 'size' => 10, 'type' => 'double');
  $form['site:longitude'] = array('title' => 'Longitude', 'required' => TRUE,
				  'maxlength' => 10, 'size' => 10, 'type' => 'double');

  $form['pi:first_name'] = array('title' => 'PI First Name', 'required' => TRUE,
				 'maxlength' => 20, 'size' => 20,
				 'comment' => '<span class='bold'>Principal Investigator Information</span>');
  $form['pi:last_name'] = array('title' => 'PI Last Name', 'required' => TRUE,
				'maxlength' => 20, 'size' => 20);
  $form['pi:title'] = array('title' => 'PI Title', 'required' => FALSE,
			    'maxlength' => 6, 'size' => 6);
  $form['pi:phone'] = array('title' => 'PI Phone', 'required' => TRUE,
			    'maxlength' => 20, 'size' => 20);
  $form['pi:email'] = array('title' => 'PI email', 'required' => TRUE,
			    'maxlength' => 40, 'size' => 20);
  if ($register_mode) {
    $form['pi:password'] = array('title' => 'PI password', 'required' => TRUE,
				 'maxlength' => 20, 'size' => 20, 'type' => 'password');
  }


  if ($register_mode) {
    // required for the following code
    drupal_set_html_head('<script type="text/javascript" src="/planetlab/sites/site_form.js"></script>');

    $fill_from_pi_button = <<< EOF
<input type="button" value="Same as PI" onclick='copyValue("edit-pi:first_name","edit-tech:first_name");
copyValue("edit-pi:last_name","edit-tech:last_name");
copyValue("edit-pi:title","edit-tech:title");
copyValue("edit-pi:phone","edit-tech:phone");
copyValue("edit-pi:email","edit-tech:email");
copyValue("edit-pi:password","edit-tech:password")' 
</input> 
EOF;
  }


  $form['tech:first_name'] = array('title' => 'Tech First Name', 'required' => TRUE,
				   'maxlength' => 20, 'size' => 20,
				   'comment' => '<span class='bold'>Technical Contact Information</span>' . $fill_from_pi_button);
  $form['tech:last_name'] = array('title' => 'Tech Last Name', 'required' => TRUE,
				  'maxlength' => 20, 'size' => 20);
  $form['tech:title'] = array('title' => 'Tech Title', 'required' => FALSE,
			      'maxlength' => 6, 'size' => 6);
  $form['tech:phone'] = array('title' => 'Tech Phone', 'required' => TRUE,
			    'maxlength' => 20, 'size' => 20);
  $form['tech:email'] = array('title' => 'Tech email', 'required' => TRUE,
			      'maxlength' => 40, 'size' => 20);
  if ($register_mode) {
    $form['tech:password'] = array('title' => 'Tech password', 'required' => TRUE,
				   'maxlength' => 20, 'size' => 20, 'type' => 'password');
    $form['tech:user-role'] = array('type' => 'boolean', 'title' => 'Need user role', 'default' => TRUE);
  }

  $form['address:line1'] = array('title' => 'Address', 'required' => FALSE,
				 'maxlength' => 40, 'size' => 30,
				 'comment' => '<span class='bold'>Postal address</span>');
  $form['address:line2'] = array('title' => 'Address (2)', 'required' => FALSE,
				 'maxlength' => 40, 'size' => 30);
  $form['address:line3'] = array('title' => 'Address (3)', 'required' => FALSE,
				 'maxlength' => 40, 'size' => 30);
  $form['address:city'] = array('title' => 'City', 'required' => FALSE,
				'maxlength' => 20, 'size' => 20);
  $form['address:postalcode'] = array('title' => 'Postal Code', 'required' => FALSE,
				      'maxlength' => 10, 'size' => 10);
# would have liked it *not* required but it is mandatory in the DB - sigh
  $form['address:state'] = array('title' => 'State', 'required' => FALSE,
				 'maxlength' => 20, 'size' => 20);
  $form['address:country'] = array('title' => 'Country', 'required' => FALSE,
				   'maxlength' => 20, 'size' => 20);

  return $form;
}

// input : 
// $form : the form as defined above
// $request : usually $_REQUEST
// $input : a dict ('site'=>$site ..)
// takes the values from the request and fills $input accordingly
// output
// $input : the modified dict, with as many keys as form categories,
// + the 'is_empty' key that returns a boolean, FALSE if any field was set in the request

function parse_form ($form, $request, $input = NULL) {
  if (empty ($input)) {
    $input = array();
  }
  $empty_form = TRUE;

  // fill with values form the form
  foreach ($form as $fullname => $item) {
    list($objname,$field) = explode(":",$fullname);
    $raw_input=$request[$fullname];
    if (!empty($raw_input)) {
      $empty_form = FALSE;
      // implement type conversion
      switch ($item['type']) {
      case 'double':
	$input[$objname][$field] = doubleval(trim($raw_input));
	break;
      case 'int':
	$input[$objname][$field] = intval(trim($raw_input));
	break;
      case 'boolean':
	$input[$objname][$field] = ($raw_input=="yes");
	break;
      case 'password':
      case 'raw':
	$input[$objname][$field] = $raw_input;
	break;
      default:
	$input[$objname][$field] = trim($raw_input);
	break;
      }
    } else {
      switch ($item['type']) {
      case 'double':
	$input[$objname][$field] = 0.0;
	break;
      case 'int':
	$input[$objname][$field] = 0;
	break;
      case 'boolean':
	if (array_key_exists($field,$request)) {
	  $input[$objname][$field]=FALSE;
	}
	break;
      default:
	$input[$objname][$field] = '';
	break;
      }
    }
  }

  $input['is_empty'] = $empty_form;

  return $input;
}

// checks all required fields are filled
// returns a - possibly empty - html error string
function form_check_required ($form, $input) {
  $missing = array();
  foreach ($form as $fullname => $item) {
    list($objname,$field) = explode(":",$fullname);
    if ($item['required'] && empty($input[$objname][$field])) {
      $missing[] = $item['title'];
    }
  }
  if (empty($missing)) 
    return "";
  $error = "<ul>";
  foreach ($missing as $field) {
    $error .= "<li>Field '$field' is required.</li>";
  }
  $error .= "</ul>";
  return $error;
}

// displays the actual form, with values from $input
// if $outline_missing is set, missing required fields are outlined
// fields typed as 'password' are displayed differently
// expected to be embedded in a table with 2 columns
function form_render_details ($details, $site_form, $input, $outline_missing) {

  foreach ($site_form as $fullname => $item) {
    
    list($objname,$field) = explode(":",$fullname);
    
    // render the comment field
    if ( ! empty($item['comment'])) {
      $details->space();
      $details->tr ($item['comment'] . ":");
    }

    // compute line attributes
    $title = $item['title'];
    $required = $item['required'] ? '<span class="form-required" title="This field is required.">*</span>' : "";
    $class = $item['required'] ? "required" : "";
    if ($outline_missing && $item['required'] && empty($input[$objname][$field])) {
      $class .= " error";
    }

    // Label part
    $left_part = "<label class='$class' for='edit-$fullname'>$title: $required</label>";

    // input part
    if ($item['type'] == 'boolean') {
      // compute boolean default : whether we have this in the request
      if (array_key_exists($field,$input[$objname])) {
	$default = $input[$objname][$field] ;
      } else { // or not - in which case we use the form default
	$default = ($item['default'] == TRUE);
      }
      if ($default) {
	$checkedyes = "checked='checked'";
	$checkedno = "";
      } else {
	$checkedyes = "";
	$checkedno = "checked='checked'";
      }
      $right_part = <<<EOF
<input type='radio' id="check-$fullname" name="$fullname" value="yes" $checkedyes> Yes
<input type='radio' id="check-$fullname" name="$fullname" value="no"  $checkedno> No
EOF;
    } else {
      $type = ($item['type'] == 'password') ? "password" : "text";
      $value = !empty($input[$objname][$field]) ? $input[$objname][$field] : "";
      $maxlength = $item['maxlength'];
      $size = $item['size'];
      $right_part= <<<EOF
<input type="$type" id="edit-$fullname" name="$fullname" value="$value" 
size="$size" maxlength="$maxlength" 
class="form-text $class" /> 
EOF;
    }

    $details->th_td($left_part,$right_part);
  }
}

?>
