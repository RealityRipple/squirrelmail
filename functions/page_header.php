<?
   /**
    **  page_header.php
    **
    **  Prints the page header (duh)
    **
    **/

   function displayPageHeader($mailbox) {
      /** Here is the header and wrapping table **/
      $shortBoxName = readShortMailboxName($mailbox, ".");
      echo "<TABLE BGCOLOR=FFFFFF BORDER=0 COLS=2 WIDTH=100% CELLSPACING=0 CELLPADDING=2>";
      echo "   <TR BGCOLOR=A0B8C8 WIDTH=100%>";
      echo "      <TD ALIGN=left WIDTH=30%>";
      echo "         <FONT FACE=\"Arial,Helvetica\"><A HREF=\"signout.php\" TARGET=_top><B>Sign Out</B></A></FONT>";
      echo "      </TD><TD ALIGN=right WIDTH=70%>";
      echo "         <FONT FACE=\"Arial,Helvetica\"><div align=right>Current Folder: <B>$shortBoxName</div></B></FONT>";
      echo "      </TD>";
      echo "   </TR></TABLE>\n";
      echo "<TABLE BGCOLOR=FFFFFF BORDER=0 COLS=2 WIDTH=100% CELLSPACING=0 CELLPADDING=2><TR>";
      echo "      <TD ALIGN=left WIDTH=70%>";
      echo "         <FONT FACE=\"Arial,Helvetica\"><A HREF=\"compose.php\">Compose</A></FONT>&nbsp&nbsp";
      echo "         <FONT FACE=\"Arial,Helvetica\">Addresses</FONT>&nbsp&nbsp";
      echo "         <FONT FACE=\"Arial,Helvetica\"><A HREF=\"folders.php\">Folders</A></FONT>&nbsp&nbsp";
      echo "         <FONT FACE=\"Arial,Helvetica\">Options</FONT>&nbsp&nbsp";
      echo "      </TD><TD ALIGN=right WIDTH=30%>";
      echo "         <FONT FACE=\"Arial,Helvetica\"><A HREF=\"http://adam.usa.om.org/~luke/main.php3\" TARGET=_top>Todos & Bugs</A></FONT>&nbsp&nbsp";
      echo "         <FONT FACE=\"Arial,Helvetica\">Help!</FONT>";
      echo "      </TD>";
      echo "</TABLE>";
  }
?>
