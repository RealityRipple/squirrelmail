<?php

   /**
    *   imap_mailbox.php
    *
    *   Copyright (c) 1999-2001 The Squirrelmail Development Team
    *   Licensed under the GNU GPL. For full terms see the file COPYING.
    *
    *   This impliments all functions that manipulate mailboxes
    *
    *   $Id$
    */

   /******************************************************************************
    **  Expunges a mailbox 
    ******************************************************************************/
   function sqimap_mailbox_expunge ($imap_stream, $mailbox,$handle_errors = true) {
      fputs ($imap_stream, sqimap_session_id() . " EXPUNGE\r\n");
      $read = sqimap_read_data($imap_stream, sqimap_session_id(), $handle_errors, $response, $message);
   }


   /******************************************************************************
    **  Checks whether or not the specified mailbox exists 
    ******************************************************************************/
   function sqimap_mailbox_exists ($imap_stream, $mailbox) {
      if (! isset($mailbox))
          return false;
      fputs ($imap_stream, sqimap_session_id() . " LIST \"\" \"$mailbox\"\r\n");
      $mbx = sqimap_read_data($imap_stream, sqimap_session_id(), true, $response, $message);
      return isset($mbx[0]);
   }

   /******************************************************************************
    **  Selects a mailbox
    ******************************************************************************/
   function sqimap_mailbox_select ($imap_stream, $mailbox, $hide=true, $recent=false) {
      global $auto_expunge;

      if( $mailbox == 'None' )
          return;

      fputs ($imap_stream, sqimap_session_id() . " SELECT \"$mailbox\"\r\n");
             $read = sqimap_read_data($imap_stream, sqimap_session_id(), true, $response, $message);
      if ($recent) {
         for ($i=0; $i<count($read); $i++) {
            if (strpos(strtolower($read[$i]), 'recent')) {
               $r = explode(' ', $read[$i]);
            }
         }
         return $r[1];
      }
      if ($auto_expunge) {
         fputs ($imap_stream, sqimap_session_id() . " EXPUNGE\r\n");
         $tmp = sqimap_read_data($imap_stream, sqimap_session_id(), false, $a, $b);
      }   
   }

   

   /******************************************************************************
    **  Creates a folder 
    ******************************************************************************/
   function sqimap_mailbox_create ($imap_stream, $mailbox, $type) {
      global $delimiter;
      if (strtolower($type) == 'noselect') {
         $mailbox = $mailbox.$delimiter;
      }
      fputs ($imap_stream, sqimap_session_id() . " CREATE \"$mailbox\"\r\n");
      $read_ary = sqimap_read_data($imap_stream, sqimap_session_id(), true, $response, $message);

      sqimap_subscribe ($imap_stream, $mailbox);
   }



   /******************************************************************************
    **  Subscribes to an existing folder 
    ******************************************************************************/
   function sqimap_subscribe ($imap_stream, $mailbox) {
      fputs ($imap_stream, sqimap_session_id() . " SUBSCRIBE \"$mailbox\"\r\n");
      $read_ary = sqimap_read_data($imap_stream, sqimap_session_id(), true, $response, $message);
   }




   /******************************************************************************
    **  Unsubscribes to an existing folder 
    ******************************************************************************/
   function sqimap_unsubscribe ($imap_stream, $mailbox) {
                global $imap_server_type;

      fputs ($imap_stream, sqimap_session_id() . " UNSUBSCRIBE \"$mailbox\"\r\n");
      $read_ary = sqimap_read_data($imap_stream, sqimap_session_id(), true, $response, $message);
   }



   
   /******************************************************************************
    **  This function simply deletes the given folder
    ******************************************************************************/
   function sqimap_mailbox_delete ($imap_stream, $mailbox) {
      fputs ($imap_stream, sqimap_session_id() . " DELETE \"$mailbox\"\r\n");
      $read_ary = sqimap_read_data($imap_stream, sqimap_session_id(), true, $response, $message);
      sqimap_unsubscribe ($imap_stream, $mailbox);
   }
   
   /***********************************************************************
    ** Determines if the user is subscribed to the folder or not
    **********************************************************************/
   function sqimap_mailbox_is_subscribed($imap_stream, $folder) {
       $boxes = sqimap_mailbox_list ($imap_stream);
       foreach ($boxes as $ref) {
          if ($ref['unformatted'] == $folder)
	     return true;
       }
       return false;
   }
      


   /******************************************************************************
    **  Formats a mailbox into 4 parts for the $boxes array
    **
    **  The four parts are:
    **
    **    raw            - Raw LIST/LSUB response from the IMAP server
    **    formatted      - nicely formatted folder name
    **    unformatted    - unformatted, but with delimiter at end removed
    **    unformatted-dm - folder name as it appears in raw response
    **    unformatted-disp - unformatted without $folder_prefix
    **
    ******************************************************************************/
   function sqimap_mailbox_parse ($line, $line_lsub) {
      global $folder_prefix, $delimiter;
     
      // Process each folder line
      for ($g=0; $g < count($line); $g++) {

         // Store the raw IMAP reply
         if (isset($line[$g]))
            $boxes[$g]["raw"] = $line[$g];
         else
            $boxes[$g]["raw"] = "";


         // Count number of delimiters ($delimiter) in folder name
         $mailbox = trim($line_lsub[$g]);
         $dm_count = countCharInString($mailbox, $delimiter);
         if (substr($mailbox, -1) == $delimiter)
            $dm_count--;  // If name ends in delimiter - decrement count by one

         // Format folder name, but only if it's a INBOX.* or have
         // a parent.
         $boxesbyname[$mailbox] = $g;
         $parentfolder = readMailboxParent($mailbox, $delimiter);
         if((strtolower(substr($mailbox, 0, 5)) == "inbox") ||
            (substr($mailbox, 0, strlen($folder_prefix)) == $folder_prefix) ||
            (isset($boxesbyname[$parentfolder]) && (strlen($parentfolder) > 0) ) ) {
            $indent = $dm_count - (countCharInString($folder_prefix, $delimiter));
            if ($indent > 0)
                $boxes[$g]["formatted"]  = str_repeat("&nbsp;&nbsp;", $indent);
            else
                $boxes[$g]["formatted"] = '';
            $boxes[$g]["formatted"] .= readShortMailboxName($mailbox, $delimiter);
         } else {
            $boxes[$g]["formatted"]  = $mailbox;
         }
            
         $boxes[$g]['unformatted-dm'] = $mailbox;
         if (substr($mailbox, -1) == $delimiter)
            $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
         $boxes[$g]['unformatted'] = $mailbox;
         if (substr($mailbox,0,strlen($folder_prefix))==$folder_prefix)
            $mailbox = substr($mailbox, strlen($folder_prefix));
         $boxes[$g]['unformatted-disp'] = $mailbox;
         $boxes[$g]['id'] = $g;

         $boxes[$g]['flags'] = array();
         if (isset($line[$g])) {
            ereg("\(([^)]*)\)",$line[$g],$regs);
            $flags = trim(strtolower(str_replace('\\', '',$regs[1])));
            if ($flags)
               $boxes[$g]['flags'] = explode(' ', $flags);
	 }
      }

      return $boxes;
   }
   
   /* Apparently you must call a user function with usort instead
    * of calling a built-in directly.  Stupid.
    * Patch from dave_michmerhuizen@yahoo.com
    * Allows case insensitivity when sorting folders
    */
   function user_strcasecmp($a, $b)
   {
       return strcasecmp($a, $b);
   }


   /******************************************************************************
    **  Returns sorted mailbox lists in several different ways.
    **  See comment on sqimap_mailbox_parse() for info about the returned array.
    ******************************************************************************/
   function sqimap_mailbox_list ($imap_stream) {
      global $data_dir, $username, $list_special_folders_first;
      global $folder_prefix, $trash_folder, $sent_folder, $draft_folder;
      global $move_to_trash, $move_to_sent, $save_as_draft;
      global $delimiter;

      $inbox_in_list = false;
      $inbox_subscribed = false;

      require_once('../src/load_prefs.php');
      require_once('../functions/array.php');

      /** LSUB array **/
      fputs ($imap_stream, sqimap_session_id() . " LSUB \"$folder_prefix\" \"*\"\r\n");
      $lsub_ary = sqimap_read_data ($imap_stream, sqimap_session_id(), true, $response, $message);
      
      // Section about removing the last element was removed
      // We don't return "* OK" anymore from sqimap_read_data

      $sorted_lsub_ary = array();
      for ($i=0;$i < count($lsub_ary); $i++) {
         // Workaround for EIMS
         // Doesn't work if the mailbox name is multiple lines
         if (isset($lsub_ary[$i + 1]) &&
	     ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$", 
	          $lsub_ary[$i], $regs)) {
	    $i ++;
	    $lsub_ary[$i] = $regs[1] . '"' . addslashes(trim($lsub_ary[$i])) .
	       '"' . $regs[2];
	 }
	 $temp_mailbox_name = find_mailbox_name($lsub_ary[$i]);
         $sorted_lsub_ary[] = $temp_mailbox_name;
         if (strtoupper($temp_mailbox_name) == 'INBOX')
            $inbox_subscribed = true;
      }
      $new_ary = array();
      for ($i=0; $i < count($sorted_lsub_ary); $i++) {
         if (!in_array($sorted_lsub_ary[$i], $new_ary)) {
            $new_ary[] = $sorted_lsub_ary[$i];
         }
      }
      $sorted_lsub_ary = $new_ary;
      if (isset($sorted_lsub_ary)) {
         usort($sorted_lsub_ary, 'user_strcasecmp');
         //sort($sorted_lsub_ary);
      }   

      /** LIST array **/
      $sorted_list_ary = array();
      for ($i=0; $i < count($sorted_lsub_ary); $i++) {
         if (substr($sorted_lsub_ary[$i], -1) == $delimiter)
            $mbx = substr($sorted_lsub_ary[$i], 0, strlen($sorted_lsub_ary[$i])-1);
         else
            $mbx = $sorted_lsub_ary[$i];

         fputs ($imap_stream, sqimap_session_id() . " LIST \"\" \"$mbx\"\r\n");
         $read = sqimap_read_data ($imap_stream, sqimap_session_id(), true, $response, $message);
	 // Another workaround for EIMS
         if (isset($read[1]) && 
	     ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$", 
	          $read[0], $regs)) {
	    $read[0] = $regs[1] . '"' . addslashes(trim($read[1])) .
	       '"' . $regs[2];
	 }
         if (isset($sorted_list_ary[$i]))
            $sorted_list_ary[$i] = "";
         if (isset($read[0]))
            $sorted_list_ary[$i] = $read[0];
         else
            $sorted_list_ary[$i] = "";
         if (isset($sorted_list_ary[$i]) && 
	     strtoupper(find_mailbox_name($sorted_list_ary[$i])) == "INBOX")
            $inbox_in_list = true;
      }
                
      /**
       * Just in case they're not subscribed to their inbox,
       * we'll get it for them anyway
       */
      if ($inbox_subscribed == false || $inbox_in_list == false) {
         fputs ($imap_stream, sqimap_session_id() . " LIST \"\" \"INBOX\"\r\n");
         $inbox_ary = sqimap_read_data ($imap_stream, sqimap_session_id(), true, $response, $message);
	 // Another workaround for EIMS
         if (isset($inbox_ary[1]) &&
	     ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$", 
	          $inbox_ary[0], $regs)) {
	    $inbox_ary[0] = $regs[1] . '"' . addslashes(trim($inbox_ary[1])) .
	       '"' . $regs[2];
	 }

         $sorted_list_ary[] = $inbox_ary[0];
         $sorted_lsub_ary[] = find_mailbox_name($inbox_ary[0]);
      }

      $boxes = sqimap_mailbox_parse ($sorted_list_ary, $sorted_lsub_ary);

      /** Now, lets sort for special folders **/
      $boxesnew = Array();

      /* Find INBOX */
      for ($i = 0; $i < count($boxes); $i++) {
         if (strtolower($boxes[$i]["unformatted"]) == "inbox") {
            $boxesnew[] = $boxes[$i];
            $used[$i] = true;
            $i = count($boxes);
         }
      }

      /* List special folders and their subfolders, if requested. */
      if ($list_special_folders_first == true) {
         /* First list the trash folder. */
         for ($i = 0 ; $i < count($boxes) ; $i++) {
            if ($move_to_trash &&
                   eregi('^' . quotemeta($trash_folder) . '(' .
                     quotemeta($delimiter) . '.*)?$', $boxes[$i]['unformatted'])) {
               $boxesnew[] = $boxes[$i];
               $used[$i] = true;
            }
         }

         /* Then list the sent folder. */
         for ($i = 0 ; $i < count($boxes) ; $i++) {
            if ($move_to_sent &&
                  eregi('^' . quotemeta($sent_folder) . '(' .
                    quotemeta($delimiter) . '.*)?$', $boxes[$i]['unformatted'])) {
               $boxesnew[] = $boxes[$i];
               $used[$i] = true;
            }
         }

         /* Lastly, list the list the draft folder. */
         for ($i = 0 ; $i < count($boxes) ; $i++) {
            if ($save_as_draft &&
                  eregi('^' . quotemeta($draft_folder) . '(' .
                    quotemeta($delimiter) . '.*)?$', $boxes[$i]['unformatted'])) {
               $boxesnew[] = $boxes[$i];
               $used[$i] = true;
            }
         }

         /* Put INBOX.* folders ahead of the rest. */
         for ($i = 0; $i < count($boxes); $i++) {
            if (eregi('^inbox\\.', $boxes[$i]["unformatted"]) &&
                (!isset($used[$i]) || $used[$i] == false)) {
               $boxesnew[] = $boxes[$i];
               $used[$i] = true;
            }
         }
      }

      // Rest of the folders
      for ($i = 0; $i < count($boxes); $i++) {
         if ((strtolower($boxes[$i]["unformatted"]) != "inbox") &&
             (!isset($used[$i]) || $used[$i] == false))  {
            $boxesnew[] = $boxes[$i];
            $used[$i] = true;
         }
      }

      return $boxesnew;
   }
   
   /******************************************************************************
    **  Returns a list of all folders, subscribed or not
    ******************************************************************************/
   function sqimap_mailbox_list_all ($imap_stream) {
      global $list_special_folders_first, $folder_prefix;
      global $delimiter;

      if (!function_exists ("ary_sort"))
         include_once('../functions/array.php');

      $ssid = sqimap_session_id();
      $lsid = strlen( $ssid ); 
      fputs ($imap_stream, $ssid . " LIST \"$folder_prefix\" *\r\n");
      $read_ary = sqimap_read_data ($imap_stream, $ssid, true, $response, $message);
      $g = 0;
      $phase = "inbox";

      for ($i = 0; $i < count($read_ary); $i++) {
         // Another workaround for EIMS
			if (isset($read_ary[$i + 1]) &&
			  ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$", 
			  $read_ary[$i], $regs)) {
			 $i ++;
			 $read_ary[$i] = $regs[1] . '"' . 
			    addslashes(trim($read_ary[$i])) .
			    '"' . $regs[2];
			}
         if (substr ($read_ary[$i], 0, $lsid) != $ssid ) {

            // Store the raw IMAP reply
            $boxes[$g]["raw"] = $read_ary[$i];

            // Count number of delimiters ($delimiter) in folder name
            $mailbox = find_mailbox_name($read_ary[$i]);
            $dm_count = countCharInString($mailbox, $delimiter);
            if (substr($mailbox, -1) == $delimiter)
               $dm_count--;  // If name ends in delimiter - decrement count by one
            
            // Format folder name, but only if it's a INBOX.* or have
            // a parent.
            $boxesbyname[$mailbox] = $g;
            $parentfolder = readMailboxParent($mailbox, $delimiter);
            if((eregi('^inbox'.quotemeta($delimiter), $mailbox)) || 
               (ereg('^'.$folder_prefix, $mailbox)) ||
               ( isset($boxesbyname[$parentfolder]) && (strlen($parentfolder) > 0) ) ) {
               if ($dm_count)
                   $boxes[$g]["formatted"]  = str_repeat("&nbsp;&nbsp;", $dm_count);
               else
                   $boxes[$g]["formatted"] = '';
               $boxes[$g]["formatted"] .= readShortMailboxName($mailbox, $delimiter);
            } else {
               $boxes[$g]["formatted"]  = $mailbox;
            }
               
            $boxes[$g]["unformatted-dm"] = $mailbox;
            if (substr($mailbox, -1) == $delimiter)
               $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
            $boxes[$g]["unformatted"] = $mailbox;
            $boxes[$g]["unformatted-disp"] = ereg_replace('^' . $folder_prefix, '', $mailbox);
            $boxes[$g]["id"] = $g;

            /** Now lets get the flags for this mailbox **/
            fputs ($imap_stream, sqimap_session_id() . " LIST \"\" \"$mailbox\"\r\n"); 
            $read_mlbx = sqimap_read_data ($imap_stream, sqimap_session_id(), true, $response, $message);
            
				// Another workaround for EIMS
				if (isset($read_mlbx[1]) &&
				  ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$", 
				  $read_mlbx[0], $regs)) {
				 $read_mlbx[0] = $regs[1] . '"' . 
				    addslashes(trim($read_mlbx[1])) .
				    '"' . $regs[2];
				}

            $flags = substr($read_mlbx[0], strpos($read_mlbx[0], "(")+1);
            $flags = substr($flags, 0, strpos($flags, ")"));
            $flags = str_replace('\\', '', $flags);
            $flags = trim(strtolower($flags));
            if ($flags) {
               $boxes[$g]['flags'] = explode(" ", $flags);
            }
            else
            {
               $boxes[$g]['flags'] = array();
            }
         }
         $g++;
      }
      if(is_array($boxes)) {
         $boxes = ary_sort ($boxes, "unformatted", 1);
      }

      return $boxes;
   }
   
?>
