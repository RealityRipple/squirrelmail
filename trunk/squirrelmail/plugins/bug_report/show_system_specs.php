<?php

/**
 * This shows system specification details.
 *
 * This is a standard SquirrelMail 1.2 API for plugins.
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage bug_report
 */

/**
 * @ignore
 */
define('SM_PATH','../../');
include_once(SM_PATH . 'include/validate.php');

/** is bug_report plugin disabled */
if (! is_plugin_enabled('bug_report')) {
    error_box(_("Plugin is disabled."),$color);
    echo "\n</body></html>\n";
    exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<body>
<pre>
<?php

include_once(SM_PATH . 'plugins/bug_report/system_specs.php');
global $body;
echo $body;

?>
</pre>
</body>
</html>