<?php
   /**
    **  left_main.php
    **
    **  This is the code for the left bar.  The left bar shows the folders
    **  available, and has cookie information.
    **
    **/

   session_start();

   if(!isset($username)) {
      echo "You need a valid user and password to access this page!";
      exit;
   }

   // Configure the left frame for the help menu
   // Maybe this should be a function but since I haven't done one it isn't

   $ishelp = substr(getenv(REQUEST_URI),-8);	// take the right 8 characters from the requested URL
   if ($ishelp == "help.php") {
	if (!isset($config_php))
      	   include("../config/config.php");
   	if (!isset($i18n_php))
      	   include("../functions/i18n.php");
	include("../src/load_prefs.php");
	echo "<HTML BGCOLOR=\"$color[3]\">";
  	echo "<BODY BGCOLOR=\"$color[3]\" TEXT=\"$color[6]\" BGCOLOR=\"$color[3]\" LINK=\"$color[11]\" VLINK=\"$color[6]\" ALINK=\"$color[11]\">\n";
   	$left_size = 250;	//doesn't seem to work
   /**
    ** Array used to list the include .hlp files, we could use a dir function
    ** to step through the directory and list its contents but it doesn't order those.
    ** This should probably go in config.php but it might mess up conf.pl
    **/
	$helpdir[0] = "basic.hlp";
	$helpdir[1] = "main_folder.hlp";
	$helpdir[2] = "read_mail.hlp";
	$helpdir[3] = "addresses.hlp";
	$helpdir[4] = "compose.hlp";
	$helpdir[5] = "folders.hlp";
	$helpdir[6] = "options.hlp";
	$helpdir[7] = "FAQ.hlp";

  /**
   **  Build a menu dynamically for the left frame from the HTML tagged right frame include (.hlp) files listed in the $helpdir var.
   **  This is done by first listing all the .hlp files in the $helpdir array. 
   **  Next, we loop through the array, for every value of $helpdir we loop through the file and look for anchor tags (<A NAME=) and 
   **  header tags (<H1> or <H3>).
   **/

	if (!file_exists("../help/$user_language"))			// If the selected language doesn't exist, use english
	   $user_language = "en";


	while ( list( $key, $val ) = each( $helpdir ) ) {		// loop through the array of files
	   $fcontents = file("../help/$user_language/$val");		// assign each line of the above file to another array
	   while ( list( $line_num, $line ) = each( $fcontents ) ) {	// loop through the second array
     	   	$temphed="";
      	   	$tempanc="";
    	   	if ( eregi("<A NAME=", $line, $tempanc)) {		// if a name anchor is found, make a link
		   $tempanc = $line;
		   $tempanc = ereg_replace("<A NAME=", "", $tempanc);
    		   $tempanc = ereg_replace("></A>", "", $tempanc);
        	   echo "<A HREF=\"help.php#$tempanc\" target=\"right\">";
    	   	} 
    	   	if ( eregi("<H1>", $line, $temphed)) {			// grab a description for the link made above
		   $temphed = $line;
		   $temphed = ereg_replace("<H1>", "", $temphed);
    		   $temphed = ereg_replace("</H1>", "", $temphed);
		   echo "<BR>";
		   echo "<FONT SIZE=+1>" . _("$temphed") . "</FONT></A><BR>\n";	// make it bigger since it is a heading type 1
   	   	}
    	   	if ( eregi("<H3>", $line, $temphed)) {			// grab a description for the link made above
		   $temphed = $line;
		   $temphed = ereg_replace("<H3>", "", $temphed);
    		   $temphed = ereg_replace("</H3>", "", $temphed);
		   echo "" . _("$temphed") . "</A><BR>\n";		// keep same size since it is a normal entry
    	   	}
	   }
	}                  
   } else {
   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($array_php))
      include("../functions/array.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($i18n_php))
      include("../functions/i18n.php");


   displayHtmlHeader();

   function formatMailboxName($imapConnection, $mailbox, $real_box, $delimeter, $unseen) {
		global $folder_prefix, $trash_folder, $sent_folder;
		global $color, $move_to_sent, $move_to_trash;

      $mailboxURL = urlencode($real_box);
		if($real_box=="INBOX") {
	      $unseen = sqimap_unseen_messages($imapConnection, $numUnseen, $real_box);
		}

      $line .= "<NOBR>";
      if ($unseen > 0)
         $line .= "<B>";

      $special_color = false;
		if ((strtolower($real_box) == "inbox") ||
		    (($real_box == $trash_folder) && ($move_to_trash)) ||
			 (($real_box == $sent_folder) && ($move_to_sent)))
			$special_color = true;

      if ($special_color == true) {
         $line .= "<a href=\"right_main.php?sort=0&startMessage=1&mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\"><FONT COLOR=\"$color[11]\">";
         $line .= replace_spaces($mailbox);
         $line .= "</font></a>";
      } else {
         $line .= "<a href=\"right_main.php?sort=0&startMessage=1&mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\">";
         $line .= replace_spaces($mailbox);
         $line .= "</a>";
      }

      if ($unseen > 0)
         $line .= "</B>";

      if ($unseen > 0) {
         $line .= "&nbsp;<small>($unseen)</small>";
      }

      if (($move_to_trash == true) && ($real_box == $trash_folder)) {
         $urlMailbox = urlencode($real_box);
         $line .= "\n<small>\n";
         $line .= "  &nbsp;&nbsp;&nbsp;&nbsp;(<B><A HREF=\"empty_trash.php?numMessages=$numMessages&mailbox=$urlMailbox\" TARGET=right style=\"text-decoration:none\">"._("purge")."</A></B>)";
         $line .= "\n</small>\n";
      }
      $line .= "</NOBR>";
      return $line;
   }

   // open a connection on the imap port (143)
   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10); // the 10 is to hide the output

   /** If it was a successful login, lets load their preferences **/
   include("../src/load_prefs.php");

   if (isset($left_refresh) && ($left_refresh != "None") && ($left_refresh != "")) {
      echo "<META HTTP-EQUIV=\"Expires\" CONTENT=\"Thu, 01 Dec 1994 16:00:00 GMT\">\n";
      echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n"; 
      echo "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"$left_refresh;URL=left_main.php\">\n";
   }
   
   echo "\n<BODY BGCOLOR=\"$color[3]\" TEXT=\"$color[6]\" LINK=\"$color[6]\" VLINK=\"$color[6]\" ALINK=\"$color[6]\">\n\n";

   $boxes = sqimap_mailbox_list($imapConnection);

   echo "<CENTER><FONT SIZE=4><B>";
   echo _("Folders") . "</B><BR></FONT>\n\n";

   echo "<small>(<A HREF=\"../src/left_main.php\" TARGET=\"left\">";
   echo _("refresh folder list");
   echo "</A>)</small></CENTER><BR>";
   $delimeter = sqimap_get_delimiter($imapConnection);

   for ($i = 0;$i < count($boxes); $i++) {
      $line = "";
      $mailbox = $boxes[$i]["formatted"];

      if ($boxes[$i]["flags"]) {
         $noselect = false;
         for ($h = 0; $h < count($boxes[$i]["flags"]); $h++) {
            if (strtolower($boxes[$i]["flags"][$h]) == "noselect")
               $noselect = true;
         }
         if ($noselect == true) {
            $line .= "<FONT COLOR=\"$color[10]\">";
            $line .= replace_spaces(readShortMailboxName($mailbox, $delimeter));
            $line .= "</FONT>";
         } else {
            $line .= formatMailboxName($imapConnection, $mailbox, $boxes[$i]["unformatted"], $delimeter, $boxes[$i]["unseen"]);
         }
      } else {
         $line .= formatMailboxName($imapConnection, $mailbox, $boxes[$i]["unformatted"], $delimeter, $boxes[$i]["unseen"]);
      }
      echo "\n$line<BR>\n";
   }


   fclose($imapConnection);

   }                                  
?>
</BODY></HTML>
