<?php
    /**
     * options.php
     *
     * Copyright (c) 1999-2001 The SquirrelMail Development Team
     * Licensed under the GNU GPL. For full terms see the file COPYING.
     *
     * Displays the options page. Pulls from proper user preference files
     * and config.php. Displays preferences as selected and other options.
     *
     *  $Id$
     */

    require_once('../src/validate.php');
    require_once('../functions/display_messages.php');
    require_once('../functions/imap.php');
    require_once('../functions/array.php');
   
    ereg ("(^.*/)[^/]+/[^/]+$", $PHP_SELF, $regs);
    $base_uri = $regs[1];   

    if (isset($language)) {
        setcookie('squirrelmail_language', $language, time()+2592000, $base_uri);
        $squirrelmail_language = $language;
    }   

    displayPageHeader($color, _("None"));

?>

<br>
<table bgcolor="<?php echo $color[0] ?>" width="95%" align="center" cellpadding="2" cellspacing="0" border="0">
<tr><td align="center">
    <b><?php echo _("Options") ?></b><br>

    <table width="100%" border="0" cellpadding="5" cellspacing="0">
    <tr><td bgcolor="<?php echo $color[4] ?>" align="center">

<?php
    if (isset($submit_personal)) {
        /* Save personal information. */
        if (isset($full_name)) {
           setPref($data_dir, $username, 'full_name', $full_name);
        }
        if (isset($email_address)) {
           setPref($data_dir, $username, 'email_address', $email_address);
        }
        if (isset($reply_to)) {
           setPref($data_dir, $username, 'reply_to', $reply_to);
        }
        setPref($data_dir, $username, 'reply_citation_style', $new_reply_citation_style);
        setPref($data_dir, $username, 'reply_citation_start', $new_reply_citation_start);
        setPref($data_dir, $username, 'reply_citation_end', $new_reply_citation_end);
        if (! isset($usesignature))
            $usesignature = 0;
        setPref($data_dir, $username, 'use_signature', $usesignature);  
        if (! isset($prefixsig)) {
            $prefixsig = 0;
        }
        setPref($data_dir, $username, 'prefix_sig', $prefixsig);
        if (isset($signature_edit)) {
            setSig($data_dir, $username, $signature_edit);
        }
      
        do_hook('options_personal_save');
      
        echo '<br><b>'._("Successfully saved personal information!").'</b><br>';
    } else if (isset($submit_display)) {
        // Do checking to make sure $chosentheme is in the array
        $in_ary = false;
        for ($i=0; $i < count($theme); $i++) {
            if ($theme[$i]['PATH'] == $chosentheme) {
                $in_ary = true;
                break;
            }
        }
        if (! $in_ary) {
            $chosentheme = '';
        }
   
        /* Save display preferences. */
        setPref($data_dir, $username, 'chosen_theme', $chosentheme);
        setPref($data_dir, $username, 'language', $language);
        setPref($data_dir, $username, 'use_javascript_addr_book', $javascript_abook);
        setPref($data_dir, $username, 'show_num', $shownum);
        setPref($data_dir, $username, 'wrap_at', $wrapat);
        setPref($data_dir, $username, 'editor_size', $editorsize);
        setPref($data_dir, $username, 'left_refresh', $leftrefresh);
        setPref($data_dir, $username, 'location_of_bar', $folder_new_location);
        setPref($data_dir, $username, 'location_of_buttons', $button_new_location);
        setPref($data_dir, $username, 'left_size', $leftsize);

        if (isset($altIndexColors) && $altIndexColors == 1) {
            setPref($data_dir, $username, 'alt_index_colors', 1);
        } else {
            setPref($data_dir, $username, 'alt_index_colors', 0);
        }

        setPref($data_dir, $username, 'show_html_default', ($showhtmldefault?1:0) );

        if (isset($includeselfreplyall)) {
            setPref($data_dir, $username, 'include_self_reply_all', 1);
        } else {
            removePref($data_dir, $username, 'include_self_reply_all');
        }

        if (isset($pageselectormax)) {
            setPref($data_dir, $username, 'page_selector_max', $pageselectormax);
        } else {
            removePref($data_dir, $username, 'page_selector_max', 0 );
        }

        if (isset($pageselector)) {
            removePref($data_dir, $username, 'page_selector');
        } else {
            setPref($data_dir, $username, 'page_selector', 1);
        }

        do_hook('options_display_save');

        echo '<br><b>'._("Successfully saved display preferences!").'</b><br>';
        echo '<a href="../src/webmail.php?right_frame=options.php" target=_top>' . _("Refresh Page") . '</a><br>';
    } else if (isset($submit_folder)) { 
        /* Save folder preferences. */
        if ($trash != 'none') {
            setPref($data_dir, $username, 'move_to_trash', true);
           setPref($data_dir, $username, 'trash_folder', $trash);
        } else {
            setPref($data_dir, $username, 'move_to_trash', '0');
            setPref($data_dir, $username, 'trash_folder', 'none');
        }
        if ($sent != 'none') {
            setPref($data_dir, $username, 'move_to_sent', true);
            setPref($data_dir, $username, 'sent_folder', $sent);
        } else {
            setPref($data_dir, $username, 'move_to_sent', '0');
            setPref($data_dir, $username, 'sent_folder', 'none');
        }
        if ($draft != 'none') {
            setPref($data_dir, $username, 'save_as_draft', true);
            setPref($data_dir, $username, 'draft_folder', $draft);
        } else {
            setPref($data_dir, $username, 'save_as_draft', '0');
            setPref($data_dir, $username, 'draft_folder', 'none');
        }
        if (isset($folderprefix)) {
            setPref($data_dir, $username, 'folder_prefix', $folderprefix);
        } else {
            setPref($data_dir, $username, 'folder_prefix', '');
        }
        setPref($data_dir, $username, 'unseen_notify', $unseennotify);
        setPref($data_dir, $username, 'unseen_type', $unseentype);
        if (isset($collapsefolders))
             setPref($data_dir, $username, 'collapse_folders', $collapsefolders);
        else
             removePref($data_dir, $username, 'collapse_folders');
        setPref($data_dir, $username, 'date_format', $dateformat);
        setPref($data_dir, $username, 'hour_format', $hourformat);
        do_hook('options_folders_save');
        echo '<br><b>'._("Successfully saved folder preferences!").'</b><br>';
        echo '<a href="../src/left_main.php" target=left>' . _("Refresh Folder List") . '</a><br>';
    } else {
        do_hook('options_save');
    }

    /****************************************/
    /* Now build our array of option pages. */
    /****************************************/

    /* Build a section for Personal Options. */
    $optionpages[] = array(
        'name' => _("Personal Information"),
        'url'  => 'options_personal.php',
        'desc' => _("This contains personal information about yourself such as your name, your email address, etc."),
        'js'   => false
    );

    /* Build a section for Display Options. */
    $optionpages[] = array(
        'name' => _("Display Preferences"),
        'url'  => 'options_display.php',
        'desc' => _("You can change the way that SquirrelMail looks and displays information to you, such as the colors, the language, and other settings."),
        'js'   => false
    );

    /* Build a section for Message Highlighting Options. */
    $optionpages[] = array(
        'name' =>_("Message Highlighting"),
        'url'  => 'options_highlight.php',
        'desc' =>_("Based upon given criteria, incoming messages can have different background colors in the message list.  This helps to easily distinguish who the messages are from, especially for mailing lists."),
        'js'   => false
    );

    /* Build a section for Folder Options. */
    $optionpages[] = array(
        'name' => _("Folder Preferences"),
        'url'  => 'options_folder.php',
        'desc' => _("These settings change the way your folders are displayed and manipulated."),
        'js'   => false
    );

    /* Build a section for Index Order Options. */
    $optionpages[] = array(
        'name' => _("Index Order"),
        'url'  => 'options_order.php',
        'desc' => _("The order of the message index can be rearanged and changed to contain the headers in any order you want."),
        'js'   => false
    );
    /* Build a section for plugins wanting to register an optionpage. */
    do_hook('options_register');

    /*****************************************************/
    /* Let's sort Javascript Option Pages to the bottom. */
    /*****************************************************/
    foreach ($optionpages as $optpage) {
        if ($optpage['js']) {
            $js_optionpages[] = $optpage;
        } else {
            $nojs_optionpages[] = $optpage;
        }
    }
    $optionpages = array_merge($nojs_optionpages, $js_optionpages);

    /********************************************/
    /* Now, print out each option page section. */
    /********************************************/
    $first_optpage = false;
    foreach ($optionpages as $next_optpage) {
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

    do_hook('options_link_and_description');

?>
    </td></tr>
    </table>

</td></tr>
</table>

</body></html>

<?php

    /*******************************************************************/
    /* Please be warned. The code below this point sucks. This is just */
    /* my first implementation to make the option rows work for both   */
    /* Javascript and non-Javascript option chunks.                    */
    /*                                                                 */
    /* Please, someone make these better for me. All three functions   */
    /* below REALLY do close to the same thing.                        */
    /*                                                                 */
    /* This code would be GREATLY improved by a templating system.     */
    /* Don't try to implement that now, however. That will come later. */
    /*******************************************************************/

    /*******************************************************************/
    /* Actually, now that I think about it, don't do anything with     */
    /* this code yet. There is ACTUALLY supposed to be a difference    */
    /* between the three functions that write the option rows. I just  */
    /* have not yet gotten to integrating that yet.                    */
    /*******************************************************************/

    /**
     * This function prints out an option page row. All it actually
     * does is call the three functions below.
     */
    function print_optionpages_row($leftopt, $rightopt = false) {
        if ($rightopt == false) {
            if ($leftopt['js']) {
                print_optionpages_row_fulljs($leftopt);
            } else {
                print_optionpages_row_nojs($leftopt);
            }
        } else {
            if ($leftopt['js']) {
                if ($rightopt['js']) {
                    print_optionpages_row_fulljs($leftopt, $rightopt);
                } else {
                    print_optionpages_row_partjs($leftopt, $rightopt);
                }
            } else {
                print_optionpages_row_nojs($leftopt, $rightopt);
            }
        }
    }

    /**
     * This function prints out an option page row: in which the left
     *   Left:  options for functionality that do not require javascript
     *   Right: options for functionality that do not require javascript
     */
    function print_optionpages_row_nojs($leftopt, $rightopt = false) {
        global $color;
?>
<table bgcolor="<?php echo $color[4] ?>" width="100%" cellpadding="0" cellspacing="5" border="0">
   <tr><td valign=top>
      <table width="100%" cellpadding="3" cellspacing="0" border="0">
         <tr>
            <td valign="top" bgcolor="<?php echo $color[9] ?>" width="50%">
               <a href="<?php echo $leftopt['url'] ?>"><?php echo $leftopt['name'] ?></a>
            </td>
            <td valign="top" bgcolor="<?php echo $color[4] ?>">&nbsp;</td>
<?php if ($rightopt != false) { ?>
            <td valign="top" bgcolor="<?php echo $color[9] ?>" width="50%">
               <a href="<?php echo $rightopt['url'] ?>"><?php echo $rightopt['name'] ?></a>
            </td>
<?php } else { ?>
            <td valign="top" bgcolor="<?php echo $color[4] ?>" width="50%">&nbsp;</td>
<?php } ?>
         </tr>
         <tr>
            <td valign="top" bgcolor="<?php echo $color[0] ?>">
               <?php echo $leftopt['desc'] ?>
            </td>
            <td valign="top" bgcolor="<?php echo $color[4] ?>">&nbsp;</td>
<?php if ($rightopt != false) { ?>
            <td valign="top" bgcolor="<?php echo $color[0] ?>">
               <?php echo $rightopt['desc'] ?>
            </td>
<?php } else { ?>
            <td valign="top" bgcolor="<?php echo $color[4] ?>">&nbsp;</td>
<?php } ?>
         </tr>
      </table>
   </td></tr>
</table>
<?php
    }

    /**
     * This function prints out an option page row: in which the left
     *   Left:  options for functionality that does not require javascript
     *   Right: options for functionality that are javascript only
     */
    function print_optionpages_row_partjs($leftopt, $rightopt = false) {
        global $color;
?>
<table bgcolor="<?php echo $color[4] ?>" width="100%" cellpadding="0" cellspacing="5" border="0">
   <tr><td valign=top>
      <table width="100%" cellpadding="3" cellspacing="0" border="0">
         <tr>
            <td valign="top" bgcolor="<?php echo $color[9] ?>" width="50%">
               <a href="<?php echo $leftopt['url'] ?>"><?php echo $leftopt['name'] ?></a>
            </td>
            <td valign="top" bgcolor="<?php echo $color[4] ?>">&nbsp;</td>
<?php if ($rightopt != false) { ?>
            <td valign="top" bgcolor="<?php echo $color[9] ?>" width="50%">
               <a href="<?php echo $rightopt['url'] ?>"><?php echo $rightopt['name'] ?></a>
            </td>
<?php } else { ?>
            <td valign="top" bgcolor="<?php echo $color[4] ?>" width="50%">&nbsp;</td>
<?php } ?>
         </tr>
         <tr>
            <td valign="top" bgcolor="<?php echo $color[0] ?>">
               <?php echo $leftopt['desc'] ?>
            </td>
            <td valign="top" bgcolor="<?php echo $color[4] ?>">&nbsp;</td>
<?php if ($rightopt != false) { ?>
            <td valign="top" bgcolor="<?php echo $color[0] ?>">
               <?php echo $rightopt['desc'] ?>
            </td>
<?php } else { ?>
            <td valign="top" bgcolor="<?php echo $color[4] ?>">&nbsp;</td>
<?php } ?>
         </tr>
      </table>
   </td></tr>
</table>
<?php
    }

    /**
     * This function prints out an option page row: in which the left
     *   Left:  options for functionality that are javascript only
     *   Right: options for functionality that are javascript only
     */
    function print_optionpages_row_fulljs($leftopt, $rightopt = false) {
        global $color;
?>
<table bgcolor="<?php echo $color[4] ?>" width="100%" cellpadding="0" cellspacing="5" border="0">
   <tr><td valign=top>
      <table width="100%" cellpadding="3" cellspacing="0" border="0">
         <tr>
            <td valign="top" bgcolor="<?php echo $color[9] ?>" width="50%">
               <a href="<?php echo $leftopt['url'] ?>"><?php echo $leftopt['name'] ?></a>
            </td>
            <td valign="top" bgcolor="<?php echo $color[4] ?>">&nbsp;</td>
<?php if ($rightopt != false) { ?>
            <td valign="top" bgcolor="<?php echo $color[9] ?>" width="50%">
               <a href="<?php echo $rightopt['url'] ?>"><?php echo $rightopt['name'] ?></a>
            </td>
<?php } else { ?>
            <td valign="top" bgcolor="<?php echo $color[4] ?>" width="50%">&nbsp;</td>
<?php } ?>
         </tr>
         <tr>
            <td valign="top" bgcolor="<?php echo $color[0] ?>">
               <?php echo $leftopt['desc'] ?>
            </td>
            <td valign="top" bgcolor="<?php echo $color[4] ?>">&nbsp;</td>
<?php if ($rightopt != false) { ?>
            <td valign="top" bgcolor="<?php echo $color[0] ?>">
               <?php echo $rightopt['desc'] ?>
            </td>
<?php } else { ?>
            <td valign="top" bgcolor="<?php echo $color[4] ?>">&nbsp;</td>
<?php } ?>
         </tr>
      </table>
   </td></tr>
</table>
<?php
    }

?>
