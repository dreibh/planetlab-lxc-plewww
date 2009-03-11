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
  window.console.log('initing - 1');
  if ( nifty_inited ) return;
  window.console.log('initing - 2');
  var elements=document.getElementsByClassName('nifty-medium');
  for (var i=0; i<elements.length; i++) {
    window.console.log('catched ' + elements[i].id);
    Nifty('div#'+elements[i].id,'medium');
  }
  nifty_inited = true;
}

