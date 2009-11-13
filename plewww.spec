#
# $Id$
#

# what the myplc rpm requires
%define name plewww
%define version 4.3
%define taglevel 34

# no need to mention pldistro as this module differs in both distros
#%define release %{taglevel}%{?pldistro:.%{pldistro}}%{?date:.%{date}}
%define release %{taglevel}%{?date:.%{date}}

Summary: PlanetLab Europe (PLC) Web Pages
Name: %{name}
Version: %{version}
Release: %{release}
License: PlanetLab
Group: Applications/Systems
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
BuildArch: noarch
BuildRequires: python

Vendor: OneLab
Packager: OneLab <support@one-lab.org>
Distribution: PlanetLab %{version}
URL: http://svn.one-lab.org/svn/new_plc_www/

# We use set everywhere
#Requires: httpd >= 2.0
Requires: php >= 5.0
Requires: postgresql >= 8.0
Requires: PLCAPI >= 4.3
Requires: drupal = 4.7
Requires: plewww-plekit

# this is what MyPLC requires
Provides: PLCWWW

# on centos5, when rebuilding the full monty, we get:
# Error: Missing Dependency: perl(GD) is needed by package PLCWWW
# and the perl-GD rpm is nowhere to be found
AutoReqProv: no

%package plekit
Summary: Utilities used by the plewww pages
Group: Applications/Systems

%description
The plewww package is made of the web pages that run on top of the 
PLCAPI component to provide the Web Interface to MyPLC users.

%description plekit
This subset of the plewww package has general purpose features for the benefit of other PL-related UI components.

%prep
%setup -q

%build
echo "Compressing javascript files"
make compress

%install
rm -rf $RPM_BUILD_ROOT

#
# plewww
# xxx : uninstall should undo this
#

echo "* PLEWWW: Installing web pages"
mkdir -p $RPM_BUILD_ROOT/var/www/html
# exclude codebase just in case
rsync -a --exclude jsmin.py --exclude Makefile --exclude httpd --exclude \*.spec --exclude .svn ./ $RPM_BUILD_ROOT/var/www/html/

