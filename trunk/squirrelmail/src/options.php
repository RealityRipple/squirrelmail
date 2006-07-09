<?php

/**
 * options.php
 *
 * Displays the options page. Pulls from proper user preference files
 * and config.php. Displays preferences as selected and other options.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage prefs
 */

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/* SquirrelMail required files. */

//include(SM_PATH . 'functions/imap_general.php');
require_once(SM_PATH . 'functions/options.php');
require_once(SM_PATH . 'functions/forms.php');

/*********************************/
/*** Build the resultant page. ***/
/*********************************/

define('SMOPT_MODE_DISPLAY', 'display');
define('SMOPT_MODE_SUBMIT', 'submit');
define('SMOPT_MODE_LINK', 'link');

define('SMOPT_PAGE_MAIN', 'main');
define('SMOPT_PAGE_PERSONAL', 'personal');
define('SMOPT_PAGE_DISPLAY', 'display');
define('SMOPT_PAGE_COMPOSE', 'compose');
define('SMOPT_PAGE_HIGHLIGHT', 'highlight');
define('SMOPT_PAGE_FOLDER', 'folder');
define('SMOPT_PAGE_ORDER', 'order');

function process_optionmode_submit($optpage, $optpage_data) {
    /* Initialize the maximum option refresh level. */
    $max_refresh = SMOPT_REFRESH_NONE;

    /* Save each option in each option group. */
    foreach ($optpage_data['options'] as $option_grp) {
        foreach ($option_grp['options'] as $option) {
            /* Remove Debug Mode Until Needed
            echo "name = '$option->name', "
               . "value = '$option->value', "
               . "new_value = '$option->new_value'\n";
            echo "<br />";
            */
            if ($option->changed()) {
                $option->save();
                $max_refresh = max($max_refresh, $option->refresh_level);
            }
        }
    }

    /* Return the max refresh level. */
    return ($max_refresh);
}

function process_optionmode_link($optpage) {
   /* There will be something here, later. */
}



/* ---------------------------- main ---------------------------- */

/* get the globals that we may need */
sqgetGlobalVar('key',       $key,           SQ_COOKIE);
sqgetGlobalVar('username',  $username,      SQ_SESSION);
sqgetGlobalVar('onetimepad',$onetimepad,    SQ_SESSION);
sqgetGlobalVar('delimiter', $delimiter,     SQ_SESSION);

sqgetGlobalVar('optpage',     $optpage);
sqgetGlobalVar('optmode',     $optmode,      SQ_FORM);
sqgetGlobalVar('optpage_data',$optpage_data, SQ_POST);
/* end of getting globals */

/* Make sure we have an Option Page set. Default to main. */
if ( !isset($optpage) || $optpage == '' ) {
    $optpage = SMOPT_PAGE_MAIN;
} else {
    $optpage = strip_tags( $optpage );
}

/* Make sure we have an Option Mode set. Default to display. */
if (!isset($optmode)) {
    $optmode = SMOPT_MODE_DISPLAY;
}

/*
 * First, set the load information for each option page.
 */

/* Initialize load information variables. */
$optpage_name = '';
$optpage_file = '';
$optpage_loader = '';

