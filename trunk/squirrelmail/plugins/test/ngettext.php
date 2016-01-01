<?php

/**
 * SquirrelMail Test Plugin
 *
 * This page tests the ngettext() function.
 *
 * @copyright 2006-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage test
 */

include_once('../../include/init.php');

global $oTemplate, $color;

displayPageHeader($color, '');

sq_change_text_domain('test');

// NOTE: Not bothering to "templatize" the following output, since
//       this plugin is merely an administrative (and not user-facing)
//       tool.  If this is really important to you, please help by
//       submitting the work to the team.

?>
<strong>ngettext Test Strings:</strong>

<p>The results of this test depend on your current language (translation) selection (see Options ==> Display Preferences) and the corresponding translation strings in locale/xx/LC_MESSAGES/test.mo</p>

<pre>

<?php

for ($i = -10 ; $i <= 250 ; $i++) {
    echo sprintf(ngettext("%s squirrel is on the tree.", "%s squirrels are on the tree.", $i), $i);
    echo "\n";
}

echo "</pre>";

sq_change_text_domain('squirrelmail');
$oTemplate->display('footer.tpl');


