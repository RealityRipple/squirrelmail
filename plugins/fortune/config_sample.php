<?php

/**
 * Sample Fortune plugin configuration file
 *
 * Configuration defaults to /usr/games/fortune with short quotes
 *
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage fortune
 */

/**
 * Command that is used to display fortune cookies
 * @global string $fortune_command
 * @since 1.5.2
 */
$fortune_command = '/usr/games/fortune -s';
