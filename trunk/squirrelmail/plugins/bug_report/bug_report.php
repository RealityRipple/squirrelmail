<?php

/**
 * bug_report.php
 *
 * This generates the bug report data, gives information about where
 * it will be sent to and what people will do with it, and provides
 * a button to show the bug report mail message in order to actually
 * send it.
 *
 * Copyright (c) 1999-2002 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This is a standard Squirrelmail-1.2 API for plugins.
 *
 * $Id$
 */
                                       
   chdir("..");

   session_start();

   if (!isset($config_php))
      /* '_once' Added by ri_once */ include_once("../config/config.php");
   if (!isset($strings_php))
      /* '_once' Added by ri_once */ include_once("../functions/strings.php");
   if (!isset($page_header_php))
      /* '_once' Added by ri_once */ include_once("../functions/page_header.php");
   if (!isset($display_messages_php))
      /* '_once' Added by ri_once */ include_once("../functions/display_messages.php");
   if (!isset($imap_php))
      /* '_once' Added by ri_once */ include_once("../functions/imap.php");
   if (!isset($array_php))
      /* '_once' Added by ri_once */ include_once("../functions/array.php");
   if (!isset($i18n_php))
      /* '_once' Added by ri_once */ include_once("../functions/i18n.php");


   /* '_once' Added by ri_once */ include_once("../src/load_prefs.php");
   displayPageHeader($color, "None");


   function Show_Array($array)
   {
      foreach ($array as $key => $value)
      {
         if ($key != 0 || $value != '')
         {
           $str .= "    * $key = $value\n";
         }
      }
      if ($str == '')
          return "    * Nothing listed\n";
      return $str;
   }
   
   $body_top .= "I subscribe to the squirrelmail-list mailing list.\n";
   $body_top .= "  [ ]  True - No need to CC me when replying\n";
   $body_top .= "  [ ]  False - Please CC me when replying\n";
   $body_top .= "\n";
   $body_top .= "This bug occurs when I ...\n";
   $body_top .= "  ... view a particular message\n";
   $body_top .= "  ... use a specific plugin/function\n";
   $body_top .= "  ... try to do/view/use ....\n";
   $body_top .= "\n\n\n";
   $body_top .= "The description of the bug:\n\n\n";
   $body_top .= "I can reproduce the bug by:\n\n\n";
   $body_top .= "(Optional) I got bored and found the bug occurs in:\n\n\n";
   $body_top .= "(Optional) I got really bored and here's a fix:\n\n\n";
   $body_top .= "----------------------------------------------\n";


   $body .= "\nMy browser information:\n";
   $body .= "  $HTTP_USER_AGENT\n";
   $body .= "  get_browser() information (List)\n";
   $browser = get_browser();
   $body .= Show_Array((array) $browser);

   $body .= "\nMy web server information:\n";
   $body .= "  PHP Version " . phpversion() . "\n";
   $body .= "  PHP Extensions (List)\n";
   $body .= Show_Array(get_loaded_extensions());
   // Apache version?
   
   $body .= "\nSquirrelMail-specific information:\n";
   $body .= "  Version:  $version\n";
   $body .= "  Plugins (List)\n";
   $body .= Show_Array($plugins);
   if ($ldap_server[0] && ! extension_loaded('ldap'))
   {
       $warning = 1;
       $warnings['ldap'] = "LDAP server defined in SquirrelMail config, " .
           "but the module is not loaded in PHP";
       $corrections['ldap'][] = "Reconfigure PHP with the option '--with-ldap'";
       $corrections['ldap'][] = "Then recompile PHP and reinstall";
       $corrections['ldap'][] = "-- OR --";
       $corrections['ldap'][] = "Reconfigure SquirrelMail to not use LDAP";
   }
   
   $body .= "\nMy IMAP server information:\n";
   $body .= "  Server type:  $imap_server_type\n";
   $imap_stream = fsockopen ($imapServerAddress, $imapPort, &$error_number, &$error_string);
   $server_info = fgets ($imap_stream, 1024);
   if ($imap_stream)
   {
       // SUPRESS HOST NAME
       $list = explode(' ', $server_info);
       $list[2] = '[HIDDEN]';
       $server_info = implode(' ', $list);
       $body .=  "  Server info:  $server_info";
       fputs ($imap_stream, "a001 CAPABILITY\r\n");
       $read = fgets($imap_stream, 1024);
       $list = explode(' ', $read);
       array_shift($list);
       array_shift($list);
       $read = implode(' ', $list);
       $body .= "  Cabailities:  $read";
       fputs ($imap_stream, "a002 LOGOUT\r\n");
       fclose($imap_stream);
   }
   else
   {
     $body .= "  Unable to connect to IMAP server to get information.\n";
     $warning = 1;
     $warnings['imap'] = "Unable to connect to IMAP server";
     $corrections['imap'][] = "Make sure you specified the correct mail server";
     $corrections['imap'][] = "Make sure the mail server is running IMAP, not POP";
     $corrections['imap'][] = "Make sure the server responds to port $imapPort";
   }
   
   $warning_num = 0;
   if ($warning)
   {
       foreach ($warnings as $key => $value)
       {
           if ($warning_num == 0)
           {
               $body_top .= "WARNINGS WERE REPORTED WITH YOUR SETUP:\n";
               $body_top = "WARNINGS WERE REPORTED WITH YOUR SETUP -- SEE BELOW\n\n$body_top";
               $warning_html = "<h1>Warnings were reported with your setup:</h1>\n<dl>\n";
           }
           $warning_num ++;
           $warning_html .= "<dt><b>$value</b></dt>\n";
           $body_top .= "\n$value\n";
           foreach ($corrections[$key] as $corr_val)
           {
               $body_top .= "  * $corr_val\n";
               $warning_html .= "<dd>* $corr_val</dd>\n";
           }
       }
       $warning_html .= "</dl>\n<p>$warning_num warning(s) reported.</p>\n<hr>\n";
       $body_top .= "\n$warning_num warning(s) reported.\n";
       $body_top .= "----------------------------------------------\n";
   }
   
   $body = htmlspecialchars($body_top . $body);

