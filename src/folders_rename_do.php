<?
   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($imap_php))
      include("../functions/imap.php");

   include("../src/load_prefs.php");

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, 0);
   $dm = sqimap_get_delimiter($imapConnection);

   if (strpos($orig, $dm))
      $old_dir = substr($orig, 0, strrpos($orig, $dm));
   else
      $old_dir = "";

   if ($old_dir != "")
      $newone = "$old_dir$dm$new_name";
   else
      $newone = "$new_name";

   fputs ($imapConnection, ". RENAME \"$orig\" \"$newone\"\n");
   $data = sqimap_read_data($imapConnection, ".", true, $a, $b);

   // Renaming a folder doesn't renames the folder but leaves you unsubscribed
   //    at least on Cyrus IMAP servers.
   fputs ($imapConnection, "sub UNSUBSCRIBE \"$orig\"\n");
   fputs ($imapConnection, "sub SUBSCRIBE \"$newone\"\n");
   $data = sqimap_read_data($imapConnection, "sub", true, $a, $b);

   /** Log out this session **/
   fputs($imapConnection, "1 logout");

   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   echo "<BR><BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>";
   echo _("Return");
   echo "</A>";
   echo "</BODY></HTML>";
?>
