<?php
   /**
    **  page_header.php
    **
    **  Prints the page header (duh)
    **
    **  $Id$
    **/

   if (defined('page_header_php'))
       return;
   define('page_header_php', true);

   // Always set up the language before calling these functions

   function displayHtmlHeader ($title="SquirrelMail") {
     global $theme_css;

      echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">' .
           "\n\n<HTML>\n<HEAD>\n";
      if ($theme_css != '') {
        echo "<LINK REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"$theme_css\">\n"; 
      }
      
      do_hook ("generic_header");

      echo "<TITLE>$title</TITLE>\n";
      echo "</HEAD>\n\n";
   }

   function displayInternalLink ($path, $text, $target='') {
      global $base_uri;

      if ($target != '')
         $target = " target=\"$target\"";
      
      echo '<a href="'.$base_uri.$path.'"'.$target.'>'.$text.'</a>';
   }

   function displayPageHeader($color, $mailbox) {
      global $delimiter;
      displayHtmlHeader ();

      echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\" onLoad='if ( ( document.forms.length > 0 ) && ( document.forms[0].elements[0].type == \"text\" ) ) { document.forms[0].elements[0].focus(); }'>\n\n";

      /** Here is the header and wrapping table **/
      $shortBoxName = readShortMailboxName($mailbox, $delimiter);
      echo "<A NAME=pagetop></A>\n";
      // echo "<table cellpadding=1 cellspacing=1 BGCOLOR=\"$color[4]\" width=100%><tr><td>";
      echo "<TABLE BGCOLOR=\"$color[4]\" BORDER=0 WIDTH=\"100%\" CELLSPACING=0 CELLPADDING=2>\n";
      echo "   <TR BGCOLOR=\"$color[9]\" >\n";
      echo "      <TD ALIGN=left><b>\n";
      displayInternalLink ("src/signout.php", _("Sign Out"), "_top");
      echo "      </b></TD><TD ALIGN=right>\n";
      echo '         ' . _("Current Folder") . ": <B>$shortBoxName&nbsp;</B>\n";
      echo "      </TD>\n";
      echo "   </TR>\n";
      echo "   <TR BGCOLOR=\"$color[4]\">\n";
      echo "      <TD ALIGN=left>\n";
      $urlMailbox = urlencode($mailbox);
      displayInternalLink ("src/compose.php?mailbox=$urlMailbox", _("Compose"), "right");
      echo "&nbsp;&nbsp;\n";
      displayInternalLink ("src/addressbook.php", _("Addresses"), "right");
      echo "&nbsp;&nbsp;\n";
      displayInternalLink ("src/folders.php", _("Folders"), "right");
      echo "&nbsp;&nbsp;\n";
      displayInternalLink ("src/options.php", _("Options"), "right");
      echo "&nbsp;&nbsp;\n";
      displayInternalLink ("src/search.php?mailbox=$urlMailbox", _("Search"), "right");
      echo "&nbsp;&nbsp;\n";
      displayInternalLink ("src/help.php", _("Help"), "right");
      echo "&nbsp;&nbsp;\n";

      do_hook("menuline");

      echo "      </TD><TD ALIGN=right>\n";
      echo "         <A HREF=\"http://www.squirrelmail.org/\" TARGET=\"_blank\">SquirrelMail</A>\n";
      echo "      </TD>\n";
      echo "   </TR>\n";
      echo "</TABLE>\n\n";
      // echo "</td></tr></table>";
  }
?>
