<?php
   /**
    **  page_header.php
    **
    **  Copyright (c) 1999-2001 The Squirrelmail Development Team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Prints the page header (duh)
    **
    **  $Id$
    **/

   // Always set up the language before calling these functions

   function displayHtmlHeader( $title = 'SquirrelMail', $xtra = '', $do_hook = TRUE ) {

        global $theme_css;

        echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">' .
             "\n\n<HTML>\n<HEAD>\n";

        if ($theme_css != '') {
            echo "<LINK REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"$theme_css\">\n";
        }

        if( $do_hook ) {
            do_hook ("generic_header");
        }

        echo "<title>$title</title>$xtra</head>\n\n";

   }

   function displayInternalLink ($path, $text, $target='') {
      global $base_uri;

      if ($target != '')
         $target = " target=\"$target\"";
      
      echo '<a href="'.$base_uri.$path.'"'.$target.'>'.$text.'</a>';
   }

   function displayPageHeader($color, $mailbox) {
      global $delimiter, $hide_sm_attributions;
      displayHtmlHeader ();

      echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\" onLoad='if ( ( document.forms.length > 0 ) && ( document.forms[0].elements[0].type == \"text\" ) ) { document.forms[0].elements[0].focus(); }'>\n\n";

      /** Here is the header and wrapping table **/
      $shortBoxName = readShortMailboxName($mailbox, $delimiter);
      echo "<A NAME=pagetop></A>\n".
      // "<table cellpadding=1 cellspacing=1 BGCOLOR=\"$color[4]\" width=100%><tr><td>".
           "<TABLE BGCOLOR=\"$color[4]\" BORDER=0 WIDTH=\"100%\" CELLSPACING=0 CELLPADDING=2>\n".
           "   <TR BGCOLOR=\"$color[9]\" >\n".
           "      <TD ALIGN=left><b>\n";
      displayInternalLink ("src/signout.php", _("Sign Out"), "_top");
      echo "      </b></TD><TD ALIGN=right>\n".
           '         ' . _("Current Folder") . ": <B>$shortBoxName&nbsp;</B>\n".
           "      </TD>\n".
           "   </TR>\n".
           "   <TR BGCOLOR=\"$color[4]\">\n".
           "      <TD ALIGN=left>\n";
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
      echo ($hide_sm_attributions ? '&nbsp;' :
            "<A HREF=\"http://www.squirrelmail.org/\" TARGET=\"_blank\">SquirrelMail</A>\n");
      echo "      </TD>\n".
           "   </TR>\n".
           "</TABLE>\n\n";
      // echo "</td></tr></table>";
  }

?>