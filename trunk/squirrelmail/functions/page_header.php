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
         
         // Setting cookie to use on the login screen the next time the
         // same user logs in.
         #setcookie("squirrelmail_language", $squirrelmail_language, 
         #          time()+2592000);
         $squirrelmail_language = $language;
      }
   } else {
      function _($string) {
         return $string;
      }
   }

   // This is done to ensure that the character set is correct.
   if ($default_charset != "")
      header ("Content-Type: text/html; charset=$default_charset");

   function displayPageHeader($color, $mailbox) {
      /** Here is the header and wrapping table **/
      $shortBoxName = readShortMailboxName($mailbox, ".");
      $shortBoxName = stripslashes($shortBoxName);
      echo "<TABLE BGCOLOR=\"$color[4]\" BORDER=0 COLS=2 WIDTH=100% CELLSPACING=0 CELLPADDING=2>";
      echo "   <TR BGCOLOR=\"$color[9]\" WIDTH=100%>";
      echo "      <TD ALIGN=left WIDTH=30%>";
      echo "         <A HREF=\"signout.php\" TARGET=_top><B>" . _("Sign Out") . "</B></A>";
      echo "      </TD><TD ALIGN=right WIDTH=70%>";
      echo "         <div align=right>" . _("Current Folder: ") . "<B>$shortBoxName&nbsp;</div></B>";
      echo "      </TD>";
      echo "   </TR></TABLE>\n";
      echo "<TABLE BGCOLOR=\"$color[4]\" BORDER=0 COLS=2 WIDTH=100% CELLSPACING=0 CELLPADDING=2><TR>";
      echo "      <TD ALIGN=left WIDTH=70%>";
      $urlMailbox = $mailbox;
      echo "         <A HREF=\"compose.php?mailbox=$urlMailbox\">" . _("Compose") . "</A>&nbsp;&nbsp;";
      echo "         <A HREF=\"addressbook.php\">" . _("Addresses") . "</A>&nbsp;&nbsp;";
      echo "         <A HREF=\"folders.php\">" . _("Folders") . "</A>&nbsp;&nbsp;";
      echo "         <A HREF=\"options.php\">" . _("Options") . "</A>&nbsp;&nbsp;";
      echo "      </TD><TD ALIGN=right WIDTH=30%>";
      echo "         <A HREF=\"http://squirrelmail.sourceforge.net/index.php3?from=1\" TARGET=_top>SquirrelMail</A>";
      echo "      </TD>";
      echo "</TABLE>";
  }
?>