echo "* PLEWWW: Installing conf files for httpd"
mkdir -p $RPM_BUILD_ROOT/etc/httpd/conf.d
install -D -m 644 httpd/*.conf $RPM_BUILD_ROOT/etc/httpd/conf.d/

%post
# attempt to perform most of the drupal post-install stuff - assuming version 6.x
drupal_settings_dir=/var/www/html/sites/default
if [ ! -d $drupal_settings_dir ] ; then
    echo "Could not find directory $drupal_settings_dir"
    echo "This suggests that you do not have a planetlab-custom drupal installed"
    exit 1
fi
pushd $drupal_settings_dir
# tune $db_url
if [ ! -f settings.php.drupal ] ; then
    cp settings.php settings.php.drupal
    sed -e 's|^[ \t]*\$db_url.*|require_once("plc_config.php");$db_url="pgsql://" . PLC_DB_USER . ":" . PLC_DB_PASSWORD . "@" . PLC_DB_HOST . ":" . PLC_DB_PORT . "/drupal";|' \
        settings.php.drupal > settings.php
fi
popd
# append our own database creation hacks to the drupal database schema
pushd /var/www/html/database
if [ ! -f database.pgsql.drupal ] ; then
    cp database.pgsql database.pgsql.drupal
    cat database.pgsql.drupal ../drupal-hacks/database.pgsql > database.pgsql
fi
popd
# hack the welcome page for MyPLC
pushd /var/www/html/modules
if [ ! -f node.module.drupal ] ; then
    cp node.module node.module.drupal
    [ -f /var/www/html/drupal-hacks/node.module ] && cp -f /var/www/html/drupal-hacks/node.module /var/www/html/modules/node.module
fi
popd

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
/var/www/html/modules
/var/www/html/planetlab
/var/www/html/googlemap
/var/www/html/drupal-hacks
/etc/httpd/conf.d

%files plekit
/var/www/html/plekit

%changelog
* Fri Nov 13 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-34
- extensible set of columns in the nodes area of the (my)slice page
- nodegroups can be added/deleted/updated
- tags management improved marginally

* Tue Oct 20 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-33
- fix pending sites page - was getting fooled by ext_consortium_id=None

* Tue Oct 20 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-32
- resetting tag as something went wrong when setting 31

* Wed Oct 07 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-30
- nicer look for slice creation, (public) sites
- users show with all their sites in the persons page

* Fri Sep 18 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-29
- reviewed registration pages for persons and sites

* Fri Sep 11 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-28
- increase memory limit in the nodes page

* Thu Sep 10 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-27
- fixes one typo

* Mon Sep 07 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-26
- minor/cosmetic

* Mon Aug 10 2009 Stephen Soltesz <soltesz@cs.princeton.edu> - PLEWWW-4.3-25
- Add default Interface tags to extra interfaces.
- Add clearer 'Add New PCU' link on node page.
- Disable user registration for tech and PI roles.

* Thu Jul 02 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-24
- new 'controller' instantiation state available in add slice page

* Thu Jul 02 2009 Baris Metin <tmetin@sophia.inria.fr> - PLEWWW-4.3-23
- exclude datepicler.js from jsmin
- table sort function for Last Contact columns
- drop options for generic boot images

* Wed Jul 01 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-22
- displays editable mac address for interfaces
- properly sorts bandwidths
- new 'controller' slice instantiation method
- bugfix, division by zero when displaying a just-changed node

* Tue Jun 16 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-21
- bugfix with linetabs

* Tue Jun 16 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-20
- fix add interface from the node page

* Mon Jun 15 2009 Stephen Soltesz <soltesz@cs.princeton.edu> - PLEWWW-4.3-19
- only add users that are enabled and not yet a member of the slice
- fix to plc_peers
- my sites, my nodes, my persons improvements for users with multiple sites
- adds a script to minimize the java script files.

* Sun Jun 07 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-18
- planetlab module to show 'All My Sites Nodes' link rather than 'My Site Nodes' if several sites

* Sun Jun 07 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-17
- first draft for pcu-handling features
- lighter contrat for PLE/PLC toggles
- as many 'my site'-like  links as the user has sites

* Wed Jun 03 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-16
- fix for whitelisted nodes
- now links to the node register wizard
- can enable sites
- various other minor fixes, like broken planetlab.module for techs, and login link

* Sat May 30 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-15
- plekittable knows how to turn off sort-on-load, and the admin users pages do
- + various cosmetic fixes

* Tue May 26 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-14
- a few minor improvements pushed on PLE

* Fri May 15 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-13
- fix for sites that were displayed as not public
- improvements to the python interface to sortable tables for monitor

* Fri May 15 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-12
- various improvements

* Wed May 06 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-11
- sliver page now correctly shows sliver tags
- support for download-node-usb-partition and various other improvements

* Tue Apr 28 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-10
- a few tweaks and typos fixed on PLE

* Tue Apr 21 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-9
- slice_add & node_download dialogs use plekit
- slice_add can add people in the slice
- various improvements after rollout on PLE

* Fri Apr 17 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-8
- cosmetic fixes to be in 4.3-rc2
- also a first (unpackaged) draft of the plekit table in python

* Tue Apr 14 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-7
- search in tables more robust

* Thu Apr 09 2009 Baris Metin <Talip-Baris.Metin@sophia.inria.fr> - PLEWWW-4.3-6
- performance improvements

* Thu Apr 09 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-5
- detect expired session and redirect to the login page

* Thu Apr 09 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-4
- improve browser health - was using too many cycles

* Tue Apr 07 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-3
- more consistency between views, and cosmetic changes

* Mon Mar 30 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-2
- area for managing slice tags

* Tue Mar 24 2009 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLEWWW-4.3-1
- first checkpoint tag for PLEWWW
- mostly functionally complete, probably a lot of tweaks still needed

* Fri Apr 25 2008 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLCWWW-onelab.4.2-11
- everyone is welcome to add nodes

* Thu Apr 24 2008 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLCWWW-onelab.4.2-10
- node-specific bootcd images to include arch in their name

* Wed Apr 23 2008 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLCWWW-onelab.4.2-9
- remove explicit dep to bootcd as the rpm name has changed

* Mon Mar 03 2008 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLCWWW-4.2-7 PLCWWW-4.2-8
- noarch

* Fri Feb 15 2008 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLCWWW-4.2-6 PLCWWW-4.2-7
- should fix yum.conf on nodes after install

* Thu Feb 14 2008 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLCWWW-4.2-5 PLCWWW-4.2-6
- uses different path for getbootmedium results - should be safer

* Sun Feb 10 2008 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLCWWW-4.2-4 PLCWWW-4.2-5
- comon icon replaced with the one from the comon website

* Fri Feb 08 2008 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLCWWW-4.2-3 PLCWWW-4.2-4
- displays rpms in the about page

* Thu Feb 07 2008 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLCWWW-4.2-2 PLCWWW-4.2-3
- safer, reference-less, way to implement layout mechnism - see
- settings.php for details
- setting types properly displayed according to user's role
- setting deletion : fixed (was still using code from slice attribute)
- more comon buttons : in the nodes and peers index pages
- comon_button knows about peer_id

* Tue Jan 22 2008 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> - PLCWWW-4.2-1 PLCWWW-4.2-2
- merged the PlanetLabConf from Princeton's tag PLCWWW-4.1-1

* Mon Apr 16 2007 Thierry Parmentelat <thierry.parmentelat@sophia.inria.fr> -
- Initial build.
