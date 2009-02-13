<?php
  // $Id$

drupal_set_html_head('
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

// examples
// function my_tab () { return array('label'=>'The Text','url'=>'http://google.com'); }
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

function plc_tabs ($array) {
  print '<div id="minitabs-container">';
  print '<ul id="minitabs-list">';
  print "\n";
  foreach ($array as $label=>$todo) {
    // the 'label' key, if set in the hash, supersedes $key
    if ($todo['label']) $label=$todo['label'];
    $tracer="class=minitabs";
    if ($todo['id']) 
      $tracer .= " id=".$todo['id'];
    printf ("<li %s>\n",$tracer);
    // in case we have a string, rewrite it as an array
    if (is_string ($todo)) $todo=array('method'=>'GET','url'=>$todo);
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
	printf('<input class="minitabs-hidden" type=hidden name="%s" value="%s" />',$key,$value);
      }
    $tracer="class=minitabs-submit";
    // image and its companions 'height' 
    if ($todo['image']) {
      $type='type=image src="' . $todo['image'] . '"';
      if ($todo['height']) $type.= ' height=' . $todo['height'];
    } else {
      $type='type=button value="' . $label . '"';
    }
    printf('<span title="%s">',$todo['bubble']);
    $message="";
    if ($todo['confirm']) $message=$todo['confirm'] . " ?";
    printf('<input %s %s onclick=\'miniTab.submit("%s");\' />',$tracer,$type,$message);
    printf('</span>',$todo['bubble']);
    printf("</form></li>\n");
  }
  print '</ul>';
  print '</div>';
  print "<p class='plc-minittabs'></p>\n";
}

?>