?>
   <br>
   <table width=95% align=center border=0 cellpadding=2 cellspacing=0><tr><td bgcolor="<?php echo $color[0] ?>">
      <center><b>Submit a Bug Report</b></center>
   </td></tr></table>

   <?PHP echo $warning_html; ?>

   <p><font size="+1">Before you send your bug report</font>, please make sure to
   check this checklist for any common problems.</p>
   
   <ul>
   <li>Make sure that you are running the most recent copy of 
     <a href="http://www.squirrelmail.org/">SquirrelMail</a>.  You are currently
     using version <?PHP echo $version ?>.</li>
   <li>Check to see if you bug is already listed in the
   <a href="http://sourceforge.net/bugs/?group_id=311">Bug List</a> on SourceForge.
   If it is, we already know about it and are trying to fix it.</li>
   <li>Try to make sure that you can repeat it.  If the bug happens
     sporatically, try to document what you did when it happened.  If it
     always occurs when you view a specific message, keep that message around 
     so maybe we can see it.</li>
   <li>If there were warnings displayed above, try to resolve them yourself.
     Read the guides in the <tt>doc/</tt> directory where SquirrelMail was
     installed.</li>
   </ul>
   
   <p>Pressing the button below will start a mail message to the developers
   of SquirrelMail that will contain a lot of information about your system,
   your browser, how SquirrelMail is set up, and your IMAP server.  It will
   also prompt you for information.  Just fill out the sections at the top.
   If you like, you can scroll down in the message to see what else is being
   sent.</p>
   
   <p>Please make sure to fill out as much information as you possibly can to
   give everyone a good chance of finding and removing the bug.  Submitting
   your bug like this will not have it automatically added to the bug list on
   SourceForge, but someone who gets your message may add it for you.</p>

   <form action="../../src/compose.php" method=post>
   <table align=center border=0>
   <tr>
     <td>
       This bug involves: <select name="send_to">
         <option value="squirrelmail-list@lists.sourceforge.net">the general program</option>
         <option value="squirrelmail-plugins@lists.sourceforge.net">a specific plugin</option>
       </select>
     </td>
   </tr>
   <tr>
     <td align=center>
       <input type="hidden" name="send_to_cc" value="">
       <input type="hidden" name="send_to_bcc" value="">
       <input type="hidden" name="subject" value="Bug Report">
       <input type="hidden" name="body" value="<?PHP echo $body ?>">
       <input type="submit" value="Start Bug Report Form">
     </td>
   </tr>
   </table>
   </form>
</body></html>
