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

$optpage_title = _("Options");
if (isset($optpage_name) && ($optpage_name != '')) {
    $optpage_title .= " - $optpage_name";
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

/***************************************************************/
/* Finally, display whatever page we are supposed to show now. */
/***************************************************************/

displayPageHeader($color, 'None', (isset($optpage_data['xtra']) ? $optpage_data['xtra'] : ''));

echo html_tag( 'table', '', 'center', $color[0], 'width="95%" cellpadding="1" cellspacing="0" border="0"' ) . "\n" .
        html_tag( 'tr' ) . "\n" .
            html_tag( 'td', '', 'center' ) .
                "<b>$optpage_title</b><br />\n".
                html_tag( 'table', '', '', '', 'width="100%" cellpadding="5" cellspacing="0" border="0"' ) . "\n" .
                    html_tag( 'tr' ) . "\n" .
                        html_tag( 'td', '', 'center', $color[4] ) . "\n";

/*
 * The main option page has a different layout then the rest of the option
 * pages. Therefore, we create it here first, then the others below.
 */
if ($optpage == SMOPT_PAGE_MAIN) {
    /**********************************************************/
    /* First, display the results of a submission, if needed. */
    /**********************************************************/
    $notice = '';
    if ($optmode == SMOPT_MODE_SUBMIT) {
        if (!isset($frame_top)) {
            $frame_top = '_top';
        }

        if (isset($optpage_save_error) && $optpage_save_error!=array()) {
            $notice = "<font color=\"$color[2]\"><b>" . _("Error(s) occurred while saving your options") . "</b></font><br />\n"
                ."<ul>\n";
            foreach ($optpage_save_error as $error_message) {
                $notice.= '<li><small>' . $error_message . "</small></li>\n";
            }
            $notice.= "</ul>\n"
                . '<b>' . _("Some of your preference changes were not applied.") . "</b><br />\n";
        } else {
            /* Display a message indicating a successful save. */
            $notice = '<b>' . _("Successfully Saved Options") . ": $optpage_name</b><br />\n";
        }

        /* If $max_refresh != SMOPT_REFRESH_NONE, provide a refresh link. */
        if ( !isset( $max_refresh ) ) {
        } else if ($max_refresh == SMOPT_REFRESH_FOLDERLIST) {
            $notice .= '<a href="../src/left_main.php" target="left">' . _("Refresh Folder List") . '</a><br />';
        } else if ($max_refresh) {
            $notice .= '<a href="../src/webmail.php?right_frame=options.php" target="' . $frame_top . '">' . _("Refresh Page") . '</a><br />';
        }
    }
    $oTemplate->assign('notice',$notice);

    /******************************************/
    /* Build our array of Option Page Blocks. */
    /******************************************/
    $optpage_blocks = array();

    /* Build a section for Personal Options. */
    $optpage_blocks[] = array(
        'name' => _("Personal Information"),
        'url'  => 'options.php?optpage=' . SMOPT_PAGE_PERSONAL,
        'desc' => _("This contains personal information about yourself such as your name, your email address, etc."),
        'js'   => false
    );

    /* Build a section for Display Options. */
    $optpage_blocks[] = array(
        'name' => _("Display Preferences"),
        'url'  => 'options.php?optpage=' . SMOPT_PAGE_DISPLAY,
        'desc' => _("You can change the way that SquirrelMail looks and displays information to you, such as the colors, the language, and other settings."),
        'js'   => false
    );

    /* Build a section for Message Highlighting Options. */
    $optpage_blocks[] = array(
        'name' =>_("Message Highlighting"),
        'url'  => 'options_highlight.php',
        'desc' =>_("Based upon given criteria, incoming messages can have different background colors in the message list. This helps to easily distinguish who the messages are from, especially for mailing lists."),
        'js'   => false
    );

    /* Build a section for Folder Options. */
    $optpage_blocks[] = array(
        'name' => _("Folder Preferences"),
        'url'  => 'options.php?optpage=' . SMOPT_PAGE_FOLDER,
        'desc' => _("These settings change the way your folders are displayed and manipulated."),
        'js'   => false
    );

    /* Build a section for Index Order Options. */
    $optpage_blocks[] = array(
        'name' => _("Index Order"),
        'url'  => 'options_order.php',
        'desc' => _("The order of the message index can be rearranged and changed to contain the headers in any order you want."),
        'js'   => false
    );

    /* Build a section for Compose Options. */
    $optpage_blocks[] = array(
        'name' => _("Compose Preferences"),
        'url'  => 'options.php?optpage=' . SMOPT_PAGE_COMPOSE,
        'desc' => _("Control the behaviour and layout of writing new mail messages, replying to and forwarding messages."),
        'js'   => false
    );

    /* Build a section for plugins wanting to register an optionpage. */
    do_hook('optpage_register_block');

    /*****************************************************/
    /* Let's sort Javascript Option Pages to the bottom. */
    /*****************************************************/
    $js_optpage_blocks = array();
    $reg_optpage_blocks = array();
    foreach ($optpage_blocks as $cur_optpage) {
        if (!isset($cur_optpage['js']) || !$cur_optpage['js']) {
            $reg_optpage_blocks[] = $cur_optpage;
        } else if ($javascript_on == SMPREF_JS_ON) {
            $js_optpage_blocks[] = $cur_optpage;
        }
    }
    $optpage_blocks = array_merge($reg_optpage_blocks, $js_optpage_blocks);

    /********************************************/
    /* Now, print out each option page section. */
    /********************************************/

    $oTemplate->assign('color',$color);
    $oTemplate->assign('optpage_blocks',$optpage_blocks);
    $oTemplate->display('option_groups.tpl');

    do_hook('options_link_and_description');


/*************************************************************************/
/* If we are not looking at the main option page, display the page here. */
/*************************************************************************/
} else {
    echo addForm('options.php', 'post', 'f')
       . create_optpage_element($optpage)
       . create_optmode_element(SMOPT_MODE_SUBMIT)
       . html_tag( 'table', '', '', '', 'width="100%" cellpadding="2" cellspacing="0" border="0"' ) . "\n";

    /* Output the option groups for this page. */
    print_option_groups($optpage_data['options']);

    /* Set the inside_hook_name and submit_name. */
    switch ($optpage) {
        case SMOPT_PAGE_PERSONAL:
            $inside_hook_name = 'options_personal_inside';
            $bottom_hook_name = 'options_personal_bottom';
            $submit_name = 'submit_personal';
            break;
        case SMOPT_PAGE_DISPLAY:
            $inside_hook_name = 'options_display_inside';
            $bottom_hook_name = 'options_display_bottom';
            $submit_name = 'submit_display';
            break;
        case SMOPT_PAGE_COMPOSE:
            $inside_hook_name = 'options_compose_inside';
            $bottom_hook_name = 'options_compose_bottom';
            $submit_name = 'submit_compose';
            break;
        case SMOPT_PAGE_HIGHLIGHT:
            $inside_hook_name = 'options_highlight_inside';
            $bottom_hook_name = 'options_highlight_bottom';
            $submit_name = 'submit_highlight';
            break;
        case SMOPT_PAGE_FOLDER:
            $inside_hook_name = 'options_folder_inside';
            $bottom_hook_name = 'options_folder_bottom';
            $submit_name = 'submit_folder';
            break;
        case SMOPT_PAGE_ORDER:
            $inside_hook_name = 'options_order_inside';
            $bottom_hook_name = 'options_order_bottom';
            $submit_name = 'submit_order';
            break;
        default:
            $inside_hook_name = '';
            $bottom_hook_name = '';
            $submit_name = 'submit';
    }

    /* If it is not empty, trigger the inside hook. */
    if ($inside_hook_name != '') {
        do_hook($inside_hook_name);
    }

    /* Spit out a submit button. */
    OptionSubmit($submit_name);
    echo '</table></form>';

    /* If it is not empty, trigger the bottom hook. */
    if ($bottom_hook_name != '') {
        do_hook($bottom_hook_name);
    }
    if (isset($max_refresh)) $oTemplate->assign('max_refresh',$max_refresh);
    $oTemplate->assign('color',$color);
    $oTemplate->assign('optpage',$optpage);
    $oTemplate->assign('optpage_name',$optpage_name);
    $oTemplate->assign('optpage_data',$optpage_data);
    $oTemplate->assign('optmode',$optmode);
    $oTemplate->display('options.tpl');
}

$oTemplate->display('footer.tpl');
