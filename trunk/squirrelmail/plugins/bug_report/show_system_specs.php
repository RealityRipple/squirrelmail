<?php

/**
 * This shows system specification details.
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
include_once(SM_PATH . 'include/validate.php');


?>
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
