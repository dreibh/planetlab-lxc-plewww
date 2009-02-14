/*
 
  Animated miniTabs by frequency decoder (http://www.frequency-decoder.com/)
 
  Based on an idea by Rob L Glazebrook (http://www.rootarcana.com/test/smartmini/) itself
  derived from the original idea of Stephen Clark (http://www.sgclark.com/sandbox/minislide/)
 
  Adjusted by Thierry Parmentelat -- INRIA - uses only forms rather than <a> tags, for supporting http-POST
  
  $Id$

*/

/* I'm done with this - write it ourselves - don't care about perfs so much anyway */
/* define getElementsByClassName on Element if missing */
function getElementsByClassName (elt,cls) {
  try {
    var retval= elt.getElementsByClassName(cls);
    return retval;
    } catch (err) {
    var retVal = new Array();
    var elements = elt.getElementsByTagName("*");
    for (var i = 0; i < elements.length; i++) {
      var element = elements[i];
      var classes = element.className.split(" ");
      for (var c = 0; c < classes.length; c++) 
	if (classes[c] == cls) 
	  retVal.push(elements[i]);
    }
    return retVal;
  }
}

var miniTab = {
 currentTab:     0,
 activeTab:      0,
 destX:          0,
 destW:          0,
 t:              0,
 b:              0,
 c:              0,
 d:              20,
 animInterval:   null,
 sliderObj:      null,
 aHeight:        0,
 ul:             [],
 liArr:          [],
 inputArr:       [],
        
 init: function() {

    miniTab.ul          = document.getElementById("minitabs-list");
    miniTab.liArr       = miniTab.ul.getElementsByTagName("li");
    // Thierry: the original impl. relied on <a> links rather than forms 
    // we use <input>s and there might be hidden ones, so use a class marker instead
    miniTab.inputArr        = getElementsByClassName(miniTab.ul,"minitabs-submit");
 
    for(var i = 0, li; li = miniTab.liArr[i]; i++) {
      li.onmouseover = miniTab.inputArr[i].onfocus = function(e) {
	var pos = 0;
	var elem = this;
	/* some browsers - firefox - somehow trigger this on <input> */
	if (this.nodeName != "LI") return;
	/* move up until we find the 'LI' tag */
	while(elem.previousSibling) {
	  elem = elem.previousSibling;
	  if(elem.tagName && elem.tagName == "LI") pos++;
 
	}
	miniTab.initSlide(pos,true);
      }
    }
 
    miniTab.ul.onmouseout = function(e) {
      miniTab.initSlide(miniTab.currentTab,true);
      miniTab.setActive (miniTab.activeTab,false);
    };
 
    window.onresize = function (e) {
      miniTab.initSlide (miniTab.activeTab,true);
    }

    for(var i = 0, input; input = miniTab.inputArr[i]; i++) {
      if(input.className.search("active") != -1) {
	miniTab.activeTab = miniTab.currentTab = i;
      }
      /*input.style.borderBottom  = "0px";*/
      /*input.style.paddingBottom = "6px";*/
    }
 
    miniTab.slideObj                = miniTab.ul.parentNode.appendChild(document.createElement("div"));
    miniTab.slideObj.appendChild(document.createTextNode(String.fromCharCode(160)));
    miniTab.slideObj.id             = "minitabs-sliding";

    miniTab.setTop();

    miniTab.slideObj.style.left     = (miniTab.ul.offsetLeft + miniTab.liArr[miniTab.activeTab].offsetLeft + 
				       miniTab.inputArr[miniTab.activeTab].offsetLeft) + "px";
    miniTab.slideObj.style.width    = miniTab.inputArr[miniTab.activeTab].offsetWidth + "px";
    miniTab.aHeight                 = (miniTab.ul.offsetTop + miniTab.liArr[miniTab.activeTab].offsetTop + 
				       miniTab.inputArr[miniTab.activeTab].offsetTop);
 
    miniTab.initSlide(miniTab.activeTab, true);
 
    var intervalMethod = function() { miniTab.slideIt(); }
    miniTab.animInterval = setInterval(intervalMethod,10);
  },
 
 cleanUp: function() {
    clearInterval(miniTab.animInterval);
    miniTab.animInterval = null;
  },
 
 initSlide: function(pos, force) {
    if(!force && pos == miniTab.activeTab) return;
    miniTab.setActive (miniTab.activeTab,false);
    miniTab.activeTab = pos;
    miniTab.setActive (miniTab.activeTab,true);
    miniTab.initAnim();
  },
 
 setActive: function (pos,active) {
    var input=getElementsByClassName(miniTab.liArr[pos],"minitabs-submit")[0];
    var cn=input.className;
    cn=cn.replace(" active","");
    if (active) cn += " active";
    input.className=cn;
  },
 
 initAnim: function() {
    /* search for the input with type != hidden */
    var input=getElementsByClassName(miniTab.liArr[miniTab.activeTab],"minitabs-submit")[0];
    miniTab.destX = parseInt(miniTab.liArr[miniTab.activeTab].offsetLeft + input.offsetLeft + miniTab.ul.offsetLeft);
    miniTab.destW = parseInt(input.offsetWidth);
    miniTab.t = 0;
    miniTab.b = miniTab.slideObj.offsetLeft;
    miniTab.c = miniTab.destX - miniTab.b;
 
    miniTab.bW = miniTab.slideObj.offsetWidth;
    miniTab.cW = miniTab.destW - miniTab.bW;
 
    miniTab.setTop();
  },
 
 setTop: function () {
    var delta=0;
    /* up 5px for firefox */
    if (navigator.userAgent.match(/Firefox/)) delta=-5; 
    miniTab.slideObj.style.top  = (miniTab.ul.offsetTop + miniTab.liArr[miniTab.activeTab].offsetTop 
				   + miniTab.inputArr[miniTab.activeTab].offsetTop + delta ) + "px";

  },

 slideIt:function() {
 
    // Has the browser text size changed?
    if(miniTab.aHeight != miniTab.ul.offsetTop + miniTab.liArr[miniTab.activeTab].offsetTop + miniTab.inputArr[miniTab.activeTab].offsetTop) {
      miniTab.initAnim();
      miniTab.aHeight = miniTab.ul.offsetTop + miniTab.liArr[miniTab.activeTab].offsetTop + miniTab.inputArr[miniTab.activeTab].offsetTop
    };
 
    if(miniTab.t++ < miniTab.d) {
      var x = miniTab.animate(miniTab.t,miniTab.b,miniTab.c,miniTab.d);
      var w = miniTab.animate(miniTab.t,miniTab.bW,miniTab.cW,miniTab.d);
 
      miniTab.slideObj.style.left = parseInt(x) + "px";
      miniTab.slideObj.style.width = parseInt(w) + "px";
    } else {
      miniTab.slideObj.style.left = miniTab.destX + "px";
      miniTab.slideObj.style.width = miniTab.destW +"px";
    }
  },
 
 animate: function(t,b,c,d) {
    if ((t/=d/2) < 1) return c/2*t*t + b;
    return -c/2 * ((--t)*(t-2) - 1) + b;
  },

 submit: function (message) {
    /* save activeTab before confirmation; some browsers - firefox - send mouseout during confirm .. */
    var submitTab = this.activeTab;
    /* ask for confirmation if message is not empty */
    if (message && ! confirm (message) ) return;
    this.inputArr[submitTab].parentNode.parentNode.submit();
  }
}
 
window.onload = miniTab.init;
window.onunload = miniTab.cleanUp;
 
