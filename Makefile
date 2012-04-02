# refresh tags
tags:
	find . '(' -name '*.php' -o -name '*.js' -o -name '*.module' -o -name '*.css' -o -name Makefile ')' | xargs etags

.PHONY: tags

########## sync
# 2 forms are supported
# (*) if your plc root context has direct ssh access:
# make sync PLC=private.one-lab.org
# (*) otherwise, for test deployments, use on your testmaster
# $ run export
# and cut'n paste the export lines before you run make sync

PLCHOST ?= testplc.onelab.eu

ifdef PLC
SSHURL:=root@$(PLC):/
SSHCOMMAND:=ssh root@$(PLC)
else
ifdef PLCHOSTLXC
SSHURL:=root@$(PLCHOST):/var/lib/lxc/$(GUESTNAME)/rootfs
SSHCOMMAND:=ssh root@$(PLCHOSTLXC) ssh $(GUESTHOSTNAME)
else
ifdef PLCHOSTVS
SSHURL:=root@$(PLCHOSTVS):/vservers/$(GUESTNAME)
SSHCOMMAND:=ssh root@$(PLCHOSTVS) vserver $(GUESTNAME) exec
endif
endif
endif

LOCAL_RSYNC_EXCLUDES	:= --exclude '*.pyc' 
RSYNC_EXCLUDES		:= --exclude .svn --exclude .git --exclude '*~' --exclude TAGS $(LOCAL_RSYNC_EXCLUDES)
RSYNC_COND_DRY_RUN	:= $(if $(findstring n,$(MAKEFLAGS)),--dry-run,)
RSYNC			:= rsync -a -v $(RSYNC_COND_DRY_RUN) $(RSYNC_EXCLUDES)

sync:
	@echo "sync: I need more info from the command line, e.g."
	@echo "  make sync PLC=boot.planetlab.eu"
	@echo "  make sync PLCHOSTVS=.. GUESTNAME=.."
	@echo "  make sync PLCHOSTLXC=.. GUESTNAME=.. GUESTHOSTNAME=.."
	@exit 1
else
	+$(RSYNC) planetlab plekit modules $(SSHURL)/var/www/html/
endif

compress:
	$(foreach file,\
		$(shell find . -type f -iname "*.js" | grep -v datepicker.js | grep -v raphael),\
		$(shell python jsmin.py < $(file) > $(file).new && mv $(file).new $(file)))
	@echo "Compressed .js files with jsmin.py"

.PHONY: compress

#################### convenience, for debugging only
# make +foo : prints the value of $(foo)
# make ++foo : idem but verbose, i.e. foo=$(foo)
++%: varname=$(subst +,,$@)
++%:
	@echo "$(varname)=$($(varname))"
+%: varname=$(subst +,,$@)
+%:
	@echo "$($(varname))"
