<?php

/**
  * usage.tpl
  *
  * Template for the usage explanation screen for the Bug Report plugin.
  *
  * The following variables are available in this template:
  *
  * string $message_body        The initial template text of the bug report
  *                             message
  * array  $warning_messages    A list of warning texts, if any occurred
  *                             (array keys must be the "type" used to
  *                             index the $correction_messages array below)
  * array  $correction_messages A list of arrays keyed by warning type,
  *                             wherein arrays of correction messages are
  *                             placed
  * int    $warning_count       The number of warnings
  * string $version             The current SquirrelMail version string
  * string $title_bg_color      The background color for the page title
  * string $admin_email         The administrator's email address, if any
  *                             (possibly empty)
  *                       
  * @copyright 1999-2016 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  * @subpackage plugins
  */


// retrieve the template vars
//
extract($t);


?>
    <br />
    <table width="95%" align="center" border="0" cellpadding="2" cellspacing="0"><tr>
      <td align="center" bgcolor="<?php echo $title_bg_color; ?>"><b>
        <?php echo _("Submit a Bug Report"); ?>
      </b></td>
    </tr></table>

<?php

   // warnings require special layout
   //
   if (!empty($warning_messages)) {
       echo '<h1>' . _("Warnings were reported in your setup:") . '</h1><dl>';
       foreach ($warning_messages as $warning_type => $warning_text) {
           echo "<dt><b>$warning_text</b></dt>\n";
           foreach ($correction_messages[$warning_type] as $correction_text) {
               echo "<dd>* $correction_text</dd>\n";
           }
       }
       echo "</dl>\n<p>" . sprintf(_("%d warning(s) reported."), $warning_count) 
          . "</p>\n<hr />\n";
   }


echo '<p><a href="show_system_specs.php" target="_blank">'
   . _("Show System Specifications")
   . "</a></p>\n\n"
   . '<p><big>'
   . _("Before you send your bug report, please make sure to check this checklist for any common problems.")
   . "</big></p>\n"

   . '<ul>'
   . '<li>'
   . sprintf(_("Make sure that you are running the most recent copy of %s. You are currently using version %s."), '<a href="http://squirrelmail.org/" target="_blank">SquirrelMail</a>', $version)
   . "</li>\n"

   . '<li>'
   . sprintf(_("Check to see if your bug is already listed in the %sBug List%s on SourceForge. If it is, we already know about it and are trying to fix it."), '<a href="http://sourceforge.net/bugs/?group_id=311" target="_blank">', '</a>')
   . "</li>\n"

   . '<li>'
   . _("Try to make sure that you can repeat it. If the bug happens sporatically, try to document what you did when it happened. If it always occurs when you view a specific message, keep that message around so maybe we can see it.")
   . "</li>\n"

   . '<li>'
   . sprintf(_("If there were warnings displayed above, try to resolve them yourself. Read the guides in the %s directory where SquirrelMail was installed."), '<tt>doc/</tt>')
   . "</li>\n"
   . "</ul>\n"

   . '<p>'
   . _("Pressing the button below will start a mail message to the developers of SquirrelMail that will contain a lot of information about your system, your browser, how SquirrelMail is set up, and your IMAP server. It will also prompt you for information. Just fill out the sections at the top. If you like, you can scroll down in the message to see what else is being sent.")
   . "</p>\n"

   . '<p>'
   . _("Please make sure to fill out as much information as you possibly can to give everyone a good chance of finding and removing the bug. Submitting your bug like this will not have it automatically added to the bug list on SourceForge, but someone who gets your message may add it for you.")
   . "</p>\n"

   . '<form action="' . $base_uri . 'src/compose.php" method="post">';
?>
      <table align="center" border="0">
        <tr>
          <td>
            <?php echo _("This bug involves:")
                     . ' <select name="send_to">';

            // if admin's email is set - add 'report to admin'
            // option and make it default one
            //
            if (! empty($admin_email)) {
                echo '<option value="' . $admin_email . '" selected="selected">'
                   . _("my email account") . '</option>';
            }
            ?>
              <option value="squirrelmail-users@lists.sourceforge.net"><?php
                  echo _("the general program"); ?></option>
              <option value="squirrelmail-plugins@lists.sourceforge.net"><?php
                  echo _("a specific plugin"); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <td align="center">
            <input type="hidden" name="send_to_cc" value="" />
            <input type="hidden" name="send_to_bcc" value="" />
            <input type="hidden" name="subject" value="Bug Report" />
            <input type="hidden" name="body" value="<?php echo $message_body; ?>" />
            <input type="submit" value="<?php echo _("Start Bug Report Form"); ?>" />
          </td>
        </tr>
      </table>
    </form>
    <br />
