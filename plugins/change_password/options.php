<?php

/**
 * options.php - Change Password HTML page
 *
 * @copyright &copy; 2004-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage change_password
 */

/** @ignore */
define('SM_PATH','../../');

include_once (SM_PATH . 'include/validate.php');
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

    // SM14 code: use change_password gettext domain binding for 1.4.x
    if (! check_sm_version(1,5,0)) {
        bindtextdomain('change_password',SM_PATH . 'locale');
        textdomain('change_password');
    }

    /* perform basic checks */
    $Messages = cpw_check_input();

    /* if no errors, go ahead with the actual change */
    if(count($Messages) == 0) {
        $Messages = cpw_do_change();
    }

    // SM14 code: use change_password gettext domain binding for 1.4.x
    if (! check_sm_version(1,5,0)) {
        bindtextdomain('squirrelmail',SM_PATH . 'locale');
        textdomain('squirrelmail');
    }
}

displayPageHeader($color, 'None');

// SM14 code: use change_password gettext domain binding for 1.4.x
if (! check_sm_version(1,5,0)) {
    bindtextdomain('change_password',SM_PATH . 'locale');
    textdomain('change_password');
}

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
    <?php echo addForm($_SERVER['PHP_SELF'], 'post'); ?>
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