# $Id$

class plekit_table:

    def __init__ (self, table_id, headers, column_sort, 
                  caption=None,
                  search_area=True, pagesize_area=True, notes_area=True,
                  search_width=40, pagesize=250, pagesize_def=999,
                  max_pages=10,
                  notes=None):
        self.table_id=table_id
        self.headers=headers
        self.column_sort =column_sort 
        self.caption=caption
        self.search_area=search_area
        self.pagesize_area=pagesize_area
        self.notes_area=notes_area
        self.search_width =search_width 
        self.pagesize =pagesize 
        self.pagesize_def=pagesize_def
        self.max_pages=max_pages
        self.notes=notes
        self.has_tfoot=False

    def columns (self):
        return len(self.headers)

    def start (self):
        paginator = self.table_id + "_paginator"
        classname = "paginationcallback-%s" % paginator
        classname += " max-pages-%d" % self.max_pages
        classname += " paginate-%d" % self.pagesize
        # instantiate paginator callback
        table_id = self.table_id
        print """
<script type="text/javascript"> 
function %(paginator)s (opts) { plekit_table_paginator (opts,"%(table_id)s)"; }
</script>
<br/>
<table id="%(table_id)s" cellpadding="0" cellspacing="0" border="0" 
class="plekit_table sortable-onload-self.sort_column rowstyle-alt colstyle-alt no-arrow %(classname)s">
<thead>
""" % locals()

        if self.pagesize_area:
            print self.pagesize_area_html ()
        if self.search_area:
            print self.search_area_html ()

        if (self.caption):
            print "<caption> %s </caption>" % self.caption
        print "<tr>"
        for (label,type) in self.headers.iteritems():
            if type == "none":
                classpart=""
            elif type == "string" or type == "int" or type == "float":
                classpart="sortable"
            elif type.find ("date-") == 0:
                classpart="sortable-" + type
            else:
                classpart="sortable-sort" + type
            print '<th class="%(classpart)s plekit_table">%(label)s</th>'%locals()

        print """</tr></thead><tbody>\n"""

    ##########
    ## unlike the php version there is no support for last-minute options, pass them all to the constructor
    def end (self):
        print self.bottom_html()
        if (self.notes_area):
            print self.notes_area_html()
		    
    ##########
    def pagesize_area_html (self):
        width=len(self.headers)
        pagesize_text_id = self.table_id + "_pagesize"
        result_dict = locals()
        result_dict.update(self.__dict__)
        result = """
<tr class='pagesize_area'><td class='pagesize_area' colspan='%(width)s'>
<form class='pagesize' action='satisfy_xhtml_validator'><fieldset>
   <input class='pagesize_input' type='text' id="%(pagesize_text_id)s" value='%(pagesize)s'
      onkeyup='plekit_pagesize_set("%(table_id)s","%(pagesize_text_id)s", %(pagesize)s);' 
      size='3' maxlength='3' /> 
  <label class='pagesize_label'> items/page </label>   
  <img class='reset' src="/planetlab/icons/clear.png" alt="reset visible size"
      onmousedown='plekit_pagesize_reset("%(table_id)s","%(pagesize_text_id)s",%(pagesize_def)s);' />
</fieldset></form></td></tr>
""" % result_dict
        return result

    ##########      
    def search_area_html (self):
        width = len(self.headers)
        search_text_id = self.table_id + "_search"
        search_reset_id = self.table_id + "_search_reset"
        search_and_id = self.table_id + "_search_and"
        result_dict = locals()
        result_dict.update(self.__dict__)
        result = """
<tr class='search_area'><td class='search_area' colspan='%(width)s'>
<div class='search'><fieldset>
   <label class='search_label'> Search </label> 
   <input class='search_input' type='text' id='%(search_text_id)s'
      onkeyup='plekit_table_filter("%(table_id)s","%(search_text_id)s","%(search_and_id)s");'
      size='%(search_width)s' maxlength='256' />
   <label>and</label>
   <input id='%(search_and_id)s' class='search_and' 
      type='checkbox' checked='checked' 
      onchange='plekit_table_filter("%(table_id)s","%(search_text_id)s","%(search_and_id)s");' />
   <img class='reset' src="/planetlab/icons/clear.png" alt="reset search"
      onmousedown='plekit_table_filter_reset("%(table_id)s","%(search_text_id)s","%(search_and_id)s");' />
</fieldset></div></td></tr>
""" % result_dict
        return result

    ##########
    ## start a <tfoot> section
    def tfoot_start (self): print self.tfoot_start_html()
    def tfoot_start_html (self):
        self.has_tfoot=true
        return "</tbody><tfoot>"

    ##########
    def bottom_html (self):
        result=""
        if self.has_tfoot:
            result += "</tfoot>"
        else:
            result += "</tbody>"
            result += "</table>\n"
        return result

    ##########
    default_notes =  [
        "Enter &amp; or | in the search area to switch between <span class='bold'>AND</span> and <span class='bold'>OR</span> search modes",
        "Hold down the shift key to select multiple columns to sort" ]

    def notes_area_html (self):
        if (self.notes):
            notes=self.notes
        else:
            notes=[]
        notes = notes + self.default_notes
        result = ""
        result += "<p class='table_note'> <span class='table_note_title'>Notes</span>\n"
        for note in notes:
            result += "<br/>%s\n" % note
        result += "</p>"
        return result

    ##########
    def row_start (self, id=None, classpart=None):
        print "<tr"
        if id: print " id='%s'" % id
        if classpart: print ' class="%s"' % classpart
        print ">\n"
        
    def row_end (self):
        print "</tr>\n"

    ##########
    ## supported options:
    ## (*) only-if : if set and false, then print 'n/a' instead of (presumably void) $text
    ## (*) classpart
    ## (*) columns
    ## (*) hfill
    ## (*) align
    def cell (self, text,only_if=True, classpart=None, columns=None, hfill=None, align=None): 
        print self.cell_html (text, only_if, classpart, columns, hfill, align)
    def cell_html (self, text, only_if=True, classpart=None, columns=None, hfill=None, align=None):
        if not only_if:
            text="n/a"
        html=""
        html += "<td"
        if classpart: html += " class='%s'" % classpart
        if columns: html += " colspan='%d'" % columns
        elif hfill: html += " colspan='%d'" % self.columns()
        if align: html += " style='text-align:%s'" % align
        html += ">%s</td>" % text
        return html
