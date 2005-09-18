<?php

/**
 * Default Fortune plugin configuration
 *
 * Configuration defaults to /usr/games/fortune with short quotes
 *
 * @copyright &copy; 2004-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage fortune
 */

/**
 * program that displays quotes
 * @global string $fortune_location
 */
$fortune_location = '/usr/games/fortune';

/**
 * options that have to be passed to program
 * @global string $fortune_options
 * @since 1.5.1
 */
$fortune_options = '-s';
?>