<?
$imap_stream_def = "The imap stream returned from <a href=\"#login\">sqimap_login</a>";

$name[0] = "read_data";
$params[0] = "(\$imap_stream, \$pre, \$handle_errors, &\$response, &\$message)";
$explain[0] = "This is used to read information from the imap server. This handles error correction, error notification, etc. It will return an array of strings that contains the output for your use.";
$params_desc[0][0]["name"] = "imap_stream";
$params_desc[0][0]["desc"] = $imap_stream_def; 
$params_desc[0][0]["type"] = "int";
$params_desc[0][1]["name"] = "pre";
$params_desc[0][1]["desc"] = "The string that goes before any commands.  In the example below, it is a001.";
$params_desc[0][1]["type"] = "string";
$params_desc[0][2]["name"] = "handle_errors";
$params_desc[0][2]["desc"] = "Whether or not to handle all error messages for you";
$params_desc[0][2]["type"] = "boolean";
$params_desc[0][3]["name"] = "respone";
$params_desc[0][3]["desc"] = "Returned if handle_errors is false.  It is the server's response";
$params_desc[0][3]["type"] = "string";
$params_desc[0][4]["name"] = "message";
$params_desc[0][4]["desc"] = "Returned if handle_errors is false.  It is the error message";
$params_desc[0][4]["type"] = "string";
$example[0] = "fputs (\$imap_stream, \"a001 SELECT INBOX\\n\");<br>";
$example[0] .="\$read_ary = sqimap_read_data(\$imap_stream, \"a001\", true, \$response, \$message);<br>";
$example[0] .="for (\$i = 0; \$i &lt; count(\$read_ary); \$i++) {<br>";
$example[0] .="&nbsp;&nbsp;&nbsp;&nbsp;echo \$read_ary[\$i];<br>";
$example[0] .="}<br>";


$name[1] = "login";
$params[1] = "(\$username, \$password, \$imap_server_address, \$hide)";
$explain[1] = "This function simply logs the specified user into the imap server.";
$params_desc[1][0]["name"] = "username";
$params_desc[1][0]["type"] = "string";
$params_desc[1][0]["desc"] = "The user who you wish to log in.";
$params_desc[1][1]["name"] = "password";
$params_desc[1][1]["type"] = "string";
$params_desc[1][1]["desc"] = "The password for the user.";
$params_desc[1][2]["name"] = "imap_server_address";
$params_desc[1][2]["type"] = "string";
$params_desc[1][2]["desc"] = "Address of the IMAP server.";
$params_desc[1][3]["name"] = "hide";
$params_desc[1][3]["type"] = "boolean";
$params_desc[1][3]["desc"] = "If set, this will hide all error messages from the login session.";
$example[1] = "sqimap_login (\"luke\", \"lkajskw\", \"mail.luke.com\", false);";


$name[2] = "logout";
$params[2] = "(\$imap_stream)";
$explain[2] = "This simply logs out whoever is logged into the \$imap_stream.";
$params_desc[2][0]["name"] = "imap_stream";
$params_desc[2][0]["type"] = "int";
$params_desc[2][0]["desc"] = $imap_stream_def;
$example[2] = "sqimap_logout (\$imap_stream);";


$name[3] = "get_delimiter";
$params[3] = "(\$imap_stream)";
$explain[3] = "Each mailbox is delimited differently between IMAP servers.  Some would look like \"INBOX.Folder\", but others might look like \"INBOX/Folder\".  This function returns what the delimiter is so you can create mailboxes of your own.";
$params_desc[3][0]["name"] = "imap_stream";
$params_desc[3][0]["type"] = "int";
$params_desc[3][0]["desc"] = $imap_stream_def;
$example[3] = "\$dm = sqimap_get_delimiter(\$imap_stream);";


$name[4] = "get_num_messages";
$params[4] = "(\$imap_stream, \$mailbox)";
$explain[4] = "Returns the number of messages in the specified folder."; 
$params_desc[4][0]["name"] = "imap_stream";
$params_desc[4][0]["type"] = "int";
$params_desc[4][0]["desc"] = $imap_stream_def;
$params_desc[4][1]["name"] = "mailbox";
$params_desc[4][1]["type"] = "string";
$params_desc[4][1]["desc"] = "The mailbox that you wish to check out.";
$example[4] = "\$num = sqimap_get_num_messages (\$imap_stream, \"INBOX\");";

$name[5] = "find_email";
$params[5] = "(\$string)";
$explain[5] = "This parses the given string for an email address.  It is meant for taking the \"from:\" header and return the email address for replying.  <br>If \$string looks like this:  Luke Ehresman &lt;lehresma@css.tayloru.edu><br>It will return this:  lehresma@css.tayloru.edu ";
$params_desc[5][0]["name"] = "string";
$params_desc[5][0]["type"] = "string";
$params_desc[5][0]["desc"] = "The string that needs parsing";
$example[5] = "\$from = \"Luke Ehresman &lt;lehresma@css.tayloru.edu><br>";
$example[5] .="\$from_email = sqimap_find_email(\$from);";

$name[6] = "find_displayable_name";
$params[6] = "(\$string)";
$explain[6] = "this parses the given string for a displayable name.  It is meant for taking the \"from:\" header and return the name of whom it is from.  If no name is found, it will display the email address.  If nothing acceptable is found, it just returns \$string back to you.";
$params_desc[6][0]["name"] = "string";
$params_desc[6][0]["type"] = "string";
$params_desc[6][0]["desc"] = "The string that needs parsing";
$example[6] = "";



/*  16
$name[1] = "";
$params[1] = "";
$explain[1] = "";
$params_desc[1][0]["name"] = "";
$params_desc[1][0]["type"] = "";
$params_desc[1][0]["desc"] = "";
$params_desc[1][1]["name"] = "";
$params_desc[1][1]["type"] = "";
$params_desc[1][1]["desc"] = "";
$params_desc[1][2]["name"] = "";
$params_desc[1][2]["type"] = "";
$params_desc[1][2]["desc"] = "";
$params_desc[1][3]["name"] = "";
$params_desc[1][3]["type"] = "";
$params_desc[1][3]["desc"] = "";
$example[0] = "";
*/
?>
