<?php
   /**
    **  left_help.php
    **
    **  This is the code for the left bar.  The left bar normally shows the folders
    **  available, and has cookie information. This file is only used for the help system.
    **  To be used, webmail must be called with ?help.php.
    **
    **/

   session_start();

   if(!isset($username)) {
      echo "You need a valid user and password to access this page!";
      exit;
   }
	if (!isset($config_php))
      	   include("../config/config.php");
   	if (!isset($i18n_php))
      	   include("../functions/i18n.php");
	include("../src/load_prefs.php");
	echo "<HTML BGCOLOR=\"$color[3]\">";
  	echo "<BODY BGCOLOR=\"$color[3]\" TEXT=\"$color[6]\" BGCOLOR=\"$color[3]\" LINK=\"$color[11]\" VLINK=\"$color[6]\" ALINK=\"$color[11]\">\n";
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

	if (file_exists("../help/$user_language")) {			
        } elseif(file_exists("../help/en")){                             // If the selected language doesn't exist, use english
	   $user_language = en;
	} else {                                                         // If that is gone too, send a message
	   $nohelp = true;
	   echo "<BR><CENTER><B><FONT COLOR=$color[2]>",_("ERROR: Some or all of the standard English help files ar missing."), "</FONT></B></CENTER><BR>";
        }

	if(!$nohelp) {
	   while ( list( $key, $val ) = each( $helpdir ) ) {		// loop through the array of files
	      $fcontents = file("../help/$user_language/$val");		// assign each line of the above file to another array
	      while ( list( $line_num, $line ) = each( $fcontents ) ) {	// loop through the second array
     	         $temphed="";
      	   	 $tempanc="";

    	   	 if ( eregi("<A NAME=", $line, $tempanc)) {		// if a name anchor is found, make a link
		    $tempanc = trim($line);
		    $tempanc = str_replace("<A NAME=", "", $tempanc);
    		    $tempanc = str_replace("></A>", "", $tempanc);
        	    echo "<A HREF=\"help.php#$tempanc\" target=\"right\">";
    	   	 }
    	   	 if ( eregi("<H1>", $line, $temphed)) {			// grab a description for the link made above
		    $temphed = trim($line);
		    $temphed = str_replace("<H1>", "", $temphed);
    		    $temphed = str_replace("</H1>", "", $temphed);
		    echo "<BR>";
		    echo "<FONT SIZE=+1>" . _("$temphed") . "</FONT></A><BR>\n";	// make it bigger since it is a heading type 1
   	   	 }
    	   	 if ( eregi("<H3>", $line, $temphed)) {			// grab a description for the link made above
		    $temphed = trim($line);
		    $temphed = str_replace("<H3>", "", $temphed);
    		    $temphed = str_replace("</H3>", "", $temphed);
		    echo "" . _("$temphed") . "</A><BR>\n";		// keep same size since it is a normal entry
    	   	 }
	      }
	   }
	}
?>
