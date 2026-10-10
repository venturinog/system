-- Issabel 6: drop the Telephony Hardware and Faxes dashboard applets.
-- Telephony hardware is no longer supported, and fax is not part of Issabel 6.
DELETE FROM activated_applet_by_user
    WHERE id_dabu IN (SELECT id FROM default_applet_by_user WHERE id_applet IN (10, 13));
DELETE FROM default_applet_by_user WHERE id_applet IN (10, 13);
DELETE FROM applet WHERE id IN (10, 13);
