<?php
   /**
    **  page_header.php
    **
    **  Prints the page header (duh)
    **
    **/

   session_start();

   $page_header_php = true;

   if (!isset($prefs_php))
      include ("../functions/prefs.php");
   if (!isset($i18n_php))
      include ("../functions/i18n.php");
   if (!isset($plugin_php))
      include ("../functions/plugin.php");

   // Check to see if gettext is installed
   if (function_exists("_")) {
      // Setting the language to use for gettext if it is not English
      // (the default language) or empty.
      $squirrelmail_language = getPref ($data_dir, $username, "language");
      if ($squirrelmail_language != "en" && $squirrelmail_language != "") {
         putenv("LC_ALL=$squirrelmail_language");
         bindtextdomain("squirrelmail", "../locale/");
         textdomain("squirrelmail");
         $default_charset = $languages[$squirrelmail_language]["CHARSET"];
      }
   } else {
      function _($string) {
         return $string;
      }
   }

   // This is done to ensure that the character set is correct.
   if ($default_charset != "")
      header ("Content-Type: text/html; charset=$default_charset");

   function displayHtmlHeader ($title="SquirrelMail") {
     global $theme_css;

      echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">';
      echo "\n\n";
      echo "<HTML>\n";
      echo "<HEAD>\n";
      if ($theme_css != "") {
        printf ('<LINK REL="stylesheet" TYPE="text/css" HREF="%s">', 
                $theme_css);
        echo "\n";
      }
      
      do_hook ("generic_header");

      echo "<TITLE>$title</TITLE>";
      echo "</HEAD>\n\n";
   }

   function displayInternalLink ($path, $text, $target="") {
      global $base_uri;

      if ($target != "")
         $target = " target=\"$target\"";
      
      echo '<a href="'.$base_uri.$path.'"'.$target.'>'.$text.'</a>';
   }

   function displayPageHeader($color, $mailbox) {
      displayHtmlHeader ($color);

      printf('<BODY TEXT="%s" BGCOLOR="%s" LINK="%s" VLINK="%s" ALINK="%s">',
             $color[8], $color[4], $color[7], $color[7], $color[7]);
      echo "\n\n";

      /** Here is the header and wrapping table **/
      $shortBoxName = readShortMailboxName($mailbox, ".");
      $shortBoxName = stripslashes($shortBoxName);
      echo "<A NAME=pagetop></A>\n";
      echo "<TABLE BGCOLOR=\"$color[4]\" BORDER=0 WIDTH=\"100%\" CELLSPACING=0 CELLPADDING=2>\n";
      echo "   <TR BGCOLOR=\"$color[9]\">\n";
      echo "      <TD ALIGN=left WIDTH=\"30%\"><b>\n";
      displayInternalLink ("src/signout.php", _("Sign Out"), "_top");
      echo "      </b></TD><TD ALIGN=right WIDTH=\"70%\">\n";
      echo "         <div align=right>" . _("Current Folder") . ": <B>$shortBoxName&nbsp;</B></div>\n";
      echo "      </TD>\n";
      echo "   </TR>\n";
      echo "</TABLE>\n\n";
      echo "<TABLE BGCOLOR=\"$color[4]\" BORDER=0 WIDTH=\"100%\" CELLSPACING=0 CELLPADDING=2>\n";
      echo "   <TR>\n";
      echo "      <TD ALIGN=left WIDTH=\"99%\">\n";
      $urlMailbox = $mailbox;
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

      echo "      </TD><TD ALIGN=right nowrap WIDTH=\"1%\">\n";
      echo "         <A HREF=\"http://www.squirrelmail.org/index.php3?from=1\" TARGET=\"_top\">SquirrelMail</A>\n";
      echo "      </TD>\n";
      echo "   </TR>\n";
      echo "</TABLE>\n\n";
  }
?>
