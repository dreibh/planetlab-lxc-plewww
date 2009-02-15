<?php
  // $Id$

drupal_set_html_head('
<script type="text/javascript" src="/planetlab/prototype/prototype.js"></script>
<script type="text/javascript" src="/planetlab/minitabs/minitabs.js"></script>
<link href="/planetlab/minitabs/minitabs.css" rel="stylesheet" type="text/css" />
');


// the expected argument is an (ordered) associative array
// ( label => todo , ...)
// label is expected to be the string to display in the menu
// todo can be either
// (*) a string : it is then taken to be a URL to move to
// (*) or an associative array with the following keys
//     (*) 'label' : if set, this overrides the string key just above
//	   this is used for functions that return a tab, more convenient to write&use
//     (*) 'method': 'POST' or 'GET' -- default is 'GET'
//     (*) 'url': where to go
//     (*) 'values': an associative array of (key,value) pairs to send to the URL; values are strings
//     (*) 'confirm': a question to display before actually triggering
//     (*) 'bubble': a longer message displayed when the mouse stays quite for a while on the label
//     (*) 'image' : the url of an image used instead of the label
//     (*) 'height' : used for the image
//     (*) 'id' : assign given id to the <li> element

// NOTE
// (*) values can also be set in the URL, e.g. ?var=value&foo=bar, even for POST'ing
// (*) several instances can appear on the same page but you need to give them different id's

// EXAMPLES
// function my_tab () { return array('label'=>'Go to google','url'=>'http://google.com'); }
// $tabs=array();
// $tabs[] = my_tab();
// $tabs['Simple Tab']="http://planet-lab.org";
// $tabs['Complex Tab']=array('url'=>'http://planet-lab.org/',
//			      'bubble'=>'This text gets displayed when the mouse remains over for a while');
// plc_tabs($tabs);

////////// Notes: limited support for images
// (*) for some reason, confirmation does not work with image tabs 
//     (the form gets submitted whatever the confirmation....)
// (*) you need to tune the image size, which is wrong, as the image should rather be bottom-aligned 

function plc_tabs ($array, $id=NULL) {
  // need id to pass to the onclick function attached to the input buttons
  $id="minitabs";
  if (! $id) $id .= '-' + $id;
  print "<div id='$id' class='minitabs'>";
  print "<ul>";
  foreach ($array as $label=>$todo) {
    // in case we have a simple string, rewrite it as an array
    if (is_string ($todo)) $todo=array('method'=>'GET','url'=>$todo);
    // the 'label' key, if set in the hash, supersedes key
    if ($todo['label']) $label=$todo['label'];
    $tracer="";
    if ($todo['id']) $tracer .= "id=".$todo['id'];
    print "<li $tracer>";
    // set default method
    if ( ! $todo['method'] ) $todo['method']='GET';
    // extract var=value settings from url if any
    $full_url=$todo['url'];
    $split=split_url($full_url);
    $url=$split['url'];
    $url_values=$split['values'];

    // create form
    $method=$todo['method'];
    print "<form name='$label' action='$url' method='$method'>";
    // set values
    $values=$todo['values'];
    if ( ! $values) $values = array();
    if ($url_values) $values = array_merge($values,$url_values);
    if ( $values ) foreach ($values as $key=>$value) {
	print "<input type=hidden name='$key' value='$value' />";
      }
    $tracer="class=minitabs-submit";
    // image and its companions 'height' 
    if ($todo['image']) {
      $type='type=image src="' . $todo['image'] . '"';
      if ($todo['height']) $type.= ' height=' . $todo['height'];
    } else {
      $type='type=button value="' . $label . '"';
    }
    $bubble=$todo['bubble'];
    print "<span title='$bubble'>";
    $message="";
    if ($todo['confirm']) $message=$todo['confirm'] . " ?";
    print "<input $tracer $type onclick='minitabs_namespace.submit(\"$id\",\"$message\")' />";
    print "</span>";
    print "</form></li>\n";
  }
  print '</ul>';
  print '</div>';
  print "<p class='plc-minittabs'></p>\n";
}

?>
