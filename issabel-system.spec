%define modname system
Name: issabel-system
Summary: Issabel System Module
Version: 6.0.0
Release: 75
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

# commands: /usr/sbin/ping /usr/sbin/tracepath (módulo Ping & Traceroute)
Requires: /usr/sbin/ping
Requires: /usr/sbin/tracepath

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

# ** Health Center collector cron ** #
mkdir -p $RPM_BUILD_ROOT/etc/cron.d
mv setup/etc/cron.d/issabel-healthcollect $RPM_BUILD_ROOT/etc/cron.d/
rmdir setup/etc/cron.d setup/etc

# Additional (module-specific) files that can be handled by RPM
#mkdir -p $RPM_BUILD_ROOT/opt/issabel/
#mv setup/dialer

mkdir -p $RPM_BUILD_ROOT/usr/sbin/

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
    rpm -q --queryformat='%%{VERSION}-%%{RELEASE}' %{name} > /usr/share/issabel/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
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

# Updates: estado del job de actualización (helper pkgupdate). Root-only.
mkdir -p /var/lib/issabel/updates
chmod 700 /var/lib/issabel/updates
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
/usr/sbin/issabel_migration.sh
/usr/sbin/issabel_migration_fpbx.sh
/usr/share/issabel/privileged/*

%defattr(644, root, root)
/usr/lib/systemd/system/issabel-netconfig-recover.service
%config(noreplace) /etc/cron.d/issabel-healthcollect

%changelog
* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-75
- Backup and restore: replace the file upload control, drawn by a third party
  plugin with its own colours, with the theme button and a hidden file input.
  The plugin is no longer shipped.
- Backup and restore: translate the messages of the upload check, which were
  hardcoded in English in the JavaScript.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-74
- Updates and Health: give the table the full width of the card. The theme
  table primitive only sets the colours, the width came from the Bootstrap
  table class, which the migration to the theme grid dropped.
- Backup: the version comparison table now fills the card instead of keeping
  the fixed 750 pixels it had.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-73
- Complete the language files of the date and time, applet admin, DHCP
  client list, dashboard and backup pages. The keys the code looks for were
  missing in most of the languages, so those pages fell back to the English
  text.
- Remove the keys no code uses any more: the fragments of an old message
  scheme, the email list leftovers of the DHCP client list and the applet
  names the dashboard menu already provides.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-72
- Currency: name the currencies in English. The English base file held the
  Spanish names as keys and, for half of them, as values too.
- Currency: translate the currency names. The French, Turkish and Brazilian
  files were copies of the English one, and the Persian file had a dozen
  names left untranslated.
- Currency: add the Ukrainian hryvnia, which the code offered but no
  language file had.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-71
- Beta channel: show the result of the switch with the theme alert. The
  script prepended the message to a container that no theme defines, so the
  operator never saw whether the change had worked.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-70
- Backup: use the theme grid in the decrypt form, the version comparison and
  the migration page. They used raw tables and a class that no theme defines.
- Backup: the version comparison was a whole HTML document of its own, nested
  inside the module page.
- Backup: validate the FTP credentials per field. The page used the legacy
  validation, which reports the errors keyed by the label, and the messages
  went to a global notice the module page does not show.
- Backup: remove the filter template, it was not referenced.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-69
- Health: use the theme grid. The page used the raw Tabler markup, so the
  cards, the label columns and the table did not follow the rest of the
  interface.
- Updates: use the theme grid, for the same reason. The package table now
  uses the table of the theme.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-68
- Applet admin: show the error in the page. The message was only assigned
  to mb_message, which the shell paints and the module page does not, so the
  operator never saw why the applets were not saved.
- Applet admin: drop the "letra12" class. It is not defined in any theme
  and only the script that selects the checkboxes was looking for it.
- Shutdown: report an invalid shutdown mode in the page. The page printed
  the Spanish string "Modo invalido" outside the template.
- Shutdown: use the /usr/sbin path of the shutdown command. Rocky Linux 10
  merged /sbin into /usr/sbin.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-67
- Date and time: split the page into a view and an edit mode. The page used
  to render the live controls straight away, so the current time was never
  readable at a glance.
- Date and time: validate every field and mark the one that is wrong. The
  page used a single boolean and a global message, and the timezone was
  silently replaced by the current one when it was not in the list.
- Date and time: use a 24 hour clock. The selector showed a 12 hour clock
  and the meridian was never saved, so any afternoon time was stored as the
  matching morning time.
- Date and time: the date picker wrote a two digit year while the parser
  expects four, so a date picked in the calendar never validated.
- Date and time: replace strftime(), deprecated since PHP 8.1.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-66
- DHCP client list: show the client details as plain text. The page used to
  render the fields as disabled inputs, so the view was a form nobody could
  use.
- DHCP client list: drop the create and edit paths. The list comes from the
  Kea lease file, so the page is a read only view of the leases Kea manages
  and the half implemented forms could never save anything.
- DHCP client list: remove the filter template. The filter had been
  commented out for a long while.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-65
- Currency: split the page into a view and an edit mode. The page used to
  render the live select straight away, so the current currency was never
  readable at a glance.
- Currency: validate the posted currency against the list before saving
  it. The value is stored in the settings table and used when the billing
  rates are shown.
- Currency: save the value as a query parameter. The string used to be
  interpolated into the statement.
- Currency: use the colon sign for the Costa Rican colón. Both colóns used
  the same cent sign, so the array kept only the last one and the Costa
  Rican colón could never be picked.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-64
- Drop the local copies of the "%s is required" and "%s is invalid" keys
  from the network parameters, DHCP by MAC, DHCP server and ping language
  files. The keys now live in the global language files of the framework.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-63
- DHCP server: split the page into a view and an edit mode. The page used to
  render the live inputs straight away, so the configuration was never
  readable at a glance. The default view is now a compact card with the
  values as plain text, and the edit mode is a separate step reached with a
  POST. The service start and stop buttons stay in the view.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-62
- Applet admin, currency, DHCP client list, shutdown and backup: send the
  CSRF token with the forms. The token is injected into the first <form> of
  the rendered template, so a form built in the PHP wrapper instead of the
  template went out without one. The wrapper forms move to the template, and
  the templates fetched raw print the field themselves.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-61
- Health: use the theme severity tokens for the score border and the finding
  dots. The colours were hardcoded, so the mid tones the page picked washed
  out on a dark background.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-60
- Network parameters: use the theme alert primitives for the pending change
  and the pending draft banners. They hardcoded a light background and
  inherited the body text colour, so in dark mode they showed light text on
  a light background. The error box now uses the same family.
- Health: use the theme alert primitives. The page used the Bootstrap 3
  "alert alert-*" classes, which Tabler does not have, so the message showed
  without any style.
- Updates: use the theme alert primitives, for the same reason as the Health
  page. The message type is now "error" instead of "danger" so it matches the
  primitive modifier.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-59
- Updates: stop loading the module script twice. paloSantoNavigation already
  injects every .js from modules/<id>/themes/default/js into the iframe head, so
  the extra <script> in updates.tpl registered a second click handler and the
  two cancelled each other (Select all marked and immediately unmarked every
  package). Rename the file to updates.js, drop the manual include and the
  unused ASSET_V cache-buster.

* Sun Oct 18 2026 Issabel Foundation <info@issabel.org> - 6.0.0-58
- Updates: fix the Select all label. It used the state before the click, so
  after checking every package it still read "Select all"; it now reflects the
  result ("Select none" when the packages end up checked).
- Updates: append the javascript.js mtime as a ?v= cache-buster so a new build
  is not masked by a browser-cached copy of the old script.

* Sun Oct 18 2026 Issabel Foundation <info@issabel.org> - 6.0.0-57
- Updates: new System module that lists the packages with a pending update and
  lets an administrator apply them. It is a direct child of System, last in the
  menu, and admin-only.
- The package list comes from the health snapshot: the collector now runs
  `dnf check-update` once per run (shared by the issabel and security checks)
  and stores it in the new health_update_pkg table, so the Health Center
  counters and the Updates grid can never disagree.
- New privileged helper pkgupdate runs `dnf -y update` in a detached background
  job with its state under /var/lib/issabel/updates, so the "working" banner
  survives the session. It validates every package against the snapshot before
  running dnf, refreshes the health snapshot when it finishes and leaves a
  reboot notice in the notifications bell when needed.
- The health updates checks now link to the new Updates module (menu=updates).

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-55
- Remove the Hardware Detector module and the legacy DAHDI/wanpipe cluster that
  only existed to serve it: modules/hardware_detector, the privileged helpers
  hardware_detector and dahdiconfig, /usr/sbin/switch_wanpipe_media and the
  hardware_detector database schema (db.info, install and update). The menu item
  is gone too. Also drop the now dead hdmodelreport helper (its only caller, the
  HardDrives applet, was retired), the /etc/dahdi/genconf_parameters defaults
  written by %post and the hdparm dependency they needed. Nothing requires any
  of it: dahdiconfig in issabelPBX writes /etc/dahdi/system.conf on its own.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-54
- Health Center: the module is now a direct child of System in the menu, right
  after Dashboard, instead of hanging from the Dashboard group, and it is named
  "Health Center" (its translations live in the framework lang files). The menu
  id stays "health", so the ACL resource and the links keep working, and the
  Health page title and the applet button use the same name.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-53
- Health: the down trunks and the critical unregistered extensions are reported
  with the descriptive name that issabelPBX shows ("Trunk Name" of the trunks
  table, the user Display Name for an extension), not the Asterisk identifier, so
  a trunk reads "Pruebas" instead of "pruebas" and an extension "1000 (Ana)".
  The raw identifier is still what is stored and what the critical-endpoint
  policy uses, so renaming in the PBX does not break the configuration.
- Health: the endpoint messages now say what they are ("trunk down: ...",
  "3 trunks down (within grace)", "1 extension down (within grace)"), and the
  Critical endpoints selector of the Health page shows the descriptive name with
  the identifier.
- Health: the View button of the trunk and extension checks no longer points at
  the "pbxconfig" menu group. That item has no ACL resource (issabel-menumerge
  only creates resources for items with a parent), so the tab manager answered
  403 module_not_authorized; it now opens PBX Configuration ("pbxadmin").

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-52
- Health: the check messages are no longer hardcoded Spanish. The collector now
  stores a message key plus its arguments in health.db and the applet, the page
  and the CSV render them in the view language (bg, br, en, es, fa, fr, ru),
  with proper plurals (including the Russian one/few/many forms). /health and
  the CSV keep working, and the API keeps the English fallback.
- Health: translate the module and the HealthCenter applet to bg, br, fa, fr and
  ru (they only shipped en and es). The service action labels and impact notices
  are translated too.
- Health: the "value" column of the page and the applet translates state words
  ("active", "not installed", ...), leaving numbers untouched.
- Health: translate the "threshold" column too (disk, certificate and backup
  limits, and the all/selected critical mode), so no English wording is left in
  a non-English panel.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-51
- Health Center: show a subtle "last update" stamp (relative to the view, with
  the absolute time in the tooltip) to the right of the Resources, Storage,
  Telephony and trend titles, so it is clear which numbers are live and which
  come from the cached checks (Storage 15 min, Telephony 5 min).
- Health Center: refresh in place. The Resources values and the active
  calls/channels poll every 30 s, and the trend chart every 5 min (aligned with
  the sampler), without rebuilding the applet or reloading Chart.js; the polling
  pauses while the tab is hidden.
- Health: add liveTelephony() (core show channels count) for the active
  calls/channels, shown in the Telephony card and next to the trend chart.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-50
- Dashboard: redesign the Calls, Calendar and Voicemails applets. They now
  render from Smarty templates (no HTML built with str_replace on translated
  strings, and no <font> in the lang files), use the theme tokens and Tabler
  badges, and show a friendly empty state instead of a raw error.
- Calls: recent calls in a compact table with direction (incoming/outgoing),
  the peer number, a status badge (answered / no answer / busy / failed /
  congestion) and the duration. The CDR query now also covers outgoing calls.
- Calendar: each occurrence shows a day/month chip, the start time and a badge
  when the event launches an Asterisk call.
- Voicemails: unread count, caller, relative time, duration and mailbox; each
  row links to the voicemail module (inline playback is a later phase).
- New shared helper issabelDashboardFormat (relative time and mm:ss duration)
  plus the new strings translated to bg, br, en, es, fa, fr and ru.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-49
- Health: the /health API example no longer prints the real token. The curl
  snippet now shows a <TOKEN> placeholder; the actual value stays in the masked
  field (with reveal and copy) only.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-48
- Health: fix the endpoint classification. The trunk list was never loaded (it
  checked for a class name that does not exist), so every endpoint was counted
  as an extension and trunks showed as unknown. Load getTrunks() by function
  (with a direct `trunks` query as fallback), normalise the PJSIP endpoint names
  (id/id -> id), map the PJSIP states correctly (Unavailable/Unreachable/Invalid
  = down; Available/Not in use = up), use `pjsip show registrations` for trunks
  that register, and dedupe peers. The counts now also include endpoints that
  are still within the debounce grace window, so a just-down endpoint no longer
  disappears from the totals.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-47
- Health: apply the endpoint guards. The collector keeps per-endpoint state in
  health.db (endpoint_state) so a down trunk or extension is only reported after
  the debounce window, and extensions that were not registered recently are
  ignored (a configured but unused extension no longer alarms). The Health page
  lets the admin tune the debounce and the recent window.
- Dashboard: the Health Center trend can now show 7 and 30 days, from the new
  sampler aggregates, with a 1 h / 8 h / 24 h / 7 d / 30 d selector. Rendering
  the applet no longer deletes old samples (pruning is the sampler's job).

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-46
- Health: add the backup freshness check (the newest .tar in the backup
  directory; warns when there is none or it is older than backup_warn_hours,
  default 48 h) and its threshold field.
- Health: add the read-only /health JSON endpoint (modules/health/health.php)
  with a per-installation token, off by default. The Health page gains a
  section that explains what it is for and how to consume it, toggles it, and
  shows the token masked with a copy button and a regenerate button.
- Dashboard: show the news ticker only to the administrator group, and add a
  "Show the news ticker" switch to Dashboard Applet Admin.
- Health Center: replace the native confirm() with a theme-styled modal for the
  corrective actions.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-45
- Escape the rpm queryformat used to record the pre-version on upgrade.
  The VERSION and RELEASE macros were expanded at build time, so %pre always
  wrote the new version and issabel-dbprocess skipped every version_sql
  migration. They are now escaped so rpm expands them at run time.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-44
- Health Center: retarget the dashboard.db migrations to 6.0.0-44. They were
  pointing at 6.0.0-43, the same Release that was installed, so an upgrade from
  a 6.0.0-43 build skipped them: the database kept the old applets (whose
  directories are gone, so only the News ticker was shown) and never got the
  Health Center. Installing 6.0.0-44 now adds the Health Center applet, removes
  the retired applets and leaves Calls/Calendar/Voicemails.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-43
- Health Center: add the dashboard applet (system/modules/dashboard/applets/
  HealthCenter) that shows the platform score, the findings that require
  attention, live resources, per-mount disk usage and the performance trend
  (Chart.js from the modern theme). It is offered only to the administrator
  role and is activatable per user from applet_admin, and it renders full width
  and pinned above the sortable applet grid (it is excluded from order saving).
- Health Center: add the issabelHealth model and the privileged healthcollect
  helper that runs the slow platform/storage/security/updates/telephony checks
  into /var/www/db/health.db, honouring a per-group TTL so the expensive checks
  (dnf, du) are not run on every cycle.
- Health Center: create the health.db schema and register it in db.info, and
  schedule healthcollect every 5 minutes (with flock) via
  /etc/cron.d/issabel-healthcollect. Health defaults (thresholds, critical
  endpoints, TTLs) are seeded in settings.db by the collector.
- applet_admin: reject id_dabu values that do not belong to the user role
  catalog before saving the activation.
- Health Center: add the corrective actions (start/restart a service from a
  closed allowlist, and renew the TLS certificate via issabel-helper
  ssl_certbot). They require an administrator, are confirmed with their impact,
  are protected by the global CSRF helper and are logged in
  /var/log/issabel/audit.log. Log purging is not offered: it is logrotate's job.
- Health: add the "Health" page (system/modules/health) under Dashboard in the
  menu, administrator only. It lists every check with category/severity filters,
  exports to CSV, and lets the admin edit the thresholds and choose which trunks
  and extensions are critical.
- Dashboard: turn News into a slim ticker in the shell (dashboard/libs/
  issabelNewsFeed.class.php + themes/default/js/0_news_ticker.js) that loads the
  feed asynchronously with cache/timeout and hides itself if the feed fails. Its
  visibility is controlled by the issabel_dashboard_news setting.
- Dashboard: retire the SystemResources, ProcessesStatus, HardDrives,
  PerformanceGraphic, CommunicationActivity and News applets (replaced by the
  Health Center and the ticker) and drop the now unused flot/raphael/justgage
  assets. Calls, Calendar and Voicemails are kept.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-42
- Ping & Traceroute: flush the new console document before running the command,
  so the browser stops showing the previous result while the new one loads
  (ping's header is immediate, but tracepath's first line can take a while).
  The page now clears the previous iframe content on submit and relies on
  postMessage start/done instead of the iframe load event, which the clear
  would also trigger.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-41
- Ping & Traceroute: load Results into its iframe with JavaScript instead of
  reloading the module, so the disabled button and the waiting console are not
  lost on navigation. The blinking cursor now stays at the end of the output
  and is only removed when the command finishes, instead of disappearing with
  ping's immediate header line. The plain POST path is kept as a no-JavaScript
  fallback.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-40
- Ping & Traceroute: disable the clicked button for one second on submit so the
  click is acknowledged while the page reloads and the command starts.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-39
- Ping & Traceroute: show a blinking cursor in the Results console from the
  click until the first output line arrives, so the user gets immediate
  feedback while the command runs. The caret lives inside the console document
  (removed on the first line) and the results card is revealed on submit with a
  pending cursor for the brief POST round-trip.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-38
- Ping & Traceroute: fix the host argument so ping/tracepath stop failing with
  "'<host>': Name or service not known". index.php no longer wraps the host in
  literal single quotes and URL-encodes it, and generate.php validates the
  target (IPv4/IPv6/host name) and uses escapeshellarg() instead of
  escapeshellcmd(htmlspecialchars()), which double-escaped the value under PHP
  8. Apply the style guide: CSRF field, server and client validation with
  inline errors, translated labels, and a .iss-console results panel that is
  readable in light and dark modes. Require the iputils binaries explicitly.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-37
- Ping and Backup/Restore migration: replace the removed Smarty {php} tags with
  values assigned from PHP, so the templates compile under Smarty 4 and no
  longer return a 500 error.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-36
- Assign IP Address to Host: apply the style guide to the add/edit/view form:
  render the view mode as plain text, re-render per-field validation errors
  instead of a global list, add live client-side validation, use placeholders
  and move the <form> into the template so the CSRF token is injected. Tighten
  the IPv4 check in the data class to reject octets above 255 and restyle the
  list filter with the theme kit.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-35
- DHCP Server: replace the four dotted octet inputs with a single IPv4 text box
  per address, isolate field-level validation errors and re-render the form
  instead of lumping them into a global message, and add live client-side
  validation. Cards, placeholders and the required/optional convention follow the
  module style guide.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-34
- Network Parameters: label the interface edit form button 'Save' instead of
  'Apply Changes', matching the general parameters and static route forms. The
  button only stages the change; it is the pending changes panel that applies it.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-33
- Network Parameters: show action errors inside the module page and re-render it
  when applying the staged change set or deleting a static route fails, instead
  of returning an empty body (which left the tab blank and hid the reason: under
  the tabbed layout the iframe prints only the module content, so mb_message is
  never displayed). A failure always renders an alert, falling back to a
  translated generic message when the helper produced no output, and a PHP
  exception while applying is caught and shown as well. Show the NetworkManager
  IPv4/IPv6 method per interface in the interfaces grid so an applied change is
  visible. netconfig: add --getmethods, report hostnamectl failures when setting
  the hostname, and use /usr/sbin/ip instead of /sbin/ip.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-32
- Network Parameters: render the labels of the read-only summary card as
  uppercase micro-labels so they read clearly different from their values
  (iss-formgrid--readonly).

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-31
- Network Parameters: show the general parameters as a single compact card of
  read-only values in the default view so the interfaces and static routes
  grids stay visible without scrolling, and reveal the General/IPv4/IPv6 edit
  cards with the .iss-reveal animation only in edit mode.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-30
- Network Parameters: split the general parameters form into General, IPv4 and
  IPv6 cards and add live field validation (client and server) with inline
  ok/fail feedback. Host must be a valid FQDN, Primary DNS and Default Gateway
  are required IPv4 addresses, and Secondary DNS / IPv6 DNS are optional.
  Factor out the page rendering so a validation error keeps the interfaces and
  routes grids plus the pending changes panel visible, in edit mode.

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
