<?php

   /**
    **  options.php -- SpamCop options page
    **
    **  Copyright (c) 1999-2002 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  $Id$
    **/

define('SM_PATH','../../');
require_once(SM_PATH . 'include/validate.php');

displayPageHeader($color, 'None');
   
/* globals */
sqextractGlobalVar('action');
sqextractGlobalVar('meth');
sqextractGlobalVar('ID');
extract($_SESSION);
/* end of globals */

$action = (!isset($action) ? '' : $action);

switch ($action) {
    case 'enable':
        setPref($data_dir, $username, 'spamcop_enabled', 1);
        break;
    case 'disable':
        setPref($data_dir, $username, 'spamcop_enabled', '');
        break;
    case 'save':
        setPref($data_dir, $username, 'spamcop_delete', '');
        break;
    case 'delete':
        setPref($data_dir, $username, 'spamcop_delete', 1);
        break;
    case 'meth':
        if (isset($meth)) {
            setPref($data_dir, $username, 'spamcop_method', $meth);
        }
        break;
    case 'save_id':
        if (isset($ID)) {
            $ID = trim($ID);
            $ID = preg_replace('/@.*/','',$ID);
            $ID = preg_replace('/.*\./','',$ID);
            setPref($data_dir, $username, 'spamcop_id', $ID);
        }
        break;
}

global $spamcop_enabled, $spamcop_delete;
spamcop_load();

?>
      <br>
      <table width=95% align=center border=0 cellpadding=2 cellspacing=0><tr><td bgcolor="<?php echo $color[0] ?>">
         <center><b><?php echo _("Options") ?> - Message Filtering</b></center>
      </td></tr></table>
      <br>
      
      <table align=center>
        <tr>
	  <td align=right>SpamCop link is:</td>
	  <td><?PHP if ($spamcop_enabled) { 
	  ?>Enabled (<a href="options.php?action=disable">Disable it</a>)
	  <?PHP } else {
	  ?>Disabled (<a href="options.php?action=enable">Enable it</a>)
	  <?PHP }
	  ?></td>
	</tr>
        <tr>
	  <td align=right valign=top>Delete spam when reported:<br>
	    <font size="-2">(Only works with email-based reporting)</font>
	  </td>
	  <td valign=top><?PHP if ($spamcop_delete) { 
	  ?>Enabled (<a href="options.php?action=save">Disable it</a>)
	  <?PHP } else {
	  ?>Disabled (<a href="options.php?action=delete">Enable it</a>)
	  <?PHP }
	  ?></td>
	</tr>
	<tr>
	  <td align=right>Spam Reporting Method:</td>
	  <form method=post action=options.php><td>
	    <select name=meth>
	      <option value="quick_email"<?PHP
	        if ($spamcop_method == 'quick_email') echo ' SELECTED'
	        ?>>Quick email-based reporting</option>
	      <option value="thorough_email"<?PHP
	        if ($spamcop_method == 'thorough_email') echo ' SELECTED'
	        ?>>Thorough email-based reporting</option>
	      <option value="web_form"<?PHP
	        if ($spamcop_method == 'web_form') echo ' SELECTED'
	        ?>>Web-based form</option>
	    </select>
	    <input type=hidden name=action value=meth>
	    <input type=submit value="Save Method">
	  </td></form>
	</tr>
	<tr>
	  <td valign=top align=right>Your SpamCop authorization code:<br>
	    <font size="-2">(see below)</font>
	  </td>
	  <form method=post action=options.php><td valign=top>
	    <input type=text size=30 name="ID" value="<?PHP
	      echo htmlspecialchars($spamcop_id) ?>">
	    <input type=hidden name=action value=save_id>
	    <input type=submit value="Save ID">
	  </td></form>
	</tr>
      </table>

<p>SpamCop is a free service that greatly assists in finding the true source
of the spam and helps in letting the proper people know about the abuse.</p>

<p>To use it, you must get a SpamCop authorization code.  There is a free <a
href="http://spamcop.net/anonsignup.shtml">sign up page</a> so you can use
SpamCop.</p>

<p><b>Before you sign up, be warned:</b>  Some users have reported that the
email addresses used with SpamCop find their way onto spam lists.  To be
safe, you can just create an email forwarding account and have all SpamCop
reports get sent to there.  Also, if it gets flooded with spam, you can then
just delete that account with no worries about losing your real email address.
Just go create an email forwarder somewhere (<a 
href="http://www.yahoo.com/">Yahoo!</a> has a <a
href="http://dir.yahoo.com/Business_and_Economy/Business_to_Business/Communications_and_Networking/Internet_and_World_Wide_Web/Email_Providers/Forwarding_Services/Free_Forwarding/">list
of places</a>) so that messages from system administrators and whatnot can
be sent to you.</p>

<p>Once you have signed up with SpamCop and have received your SpamCop
authorization code, you need to enable this plugin -- just click the link
above.  Once enabled, you go about your normal life.  If you encounter a
spam message in your mailbox, just view it.  On the right-hand side, near
the top of where the message is displayed, you will see a link to report 
this message as spam.  Clicking on it brings you to a confirmation page.
Confirming that you want the spam report sent will do different things with
different reporting methods.</p>

<p><b>Email-based Reporting</b><br>
Pressing the button forwards the message to the SpamCop service and will
optionally delete the message.  From there, you just need to go to your 
INBOX and quite soon a message should appear from SpamCop.  (It gets sent to
the account you registered with, so make sure that your mail forwarder works!)
Open it up, click on the appropriate link at the top, and a new browser 
window will open.</p>

<p>Currently, the quick reporting just forwards the request to the thorough
reporting.  Also, it appears that this is for members (non-free) only.
Hopefully this will change soon.</p>

<p><b>Web-based Reporting</b><br>
When you press the button on the confirmation page, this will pop open a new
browser window and the SpamCop service should appear inside.  The message
will not be deleted (working on that part), but you won't need to wait for a
response email to start the spam reporting.</p>

<p>The SpamCop service will display information as it finds it, so scroll
down until you see a form button.  It might pause a little while it is
looking up information, so be a little patient.  Read what it says, and 
submit the spam.  Close the browser window.  Press Cancel or click on the
appropriate mail folder to see messages and/or delete the spam.</p>

<p><b>For more information</b> about SpamCop, it's services, spam in general, 
and many related topics, try reading through SpamCop's <a
href="http://spamcop.net/help.shtml">Help and Feedback</a> section.</p>

</body></html>
