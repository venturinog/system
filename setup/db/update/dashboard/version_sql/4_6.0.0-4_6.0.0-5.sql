-- Issabel 6 no longer ships a mail server: drop the email dashboard applets
-- (webmail quota "System" and "Emails"), which relied on the local IMAP server.
DELETE FROM activated_applet_by_user
    WHERE id_dabu IN (SELECT id FROM default_applet_by_user WHERE id_applet IN (9, 12));
DELETE FROM default_applet_by_user WHERE id_applet IN (9, 12);
DELETE FROM applet WHERE id IN (9, 12);
