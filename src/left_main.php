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

   include('../src/validate.php');
   include("../functions/array.php");
   include("../functions/imap.php");
   include("../functions/plugin.php");

   // open a connection on the imap port (143)
   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10); // the 10 is to hide the output
   
   displayHtmlHeader();

   if ($auto_create_special && ! isset($auto_create_done)) {
   	  if (isset ($sent_folder) && $sent_folder != "none") {
	  	 if (!sqimap_mailbox_exists ($imapConnection, $sent_folder)) {
		 	sqimap_mailbox_create ($imapConnection, $sent_folder, "");
		 } else if (! sqimap_mailbox_is_subscribed($imapConnection, $sent_folder)) {
		    sqimap_subscribe($imapConnection, $sent_folder);
		 }
	  }
   	  if (isset ($trash_folder) && $trash_folder != "none") {
	  	 if (!sqimap_mailbox_exists ($imapConnection, $trash_folder)) {
		 	sqimap_mailbox_create ($imapConnection, $trash_folder, "");
		 } else if (! sqimap_mailbox_is_subscribed($imapConnection, $trash_folder)) {
		    sqimap_subscribe($imapConnection, $trash_folder);
		 }
	  }
	  $auto_create_done = true;
	  session_register('auto_create_done');
   }

   function formatMailboxName($imapConnection, $box_array, $delimeter) {
      global $folder_prefix, $trash_folder, $sent_folder;
      global $color, $move_to_sent, $move_to_trash;
      global $unseen_notify, $unseen_type, $collapse_folders;

      $real_box = $box_array['unformatted'];
      $mailbox = $box_array['formatted'];
      $mailboxURL = urlencode($real_box);
      
      $unseen = 0;

      if (($unseen_notify == 2 && $real_box == "INBOX") ||
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
      if ((strtolower($real_box) == "inbox") ||
          (($real_box == $trash_folder) && ($move_to_trash)) ||
          (($real_box == $sent_folder) && ($move_to_sent)))
         $special_color = true;
         
      $spaces = '';
      $line = "<NOBR>";
      if (ereg("^( *)([^ ]*)$", $mailbox, $regs)) {
          $spaces = $regs[1];
          $mailbox = $regs[2];
      }
      
      if ($unseen > 0)
          $line .= "<B>";
      $line .= str_replace(' ', '&nbsp;', $spaces);
      
      if ($collapse_folders) {
         if (isset($box_array['parent']))
            $line .= FoldLink($box_array['unformatted'], $box_array['parent']);
         else
            $line .= '<tt>&nbsp;</tt>&nbsp;';
      }
          
      $line .= "<a href=\"right_main.php?sort=0&startMessage=1&mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\">";
      if ($special_color == true)
         $line .= "<FONT COLOR=\"$color[11]\">";
      $line .= str_replace(' ', '&nbsp;', $mailbox);
      if ($special_color == true)
         $line .= "</font>";
      $line .= "</a>";

      if ($unseen > 0)
         $line .= "</B>";

      if (isset($unseen_found) && $unseen_found) {
         $line .= "&nbsp;<small>$unseen_string</small>";
      }

      if (($move_to_trash == true) && ($real_box == $trash_folder)) {
         if (! isset($numMessages))
            $numMessages = sqimap_get_num_messages($imapConnection, $real_box);

         if ($numMessages > 0)
         {
            $urlMailbox = urlencode($real_box);
            $line .= "\n<small>\n";
            $line .= " &nbsp; (<B><A HREF=\"empty_trash.php\" style=\"text-decoration:none\">"._("purge")."</A></B>)";
            $line .= "\n</small>\n";
         }
      }
      $line .= "</NOBR>";
      return $line;
   }

   if (isset($left_refresh) && ($left_refresh != "None") && ($left_refresh != "")) {
      echo "<META HTTP-EQUIV=\"Expires\" CONTENT=\"Thu, 01 Dec 1994 16:00:00 GMT\">\n";
      echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n"; 
      echo "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"$left_refresh;URL=left_main.php\">\n";
   }
   
   echo "\n<BODY BGCOLOR=\"$color[3]\" TEXT=\"$color[6]\" LINK=\"$color[6]\" VLINK=\"$color[6]\" ALINK=\"$color[6]\">\n";

   do_hook("left_main_before");

   $boxes = sqimap_mailbox_list($imapConnection);

   echo "<CENTER><FONT SIZE=4><B>";
   echo _("Folders") . "</B><BR></FONT>\n\n";

   echo "<small>(<A HREF=\"../src/left_main.php\" TARGET=\"left\">";
   echo _("refresh folder list");
   echo "</A>)</small></CENTER><BR>";
   $delimeter = sqimap_get_delimiter($imapConnection);

   if (isset($collapse_folders) && $collapse_folders) {
      if (isset($fold))
         setPref($data_dir, $username, 'collapse_folder_' . $fold, 1);
      if (isset($unfold))
         setPref($data_dir, $username, 'collapse_folder_' . $unfold, 0);
      $IAmAParent = array();
      for ($i = 0; $i < count($boxes); $i ++) {
          $parts = explode($delimeter, $boxes[$i]['unformatted']);
          $box_name = array_pop($parts);
          $box_parent = implode($delimeter, $parts);
          $hidden = 0;
          if (isset($box_parent)) {
              $hidden = getPref($data_dir, $username, 
                  'collapse_folder_' . $box_parent);
              $IAmAParent[$box_parent] = $hidden;
          }
          $boxes[$i]['folded'] = $hidden;
      }
   }

   for ($i = 0;$i < count($boxes); $i++) {
      if (! isset($boxes[$i]['folded']) || ! $boxes[$i]['folded'])
      {
         $line = "";
         $mailbox = $boxes[$i]["formatted"];

         if (isset($collapse_folders) && $collapse_folders && isset($IAmAParent[$boxes[$i]['unformatted']])) {
            $boxes[$i]['parent'] = $IAmAParent[$boxes[$i]['unformatted']];
         }

         if (in_array('noselect', $boxes[$i]['flags'])) {
            $line .= "<FONT COLOR=\"$color[10]\">";
            if (ereg("^( *)([^ ]*)", $mailbox, $regs)) {
                $line .= str_replace(' ', '&nbsp;', $mailbox);
                if (isset($boxes[$i]['parent']))
                    $line .= FoldLink($boxes[$i]['unformatted'], $boxes[$i]['parent']);
                elseif ($collapse_folders)
                    $line .= '<tt>&nbsp;</tt>&nbsp;';
            }
            $line .= '</FONT>';
         } else {
            $line .= formatMailboxName($imapConnection, $boxes[$i], $delimeter);
         }
         echo "$line<BR>\n";
      }
   }
   sqimap_logout($imapConnection);
   do_hook("left_main_after");

   function FoldLink($mailbox, $folded) {
       $mailbox = urlencode($mailbox);
       echo '<tt><a target="left" style="text-decoration:none" ';
       echo 'href="left_main.php?';
       if ($folded)
           echo "unfold=$mailbox\">+";
       else
           echo "fold=$mailbox\">-";
       echo '</a></tt>&nbsp;';
   }
   
?>
</BODY></HTML>
