<?php
/*
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Library General Public License for more details.
 *
 *  You should have received a copy of the GNU Library General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */
  /****************************************************************
   **  
   **	csvimport.php
   **			copyright 2000 Lewis Bergman - lbergman@abi.tconline.net
   **			
   **	Import csv files for address book
   **      This takes a comma delimited file uploaded from addressbook.php
   **      and allows the user to rearrange the field order to better
   **      fit the address book. A subset of data is manipulated to save time.
   **
   ****************************************************************/


   session_start();

   if(!isset($username)) {
      echo "You need a valid user and password to access this page!";
      exit;
   }
	if (!isset($config_php))
      	   include("../config/config.php");
   if (!isset($i18n_php))
	include("../functions/i18n.php");

	include("../functions/page_header.php");
	include("../functions/addressbook.php");
	include("../src/load_prefs.php");

	echo "<HTML BGCOLOR=\"$color[4]\">";
	echo "<BODY BGCOLOR=\"$color[4]\" TEXT=\"$color[6]\" LINK=\"$color[11]\" VLINK=\"$color[6]\" ALINK=\"$color[11]\">\n";

   /** 
    ** Script-wide vars. Used all over the place or something you might want to change later.
    **	$example -->Sets how many rows of user data the script will use to play with before final write.
    **   $abook ---->Setup the addressbook functions for Pallo's Addressbook.
    **/
   $example = 6;
   $abook = addressbook_init(true, true);

   /** See if the submit button has been clicked, if not, set up arrays with empty elements
    ** To change the number of text boxes displayed in headers, add or delete ,"" 's
    **/
   if(!$submit || $reset){
      $nickname = array("","","");
      $firstname = array("","","");
      $lastname = array("","","");
      $email = "";
      $label = array("","","","","","","","","");
   }

   if($flag <= 1) {                                      // If first run of script, setup the filename to copy
      $tempfilename = ($username . (time()+2592000));
      $nameholder = $tempfilename;
      if(copy($smusercsv, "$data_dir$tempfilename")) {	// Set up variable to use in printing status
         $goodcopy = true;
      } else {
         $goodcopy = false;
      }
   } elseif($flag >= 2) {							           // If not use the name already set up
      $tempfilename = $nameholder;
   }

   // table with directions
   if(!$finish) {
	   echo "<FORM METHOD=\"post\">\n";
	   echo "<CENTER><TABLE BGCOLOR=\"$color[9]\" WIDTH=\"70%\" FRAME=\"void\" NOWRAP>\n";
	   echo "   <TR>\n";
	   echo "      <TD ALIGN=\"center\">", _("All the steps required to complete the process are listed below"), "</TD>\n";
	   echo "   </TR>\n";
	   echo "   <TR>\n";
	   echo "      <TD>";
	   echo _("You uploaded a file named: ");
	   echo "<B>$smusercsv_name  </B>";
	   echo "</TD>\n";
	   echo "   </TR>\n";
	   echo "   <TR>\n";
	   echo "      <TD>";
	   if(!$goodcopy && $flag == 0) {			// print correct status of file copying
	      echo _("Failed to create working copy, Please try again.");
	   } else {
	      echo _("Created working copy, continuing with process...");
   	}
   	echo "</TD></TR>\n";
   	echo "   <TR>\n";
   	echo "       <TD>", _("Displaying a small set of your data."), "</TD>";
   	echo "   </TR>\n";
   	echo "   <TR>\n";
   	echo "       <TD>", _("Arrange your data to fit the 5 address book fields. "), _("Do this by inserting the data's field number under the field for which you wish it to be included into the address book. "), _("For example: fields 5, 6, and 7 need to go into the info field 5. "), _("The boxes under field 5 would contain 5, 6, and 7 in seperate boxes. "), "</TD>\n";
   	echo "   <TR>\n";
   	echo "       <TD>", _("Submit Your reorganized data."), "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp", "\n";
   	echo "           <INPUT TYPE=\"submit\" NAME=\"submit\" VALUE=\"Submit\">", "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp\n";
   	echo "           <INPUT TYPE=\"hidden\" NAME=\"flag\" VALUE=\"2\">\n";
   	echo "           <INPUT TYPE=\"hidden\" NAME=\"nameholder\" VALUE=$nameholder>\n";
   	echo "           <INPUT TYPE=\"hidden\" NAME=\"email\" VALUE=$email>\n";
   	echo "           ", _("Erase entries and re-enter field numbers."), "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp", "\n";
   	echo "           <INPUT TYPE=reset VALUE=reset>\n";
   	echo "       </TD>\n";
   	echo "   </TR>\n";
   	echo "   <TR>\n";
   	echo "       <TD>", _("View full set of imported records in their new format."), "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp\n";
   	echo "           <INPUT TYPE=\"submit\" NAME=\"all\" VALUE=\"Show all Records\">\n";
   	echo "           <INPUT TYPE=\"hidden\" NAME=\"submit\" VALUE=\"Submit\">";	
   	echo "       </TD>";
   	echo "   </TR>\n";
   	echo "   <TR>\n";
   	echo "       <TD>", _("Omit individual records which are not to be included."), "\n";
   	echo "           ", _("To the left of each field below the \"Omit\" heading is a checkbox."), "\n";
   	echo "           ", _("Click this checkbox to omit individual records."), "\n";
      echo "       </TD>";
   	echo "   </TR>\n";
   	echo "   <TR>\n";
   	echo "       <TD>", _("Final approval. ");
   	echo "           ", _("After reviewing the rearranged data for accuracy, click \"Finish\"."), "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp\n";
   	echo "           <INPUT TYPE=\"submit\" NAME=\"finish\" VALUE=\"Finish\">\n";
   	echo "       </TD>";
   	echo "   </TR>\n";
   	echo "</TABLE>\n";
   } else {
      echo "<BR><BR><H1><STRONG><CENTER>", _("Upload Completed!"), "</STRONG></H1>", _("Click on the link below to verify your work."), "</CENTER>";
      echo "<BR><BR><CENTER><A HREF=\"addressbook.php\">" . _("Addresses") . "</A></CENTER>\n";
   }

   /**
    ** Use fgetcsv to make an array out of the uploaded file
    ** Display a sample to see if the data looks good to the user.
    **/

   // open the correct filename to work with
	if($flag <= 1) {                            // before submit
	   $fp = fopen("$data_dir$tempfilename", "r");
	} elseif($flag >= 2) {                      // after submit
	   $fp = fopen("$data_dir$nameholder", "r");
	}

	echo "<CENTER><TABLE WIDTH=\"95%\" FRAME=\"void\" CELLSPACING=\"1\">\n";	// user's data table

   // This loop sets up a table of the data they uploaded to save time while the user rearranges it.
	$row = 0;
	do {
	   ($data = fgetcsv($fp, 4096));		
	   $cols = count($data);
	   $row++;
	   
	   if($flag == 0 && !$finish) {                            // Table header on initial import
	      echo "   <TH BGCOLOR=\"$color[9]\" HEIGHT=\"35\" COLSPAN=\"$cols\" BGCOLOR=\"$color[11]\">";
	      echo "<STRONG>", _("This table shows your data after uploading it."), "</STRONG>";
	      echo "</TH><BR>\n";
      } elseif($flag == 2 && !$finish) {                      // Table header after field changes made 
	      echo "   <TH BGCOLOR=\"$color[9]\" HEIGHT=\"35\" COLSPAN=\"$cols\" BGCOLOR=\"$color[11]\">";
	      echo "<STRONG>", _("This table shows your data after you reorganized it."), "</STRONG>";
	      echo "</TH><BR>\n";
      }
      
      switch($flag) {                           // This switch sets up a method so that proper looping can be done for the table output

         case 0:		
         case 1:
         case 2:                                 // Should probably be header since thats really what they are, maybe later
            
            if(!$finish) {                       // Set up column headers unless we are finished
               echo "     <TR BGCOLOR=\"$color[9]\" ALIGN=\"center\">\n";     // fill in above the omit field
               echo "         <TD>", "&nbsp", "</TD>\n";
               echo "         <TD>", _("Field Number: "), "0", "<BR>\n";      // number the fields so they know what to put where
               reset($nickname);
               while(list($k,$v)=each($nickname)) {                           // print out the text boxes for this var
                  echo"             <INPUT TYPE=text NAME=nickname[$k] MAXLENGTH=2 SIZE=2 VALUE=\"".$v."\"><BR>\n";
               }
               echo "         </TD>\n";
               echo "         <TD>", _("Field Number: "), "1", "<BR>\n";      // number the fields so they know what to put where
               reset($firstname);
               while(list($k,$v)=each($firstname)) {                          // print out the text boxes for this var
                  echo"             <INPUT TYPE=text NAME=firstname[$k] MAXLENGTH=2 SIZE=2 VALUE=\"".$v."\"><BR>\n";
               }
               echo "         </TD>\n";
               echo "         <TD>", _("Field Number: "), "2", "<BR>\n";      // number the fields so they know what to put where
               reset($lastname);
               while(list($k,$v)=each($lastname)) {                           // print out the text boxes for this var
                  echo"             <INPUT TYPE=text NAME=lastname[$k] MAXLENGTH=2 SIZE=2 VALUE=\"".$v."\"><BR>\n";
               }
               echo "         </TD>\n";                                       // email isn't an array so no loop needed
               echo "         <TD>", _("Field Number: "), "3", "<BR>\n";      // number the fields so they know what to put where
               echo "             <INPUT TYPE=text NAME=email MAXLENGTH=\"2\" SIZE=\"2\" VALUE=$email>\n";
               echo "         </TD>\n";
               echo "         <TD>", _("Field Number: "), "4", "<BR>\n";      // number the fields so they know what to put where
               reset($label);
               while(list($k,$v)=each($label)) {                               // print out the text boxes for this var
                  echo"             <INPUT TYPE=text NAME=label[$k] MAXLENGTH=2 SIZE=2 VALUE=\"".$v."\"><BR>\n";
               }
               echo "         </TD>\n";

               // print field numbers for importable fields
               $fcols = $cols;
               $i = 5;
               for($cols > 4;$fcols > 5 ; --$fcols) {
                  echo "         <TD>", _("Field Number: "), "$i", "</TD>\n";
               $i++;
               }
               // give the imported columns a name so the user knows what they will be put into
               echo "     </TR>\n";
               echo "     <TR BGCOLOR=\"$color[9]\" ALIGN=\"center\">\n";
               echo "		<TD WIDTH=\"1\">", _("Omit"), "</TD>\n";
               echo "         <TD>", _("Nickname:"), "</TD>\n";
               echo "         <TD>", _("First name:"), "</TD>\n";
               echo "         <TD>", _("Last name:"), "</TD>\n";
               echo "         <TD>", _("E-mail address:"), "</TD>\n";
               echo "         <TD>", _("Additional info:"), "</TD>\n";

               // print some instruction in the header above the fields that need to be combined into other fields
               $fcols = $cols;
               for($cols > 4;$fcols > 5 ; --$fcols) {
                  echo "         <TD>", _("Move to field"), "<BR>", _("1-5 to include."), "</TD>\n";
               }
               echo "     </TR>";
               echo "</TH><BR>\n";
            }

         case 3:                         // user's table data
         
            if($row % 2 && !$finish) { 				  // Set up the alternating colored rows
               echo "      <TR BGCOLOR=\"$color[0]\">\n";
            } elseif(!$finish){
               echo "      <TR>\n";
            }              
               echo "         <TD WIDTH=\"1\"><INPUT TYPE=checkbox NAME=\"sel[]\" VALUE=\"omit\">"; // Print the omit checkbox, to be checked before write

            for($c=0; $c<$cols; $c++) {				// Spit out the table cells	
	            // concatenate fields based on user input into text boxes.
               if($submit) {
                  switch($c) {                     // This switch puts the correct data into the correct fields
                     case 0:                                     // concactenate nickname field
                     
                        reset($nickname);
                        $j = 0;
                        while(list($k,$v)=each($nickname)) {
                           if($v != "" && $j == 0) {
                              $reorg = "$data[$v]";              // put data in without coma
                           } elseif($v != "") {
                              $reorg .= ";  $data[$v]";          // put data in with coma
                           } else "&nbsp;";                      // put in space to keep the row colors going
                           $j++;
                        }
                        $addaddr["nickname"] = $reorg;           // assign value for writing
                        break;

                     case 1:                                     // concactenate firstname field
                     
                        reset($firstname);
                        $j = 0;
                        while(list($k,$v)=each($firstname)) {
                           if($v != "" && $j == 0) {
                              $reorg = "$data[$v]";
                           } elseif($v != "") {
                              $reorg .= ";  $data[$v]";
                           } else "&nbsp;";
                           $j++;
                        }
                        $addaddr["firstname"] = $reorg;
                        break;

                     case 2:                                     // concactenate lastname field
                     
                        reset($lastname);
                        $j = 0;
                        while(list($k,$v)=each($lastname)) {
                           if($v != "" && $j == 0) {
                              $reorg = "$data[$v]";
                           } elseif($v != "") {
                              $reorg .= ";  $data[$v]";
                           } else "&nbsp;";
                           $j++;
                        }
                        $addaddr["lastname"] = $reorg;
                        break;

                     case 3:                                     // should only have one field in $email
                     
                        $reorg = $data[$email];
                        $addaddr["email"] = $reorg;
                        break;

                     case 4:                                     // concactenate label field
                     
                        reset($label);
                        $j = 0;
                        while(list($k,$v)=each($label)) {
                           if($v != "" && $j == 0) {
                              $reorg = "$data[$v]";
                           } elseif($v != "") {
                              $reorg .= ";  $data[$v]";
                           } else "&nbsp;";
                           $j++;
                        }
                        $addaddr["label"] = $reorg;
                  }
               } else $reorg = $data[$c];

	            if($reorg != "" && !$finish) {                                // if not empty, put data in cell.
                  trim($reorg);
                  echo "         <TD NOWRAP>$reorg</TD>\n";
               } elseif(!$finish) {                                          // if empty, put space in cell keeping colors correct.
                  echo "         <TD>&nbsp;</TD>\n";
               } else           
	            $reorg = "";
            }
	      echo "      </TR>\n";
         $flag = 3;
         break;
      }
      // If finished, do the import. This uses Pallo's excellent class and object stuff 
      if($finish && !$sel[$row]) {
         $r = $abook->add($addaddr,$abook->localbackend);
         if(!r) {
            print $this->error;
         }
         unset($addaddr);
      }
      // How far should we loop through the users' data.	      
		if($row < $example && (!$all && !$finish)){
			$loop = true;
		} elseif(!feof($fp) && ($all || $finish)) {
			$loop = true;
		} else {
			$loop = false;
		}
			
	} while($loop);

	echo "</TABLE>";

	fclose($fp);
