<?php

/**
 * bug_report.php
 *
 * This generates the bug report data, gives information about where
 * it will be sent to and what people will do with it, and provides
 * a button to show the bug report mail message in order to actually
 * send it.
 *
 * Copyright (c) 1999-2004 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This is a standard Squirrelmail-1.2 API for plugins.
 *
 * @version $Id$
 * @package plugins
 * @subpackage bug_report
 */

/**
 * @ignore
 */
define('SM_PATH','../../');

require_once(SM_PATH . 'include/validate.php');

// loading form functions
require_once(SM_PATH . 'functions/forms.php');

displayPageHeader($color, 'None');

include_once(SM_PATH . 'plugins/bug_report/system_specs.php');
global $body;

$body_top = "I subscribe to the squirrelmail-users mailing list.\n" .
                "  [ ]  True - No need to CC me when replying\n" .
                "  [ ]  False - Please CC me when replying\n" .
                "\n" .
                "This bug occurs when I ...\n" .
                "  ... view a particular message\n" .
                "  ... use a specific plugin/function\n" .
                "  ... try to do/view/use ....\n" .
                "\n\n\n" .
                "The description of the bug:\n\n\n" .
                "I can reproduce the bug by:\n\n\n" .
                "(Optional) I got bored and found the bug occurs in:\n\n\n" .
                "(Optional) I got really bored and here's a fix:\n\n\n" .
                "----------------------------------------------\n\n";

$body = htmlspecialchars($body_top) . $body;

?>
   <br>
   <table width="95%" align="center" border="0" cellpadding="2" cellspacing="0"><tr>
      <?php echo html_tag('td',"<b>"._("Submit a Bug Report")."</b>",'center',$color[0]); ?>
   </tr></table>

   <?PHP echo $warning_html; 

   echo '<p><big>';
   echo _("Before you send your bug report, please make sure to check this checklist for any common problems.");
   echo "</big></p>\n";

   echo "<ul>";
   echo "<li>";
   echo _("Make sure that you are running the most recent copy of <a href=\"http://www.squirrelmail.org/\">SquirrelMail</a>.");
   echo sprintf(_("You are currently using version %s."),$version);
   echo "</li>\n";

   echo "<li>";
   echo _("Check to see if your bug is already listed in the <a href=\"http://sourceforge.net/bugs/?group_id=311\">Bug List</a> on SourceForge. If it is, we already know about it and are trying to fix it.");
   echo "</li>\n";
   
   echo "<li>";
   echo _("Try to make sure that you can repeat it. If the bug happens sporatically, try to document what you did when it happened. If it always occurs when you view a specific message, keep that message around so maybe we can see it.");
   echo "</li>\n";

   echo "<li>";
   echo _("If there were warnings displayed above, try to resolve them yourself. Read the guides in the <tt>doc/</tt> directory where SquirrelMail was installed.");
   echo "</li>\n";
   echo "</ul>\n";

   echo "<p>";
   echo _("Pressing the button below will start a mail message to the developers of SquirrelMail that will contain a lot of information about your system, your browser, how SquirrelMail is set up, and your IMAP server. It will also prompt you for information. Just fill out the sections at the top. If you like, you can scroll down in the message to see what else is being sent.");
   echo "</p>\n";

   echo "<p>";
   echo _("Please make sure to fill out as much information as you possibly can to give everyone a good chance of finding and removing the bug. Submitting your bug like this will not have it automatically added to the bug list on SourceForge, but someone who gets your message may add it for you.");
   echo "</p>\n";
?>
   <form action="../../src/compose.php" method=post>
   <table align="center" border="0">
   <tr>
     <td>
       <?php echo _("This bug involves"); ?>: <select name="send_to">
         <option value="squirrelmail-users@lists.sourceforge.net">
	 <?php echo _("the general program"); ?></option>
         <option value="squirrelmail-plugins@lists.sourceforge.net">
	 <?php echo _("a specific plugin"); ?></option>
       </select>
     </td>
   </tr>
   <tr>
     <td align="center">
    <?php
	echo addHidden("send_to_cc","");
	echo addHidden("send_to_bcc","");
	echo addHidden("subject","Bug Report");
	echo addHidden("body",$body);
	echo addSubmit(_("Start Bug Report Form"));
    ?>
     </td>
   </tr>
   </table>
   </form>
</body></html>
