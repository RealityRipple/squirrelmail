<?php

/**
 * about.php
 *
 * An "about box" detailing SquirrelMail info.
 *
 * TODO:
 * - Insert org_name, provider_url?
 * - What more information is needed?
 * - Display of system information might be restricted
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** This is the about page */
define('PAGE_NAME', 'about');

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

displayPageHeader($color);

?>
<p align="center">
<img src="../images/sm_logo.png" width="308" height="111"
    alt="SquirrelMail Logo" /><br />
<table align="center" width="80%" cellpadding="1" cellspacing="2" border="0">
<tr><td bgcolor="#dcdcdc" align="center"><div style="text-align: center;"><b>
<?php echo sprintf(_("About SquirrelMail %s"), SM_VERSION); ?>
</b></div></td></tr>
<tr><td>
<br />
<?php echo _("SquirrelMail is the name of the program that provides access to your email via the web."); ?>
<br />
<br />
<strong>
<?php
// i18n: %s displays org_name variable value enclosed in () or empty string.
echo sprintf(_("If you have questions about or problems with your mail account, passwords, abuse etc, please refer to your system administrator or provider%s."),( $org_name != 'SquirrelMail' ? ' (' . $org_name . ')':''));
echo "</strong>\n";

// i18n: %s tags are used in order to remove html URL attributes from translation
echo sprintf(_("They can assist you adequately with these issues. The SquirrelMail Project Team cannot help you with that. The %shelp system%s provides answers to frequently asked questions."),'<a href="help.php">','</a>');

echo "<br />\n<br />\n";

// i18n: %s tags are used in order to remove html URL attributes from translation
echo sprintf(_("SquirrelMail is a feature rich, standards compliant webmail application written in PHP. It was made by a group of volunteers united in the SquirrelMail Project Team and is released as open source, free software under the %sGNU General Public License%s."),'<a href="http://www.gnu.org/copyleft/gpl.html" target="_blank">','</a>');

// add space between two sentences.
// Don't want to join two sprintf strings.
echo ' ';

// i18n: %s tags are used in order to remove html URL attributes from translation
echo sprintf(_("For more information about SquirrelMail and the SquirrelMail Project Team, see %sthe SquirrelMail website%s."),'<a href="http://squirrelmail.org/" target="_blank">','</a>');
?>
<br />
<br /><br />
<b>
<?php echo _("System information"); ?>
</b><br/><br/>
<small>
<?php
echo sprintf(_("You are using SquirrelMail version: %s"), SM_VERSION);
echo "<br />\n";
echo _("The administrator installed the following plugins:");
echo "<br />\n";
if ( isset($plugins) && count ($plugins) > 0 ) {
    sort($plugins);
    echo "<ul>\n";
    foreach($plugins as $plugin) {
        echo "<li>" . $plugin . "</li>\n";
    }
    echo "</ul>\n\n";
} else {
    echo '<em>'._("none installed")."</em>\n\n";
}

?>
</small>
<br /><br />
</td></tr>
<tr><td align="center">&copy; <?php echo SM_COPYRIGHT ?> The SquirrelMail Project Team</td></tr>
</table></p>
<?php
$oTemplate->display('footer.tpl');