// unset each element in the arrays. For some reason, this doesn't work when included in the same loop as the <INPUT.
	reset($nickname);
	while(list($k,$v)=each($nickname)){
      unset($nickname[$k]);
	}
	reset($lastname);
	while(list($k,$v)=each($lastname)){
      unset($lastname[$k]);
	}
	reset($firstname);
	while(list($k,$v)=each($firstname)){
		unset($firstname[$k]);
	}
	reset($label);
	while(list($k,$v)=each($label)){
		unset($label[$k]);
	}

   // Send the field numbers entered in the text boxes by the user back to this script for more processing
   // email is handled differently, not being an array
   if($submit == "Submit"){
	   // loop through each array and send each element
	   reset($nickname);
	   while(list($k,$v)=each($nickname)){
	   	echo"   <INPUT TYPE=hidden NAME=nickname[$k] VALUE=\"".$v."\">\n";
	   }
	   reset($lastname);
	   while(list($k,$v)=each($lastname)){
	   	echo"   <INPUT TYPE=hidden NAME=lastname[$k] VALUE=\"".$v."\">\n";
	   }
	   reset($firstname);
	   while(list($k,$v)=each($firstname)){
	   	echo"   <INPUT TYPE=hidden NAME=firstname[$k] VALUE=\"".$v."\">\n";
	   }
	   reset($label);
	   while(list($k,$v)=each($label)){
	   	echo"   <INPUT TYPE=hidden NAME=label[$k] VALUE=\"".$v."\">\n";
	   }
   }

   // Clean up after ourselves.
   if($finish) {
      unlink ("$data_dir$tempfilename");
   }
   
?>
</FORM>
</BODY>
</HTML>
