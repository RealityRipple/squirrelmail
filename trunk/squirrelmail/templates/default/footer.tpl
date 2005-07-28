     <?php

/**
 * footer.tpl
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Template for viewing the footer
 *
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