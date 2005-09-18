<?php

/**
 * footer.tpl
 *
 * Template for viewing the footer
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes */

/* retrieve the template vars */
extract($t);
$this->display('error_message.tpl');
?>

</body>
</html>