<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");

   include("../src/load_prefs.php");

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, 0);
   sqimap_mailbox_select($imapConnection, $old);

   $dm = sqimap_get_delimiter($imapConnection);
   if (strpos($old, $dm)) {
      $old_name = substr($old, strrpos($old, $dm)+1, strlen($old));
      $old_parent = substr($old, 0, strrpos($old, $dm));
   } else {
      $old_name = $old;
      $old_parent = "";
   }

   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, "None");
   echo "<TABLE WIDTH=100% COLS=1>";
   echo "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><FONT FACE=\"Arial,Helvetica\"><B>";
   echo _("Rename a folder");
   echo "</B></FONT></TD></TR>";
   echo "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
   echo "<FORM ACTION=folders_rename_do.php METHOD=POST>\n";
   echo _("New name:");
   echo " &nbsp;&nbsp;<INPUT TYPE=TEXT SIZE=25 NAME=new_name VALUE=\"$old_name\"><BR>\n";
   echo "<INPUT TYPE=HIDDEN NAME=orig VALUE=\"$old\">";
   echo "<INPUT TYPE=SUBMIT VALUE=\"";
   echo _("Submit");
   echo "\">\n";
   echo "</FORM><BR></TD></TR>";
   echo "</TABLE>";

   /** Log out this session **/
   sqimap_logout($imapConnection);
?>


