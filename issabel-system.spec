%define modname system
Name: issabel-system
Summary: Issabel System Module
Version: 6.0.0
Release: 7
License: GPL
Group:   Applications/System
Source0: issabel-%{modname}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Requires(pre): issabel-framework >= 5.0.0-1
#Requires(pre): issabel-fax >= 2.2.0-4
Requires(pre): php-soap
Conflicts: elastix-agenda < 2.2.0-1
Conflicts: elastix-pbx <= 2.4.0-15
Requires: issabel-php-simplepie
Requires: hdparm

Obsoletes: elastix-system

# commands: /usr/bin/date /usr/bin/stat /usr/bin/du rm /usr/bin/chown /usr/bin/su /usr/bin/df
Requires: coreutils

# commands: /usr/sbin/dmsetup
Requires: device-mapper

# commands: rpm
Requires: rpm

# commands: yum
Requires: yum

# commands: /usr/sbin/ip
Requires: iproute

# commands: /usr/sbin/route
Requires: net-tools

# netconfig assumes postfix service is present
Requires: postfix

# dhcpconfig necesita un servidor DHCP, pero EL10 no trae el paquete 'dhcp'.
# Ver TODO.md (hay que agregarlo y configurarlo).

# commands: chkconfig
Requires: chkconfig

# commands: tar
Requires: tar

# commands: mysqldump mysql
Requires: mariadb

Requires: /usr/sbin/pidof
Requires: /usr/bin/hostname
Requires: /usr/sbin/shutdown

%description
Issabel System Module

%prep
%setup -n %{name}-%{version}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Issabel modules
mkdir -p    $RPM_BUILD_ROOT/var/www/html/
mkdir -p    $RPM_BUILD_ROOT/var/www/html/libs/
mkdir -p    $RPM_BUILD_ROOT/var/www/backup
mkdir -p    $RPM_BUILD_ROOT/usr/share/issabel/privileged
mkdir -p    $RPM_BUILD_ROOT/var/www/db/
mkdir -p    $RPM_BUILD_ROOT/usr/bin/
rm -rf modules/userlist/
mv modules/ $RPM_BUILD_ROOT/var/www/html/

