<?php

/**
 * about.php
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * An "about box" detailing SquirrelMail info.
 *
 * TODO:
 * - Insert org_name, provider_url?
 * - What more information is needed?
 *
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');

displayPageHeader($color, 'None' );

?>
<p align="center">
<img src="../images/sm_logo.png" width="308" height="111"
    alt="SquirrelMail Logo" /><br />
<table align="center" width="80%" cellpadding="1" cellspacing="2" border="0">
<tr><td bgcolor="#dcdcdc" align="center"><center><b>
<?php echo sprintf(_("About SquirrelMail %s"),$version); ?>
</b></center></td></tr>
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

// i18n: %s tags are used in order to remove html URL attributes from translation
echo sprintf(_("For more information about SquirrelMail and the SquirrelMail Project Team, see %sthe SquirrelMail website%s."),'<a href="http://www.squirrelmail.org/" target="_blank">','</a>');
?>
<br />
<br /><br />
<b>
<?php echo _("System information"); ?>
</b><br/><br/>
<small>
<?php
echo sprintf(_("You are using SquirrelMail version: %s"),$version);
echo "<br />\n";
echo _("The administrator installed the following plugins:");
echo "<br />\n";
if ( count ($plugins) > 0 ) {
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
<tr><td align="center">&copy; 1999 - 2005 The SquirrelMail Project Team</td></tr>
</table></p>
</body></html>
