<?
   /**
    **  page_header.php
    **
    **  Prints the page header (duh)
    **
    **/

   $page_header_php = true;

   // This is done to ensure that the character set is correct when
   // receiving input from HTTP forms
   header ("Content-Type: text/html; charset=iso-8859-1");

   function displayPageHeader($color, $mailbox) {
      /** Here is the header and wrapping table **/
      $shortBoxName = readShortMailboxName($mailbox, ".");
      echo "<TABLE BGCOLOR=\"$color[4]\" BORDER=0 COLS=2 WIDTH=100% CELLSPACING=0 CELLPADDING=2>";
      echo "   <TR BGCOLOR=\"$color[9]\" WIDTH=100%>";
      echo "      <TD ALIGN=left WIDTH=30%>";
      echo "         <FONT FACE=\"Arial,Helvetica\"><A HREF=\"signout.php\" TARGET=_top><B>" . _("Sign Out") . "</B></A></FONT>";
      echo "      </TD><TD ALIGN=right WIDTH=70%>";
      echo "         <FONT FACE=\"Arial,Helvetica\"><div align=right>" . _("Current Folder: ") . "<B>$shortBoxName&nbsp;</div></B></FONT>";
      echo "      </TD>";
      echo "   </TR></TABLE>\n";
      echo "<TABLE BGCOLOR=\"$color[4]\" BORDER=0 COLS=2 WIDTH=100% CELLSPACING=0 CELLPADDING=2><TR>";
      echo "      <TD ALIGN=left WIDTH=70%>";
      echo "         <FONT FACE=\"Arial,Helvetica\"><A HREF=\"compose.php\">" . _("Compose") . "</A></FONT>&nbsp&nbsp";
      echo "         <FONT FACE=\"Arial,Helvetica\">". _("Addresses") ."</FONT>&nbsp&nbsp";
      echo "         <FONT FACE=\"Arial,Helvetica\"><A HREF=\"folders.php\">" . _("Folders") . "</A></FONT>&nbsp&nbsp";
      echo "         <FONT FACE=\"Arial,Helvetica\"><A HREF=\"options.php\">" . _("Options") . "</A></FONT>&nbsp&nbsp";
      echo "      </TD><TD ALIGN=right WIDTH=30%>";
      echo "         <FONT FACE=\"Arial,Helvetica\"><A HREF=\"http://squirrelmail.sourceforge.net\" TARGET=_top>SquirrelMail</A></FONT>";
      echo "      </TD>";
      echo "</TABLE>";
  }
?>
