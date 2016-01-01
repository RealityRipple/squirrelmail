<?php

/**
 * options.php - Change Password HTML page
 *
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage change_password
 */

/**
 * Include the SquirrelMail initialization file.
 */
require('../../include/init.php');

include_once (SM_PATH . 'plugins/change_password/functions.php');
include_once (SM_PATH . 'functions/forms.php');

/** load default config */
if (file_exists(SM_PATH . 'plugins/change_password/config_default.php')) {
    include_once (SM_PATH . 'plugins/change_password/config_default.php');
} else {
    // somebody decided to remove default config
    $cpw_backend = 'template';
    $cpw_pass_min_length = 4;
    $cpw_pass_max_length = 25;
    $cpw_require_ssl = FALSE;
}

/**
 * prevent possible corruption of configuration overrides in
 * register_globals=on and preloaded php scripts.
 */
$cpw_ldap=array();
$cpw_merak=array();
$cpw_mysql=array();
$cpw_poppassd=array();
$cpw_vmailmgrd=array();

/** load site config */
if (file_exists(SM_PATH . 'config/change_password_config.php')) {
    include_once (SM_PATH . 'config/change_password_config.php');
} elseif (file_exists(SM_PATH . 'plugins/change_password/config.php')) {
    include_once (SM_PATH . 'plugins/change_password/config.php');
}

// must load backend libraries here in order to get working change_password_init hook.
if (file_exists(SM_PATH . 'plugins/change_password/backend/'.$cpw_backend.'.php')) {
   include_once(SM_PATH . 'plugins/change_password/backend/'.$cpw_backend.'.php');
}

/* the form was submitted, go for it */
if(sqgetGlobalVar('cpw_go', $cpw_go, SQ_POST)) {

    // security check
    sqgetGlobalVar('smtoken', $submitted_token, SQ_POST, '');
    sm_validate_security_token($submitted_token, -1, TRUE);

    /* perform basic checks */
    $Messages = cpw_check_input();

    /* if no errors, go ahead with the actual change */
    if(count($Messages) == 0) {
        $Messages = cpw_do_change();
    }
}

displayPageHeader($color);

do_hook('change_password_init', $null);
?>

<br />
<table align="center" cellpadding="2" cellspacing="2" border="0">
<tr><td bgcolor="<?php echo $color[0] ?>">
   <div style="text-align: center;"><b><?php echo _("Change Password") ?></b></div>
</td><?php

if (isset($Messages) && count($Messages) > 0) {
    echo "<tr><td>\n";
    foreach ($Messages as $line) {
        echo sm_encode_html_special_chars($line) . "<br />\n";
    }
    echo "</td></tr>\n";
}

?><tr><td>
    <?php echo addForm($_SERVER['PHP_SELF'], 'post'); ?>
    <input type="hidden" name="smtoken" value="<?php echo sm_generate_security_token() ?>" />
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
</table>
</body></html>
