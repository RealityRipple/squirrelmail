<?php
   /*
    *  Message and Spam Filter Plugin 
    *  By Luke Ehresman <luke@squirrelmail.org>
    *     Tyler Akins
    *     Brent Bice
    *  (c) 2000 (GNU GPL - see ../../COPYING)
    *
    *  This plugin filters your inbox into different folders based upon given
    *  criteria.  It is most useful for people who are subscibed to mailing lists
    *  to help organize their messages.  The argument stands that filtering is
    *  not the place of the client, which is why this has been made a plugin for
    *  SquirrelMail.  You may be better off using products such as Sieve or
    *  Procmail to do your filtering so it happens even when SquirrelMail isn't
    *  running.
    *
    *  If you need help with this, or see improvements that can be made, please
    *  email me directly at the address above.  I definately welcome suggestions
    *  and comments.  This plugin, as is the case with all SquirrelMail plugins,
    *  is not directly supported by the developers.  Please come to me off the
    *  mailing list if you have trouble with it.
    *
    *  Also view plugins/README.plugins for more information.
    *
    */
    
   function start_filters() {
      global $username, $key, $imapServerAddress, $imapPort, $imap,
         $imap_general, $filters, $imap_stream, $imapConnection, 
	 $UseSeparateImapConnection, $AllowSpamFilters;

      // Detect if we have already connected to IMAP or not.
      // Also check if we are forced to use a separate IMAP connection
      if ((!isset($imap_stream) && !isset($imapConnection)) ||
          $UseSeparateImapConnection) {
         $stream = sqimap_login($username, $key, $imapServerAddress, 
	    $imapPort, 10);
         $previously_connected = false;
      } elseif (isset($imapConnection)) {
         $stream = $imapConnection;
         $previously_connected = true;
      } else {
         $previously_connected = true;
	 $stream = $imap_stream;
      }

      if (sqimap_get_num_messages($stream, "INBOX") > 0) {
         // Filter spam from inbox before we sort them into folders
         if ($AllowSpamFilters)
            spam_filters($stream);
	 
         // Sort into folders
         user_filters($stream);
      }
      
      if (!$previously_connected)
         sqimap_logout($stream);
   }


   function user_filters($imap_stream) {
      $filters = load_filters();
      if (! $filters) return;
      
      sqimap_mailbox_select($imap_stream, 'INBOX');
      
      // For every rule
      for ($i=0; $i < count($filters); $i++) {
         // If it is the "combo" rule
         if ($filters[$i]["where"] == "To or Cc") {
            /*
             *  If it's "TO OR CC", we have to do two searches, one for TO
             *  and the other for CC.
             */
	    filter_search_and_delete($imap_stream, 'TO',
	       $filters[$i]['what'], $filters[$i]['folder']);
	    filter_search_and_delete($imap_stream, 'CC',
	       $filters[$i]['what'], $filters[$i]['folder']);
         } else {
            /*
             *  If it's a normal TO, CC, SUBJECT, or FROM, then handle it 
	     *  normally.
             */
	    filter_search_and_delete($imap_stream, $filters[$i]['where'],
	       $filters[$i]['what'], $filters[$i]['folder']);
         }
      }
      // Clean out the mailbox whether or not auto_expunge is on
      // That way it looks like it was redirected properly
      sqimap_mailbox_expunge($imap_stream, 'INBOX');
   }
   
   function filter_search_and_delete($imap, $where, $what, $where_to) {
      fputs ($imap, 'a001 SEARCH ALL ' . $where . ' "' . addslashes($what) . 
         "\"\r\n");
      $read = sqimap_read_data ($imap, 'a001', true, $response, $message);
      
      // This may have problems with EIMS due to it being goofy
      
      for ($r=0; $r < count($read) && 
                 substr($read[$r], 0, 8) != '* SEARCH'; $r++) {}
      if ($response == 'OK') {
         $ids = explode(' ', $read[$r]);
	 if (sqimap_mailbox_exists($imap, $where_to)) {
            for ($j=2; $j < count($ids); $j++) {
   	       $id = trim($ids[$j]);
               sqimap_messages_copy ($imap, $id, $id, $where_to);
               sqimap_messages_flag ($imap, $id, $id, 'Deleted');
            }
         }
      }
   }

   // These are the spam filters
   function spam_filters($imap_stream) {
      global $data_dir, $username;
      global $SpamFilters_YourHop;
      global $SpamFilters_DNScache;

      $filters_spam_scan = getPref($data_dir, $username, "filters_spam_scan");
      $filters_spam_folder = getPref($data_dir, $username, "filters_spam_folder");
      $filters = load_spam_filters();
      
      $run = 0;
      
      foreach ($filters as $Key=> $Value) {
         if ($Value['enabled'])
            $run ++;
      }
      
      // short-circuit
      if ($run == 0) {
          return;
      }
      
      sqimap_mailbox_select($imap_stream, 'INBOX');
      
      // Ask for a big list of all "Received" headers in the inbox with 
      // flags for each message.  Kinda big.
      fputs($imap_stream, 'A3999 FETCH 1:* (FLAGS BODY.PEEK[HEADER.FIELDS ' .
       "(RECEIVED)])\r\n");
      
      $read = sqimap_read_data ($imap_stream, 'A3999', true, $response, $message);
      
      if ($response != 'OK')
          return;

      $i = 0;
      while ($i < count($read)) {
          // EIMS will give funky results
          $Chunks = explode(' ', $read[$i]);
          if ($Chunks[0] != '*') {
              $i ++;
              continue;
          }
          $MsgNum = $Chunks[1];

          $IPs = array();
          $i ++;
          $IsSpam = 0;
          $Scan = 1;
          
	  // Check for normal IMAP servers
          if ($filters_spam_scan == 'new') {
              if (is_int(strpos($Chunks[4], '\Seen'))) {
                  $Scan = 0;
              }
          }
	  
	  // Look through all of the Received headers for IP addresses
	  // Stop when I get ")" on a line
	  // Stop if I get "*" on a line (don't advance)
          // and above all, stop if $i is bigger than the total # of lines
          while (($i < count($read)) &&
                 ($read[$i][0] != ')' && $read[$i][0] != '*' &&
	          $read[$i][0] != "\n") && (! $IsSpam))
	  {
              // Check to see if this line is the right "Received from" line
              // to check
              if (is_int(strpos($read[$i], $SpamFilters_YourHop))) {

   	         // short-circuit and skip work if we don't scan this one
                 if ($Scan) {
                     $read[$i] = ereg_replace('[^0-9\.]', ' ', $read[$i]);
                     $elements = explode(' ', $read[$i]);
                     foreach ($elements as $value) {
   		      if ($value != '' &&
                          ereg('[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}',
   			     $value, $regs)) {
		          $Chunks = explode('.', $value);
                          if ("$SpamFilters_DNScache[$value]" == "") {
                             $SpamFilters_DNScache[$value] =
                                filters_spam_check_site($Chunks[0], $Chunks[1],
		                      $Chunks[2], $Chunks[3], $filters);
                         }
                         if ($SpamFilters_DNScache[$value]) {
		            $IsSpam ++;
                            break;  // no sense in checking more IPs
                         }
                      }
                     }
                 }
              }
              $i ++;
          }
	  
	  // Lookie!  It's spam!  Yum!
          if ($IsSpam) {
              if (sqimap_mailbox_exists ($imap_stream, $filters_spam_folder)) {
                  sqimap_messages_copy ($imap_stream, $MsgNum, $MsgNum, 
		     $filters_spam_folder);
                  sqimap_messages_flag ($imap_stream, $MsgNum, $MsgNum, 
		     'Deleted');
              }
          }
      }
      
      sqimap_mailbox_expunge($imap_stream, 'INBOX');
   }   

  
   // Does the loop through each enabled filter for the specified IP address.
   // IP format:  $a.$b.$c.$d
   function filters_spam_check_site($a, $b, $c, $d, &$filters) {
      foreach ($filters as $key => $value) {
          if ($filters[$key]['enabled']) {
              if ($filters[$key]['dns']) {
                  if (checkdnsrr("$d.$c.$b.$a." . $filters[$key]['dns'],
                     'ANY')) {
                      return 1;
                  }
              }
          }
      }
      return 0;
   }
   
   function load_filters() {
      global $data_dir, $username;
      $filters = array();
      for ($i=0; $fltr = getPref($data_dir, $username, 'filter' . $i); $i++) {
         $ary = explode(',', $fltr);
         $filters[$i]['where'] = $ary[0];
         $filters[$i]['what'] = $ary[1];
         $filters[$i]['folder'] = $ary[2];
      }
      return $filters;
   }

   function load_spam_filters() {
      global $data_dir, $username;
      
      $filters['MAPS RBL']['prefname'] = 'filters_spam_maps_rbl';
      $filters['MAPS RBL']['name'] = 'MAPS Realtime Blackhole List';
      $filters['MAPS RBL']['link'] = 'http://www.mail-abuse.org/rbl/';
      $filters['MAPS RBL']['dns'] = 'blackholes.mail-abuse.org';
      $filters['MAPS RBL']['comment'] = 
_("COMMERCIAL - This list contains servers that are verified spam senders. It is a pretty reliable list to scan spam from.");
      
      $filters['MAPS RSS']['prefname'] = 'filters_spam_maps_rss';
      $filters['MAPS RSS']['name'] = 'MAPS Relay Spam Stopper';
      $filters['MAPS RSS']['link'] = 'http://www.mail-abuse.org/rss/';
      $filters['MAPS RSS']['dns'] = 'relays.mail-abuse.org';
      $filters['MAPS RSS']['comment'] =
_("COMMERCIAL - Servers that are configured (or misconfigured) to allow spam to be relayed through their system will be banned with this.  Another good one to use.");

      $filters['MAPS DUL']['prefname'] = 'filters_spam_maps_dul';
      $filters['MAPS DUL']['name'] = 'MAPS Dial-Up List';
      $filters['MAPS DUL']['link'] = 'http://www.mail-abuse.org/dul/';
      $filters['MAPS DUL']['dns'] = 'dialups.mail-abuse.org';
      $filters['MAPS DUL']['comment'] =
_("COMMERCIAL - Dial-up users are often filtered out since they should use their ISP\'s mail servers to send mail.  Spammers typically get a dial-up account and send spam directly from there.");

      $filters['MAPS RBLplus']['prefname'] = 'filters_spam_maps_rblplus';
      $filters['MAPS RBLplus']['name'] = 'MAPS RBL+ List';
      $filters['MAPS RBLplus']['link'] = 'http://www.mail-abuse.org/';
      $filters['MAPS RBLplus']['dns'] = 'rbl-plus.mail-abuse.org';
      $filters['MAPS RBLplus']['comment'] =
_("COMMERCIAL - RBL+ is a combination of RSS, DUL, and RBL.");

      $filters['Osirusoft']['prefname'] = 'filters_spam_maps_osirusoft';
      $filters['Osirusoft']['name'] = 'Osirusoft List';
      $filters['Osirusoft']['link'] = 'http://relays.osirusoft.com/';
      $filters['Osirusoft']['dns'] = 'relays.osirusoft.com';
      $filters['Osirusoft']['comment'] =
_("FREE - Osirusoft - Very thorough, but also rejects replies from many ISP\'s abuse@domain.name email messages for some reason.");

      $filters['ORDB']['prefname'] = 'filters_spam_ordb';
      $filters['ORDB']['name'] = 'Open Relay Database List';
      $filters['ORDB']['link'] = 'http://www.ordb.org/';
      $filters['ORDB']['dns'] = 'relays.ordb.org';
      $filters['ORDB']['comment'] =
_("FREE - ORDB was born when ORBS went off the air. It seems to have fewer false positives than ORBS did though.");
      
      $filters['ORBZ']['prefname'] = 'filters_spam_orbz';
      $filters['ORBZ']['name'] = 'ORBZ List';
      $filters['ORBZ']['link'] = 'http://www.orbz.org/';
      $filters['ORBZ']['dns'] = 'inputs.orbz.org';
      $filters['ORBZ']['comment'] =
_("FREE - Another ORBS replacement (just the INPUTS database used here).");
      
      $filters['Five-Ten']['prefname'] = 'filters_spam_fiveten';
      $filters['Five-Ten']['name'] = 'Five-Ten-sg.com Lists';
      $filters['Five-Ten']['link'] = 'http://www.five-ten-sg.com/blackhole.php';
      $filters['Five-Ten']['dns'] = 'blackholes.five-ten-sg.com';
      $filters['Five-Ten']['comment'] =
_("FREE - Five-Ten-sg.com has SPAM source, OpenRelay, and and Dialup IPs.");
      
      $filters['Dorkslayers']['prefname'] = 'filters_spam_dorks';
      $filters['Dorkslayers']['name'] = 'Dorkslayers Lists';
      $filters['Dorkslayers']['link'] = 'http://www.dorkslayers.com';
      $filters['Dorkslayers']['dns'] = 'orbs.dorkslayers.com';
      $filters['Dorkslayers']['comment'] =
_("FREE - Dorkslayers appears to include only really bad open relays outside the US to avoid being sued. Interestingly enough, their website recommends you NOT use their service.");
      
      $filters['ORBL']['prefname'] = 'filters_spam_orbl';
      $filters['ORBL']['name'] = 'ORBL Lists';
      $filters['ORBL']['link'] = 'http://www.orbl.org';
      $filters['ORBL']['dns'] = 'or.orbl.org';
      $filters['ORBL']['comment'] =
_("'FREE - ORBL is another ORBS spinoff formed after ORBS shut down. May be SLOOOOOOW!");
      
      $filters['ORBZ-UK']['prefname'] = 'filters_spam_orbzuk';
      $filters['ORBZ-UK']['name'] = 'ORBZ-UK Lists';
      $filters['ORBZ-UK']['link'] = 'http://orbz.gst-group.co.uk';
      $filters['ORBZ-UK']['dns'] = 'orbz.gst-group.co.uk';
      $filters['ORBZ-UK']['comment'] =
_("FREE - orbz.gst-group.co.uk lists not only open relays, but also mailservers that refuse or bounce email addressed to postmaster@<theirdomain>.");
      
      foreach ($filters as $Key => $Value) {
          $filters[$Key]['enabled'] = getPref($data_dir, $username,
              $filters[$Key]['prefname']);
      }
      
      return $filters;
   }

   function remove_filter ($id) {
      global $data_dir, $username;
      
      while ($nextFilter = getPref($data_dir, $username, 'filter' . 
         ($id + 1))) {
         setPref($data_dir, $username, 'filter' . $id, $nextFilter);
	 $id ++;
      }
      
      removePref($data_dir, $username, 'filter' . $id);
   }
   
   function filter_swap($id1, $id2) {
      global $data_dir, $username;
      
      $FirstFilter = getPref($data_dir, $username, 'filter' . $id1);
      $SecondFilter = getPref($data_dir, $username, 'filter' . $id2);
      
      if ($FirstFilter && $SecondFilter) {
         setPref($data_dir, $username, 'filter' . $id2, $FirstFilter);
         setPref($data_dir, $username, 'filter' . $id1, $SecondFilter);
      }
   }
?>
