<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");
   include("../functions/date.php");

   echo "<HTML><BODY TEXT=\"#000000\" BGCOLOR=\"#FFFFFF\" LINK=\"#0000EE\" VLINK=\"#0000EE\" ALINK=\"#0000EE\">\n";
   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   displayPageHeader($mailbox);

   echo "<FORM action=\"compose_send.php\" METHOD=POST>\n";
   echo "<CENTER>";
   echo "<TABLE COLS=2 WIDTH=100% ALIGN=CENTER>\n";
   echo "   <TR>\n";
   echo "      <TD WIDTH=15% BGCOLOR=FFFFFF ALIGN=RIGHT>\n";
   echo "         <FONT FACE=\"Arial,Helvetica\">To: </FONT>\n";
   echo "      </TD><TD WIDTH=85% BGCOLOR=FFFFFF ALIGN=LEFT>\n";
   echo "         <INPUT TYPE=TEXT NAME=passed_to SIZE=60><BR>";
   echo "      </TD>\n";
   echo "   </TR>\n";
   echo "   <TR>\n";
   echo "      <TD WIDTH=15% BGCOLOR=FFFFFF ALIGN=RIGHT>\n";
   echo "         <FONT FACE=\"Arial,Helvetica\">CC:</FONT>\n";
   echo "      </TD><TD WIDTH=85% BGCOLOR=FFFFFF ALIGN=LEFT>\n";
   echo "         <INPUT TYPE=TEXT NAME=passed_cc SIZE=60><BR>";
   echo "      </TD>\n";
   echo "   </TR>\n";
   echo "   <TR>\n";
   echo "      <TD WIDTH=15% BGCOLOR=FFFFFF ALIGN=RIGHT>\n";
   echo "         <FONT FACE=\"Arial,Helvetica\">BCC:</FONT>\n";
   echo "      </TD><TD WIDTH=85% BGCOLOR=FFFFFF ALIGN=LEFT>\n";
   echo "         <INPUT TYPE=TEXT NAME=passed_bcc SIZE=60><BR>";
   echo "      </TD>\n";
   echo "   </TR>\n";
   echo "   <TR>\n";
   echo "      <TD WIDTH=15% BGCOLOR=FFFFFF ALIGN=RIGHT>\n";
   echo "         <FONT FACE=\"Arial,Helvetica\">Subject:</FONT>\n";
   echo "      </TD><TD WIDTH=85% BGCOLOR=FFFFFF ALIGN=LEFT>\n";
   echo "         <INPUT TYPE=TEXT NAME=passed_subject SIZE=60><BR>";
   echo "      </TD>\n";
   echo "   </TR>\n";
   echo "</TABLE>\n";

   echo "<TEXTAREA NAME=passed_body ROWS=20 COLS=72 WRAP=HARD></TEXTAREA><BR>";
   echo "<INPUT TYPE=SUBMIT VALUE=\"Send\">";
   echo "</CENTER>";
   echo "</FORM>";
?>