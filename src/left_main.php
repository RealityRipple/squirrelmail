<?php

/**
 * left_main.php
 *
 * Copyright (c) 1999-2001 The Squirrelmail Development Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This is the code for the left bar. The left bar shows the folders
 * available, and has cookie information.
 *
 * $Id$
 /

/*****************************************************************/
/*** THIS FILE NEEDS TO HAVE ITS FORMATTING FIXED!!!           ***/
/*** PLEASE DO SO AND REMOVE THIS COMMENT SECTION.             ***/
/***    + Base level indent should begin at left margin, as    ***/
/***      the require_once below looks.                        ***/
/***    + All identation should consist of four space blocks   ***/
/***    + Tab characters are evil.                             ***/
/***    + all comments should use "slash-star ... star-slash"  ***/
/***      style -- no pound characters, no slash-slash style   ***/
/***    + FLOW CONTROL STATEMENTS (if, while, etc) SHOULD      ***/
/***      ALWAYS USE { AND } CHARACTERS!!!                     ***/
/***    + Please use ' instead of ", when possible. Note "     ***/
/***      should always be used in _( ) function calls.        ***/
/*** Thank you for your help making the SM code more readable. ***/
/*****************************************************************/

require_once('../src/validate.php');
require_once('../functions/array.php');
require_once('../functions/imap.php');
require_once('../functions/plugin.php');
require_once('../functions/page_header.php');

    /* These constants are used for folder stuff. */
    define('SM_BOX_UNCOLLAPSED', 0);
    define('SM_BOX_COLLAPSED',   1);