/* Set the load information for each page. */
switch ($optpage) {
    case SMOPT_PAGE_MAIN:
        break;
    case SMOPT_PAGE_PERSONAL:
        $optpage_name     = _("Personal Information");
        $optpage_file     = SM_PATH . 'include/options/personal.php';
        $optpage_loader   = 'load_optpage_data_personal';
        $optpage_loadhook = 'optpage_loadhook_personal';
        break;
    case SMOPT_PAGE_DISPLAY:
        $optpage_name   = _("Display Preferences");
        $optpage_file   = SM_PATH . 'include/options/display.php';
        $optpage_loader = 'load_optpage_data_display';
        $optpage_loadhook = 'optpage_loadhook_display';
        break;
    case SMOPT_PAGE_COMPOSE:
        $optpage_name   = _("Compose Preferences");
        $optpage_file   = SM_PATH . 'include/options/compose.php';
        $optpage_loader = 'load_optpage_data_compose';
        $optpage_loadhook = 'optpage_loadhook_compose';
        break;
    case SMOPT_PAGE_HIGHLIGHT:
        $optpage_name   = _("Message Highlighting");
        $optpage_file   = SM_PATH . 'include/options/highlight.php';
        $optpage_loader = 'load_optpage_data_highlight';
        $optpage_loadhook = 'optpage_loadhook_highlight';
        break;
    case SMOPT_PAGE_FOLDER:
        $optpage_name   = _("Folder Preferences");
        $optpage_file   = SM_PATH . 'include/options/folder.php';
        $optpage_loader = 'load_optpage_data_folder';
        $optpage_loadhook = 'optpage_loadhook_folder';
        break;
    case SMOPT_PAGE_ORDER:
        $optpage_name = _("Index Order");
        $optpage_file = SM_PATH . 'include/options/order.php';
        $optpage_loader = 'load_optpage_data_order';
        $optpage_loadhook = 'optpage_loadhook_order';
        break;
    default: do_hook('optpage_set_loadinfo');
}

/**********************************************************/
/*** Second, load the option information for this page. ***/
/**********************************************************/

if ( !@is_file( $optpage_file ) ) {
    $optpage = SMOPT_PAGE_MAIN;
} else if ($optpage != SMOPT_PAGE_MAIN ) {
    /* Include the file for this optionpage. */

    require_once($optpage_file);

    /* Assemble the data for this option page. */
    $optpage_data = array();
    $optpage_data = $optpage_loader();
    do_hook($optpage_loadhook);
    $optpage_data['options'] =
        create_option_groups($optpage_data['grps'], $optpage_data['vals']);
}

/***********************************************************/
/*** Next, process anything that needs to be processed. ***/
/***********************************************************/

$optpage_save_error=array();

if ( isset( $optpage_data ) ) {
    switch ($optmode) {
        case SMOPT_MODE_SUBMIT:
            $max_refresh = process_optionmode_submit($optpage, $optpage_data);
            break;
        case SMOPT_MODE_LINK:
            $max_refresh = process_optionmode_link($optpage, $optpage_data);
            break;
    }
}

/*******************************************************************/
/* DO OLD SAVING OF SUBMITTED OPTIONS. THIS WILL BE REMOVED LATER. */
/*******************************************************************/

/* If in submit mode, select a save hook name and run it. */
if ($optmode == SMOPT_MODE_SUBMIT) {
    /* Select a save hook name. */
    switch ($optpage) {
        case SMOPT_PAGE_PERSONAL:
            $save_hook_name = 'options_personal_save';
            break;
        case SMOPT_PAGE_DISPLAY:
            $save_hook_name = 'options_display_save';
            break;
        case SMOPT_PAGE_COMPOSE:
            $save_hook_name = 'options_compose_save';
            break;
        case SMOPT_PAGE_FOLDER:
            $save_hook_name = 'options_folder_save';
            break;
        default:
            $save_hook_name = 'options_save';
            break;
    }

    /* Run the options save hook. */
    do_hook($save_hook_name);
}

/***************************************************************/
/* Apply logic to decide what optpage we want to display next. */
/***************************************************************/

/* If this is the result of an option page being submitted, then */
/* show the main page. Otherwise, show whatever page was called. */

if ($optmode == SMOPT_MODE_SUBMIT) {
    $optpage = SMOPT_PAGE_MAIN;
}


if (isset($max_refresh)) $oTemplate->assign('max_refresh',$max_refresh);
$oTemplate->assign('color',$color);
$oTemplate->assign('optpage',$optpage);
$oTemplate->assign('optpage_name',$optpage_name);
$oTemplate->assign('optpage_data',$optpage_data);
$oTemplate->assign('optmode',$optmode);


$oTemplate->display('options.tpl');
$oTemplate->display('footer.tpl');
?>