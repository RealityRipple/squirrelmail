<?php

/**
 * options.php - Change Password HTML page
 *
 * Copyright (c) 2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * $Id$
 * @package plugins
 * @subpackage change_password
 */

define('SM_PATH','../../');

require_once (SM_PATH . 'include/validate.php');
require_once (SM_PATH . 'functions/page_header.php');
require_once (SM_PATH . 'plugins/change_password/functions.php');
require_once (SM_PATH . 'plugins/change_password/config.php');
require_once (SM_PATH . 'functions/forms.php');

/* the form was submitted, go for it */
if(sqgetGlobalVar('cpw_go', $cpw_go, SQ_POST)) {

    /* perform basic checks */
    $Messages = cpw_check_input();
    
    /* if no errors, go ahead with the actual change */
    if(count($Messages) == 0) {
        $Messages = cpw_do_change();
    }
}

displayPageHeader($color, 'None');

do_hook('change_password_init');
?>

<br />
<table align="center" cellpadding="2" cellspacing="2" border="0">
<tr><td bgcolor="<?php echo $color[0] ?>">
   <center><b><?php echo _("Change Password") ?></b></center>
</td><?php

if (isset($Messages) && count($Messages) > 0) {
    echo "<tr><td>\n";
    foreach ($Messages as $line) {
        echo htmlspecialchars($line) . "<br />\n";
    }
    echo "</td></tr>\n";
}

?><tr><td>
    <?php echo addForm($_SERVER['PHP_SELF'], 'POST'); ?>
    <table>
      <tr>
        <th align="right"><?php echo _("Current Password:")?></th>
        <td><?php echo addPwField('cpw_curpass'); ?></td>
      </tr>
      <tr>
        <th align="right"><?php echo _("New Password:")?></th>
        <td><?php echo addPwField('cpw_newpass'); ?></td>
      </tr>
      <tr>
        <th align=right><?php echo _("Verify New Password:")?></th>
        <td><?php echo addPwField('cpw_verify'); ?></td>
      </tr>
      <tr>
        <td align="center" colspan="2">
	<?php echo addSubmit(_("Change Password"), 'cpw_go'); ?></td>
      </tr>
    </table>
    </form>
</td></tr>
</tr></table>
</body></html>
