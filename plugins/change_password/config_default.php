<?php

/**
 * Change Password plugin configuration vars
 *
 * NOTE: probably you need to configure your specific backend too!
 *
 * @version $Id$
 * @package plugins
 * @subpackage change_password
 */

/** the password changing mechanism you're using */
$cpw_backend = 'template';

/** the minimum and maximum length that the plugin will enforce on new passwords */
$cpw_pass_min_length = 4;
$cpw_pass_max_length = 25;

/** whether we require the use of SSL/https to change a password */
$cpw_require_ssl = FALSE;

?>