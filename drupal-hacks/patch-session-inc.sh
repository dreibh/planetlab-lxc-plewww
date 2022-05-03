#!/bin/bash

# for php8; the handler attached to session_destroy must now return a boolean
# the patched function is a one-liner
# so we find its declaration, go down one line (+), and then add a line

readonly filetopatch=/var/www/html/includes/session.inc

# do not patch twice
if ! grep -q 'patch-session_destroy' $filetopatch >& /dev/null; then
    ed $filetopatch << EOF
/function sess_destroy
+
a
  return TRUE; // patch-session_destroy for php8
.
wq
EOF
fi
