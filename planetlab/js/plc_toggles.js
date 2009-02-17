/* 
   $Id$
*/

function plc_toggle (id) {
  var area=$('toggle-area-' + id);
  area.toggle();
  var visible=$('toggle-image-visible-' + id);
  var hidden=$('toggle-image-hidden-' + id);
  if (area.visible()) {
    visible.show();
    hidden.hide();
  } else {
    visible.hide();
    hidden.show();
  }
}
