/* 
  $Id$

 instead of explicitly calling 
 Nifty ('div#$id','medium');
 on every single element as the original niftycube.js recommends, 
 we just do that on every element that has one of the
 the nifty-{small,medium,big} class set
*/

var nifty_inited = false;

function nifty_init () {
  if ( nifty_inited ) return;
  var elements=document.getElementsByClassName('nifty-medium');
  for (var i=0; i<elements.length; i++) {
    // somehow we catch something with an empty id
    id = elements[i].id;
    if (id) 
      Nifty('div#'+id,'medium');
  }
  nifty_inited = true;
}

