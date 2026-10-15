%define modname system
Name: issabel-system
Summary: Issabel System Module
Version: 6.0.0
Release: 29
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

# commands: /usr/bin/nmcli  (Network Parameters lee y escribe con NetworkManager)
Requires: NetworkManager

# netconfig assumes postfix service is present
Requires: postfix

# dhcpconfig usa el servidor DHCP Kea (ISC dhcp no existe en EL10)
# commands: /usr/sbin/kea-dhcp4
Requires: kea

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

# ** NetworkManager boot recovery unit ** #
mkdir -p $RPM_BUILD_ROOT/usr/lib/systemd/system
mv setup/usr/lib/systemd/system/issabel-netconfig-recover.service $RPM_BUILD_ROOT/usr/lib/systemd/system/
rmdir setup/usr/lib/systemd/system setup/usr/lib/systemd setup/usr/lib

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

# Network Parameters (NetworkManager): state dir for pending changes + boot recovery
mkdir -p /var/lib/issabel/netconfig-rollback
chmod 700 /var/lib/issabel/netconfig-rollback
systemctl daemon-reload &>/dev/null || :
systemctl enable issabel-netconfig-recover.service &>/dev/null || :

# Pick up PHP code changes from this update without needing a manual restart.
systemctl reload php-fpm &>/dev/null || :

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

%defattr(644, root, root)
/usr/lib/systemd/system/issabel-netconfig-recover.service

%changelog
* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-29
- Network Parameters: split the interface edit form into IPv4 and IPv6 cards and
  add live field validation (client and server) with inline ok/fail feedback.
  IP address and network mask are only required for static configuration
  (disabled for DHCP) and the IPv6 address is only required for the manual
  method; added example placeholders and dropped the fixed field widths.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-28
- Network Parameters: drop the fixed widths of the Destination and Gateway
  fields in the static route form so they use the standard form width (up to
  430px) and the validation icon no longer overlaps the value; keep the metric
  narrow with max-width.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-27
- Network Parameters: wrap the static route form JavaScript in {literal} so
  Smarty does not try to parse the JS regex quantifiers ({1,3}), which made the
  New/Edit route form fail with a 500 error.
- Reload php-fpm on install/update so PHP code and php.d changes are picked up
  without a manual restart.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-26
- Network Parameters: the static route form gets example placeholders and
  per-field validation with inline ok/fail feedback (red outline on error),
  both client and server side. The destination defaults to /32 when no prefix
  is given; the gateway is required (0.0.0.0 means an on-link route); an empty
  metric is left to NetworkManager. The netconfig helper also validates the
  route specifications as a backstop.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-25
- Network Parameters: use checkboxes in the static routes grid so several routes
  can be deleted at once, matching the other delete-list grids.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-24
- Network Parameters: show "New Static Route" before "Delete Route" in the
  static routes grid and use Title Case for the action buttons (Apply Changes,
  Confirm Changes, Discard Changes, Revert Now, Delete Route) for consistency.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-23
- Network Parameters: redirect to the module id (network_parameters) instead of
  the parent menu (network) after saving, cancelling or applying changes, so the
  breadcrumb, tab and menu marker stay on the module.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-22
- Network Parameters: add the new strings (static routes, IPv6, staged changes
  and pending confirmation) to all shipped languages.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-21
- Network Parameters: apply network changes atomically. A new netconfig --apply
  takes a change set (interface + gateway + DNS + static routes) and applies it
  as one transaction: properties are grouped per connection into a single
  con mod and a single activation, under one snapshot and one confirmation. The
  module stages changes in the session ("Pending changes") and applies them with
  a single "Apply changes"; confirmation is only requested when the change can
  cut the management interface.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-20
- Network Parameters: IPv6 support. Show IPv6 addresses in the interface list
  and edit them: per-interface method (auto/manual/dhcp/disabled), static
  address with prefix and gateway, plus an optional IPv6 DNS in the general
  form. The netconfig helper applies them with nmcli (ipv6.method/addresses/
  gateway/dns) inside the safe apply.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-19
- Network Parameters: drop the net-tools requirement; the network module no
  longer uses 'route' (nmcli and 'ip route' only).

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-18
- Network Parameters: netconfig now creates the NetworkManager connection when
  the target interface has none: VLAN for 'parent.id' names (e.g. eth0.100) and
  ethernet otherwise, inside the safe apply so a rollback removes a newly created
  connection. Creating macvlan (mv-*) connections from the GUI is deferred.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-17
- Network Parameters: add a Static Routes grid below the interface list. Routes
  are read from NetworkManager and written back as the full ipv4.routes list of
  the connection (nmcli), through the safe commit-confirm apply. IPv4 only for
  now.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-16
- Network Parameters: read the interface type (DHCP/static), the default
  gateway and the DNS servers from NetworkManager (nmcli) instead of the
  legacy ifcfg files, which no longer exist on EL10. Falls back to
  /etc/resolv.conf and 'ip -4 route' when NM does not report them.
- Network Parameters: rewrite the netconfig helper to configure interfaces,
  gateway and DNS with nmcli (no ifcfg, no /etc/sysconfig/network, no route),
  converting the dotted netmask to a prefix. The '--gateway-dev' option is
  dropped.
- Network Parameters: apply changes in safe mode (commit-confirm). The helper
  snapshots the NetworkManager profiles, schedules an automatic rollback with a
  transient systemd timer and requires a confirmation; a pending change is
  reverted on boot by the new issabel-netconfig-recover.service.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-15
- DHCP Server: widen the IPv4 octet inputs to 48px and trim their horizontal
  padding so three digits are visible; the 30px boxes had no usable content
  width under .form-control and appeared read-only.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-14
- DHCP Server: lay the IPv4 octet fields (start/end range, DNS, WINS, gateway
  and PXE server) out in a single row instead of stacking them one per line.

* Mon Oct 12 2026 Issabel Foundation <info@issabel.org> - 6.0.0-12
- betachannel: drop the jQuery iButton plugin (and its assets); the status
  toggle now uses the theme's Bootstrap 5 switch.

* Sun Oct 11 2026 Issabel Foundation <info@issabel.org> - 6.0.0-11
- Remove hardcoded light colours and inline JS hover colours from the
  time_config, hardware_detector and backup_restore templates so they follow the
  theme in dark mode. Drop the dead jQuery facebox plugin from backup_restore.

* Sat Oct 10 2026 Issabel Foundation <info@issabel.org> - 6.0.0-10
- Dashboard: use the official Issabel brand purple (#572364) in the applet and
  News styles (was #522b76).

* Sat Oct 10 2026 Issabel Foundation <info@issabel.org> - 6.0.0-9
- Processes Status: use /usr/sbin/pidof (EL10 merges /sbin) and accept the
  mariadbd process/unit name, so the Database Service row is no longer reported
  as down on Rocky 10.

* Sat Oct 10 2026 Issabel Foundation <info@issabel.org> - 6.0.0-8
- DHCP Server: port the GUI and the privileged dhcpconfig helper from ISC
  dhcpd to Kea (JSON config in /etc/kea/kea-dhcp4.conf, systemd unit kea-dhcp4),
  and read the client list from the Kea memfile lease CSV. Require the kea
  package.

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
