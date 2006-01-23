<?php
/**
 * This script shows system specification details.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage bug_report
 */

/**
 * @ignore
 */
define('SM_PATH','../../');
/** load system functions */
include_once(SM_PATH . 'include/validate.php');
/** load error_box() function */
include_once(SM_PATH . 'functions/display_messages.php');
/** load plugin functions */
include_once(SM_PATH.'plugins/bug_report/functions.php');

/** is bug_report plugin disabled or called by wrong user */
if (! is_plugin_enabled('bug_report') || ! bug_report_check_user()) {
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