-- Issabel 6: add the Health Center dashboard applet.
--
-- Health Center replaces SystemResources, CommunicationActivity, HardDrives,
-- PerformanceGraphic and ProcessesStatus (DASHBOARDtodo.md §4). It is offered
-- ONLY to the administrator role, and it is activatable per user from
-- applet_admin. The old applets are not removed here yet (that is a later
-- step); this script only introduces the new one.

-- 1) Applet catalog entry.
INSERT INTO applet (code, name)
SELECT 'Applet_HealthCenter', 'Health Center'
WHERE NOT EXISTS (SELECT 1 FROM applet WHERE code = 'Applet_HealthCenter');

-- 2) Available only for the admin role (no row for no_admin).
INSERT INTO default_applet_by_user (id_applet, username)
SELECT a.id, 'admin'
FROM applet a
WHERE a.code = 'Applet_HealthCenter'
  AND NOT EXISTS (
      SELECT 1 FROM default_applet_by_user d
      WHERE d.id_applet = a.id AND d.username = 'admin');

-- 3) On upgrade, turn it on for administrators that already had one of the
--    health/services applets active (1 SystemResources, 2 ProcessesStatus,
--    3 HardDrives, 4 PerformanceGraphic, 6 CommunicationActivity).
--    New admins get it from applet_admin; existing ones that had none of them
--    are left untouched.
INSERT INTO activated_applet_by_user (id_dabu, order_no, username)
SELECT DISTINCT hc.id, 1, aau.username
FROM default_applet_by_user hc
JOIN applet hca ON hca.id = hc.id_applet AND hca.code = 'Applet_HealthCenter'
JOIN activated_applet_by_user aau ON aau.username = hc.username
JOIN default_applet_by_user old ON old.id = aau.id_dabu AND old.username = 'admin'
WHERE hc.username = 'admin'
  AND old.id_applet IN (1, 2, 3, 4, 6)
  AND NOT EXISTS (
      SELECT 1 FROM activated_applet_by_user x
      WHERE x.username = aau.username AND x.id_dabu = hc.id);
