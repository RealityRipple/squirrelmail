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
 * - Add localisation
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
<tr><td bgcolor="#dcdcdc" align="center"><center><b>About SquirrelMail <?php echo $version; ?></b></center></td></tr>
<tr><td>
<br />
SquirrelMail is the name of the program that provides access to your email via the web.<br />
<br />
<strong>If you have questions about or problems with your mail account, passwords, abuse etc,
please refer to your system administrator or provider<?php
if ( $org_name != 'SquirrelMail' ) {
    echo '(' . $org_name . ')';
}
?>.</strong>
They can assist you adequately with these issues. The SquirrelMail development team
cannot help you with that. The <a href="help.php">help system</a> provides answers
to frequently asked questions.<br />
<br />
SquirrelMail is a feature rich, standards compliant webmail application written in PHP.
It was made by a group of volunteers united in the SquirrelMail Development Team and is
released as open source, free software under the <a href="http://www.gnu.org/copyleft/gpl.html"
target="_blank">GNU General Public License</a>.
For more information about SquirrelMail and the SquirrelMail development team, see
<a href="http://www.squirrelmail.org/" target="_blank">the SquirrelMail website</a>.<br />
<br /><br />
<b>System information</b><br/><br/>
<small>
You are using SquirrelMail version: <?php echo $version; ?><br />
The administrator installed the following plugins:<br />
<?php
if ( count ($plugins) > 0 ) {
    sort($plugins);
    echo "<ul>\n";
    foreach($plugins as $plugin) {
        echo "<li>" . $plugin . "</li>\n";
    }
    echo "</ul>\n\n";
} else {
    echo "<em>none installed</em>\n\n";
}
?>
</small>
<br /><br />
</td></tr>
<tr><td align="center">&copy; 1999 - 2005 The SquirrelMail Project Team</td></tr>
</table></p>
</body></html>
