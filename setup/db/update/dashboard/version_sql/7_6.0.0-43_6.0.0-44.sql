-- Issabel 6: retire the applets replaced by the Health Center.
--
-- SystemResources, ProcessesStatus, HardDrives, PerformanceGraphic and
-- CommunicationActivity are now sections of the Health Center applet, and News
-- became a dashboard ticker (it is no longer an applet). See DASHBOARDtodo.md §4
-- and §9. This script removes their catalog entries and per-user activations;
-- the applet directories are removed from the package as well.
--
-- Calls, Calendar and Voicemails are kept.
--
-- The codes are matched with and without the "Applet_" prefix: the canonical
-- form in current databases is "Applet_<Name>" (renamed in the very first
-- migration), but matching both keeps this script safe on older databases.

DELETE FROM activated_applet_by_user
 WHERE id_dabu IN (
     SELECT d.id FROM default_applet_by_user d
     JOIN applet a ON a.id = d.id_applet
     WHERE a.code IN ('Applet_SystemResources', 'SystemResources',
                      'Applet_ProcessesStatus', 'ProcessesStatus',
                      'Applet_HardDrives', 'HardDrives',
                      'Applet_PerformanceGraphic', 'PerformanceGraphic',
                      'Applet_News', 'News',
                      'Applet_CommunicationActivity', 'CommunicationActivity'));

DELETE FROM default_applet_by_user
 WHERE id_applet IN (
     SELECT id FROM applet
     WHERE code IN ('Applet_SystemResources', 'SystemResources',
                    'Applet_ProcessesStatus', 'ProcessesStatus',
                    'Applet_HardDrives', 'HardDrives',
                    'Applet_PerformanceGraphic', 'PerformanceGraphic',
                    'Applet_News', 'News',
                    'Applet_CommunicationActivity', 'CommunicationActivity'));

DELETE FROM applet
 WHERE code IN ('Applet_SystemResources', 'SystemResources',
                'Applet_ProcessesStatus', 'ProcessesStatus',
                'Applet_HardDrives', 'HardDrives',
                'Applet_PerformanceGraphic', 'PerformanceGraphic',
                'Applet_News', 'News',
                'Applet_CommunicationActivity', 'CommunicationActivity');
