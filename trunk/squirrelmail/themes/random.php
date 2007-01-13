<?php

/**
 * Name:    Random Theme Every Login
 * Date:    December 24, 2001
 * Comment: Guess what this does!
 *
 * @author Tyler Akins
 * @copyright &copy; 2000-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage themes
 */

/** Prevent direct script loading */
if ((isset($_SERVER['SCRIPT_FILENAME']) && $_SERVER['SCRIPT_FILENAME'] == __FILE__) ||
    (isset($HTTP_SERVER_SERVER['SCRIPT_FILENAME']) && $HTTP_SERVER_SERVER['SCRIPT_FILENAME'] == __FILE__) ) {
    die();
}

/** load required functions */
include_once(SM_PATH . 'functions/global.php');
include_once(SM_PATH . 'functions/strings.php');

/** Initialize the random number generator */
sq_mt_randomize();

global $theme;

if (!sqsession_is_registered('random_theme_good_theme')) {
    $good_themes = array();
    foreach ($theme as $data) {
        if (substr($data['PATH'], -18) != '/themes/random.php') {
            $good_themes[] = $data['PATH'];
        }
    }
    if (count($good_themes) == 0) {
        $good_themes[] = '../themes/default.php';
    }
    $which = mt_rand(0, count($good_themes));
    $random_theme_good_theme = $good_themes[$which];
    // remove current sm_path from theme name
    $path=preg_quote(SM_PATH,'/');
    $random_theme_good_theme=preg_replace("/^$path/",'',$random_theme_good_theme);
    // store it in session
    sqsession_register($random_theme_good_theme, 'random_theme_good_theme');
} else {
    // get random theme stored in session
    sqgetGlobalVar('random_theme_good_theme',$random_theme_good_theme);
}

@include_once (SM_PATH . $random_theme_good_theme);
