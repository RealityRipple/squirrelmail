<?php
   /**
    **  left_main.php
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  This is the code for the left bar.  The left bar shows the folders
    **  available, and has cookie information.
    **
    **  $Id$
    **/

    require_once('../src/validate.php');
    require_once('../functions/array.php');
    require_once('../functions/imap.php');
    require_once('../functions/plugin.php');

    /* These constants are used for folder stuff. */
    define('SM_BOX_UNCOLLAPSED', 0);
    define('SM_BOX_COLLAPSED',   1);

    // open a connection on the imap port (143)
    $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10); // the 10 is to hide the output
   
    displayHtmlHeader();

    /* If requested and not yet complete, attempt to autocreate folders. */
    if ($auto_create_special && ! isset($auto_create_done)) {
          /* Autocreate the sent folder, if needed. */
   	  if (isset ($sent_folder) && $sent_folder != 'none') {
	  	 if (!sqimap_mailbox_exists ($imapConnection, $sent_folder)) {
		 	sqimap_mailbox_create ($imapConnection, $sent_folder, '');
		 } else if (! sqimap_mailbox_is_subscribed($imapConnection, $sent_folder)) {
		    sqimap_subscribe($imapConnection, $sent_folder);
		 }
	  }

          /* Autocreate the trash folder, if needed. */
   	  if (isset ($trash_folder) && $trash_folder != 'none') {
	  	 if (!sqimap_mailbox_exists ($imapConnection, $trash_folder)) {
		 	sqimap_mailbox_create ($imapConnection, $trash_folder, '');
		 } else if (! sqimap_mailbox_is_subscribed($imapConnection, $trash_folder)) {
		    sqimap_subscribe($imapConnection, $trash_folder);
		 }
          }

          /* Autocreate the drafts folder, if needed. */
          if (isset ($draft_folder) && $draft_folder != 'none') {
                 if (!sqimap_mailbox_exists ($imapConnection, $draft_folder)) {
                        sqimap_mailbox_create ($imapConnection, $draft_folder, '');
                 } else if (! sqimap_mailbox_is_subscribed($imapConnection, $draft_folder)) {
                    sqimap_subscribe($imapConnection, $draft_folder);
                 }
          }

          /* Let the world know that autocreation is complete! Hurrah! */
	  $auto_create_done = true;
	  session_register('auto_create_done');
   }

   function formatMailboxName($imapConnection, $box_array, $delimeter) {
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
            $line .= "\n<small>\n";
            $line .= " &nbsp; (<B><A HREF=\"empty_trash.php\" style=\"text-decoration:none\">"._("purge")."</A></B>)";
            $line .= "\n</small>\n";
         }
      }

      /* Return the final product. */
      return ($line);
   }

   /**********************************/
   /* END OF FUNCTION - BACK TO MAIN */
   /**********************************/

    if (isset($left_refresh) && ($left_refresh != 'None') && ($left_refresh != '')) {
        echo "<META HTTP-EQUIV=\"Expires\" CONTENT=\"Thu, 01 Dec 1994 16:00:00 GMT\">\n";
        echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n"; 
        echo "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"$left_refresh;URL=left_main.php\">\n";
    }
   
    echo "\n<BODY BGCOLOR=\"$color[3]\" TEXT=\"$color[6]\" LINK=\"$color[6]\" VLINK=\"$color[6]\" ALINK=\"$color[6]\">\n";

    do_hook("left_main_before");

    $boxes = sqimap_mailbox_list($imapConnection);

    echo '<CENTER><FONT SIZE=4><B>';
    echo _("Folders") . "</B><BR></FONT>\n\n";

    if ($hour_format == 1) {
      if ($date_format == 4)
         $hr = "G:i:s";
      else
         $hr = "G:i";
    } else {  
      if ($date_format == 4)
         $hr = "g:i:s a";
      else   
         $hr = "g:i a";
    }
    
    switch( $date_format ) {
    case 1:
      $clk = date("m/d/y ".$hr, time()); 
      break;
    case 2:
      $clk = date("d/m/y ".$hr, time()); 
      break;
    case 4:
    case 5:
      $clk = date($hr, time()); 
      break;
    default:   
      $clk = date("D, ".$hr, time()); 
    }

    echo "<center><small>$clk</small></center>";
    echo '<SMALL>(<A HREF="../src/left_main.php" TARGET="left">';
    echo _("refresh folder list");
    echo '</A>)</SMALL></CENTER><BR>';
    $delimeter = sqimap_get_delimiter($imapConnection);

    if (isset($collapse_folders) && $collapse_folders) {
        /* If directed, collapse or uncollapse a folder. */
        if (isset($fold)) {
            setPref($data_dir, $username, 'collapse_folder_' . $fold, SM_BOX_COLLAPSED);
        } else if (isset($unfold)) {
            setPref($data_dir, $username, 'collapse_folder_' . $unfold, $SM_BOX_UNCOLLAPSED);
        }
    }

    /* Prepare do do out collapsedness and visibility computation. */
    $curbox = 0;
    $boxcount = count($boxes);

    /* Compute the collapsedness and visibility of each box. */
    while ($curbox < $boxcount) {
        $boxes[$curbox]['visible'] = true;
        compute_folder_children($curbox, $boxcount);
    }
  
    for ($i = 0;$i < count($boxes); $i++) {
        if ($boxes[$i]['visible'] == true) {
            $mailbox = $boxes[$i]['formatted'];
            $mblevel = substr_count($boxes[$i]['unformatted'], $delimeter) + 1;

            /* Create the prefix for the folder name and link. */
            $prefix = str_repeat('  ',$mblevel);
            if (isset($collapse_folders) && $collapse_folders && $boxes[$i]['parent']) {
                $prefix = str_replace(' ','&nbsp;',substr($prefix,0,strlen($prefix)-2));
                $prefix .= create_collapse_link($i) . '&nbsp;';
            } else {
                $prefix = str_replace(' ','&nbsp;',$prefix);
            }
            $line = "<NOBR><TT>$prefix</TT>";

            /* Add the folder name and link. */
            if (in_array('noselect', $boxes[$i]['flags'])) {
                $line .= "<FONT COLOR=\"$color[10]\">";
                if (ereg("^( *)([^ ]*)", $mailbox, $regs)) {
                    $line .= str_replace(' ', '&nbsp;', $mailbox);
                }
                $line .= '</FONT>';
            } else {
                $line .= formatMailboxName($imapConnection, $boxes[$i], $delimeter);
            }

            /* Put the final touches on our folder line. */
            $line .= "</NOBR><BR>\n";

            /* Output the line for this folder. */
            echo $line;
        }
    }
    sqimap_logout($imapConnection);
    do_hook("left_main_after");

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
        global $delimeter;

        /* Extract the name of the parent of the current box. */
        $curparts = explode($delimeter, $curbox_name);
        $curname = array_pop($curparts);
        $actual_parname = implode($delimeter, $curparts);
        $actual_parname = substr($actual_parname,0,strlen($parbox_name));

        /* Compare the actual with the given parent name. */
        return ($parbox_name == $actual_parname);
    }

    /**
     * Recursive function that computes the collapsed status and parent
     * (or not parent) status of this box, and the visiblity and collapsed
     * status and parent (or not parent) status for all children boxes.
     */
    function compute_folder_children(&$parbox, $boxcount) {
        global $boxes, $data_dir, $username;
        $nextbox = $parbox + 1;

        /* Retreive the name for the parent box. */
        $parbox_name = $boxes[$parbox]['unformatted'];

        /* 'Initialize' this parent box to childless. */
        $boxes[$parbox]['parent'] = false;

        /* Compute the collapse status for this box. */
        $collapse = 0;
        $collapse = getPref($data_dir, $username, 'collapse_folder_' . $parbox_name);
        $collapse = ($collapse == '' ? SM_BOX_UNCOLLAPSED : $collapse);
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
    echo "</BODY></HTML>\n";
?>
