<?php

    // utility function for displaying extra columns based on tags and categories
  // expected type is e.g. 'node'

class VisibleTags {
  var $api;
  var $type;

  function __construct ($api, $type) {
    $this->api=$api;
    $this->type=$type;
    $this->columns=NULL;
  }

  // returns an ordered set of columns - compute only once
  function columns () {
    # if cached
    if ($this->columns != NULL)
      return $this->columns;

    // scan tag types to find relevant additional columns
    $tag_types = $this->api->GetTagTypes(array('category'=>"$this->type*/ui*"));

    $columns = array();
    foreach ($tag_types as $tag_type) {
      $tagname=$tag_type['tagname'];
      $column=array();
      $column['tagname']=$tagname;
      // defaults
      $column['header']=$tagname;
      $column['rank']=$tagname;
      $column['type']='string';
      $column['description']=$tag_type['description'];
      // split category and parse any setting
      $category_tokens=explode('/',$tag_type['category']);
      foreach ($category_tokens as $token) {
	      $assign=explode('=',$token);
	      if (count($assign)==2)
	        $column[$assign[0]]=$assign[1];
      }
      $columns []= $column;
    }

    // sort upon 'rank'
    usort ($columns, function($col1, $col2) { return strcmp($col1["header"],$col2["header"]); });

    # cache for next time
    $this->columns=$columns;

    //plc_debug('columns',$columns);
    return $columns;
  }

  // extract tagname
  function column_names () {
    return array_map(function($tt) {return $tt["tagname"];},
                     $this->columns());
  }

  // to add with array_merge to the headers part of the Plekit Table
  function headers () {
    $headers=array();
    $columns=$this->columns();
    foreach ($columns as $column)
	{
	if ($column['header'] == 'f')
                continue;

	    //panos: needed a few more fields in the header array
	$headerId = $column['header'];
	if ($column['headerId'] != "")
		$headerId = $column['headerId'];

	$headers[$headerId]=array('header'=>$column['header'],'headerId'=>$headerId, 'type'=>$column['type'],'tagname'=>$column['tagname'],'title'=>$column['description']);
	}
    /*
      if ($column['header'] == $column['tagname'])
	$headers[$column['header']]=$column['type'];
      else
	$headers[$column['header']]=array('type'=>$column['type'],'title'=>$column['description']);
     */
    return $headers;
  }

  // to add with array_merge to the notes part of the Plekit Table
  function notes () {
    $notes=array();
    $columns=$this->columns();
    foreach ($columns as $column)
      if ($column['header'] != $column['tagname'])
	$notes []= strtoupper($column['header']) . ' = ' . $column['description'];
    return $notes;
  }

}
?>
