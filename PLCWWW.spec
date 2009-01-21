#
# $Id$
#

# what the myplc rpm requires
%define name PLCWWW
%define version PLE.5.0
%define taglevel 0

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

Vendor: OneLab
Packager: OneLab <support@one-lab.org>
Distribution: PlanetLab %{version}
URL: http://svn.one-lab.org/svn/new_plc_www/

# We use set everywhere
#Requires: httpd >= 2.0
Requires: php >= 5.0
Requires: postgresql >= 8.0
Requires: PLCAPI >= 5.0
Requires: drupal = 4.7

# on centos5, when rebuilding the full monty, we get:
# Error: Missing Dependency: perl(GD) is needed by package PLCWWW
# and the perl-GD rpm is nowhere to be found
AutoReqProv: no

%description
The PLCWWW package is made of the web pages that run on top of the 
PLCAPI component to provide the Web Interface to MyPLC users.

%prep
%setup -q

%build
echo "There is no build stage for this component"
echo "All files just need to be installed as is from the codebase"

%install
rm -rf $RPM_BUILD_ROOT

#
# plcwww
#

echo "* PLEWWW: Installing web pages"
mkdir -p $RPM_BUILD_ROOT/var/www/html
# let's be conservative and exclude codebase files, though there should not be any
rsync -a --exclude \*.spec --exclude .svn --exclude CVS ./ $RPM_BUILD_ROOT/var/www/html/

echo "* PLEWWW: Installing config for httpd"
install -D -m 644 httpd.conf $RPM_BUILD_ROOT/etc/httpd/conf.d/plcwww.conf

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
if [ ! -f settings.php.distrib ] ; then
    cp settings.php settings.php.distrib
    sed -e 's|^[ \t]*\$db_url.*|require_once("plc_config.php");$db_url="pgsql://" . PLC_DB_USER . ":" . PLC_DB_PASSWORD . "@" . PLC_DB_HOST . ":" . PLC_DB_PORT . "/drupal";|' \
        settings.php.distrib > settings.php
fi
popd
# append our own database creation hacks to the drupal database schema
pushd /var/www/html/database
if [ ! -f database.pgsql.distrib ] ; then
    cp database.pgsql database.pgsql.distrib
    cat database.pgsql.distrib ../drupal-hacks/database.pgsql > database.pgsql
fi
popd
# hack the welcome page for MyPLC
pushd /var/www/html/modules
if [ ! -f node.module.distrib ] ; then
    cp node.module node.module.distrib
    [ -f /var/www/html/drupal-hacks/node.module ] && cp -f /var/www/html/drupal-hacks/node.module /var/www/html/module/node.module
fi
popd

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
/var/www/html
/etc/httpd/conf.d/plcwww.conf

%changelog
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
