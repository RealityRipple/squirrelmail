<?php
/**
 * options.tpl
 *
 * Template for rendering the options page
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes */


/** extract variables */
extract($t);


$optpage_title = _("Options");
if (isset($optpage_name) && ($optpage_name != '')) {
    $optpage_title .= " - $optpage_name";
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
    if ($optmode == SMOPT_MODE_SUBMIT) {
        if (!isset($frame_top)) {
            $frame_top = '_top';
        }

        if (isset($optpage_save_error) && $optpage_save_error!=array()) {
            echo "<font color=\"$color[2]\"><b>" . _("Error(s) occurred while saving your options") . "</b></font><br />\n";
            echo "<ul>\n";
            foreach ($optpage_save_error as $error_message) {
                echo '<li><small>' . $error_message . "</small></li>\n";
            }
            echo "</ul>\n";
            echo '<b>' . _("Some of your preference changes were not applied.") . "</b><br />\n";
        } else {
            /* Display a message indicating a successful save. */
            echo '<b>' . _("Successfully Saved Options") . ": $optpage_name</b><br />\n";
        }

        /* If $max_refresh != SMOPT_REFRESH_NONE, provide a refresh link. */
        if ( !isset( $max_refresh ) ) {
        } else if ($max_refresh == SMOPT_REFRESH_FOLDERLIST) {
            echo '<a href="../src/left_main.php" target="left">' . _("Refresh Folder List") . '</a><br />';
        } else if ($max_refresh) {
            echo '<a href="../src/webmail.php?right_frame=options.php" target="' . $frame_top . '">' . _("Refresh Page") . '</a><br />';
        }
    }
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
    $first_optpage = false;
    echo html_tag( 'table', '', '', $color[4], 'width="100%" cellpadding="0" cellspacing="5" border="0"' ) . "\n" .
                html_tag( 'tr' ) . "\n" .
                    html_tag( 'td', '', 'left', '', 'valign="top"' ) .
                        html_tag( 'table', '', '', $color[4], 'width="100%" cellpadding="3" cellspacing="0" border="0"' ) . "\n" .
                            html_tag( 'tr' ) . "\n" .
                                html_tag( 'td', '', 'left' );
    foreach ($optpage_blocks as $next_optpage) {
        if ($first_optpage == false) {
            $first_optpage = $next_optpage;
        } else {
            print_optionpages_row($first_optpage, $next_optpage);
            $first_optpage = false;
        }
    }

    if ($first_optpage != false) {
        print_optionpages_row($first_optpage);
    }

    echo "</td></tr></table></td></tr></table>\n";

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
}

?>
</td></tr>
</table>
</td></tr>
</table>
<?php











/**
 * This function prints out an option page row.
 */
function print_optionpages_row($leftopt, $rightopt = false) {
    global $color;

    if ($rightopt) {
        $rightopt_name = html_tag( 'td', '<a href="' . $rightopt['url'] . '">' . $rightopt['name'] . '</a>', 'left', $color[9], 'valign="top" width="49%"' );
        $rightopt_desc = html_tag( 'td', $rightopt['desc'], 'left', $color[0], 'valign="top" width="49%"' );
    } else {
        $rightopt_name = html_tag( 'td', '&nbsp;', 'left', $color[4], 'valign="top" width="49%"' );
        $rightopt_desc = html_tag( 'td', '&nbsp;', 'left', $color[4], 'valign="top" width="49%"' );
    }

    echo
    html_tag( 'table', "\n" .
        html_tag( 'tr', "\n" .
            html_tag( 'td', "\n" .
                html_tag( 'table', "\n" .
                    html_tag( 'tr', "\n" .
                        html_tag( 'td',
                            '<a href="' . $leftopt['url'] . '">' . $leftopt['name'] . '</a>' ,
                        'left', $color[9], 'valign="top" width="49%"' ) .
                        html_tag( 'td',
                            '&nbsp;' ,
                        'left', $color[4], 'valign="top" width="2%"' ) . "\n" .
                        $rightopt_name
                    ) . "\n" .
                    html_tag( 'tr', "\n" .
                        html_tag( 'td',
                            $leftopt['desc'] ,
                        'left', $color[0], 'valign="top" width="49%"' ) .
                        html_tag( 'td',
                            '&nbsp;' ,
                        'left', $color[4], 'valign="top" width="2%"' ) . "\n" .
                        $rightopt_desc
                    ) ,
                '', '', 'width="100%" cellpadding="2" cellspacing="0" border="0"' ) ,
            'left', '', 'valign="top"' )
        ) ,
    '', $color[4], 'width="100%" cellpadding="0" cellspacing="5" border="0"' );
}


/**
 * $Log$
 * Revision 1.1  2006/07/09 22:22:31  vanmer
 * - initial revision of a template for options output
 *
 *
**/
?>