<?
/* Organization's logo picture (blank if none) */
$org_logo = "../images/om_webmail.gif";

/* Organization's name */
$org_name = "Operation Mobilization";

/* The server that your imap server is on */
$imapServerAddress = "adam.usa.om.org";

/* This is displayed right after they log in */
$motd = "  Welcome to OM's webmail system, SquirrelMail.  We are currently in beta, and have not yet released a full version of SquirrelMail.  Please feel free to look around, and please report any bugs to <A HREF=\"mailto:nathan@usa.om.org\">Nathan</A> or <A HREF=\"mailto:luke@usa.om.org\">Luke</A>.";

/* SquirrelMail version number -- DO NOT CHANGE */
$version = "0.0.1";

/* The following are related to deleting messages.
 *   $move_to_trash
 *         - if this is set to "true", when "delete" is pressed, it will attempt
 *           to move the selected messages to the folder named $trash_folder.  If
 *           it's set to "false", we won't even attempt to move the messages, just
 *           delete them.
 *   $trash_folder
 *         - This is the path to the default trash folder.  For Cyrus IMAP, it
 *           would be "INBOX.Trash", but for UW it would be "Trash".  We need the
 *           full path name here.
 */

$move_to_trash = true;
$trash_folder = "INBOX.Trash";

?>
