<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");

   include("../src/load_prefs.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   selectMailbox($imapConnection, $old, $numMessages);

   $dm = findMailboxDelimeter($imapConnection);
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
   echo "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><FONT FACE=\"Arial,Helvetica\"><B>Rename a folder</B></FONT></TD></TR>";
   echo "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
   echo "<FORM ACTION=folders_rename_do.php METHOD=POST>\n";
   echo "New name: &nbsp;&nbsp;<INPUT TYPE=TEXT SIZE=25 NAME=new_name VALUE=\"$old_name\"><BR>\n";
   echo "<INPUT TYPE=HIDDEN NAME=orig VALUE=\"$old\">";
   echo "<INPUT TYPE=SUBMIT VALUE=Submit>\n";
   echo "</FORM><BR></TD></TR>";
   echo "</TABLE>";

   /** Log out this session **/
   fputs($imapConnection, "1 logout");
?>


