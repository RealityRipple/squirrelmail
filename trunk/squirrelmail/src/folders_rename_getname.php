<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   selectMailbox($imapConnection, $old, $numMessages);
   getFolderList($imapConnection, $boxesFormatted, $boxesUnformatted);

   $old_name = substr($old, strrpos($old, ".")+1, strlen($old));
   $old_parent = substr($old, 0, strrpos($old, "."));

   echo "<HTML><BODY TEXT=\"#000000\" BGCOLOR=\"#FFFFFF\" LINK=\"#0000EE\" VLINK=\"#0000EE\" ALINK=\"#0000EE\">";
   displayPageHeader("None");
   echo "<TABLE WIDTH=100% COLS=1>";
   echo "<TR><TD BGCOLOR=DCDCDC ALIGN=CENTER><FONT FACE=\"Arial,Helvetica\"><B>Rename or Move a folder</B></FONT></TD></TR>";
   echo "<TR><TD BGCOLOR=FFFFFF ALIGN=CENTER>";
   echo "<FORM ACTION=folders_rename_do.php METHOD=POST>\n";
   echo "Original Name: &nbsp;&nbsp;<INPUT TYPE=TEXT SIZE=25 NAME=new_name VALUE=\"$old_name\"><BR>\n";
   echo "As a subfolder of: &nbsp;&nbsp;";
   echo "<INPUT TYPE=HIDDEN NAME=orig VALUE=\"$old\">";
   echo "<SELECT NAME=subfolder><FONT FACE=\"Arial,Helvetica\">\n";
   for ($i = 0;$i < count($boxesUnformatted); $i++) {
      if ($boxesUnformatted[$i] == $old_parent)
         echo "<OPTION SELECTED>$boxesUnformatted[$i]\n";
      else
         echo "<OPTION>$boxesUnformatted[$i]\n";
   }
   echo "</SELECT><BR>\n";
   echo "<INPUT TYPE=SUBMIT VALUE=Submit>\n";
   echo "</FORM><BR></TD></TR>";
   echo "</TABLE>";

   /** Log out this session **/
   fputs($imapConnection, "1 logout");
?>


