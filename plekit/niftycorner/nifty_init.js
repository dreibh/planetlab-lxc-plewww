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
  var sizes= { 0: 'small', 1: 'medium', 2: 'big', length: 3};
  $A(sizes).each (function (size) {
    
    var elements=document.getElementsByClassName('nifty-'+size);
    for (var i=0; i<elements.length; i++) {
      // use Rounded rather than Nifty
      // the latter needs an id that some elements don't have
      // plus, it's more efficient anyway
      pleRounded(elements[i],size);
    }
    });
  nifty_inited = true;
}