mv setup/paloSantoNetwork.class.php      $RPM_BUILD_ROOT/var/www/html/libs/
mv setup/automatic_backup.php            $RPM_BUILD_ROOT/var/www/backup/
mv setup/usr/share/issabel/privileged/*  $RPM_BUILD_ROOT/usr/share/issabel/privileged

rmdir setup/usr/share/issabel/privileged setup/usr/share/issabel setup/usr/share

# Additional (module-specific) files that can be handled by RPM
#mkdir -p $RPM_BUILD_ROOT/opt/issabel/
#mv setup/dialer

# ** Dahdi files **#
mkdir -p $RPM_BUILD_ROOT/etc/dahdi
mkdir -p $RPM_BUILD_ROOT/usr/sbin/

# ** switch_wanpipe_media file ** #
mv setup/usr/sbin/switch_wanpipe_media        $RPM_BUILD_ROOT/usr/sbin/
mv setup/usr/sbin/issabel_migration.sh        $RPM_BUILD_ROOT/usr/sbin/
mv setup/usr/sbin/issabel_migration_fpbx.sh   $RPM_BUILD_ROOT/usr/sbin/
rmdir setup/usr/sbin

rmdir setup/usr

# The following folder should contain all the data that is required by the installer,
# that cannot be handled by RPM.
mkdir -p    $RPM_BUILD_ROOT/usr/share/issabel/module_installer/%{name}-%{version}-%{release}/
mv setup/   $RPM_BUILD_ROOT/usr/share/issabel/module_installer/%{name}-%{version}-%{release}/
mv menu.xml $RPM_BUILD_ROOT/usr/share/issabel/module_installer/%{name}-%{version}-%{release}/

%pre
mkdir -p /usr/share/issabel/module_installer/%{name}-%{version}-%{release}/
touch /usr/share/issabel/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
if [ $1 -eq 2 ]; then
    rpm -q --queryformat='%{VERSION}-%{RELEASE}' %{name} > /usr/share/issabel/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
fi

%post
pathModule="/usr/share/issabel/module_installer/%{name}-%{version}-%{release}"

# Run installer script to fix up ACLs and add module to Issabel menus.
issabel-menumerge $pathModule/menu.xml
pathSQLiteDB="/var/www/db"
mkdir -p $pathSQLiteDB
preversion=`cat $pathModule/preversion_%{modname}.info`
rm $pathModule/preversion_%{modname}.info

if [ $1 -eq 1 ]; then #install
  # The installer database
    issabel-dbprocess "install" "$pathModule/setup/db"

    if [ -f /etc/dahdi/genconf_parameters ]; then
        # ** The following selects oslec as default echo canceller ** #
        echo "echo_can oslec" > /etc/dahdi/genconf_parameters
        echo "bri_sig_style bri" >> /etc/dahdi/genconf_parameters
        echo "context_lines from-pstn" >> /etc/dahdi/genconf_parameters
        echo "context_phones default" >> /etc/dahdi/genconf_parameters
    fi

elif [ $1 -eq 2 ]; then #update
    issabel-dbprocess "update"  "$pathModule/setup/db" "$preversion"
fi

# If openfire is not running probably we're in the distro installation process
# So, i configure openfire init script as stopped by default
/usr/sbin/service openfire status | grep "not running" &>/dev/null
res=$?
# Openfire esta apagado
if [ $res -eq 0 ]; then
    # Desactivo el servicio openfire al inicio
    chkconfig --level 2345 openfire off
fi


# The installer script expects to be in /tmp/new_module
mkdir -p /tmp/new_module/%{modname}
cp -r /usr/share/issabel/module_installer/%{name}-%{version}-%{release}/* /tmp/new_module/%{modname}/
chown -R asterisk.asterisk /tmp/new_module/%{modname}

php /tmp/new_module/%{modname}/setup/installer.php
rm -rf /tmp/new_module

%clean
rm -rf $RPM_BUILD_ROOT

%preun
pathModule="/usr/share/issabel/module_installer/%{name}-%{version}-%{release}"
if [ $1 -eq 0 ] ; then # Validation for desinstall this rpm
  echo "Delete System menus"
  issabel-menuremove "%{modname}"

  echo "Dump and delete %{name} databases"
  issabel-dbprocess "delete" "$pathModule/setup/db"
fi

%files
%defattr(-, root, root)
%{_localstatedir}/www/html/*
/usr/share/issabel/module_installer/*
/var/www/backup/automatic_backup.php
%defattr(755, root, root)
/usr/sbin/switch_wanpipe_media
/usr/sbin/issabel_migration.sh
/usr/sbin/issabel_migration_fpbx.sh
/usr/share/issabel/privileged/*

%changelog
* Sat Oct 10 2026 Issabel Foundation <info@issabel.org> - 6.0.0-7
- Dashboard: modernize the Performance Graphic applet (flot) with a theme-aware
  HTML legend (fixes it under the dark theme) and a responsive container.

* Sat Oct 10 2026 Issabel Foundation <info@issabel.org> - 6.0.0-6
- Dashboard: drop the Telephony Hardware and Faxes applets (no hardware/fax
  support in Issabel 6), modernize the remaining applets to the "modern" theme,
  and reduce Processes Status to Asterisk, MariaDB and Apache.

* Fri Oct 09 2026 Issabel Foundation <info@issabel.org> - 6.0.0-5
- Drop the email dashboard applets (webmail quota "System" and "Emails") and
  the email backup/restore component, including its cyrus-imapd/saslpasswd2
  dependencies: Issabel 6 no longer ships a local mail server.
* Fri Oct 09 2026 Issabel Foundation <info@issabel.org> - 6.0.0-4
- Drop the dahdi and dhcp requirements: neither exists in EL10. A DHCP server
  still has to be added and configured; see TODO.md.
* Fri Oct 09 2026 Issabel Foundation <info@issabel.org> - 6.0.0-3
- Require the unversioned PHP packages (php-*, 8.3 on EL10) instead of the
  versioned php8.4-*, which conflict with the PHP 8.3 in the netinstall base.
