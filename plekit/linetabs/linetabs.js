/*
  $Id$
 
  Animated linetabs by frequency decoder (http://www.frequency-decoder.com/)
 
  Based on an idea by Rob L Glazebrook (http://www.rootarcana.com/test/smartmini/) itself
  derived from the original idea of Stephen Clark (http://www.sgclark.com/sandbox/minislide/)
 
  Rewritten by Thierry Parmentelat -- INRIA 
  support http-POST
  support multiple instances
  uses prototype.js  

*/

/* class */
function linetabs () {
  this.currentTab = 0;
  this.activeTab = 0;
  this.destX = 0;
  this.destW = 0;
  this.t = 0;
  this.b = 0;
  this.c = 0;
  this.d = 20;
  this.animInterval = null;
  this.slideObj = null;
  this.aHeight = 0;
}

linetabs.prototype.init = function (div) {
  this.ul      = div.down('ul');
  /* the array of <li>'s */
  this.li_s    = this.ul.select('li');
  /* the array of active <input>'s - without any hidden one */
  this.input_s = this.ul.select('input.linetabs-submit');
  
  /* attach event handlers */
  this.li_s.each ( function (li) { 
      li.observe ('mouseover', function(event) {
	  var elem = event.element();
	  /* make sure we're on the 'li' element */
	  if ( ! elem.match('li') ) elem=elem.up('li');
	  /* determine current position */
	  var pos = 0;
	  while(elem.previousSibling) {
	    elem = elem.previousSibling;
	    if (elem.tagName && elem.tagName == "LI") pos++;
	  }
	  linetabs_namespace.the_linetabs(elem).initSlide(pos,true);
	} )
	} );
  
  this.ul.observe('mouseout', function (event) {
      var mt = linetabs_namespace.the_linetabs(event.element());
      mt.initSlide(mt.currentTab,true);
      mt.setActive (mt.activeTab,false);
    });
  
  /* set active and current, default is index 0, set 'active' class otherwise */
  this.input_s.each ( function (input) {
      if (input.hasClassName("active")) this.activeTab = this.currentTab = i;
    });
  
  /* create slice object */
  this.slideObj    = this.ul.parentNode.appendChild(document.createElement("div"));
  this.slideObj.appendChild(document.createTextNode(String.fromCharCode(160)));
  this.slideObj.id = "linetabs-sliding";
  
    /* position it */
  this.setSlidingTop();
  this.slideObj.style.left     = (this.ul.offsetLeft + this.li_s[this.activeTab].offsetLeft + 
				  this.input_s[this.activeTab].offsetLeft) + "px";
  this.slideObj.style.width    = this.input_s[this.activeTab].offsetWidth + "px";
  this.aHeight                 = (this.ul.offsetTop + this.li_s[this.activeTab].offsetTop + 
				  this.input_s[this.activeTab].offsetTop);
  
  this.initSlide(this.activeTab, true);
    
};

linetabs.prototype.initSlide = function (pos, force) {
  
  if(!force && pos == this.activeTab) return;
  this.setActive (this.activeTab,false);
  this.activeTab = pos;
  this.setActive (this.activeTab,true);
  this.initAnim();
};
 
linetabs.prototype.setActive = function (pos,active) {
  var input=this.li_s[pos].select('input.linetabs-submit')[0];
  if (active)
    input.addClassName('active');
  else
    input.removeClassName('active');
};
  
linetabs.prototype.setSlidingTop = function () {
  var delta=0;
  /* up 5px for firefox */
  /*window.console.log('agent=' + navigator.userAgent);*/
  if (navigator.userAgent.match(/Firefox/)) delta=-5; 
  this.slideObj.style.top  = (this.ul.offsetTop + this.li_s[this.activeTab].offsetTop 
			      + this.input_s[this.activeTab].offsetTop + delta ) + "px";
};
  
linetabs.prototype.initAnim = function() {
  /* search for the input with type != hidden */
  var input=this.li_s[this.activeTab].select('input.linetabs-submit')[0];
  this.destX = parseInt(this.li_s[this.activeTab].offsetLeft + input.offsetLeft 
			+ this.ul.offsetLeft);
  this.destW = parseInt(input.offsetWidth);
  this.t = 0;
  this.b = this.slideObj.offsetLeft;
  this.c = this.destX - this.b;
  
  this.bW = this.slideObj.offsetWidth;
  this.cW = this.destW - this.bW;
  
  this.setSlidingTop();
};
  
linetabs.prototype.slideIt = function() {
  
  // Has the browser text size changed?
  var active_li = this.li_s[this.activeTab];
  var active_input = this.input_s[this.activeTab];
  if (this.aHeight != this.ul.offsetTop + active_li.offsetTop + active_input.offsetTop) {
    this.initAnim();
    this.aHeight = this.ul.offsetTop + active_li.offsetTop + active_input.offsetTop;
  }
  
  
  if (this.t++ < this.d) {
    var x = this.animate(this.t,this.b,this.c,this.d);
    var w = this.animate(this.t,this.bW,this.cW,this.d);
    
    this.slideObj.style.left = parseInt(x) + "px";
    this.slideObj.style.width = parseInt(w) + "px";
  } else {
    this.slideObj.style.left = this.destX + "px";
    this.slideObj.style.width = this.destW +"px";
  }
};
  
linetabs.prototype.animate = function(t,b,c,d) {
  if ((t/=d/2) < 1) return c/2*t*t + b;
  return -c/2 * ((--t)*(t-2) - 1) + b;
};

linetabs.prototype.submit = function (message) {
  /* save activeTab before confirmation; some browsers - firefox - send mouseout during confirm .. */
  var submitTab = this.activeTab;
  /* ask for confirmation if message is not empty */
  if (message && ! confirm (message) ) return;

  /* get the form and trigger */
  this.li_s[submitTab].down('form').submit();
  
}
  
// globals
var linetabs_namespace = {
 init: function () {
    $$('div.linetabs').each (function (div) {   
	/* create instance and attach it to the <div> element */
	div.linetabs = new linetabs ();
	div.linetabs.init(div);
      } ) ;
    
    var intervalMethod = function () {
      $$('div.linetabs').each (function (div) {
	  linetabs_namespace.the_linetabs(div).slideIt();
	} ) ;
    } ;
    linetabs_namespace.animInterval = setInterval(intervalMethod,10);
  },
 
 cleanUp: function() {
    clearInterval(linetabs_namespace.animInterval);
    linetabs_namespace.animInterval = null;
  },
 
 resize: function (e) {
    $$('div.linetabs').each ( function (div) { 
	var mt = div.linetabs; 
	mt.initSlide(mt.activeTab,true);
      } );
  },

 submit: function (id,message) {
    $(id).linetabs.submit(message);
  },

 // find the enclosing linetabs object
 the_linetabs: function (elem) {
    if (elem.match('div.linetabs')) 
      return elem.linetabs;
    else 
      return elem.up('div.linetabs').linetabs;
  }
 
};
 
window.onload   = linetabs_namespace.init;
window.onunload = linetabs_namespace.cleanUp;
window.onresize = linetabs_namespace.resize;