/* --------------------- FUNCTIONS ------------------------- */

    function formatMailboxName($imapConnection, $box_array) {
        global $folder_prefix, $trash_folder, $sent_folder;
        global $color, $move_to_sent, $move_to_trash;
        global $unseen_notify, $unseen_type, $collapse_folders;
        global $draft_folder, $save_as_draft;
        global $use_special_folder_color;

        $real_box = $box_array['unformatted'];
        $mailbox = str_replace('&nbsp;','',$box_array['formatted']);
        $mailboxURL = urlencode($real_box);

        /* Strip down the mailbox name. */
        if (ereg("^( *)([^ ]*)$", $mailbox, $regs)) {
            $mailbox = $regs[2];
        }

        $unseen = 0;

        if (($unseen_notify == 2 && $real_box == 'INBOX') ||
            $unseen_notify == 3) {
            $unseen = sqimap_unseen_messages($imapConnection, $real_box);
            if ($unseen_type == 1 && $unseen > 0) {
                $unseen_string = "($unseen)";
                $unseen_found = true;
            } else if ($unseen_type == 2) {
                $numMessages = sqimap_get_num_messages($imapConnection, $real_box);
                $unseen_string = "<font color=\"$color[11]\">($unseen/$numMessages)</font>";
                $unseen_found = true;
            }
        }

        $special_color = false;
        if ($use_special_folder_color) {
            if ((strtolower($real_box) == 'inbox')
                    || (($real_box == $trash_folder) && ($move_to_trash))
                    || (($real_box == $sent_folder) && ($move_to_sent))
                    || (($real_box == $draft_folder) && ($save_as_draft))) {
                $special_color = true;
            }
        }

        /* Start off with a blank line. */
        $line = '';

        /* If there are unseen message, bold the line. */
        if ($unseen > 0) { $line .= '<B>'; }

        /* Crate the link for this folder. */
        $line .= "<A HREF=\"right_main.php?sort=0&startMessage=1&mailbox=$mailboxURL\" TARGET=\"right\" STYLE=\"text-decoration:none\">";
        if ($special_color == true)
            $line .= "<FONT COLOR=\"$color[11]\">";
        $line .= str_replace(' ','&nbsp;',$mailbox);
        if ($special_color == true)
            $line .= "</FONT>";
        $line .= '</A>';

        /* If there are unseen message, close bolding. */
        if ($unseen > 0) { $line .= "</B>"; }

        /* Print unseen information. */
        if (isset($unseen_found) && $unseen_found) {
            $line .= "&nbsp;<SMALL>$unseen_string</SMALL>";
        }

        if (($move_to_trash == true) && ($real_box == $trash_folder)) {
            if (! isset($numMessages)) {
                $numMessages = sqimap_get_num_messages($imapConnection, $real_box);
            }

            if ($numMessages > 0) {
                $urlMailbox = urlencode($real_box);
                $line .= "\n<small>\n" .
                        "&nbsp;&nbsp;(<A HREF=\"empty_trash.php\" style=\"text-decoration:none\">"._("empty")."</A>)" .
                        "\n</small>\n";
            }
        }

        /* Return the final product. */
        return ($line);
    }

    /**
     * Recursive function that computes the collapsed status and parent
     * (or not parent) status of this box, and the visiblity and collapsed
     * status and parent (or not parent) status for all children boxes.
     */
    function compute_folder_children(&$parbox, $boxcount) {
        global $boxes, $data_dir, $username, $collapse_folders;
        $nextbox = $parbox + 1;

        /* Retreive the name for the parent box. */
        $parbox_name = $boxes[$parbox]['unformatted'];

        /* 'Initialize' this parent box to childless. */
        $boxes[$parbox]['parent'] = false;

        /* Compute the collapse status for this box. */
        if( isset($collapse_folders) && $collapse_folders ) {
            $collapse = getPref($data_dir, $username, 'collapse_folder_' . $parbox_name);
            $collapse = ($collapse == '' ? SM_BOX_UNCOLLAPSED : $collapse);
        } else {
            $collapse = SM_BOX_UNCOLLAPSED;
        }
        $boxes[$parbox]['collapse'] = $collapse;

        /* Otherwise, get the name of the next box. */
        if (isset($boxes[$nextbox]['unformatted']))
            $nextbox_name = $boxes[$nextbox]['unformatted'];
        else
            $nextbox_name = '';

        /* Compute any children boxes for this box. */
        while (($nextbox < $boxcount) &&
               (is_parent_box($boxes[$nextbox]['unformatted'], $parbox_name))) {

            /* Note that this 'parent' box has at least one child. */
            $boxes[$parbox]['parent'] = true;

            /* Compute the visiblity of this box. */
            if ($boxes[$parbox]['visible'] &&
                ($boxes[$parbox]['collapse'] != SM_BOX_COLLAPSED)) {
                $boxes[$nextbox]['visible'] = true;
            } else {
                $boxes[$nextbox]['visible'] = false;
            }

            /* Compute the visibility of any child boxes. */
            compute_folder_children($nextbox, $boxcount);
        }

        /* Set the parent box to the current next box. */
        $parbox = $nextbox;
    }

    /**
     * Create the link for a parent folder that will allow that
     * parent folder to either be collapsed or expaned, as is
     * currently appropriate.
     */
    function create_collapse_link($boxnum) {
        global $boxes;
        $mailbox = urlencode($boxes[$boxnum]['unformatted']);

        /* Create the link for this collapse link. */
        $link = '<a target="left" style="text-decoration:none" ';
        $link .= 'href="left_main.php?';
        if ($boxes[$boxnum]['collapse'] == SM_BOX_COLLAPSED) {
            $link .= "unfold=$mailbox\">+";
        } else {
            $link .= "fold=$mailbox\">-";
        }
        $link .= '</a>';

        /* Return the finished product. */
        return ($link);
    }

    /**
     * This simple function checks if a box is another box's parent.
     */
    function is_parent_box($curbox_name, $parbox_name) {
        global $delimiter;

        /* Extract the name of the parent of the current box. */
        $curparts = explode($delimiter, $curbox_name);
        $curname = array_pop($curparts);
        $actual_parname = implode($delimiter, $curparts);
        $actual_parname = substr($actual_parname,0,strlen($parbox_name));

        /* Compare the actual with the given parent name. */
        return ($parbox_name == $actual_parname);
    }


    /* -------------------- MAIN ------------------------ */

    global $delimiter, $default_folder_prefix;

    // open a connection on the imap port (143)
    $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10); // the 10 is to hide the output


    if (isset($left_refresh) && ($left_refresh != 'none') && ($left_refresh != '')) {
        $xtra =  "\n<META HTTP-EQUIV=\"Expires\" CONTENT=\"Thu, 01 Dec 1994 16:00:00 GMT\">\n" .
                 "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n".
                 "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"$left_refresh;URL=left_main.php\">\n";
    } else {
        $xtra = '';
    }

    displayHtmlHeader( 'SquirrelMail', $xtra );

    /* If requested and not yet complete, attempt to autocreate folders. */
    if ($auto_create_special && !isset($auto_create_done)) {
        $autocreate = array( $sent_folder,
                             $trash_folder,
                             $draft_folder );
        foreach( $autocreate as $folder ) {
            if ($folder != '' && $folder != 'none') {
                if ( !sqimap_mailbox_exists($imapConnection, $folder)) {
                    sqimap_mailbox_create($imapConnection, $default_folder_prefix.$folder, '');
                } elseif ( !sqimap_mailbox_is_subscribed($imapConnection, $folder)) {
                    sqimap_subscribe($imapConnection, $folder);
                }
            }
        }

        /* Let the world know that autocreation is complete! Hurrah! */
        $auto_create_done = true;
        session_register('auto_create_done');
    }

    echo "\n<BODY BGCOLOR=\"$color[3]\" TEXT=\"$color[6]\" LINK=\"$color[6]\" VLINK=\"$color[6]\" ALINK=\"$color[6]\">\n";

    do_hook('left_main_before');

    $boxes = sqimap_mailbox_list($imapConnection);

    echo '<CENTER><FONT SIZE=4><B>'. _("Folders") . "</B><BR></FONT>\n\n";

    if ($date_format != 6) {
        /* First, display the clock. */
        if ($hour_format == 1) {
            $hr = 'G:i';
            if ($date_format == 4) {
                $hr .= ':s';
            }
        } else {
            if ($date_format == 4) {
                $hr = 'g:i:s a';
            } else {
                $hr = 'g:i a';
            }
        }

        switch( $date_format ) {
        case 1:
            $clk = date('m/d/y '.$hr, time());
            break;
        case 2:
            $clk = date('d/m/y '.$hr, time());
            break;
        case 4:
        case 5:
            $clk = date($hr, time());
            break;
        default:
            $clk = date('D, '.$hr, time());
        }
        $clk = str_replace(' ','&nbsp;',$clk);

        echo '<CENTER><SMALL>' . str_replace(' ','&nbsp;',_("Last Refresh")) .
             ": $clk</SMALL></CENTER>";
    }

    /* Next, display the refresh button. */
    echo '<SMALL>(<A HREF="../src/left_main.php" TARGET="left">'.
         _("refresh folder list") . '</A>)</SMALL></CENTER><BR>';

    /* Lastly, display the folder list. */
    if ( $collapse_folders ) {
        /* If directed, collapse or uncollapse a folder. */
        if (isset($fold)) {
            setPref($data_dir, $username, 'collapse_folder_' . $fold, SM_BOX_COLLAPSED);
        } else if (isset($unfold)) {
            setPref($data_dir, $username, 'collapse_folder_' . $unfold, SM_BOX_UNCOLLAPSED);
        }
    }

    /* Prepare do do out collapsedness and visibility computation. */
    $curbox = 0;
    $boxcount = count($boxes);

    /* Compute the collapsedness and visibility of each box. */
    while ($curbox < $boxcount) {
        $boxes[$curbox]['visible'] = TRUE;
        compute_folder_children($curbox, $boxcount);
    }

    for ($i = 0;$i < count($boxes); $i++) {
        if ( $boxes[$i]['visible'] ) {
            $mailbox = $boxes[$i]['formatted'];
            $mblevel = substr_count($boxes[$i]['unformatted'], $delimiter) + 1;

            /* Create the prefix for the folder name and link. */
            $prefix = str_repeat('  ',$mblevel);
            if (isset($collapse_folders) && $collapse_folders && $boxes[$i]['parent']) {
                $prefix = str_replace(' ','&nbsp;',substr($prefix,0,strlen($prefix)-2)).
                          create_collapse_link($i) . '&nbsp;';
            } else {
                $prefix = str_replace(' ','&nbsp;',$prefix);
            }
            $line = "<NOBR><TT>$prefix</TT>";

            /* Add the folder name and link. */
            if (in_array('noselect', $boxes[$i]['flags'])) {
                $line .= "<FONT COLOR=\"$color[10]\">";
                if (ereg("^( *)([^ ]*)", $mailbox, $regs)) {
                    $mailbox = str_replace('&nbsp;','',$mailbox);
                    $line .= str_replace(' ', '&nbsp;', $mailbox);
                }
                $line .= '</FONT>';
            } else {
                $line .= formatMailboxName($imapConnection, $boxes[$i]);
            }

            /* Put the final touches on our folder line. */
            $line .= "</NOBR><BR>\n";

            /* Output the line for this folder. */
            echo $line;
        }
    }

    sqimap_logout($imapConnection);
    do_hook('left_main_after');

    echo "</BODY></HTML>\n";

?>
