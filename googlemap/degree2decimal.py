#!/usr/bin/env python2
# transforms a degree notation like
# 43:36:56.32 (43 degrees 36 minutes 56.32 seconds) 
# into a decimal notation

import sys
import re
pattern="^(-*\d+):(\d+):([\d\.]+)$"
matcher=re.compile(pattern)

minute=1./60
second=1./3600

def translate (coord):
    r=matcher.match(coord)
    if not r:
        print 'failed to parse',coord,'pattern=',pattern
    else:
        (deg,min,sec)=map(float,r.groups())
#        print 'deg',deg,'min',min,'sec',sec
        if (deg>0):
            print coord,'->',deg+min*minute+sec*second
        else:
            print coord,'->',deg-min*minute-sec*second
            

def main():
    for arg in sys.argv[1:]:
        translate(arg)
    
if __name__ == '__main__':
    main()
