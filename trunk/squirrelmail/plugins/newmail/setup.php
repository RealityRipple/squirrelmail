<?php

   /**
    **  newmail.php
    **  (c)2000 by Michael Huttinger
    **
    **  Quite a hack -- but my first attempt at a plugin.  We were
    **  looking for a way to play a sound when there was unseen
    **  messages to look at.  Nice for users who keep the squirrel
    **  mail window up for long periods of time and want to know
    **  when mail arrives.
    **
    **  Basically, I hacked much of left_main.php into a plugin that
    **  goes through each mail folder and increments a flag if
    **  there are unseen messages.  If the final count of unseen
    **  folders is > 0, then we play a sound (using the HTML at the
    **  far end of this script).
    **
    **  This was tested with IE5.0 - but I hear Netscape works well,
    **  too (with a plugin).
    **/

   function CheckNewMailboxSound($imapConnection, $mailbox, $real_box, $delimeter, $unseen, &$total_unseen) {
		global $folder_prefix, $trash_folder, $sent_folder;
		global $color, $move_to_sent, $move_to_trash;
      global $unseen_notify, $unseen_type, $newmail_allbox, $newmail_recent;
      global $newmail_changetitle;

      $mailboxURL = urlencode($real_box);
      $unseen_found = 0;

      // Skip folders for Sent and Trash

      if ($real_box == $sent_folder || $real_box == $trash_folder)
      {
	   return 0;
      }

      if (($unseen_notify == 2 && $real_box == "INBOX") ||
          ($unseen_notify == 3 && ($newmail_allbox == "on" ||
	                           $real_box == "INBOX"))) {
         $unseen = sqimap_unseen_messages($imapConnection, $real_box);
	 $total_unseen += $unseen;
	 
	 if($newmail_recent == 'on')
	   $unseen = sqimap_mailbox_select($imapConnection,$real_box,true,true);
	 
         if ($unseen > 0) {
            $unseen_found = 1;
         } 
      }
      return $unseen_found;
   }

function squirrelmail_plugin_init_newmail() {
  global $squirrelmail_plugin_hooks;

  $squirrelmail_plugin_hooks["left_main_before"]["newmail"] = "newmail_plugin";
  $squirrelmail_plugin_hooks["options_link_and_description"]["newmail"] = "newmail_opt";
  $squirrelmail_plugin_hooks["options_save"]["newmail"] = "newmail_sav";
  $squirrelmail_plugin_hooks["loading_prefs"]["newmail"] = "newmail_pref";

}

function newmail_opt() {
  global $color;
  ?>
  <table width=50% cellpadding=3 cellspacing=0 border=0 align=center>
  <tr>
     <td bgcolor="<?php echo $color[9] ?>">
       <a href="../plugins/newmail/newmail_opt.php">New Mail Notification</a>
     </td>
  </tr>
  <tr>
     <td bgcolor="<?php echo $color[0] ?>">
	This configures settings for playing sounds and/or showing
	popup windows when new mail arrives.
     </td>
  </tr>
  </table>
  <?php
}

function newmail_sav() {

  global $username,$data_dir;
  global $submit_newmail,$media_file,$media_reset,$media_enable,$media_popup;
  global $media_recent,$media_sel;
  global $media_allbox, $media_changetitle;

  if ($submit_newmail) {
   if(isset($media_enable)) {
     setPref($data_dir,$username,"newmail_enable",$media_enable);
   } else {
     setPref($data_dir,$username,"newmail_enable","");
   }
   if(isset($media_popup)) {
     setPref($data_dir,$username,"newmail_popup",$media_popup);
   } else {
     setPref($data_dir,$username,"newmail_popup","");
   }
   if(isset($media_allbox)) {
     setPref($data_dir,$username,"newmail_allbox",$media_allbox);
   } else {
     setPref($data_dir,$username,"newmail_allbox","");
   }
   if(isset($media_recent)) {
     setPref($data_dir,$username,"newmail_recent",$media_recent);
   } else {
     setPref($data_dir,$username,"newmail_recent","");
   }
   if(isset($media_changetitle)) {
     setPref($data_dir,$username,"newmail_changetitle",$media_changetitle);
   } else {
     setPref($data_dir,$username,"newmail_changetitle","");
   }
   if(isset($media_sel)) {
     if($media_sel == "(local media)") { 
  	  setPref($data_dir,$username,"newmail_media",StripSlashes($media_file));
     } else {
        setPref($data_dir,$username,"newmail_media",$media_sel);
     }
   } else {
     setPref($data_dir,$username,"newmail_media","");
   }
   echo "<center> New Mail Notification options saved</center>";
  }
}

function newmail_pref() {
  
  global $username,$data_dir;
  global $newmail_media,$newmail_enable,$newmail_popup,$newmail_allbox;
  global $newmail_recent, $newmail_changetitle;

  $newmail_recent = getPref($data_dir,$username,"newmail_recent");
  $newmail_enable = getPref($data_dir,$username,"newmail_enable");
  $newmail_media = getPref($data_dir, $username, "newmail_media");
  $newmail_popup = getPref($data_dir, $username, "newmail_popup");
  $newmail_allbox = getPref($data_dir, $username, "newmail_allbox");
  $newmail_changetitle = getPref($data_dir, $username, "newmail_changetitle");

  if ($newmail_media == "")
  {
    $newmail_media = "../plugins/newmail/sounds/Notify.wav";
  }

}

function newmail_plugin() {

 global $username,$key,$imapServerAddress,$imapPort;
 global $newmail_media,$newmail_enable,$newmail_popup,$newmail_recent;
 global $newmail_changetitle;

 if ($newmail_enable == "on" || $newmail_popup == "on" || $newmail_changetitle) {

   // open a connection on the imap port (143)

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10); // the 10 is to hide the output

   $boxes = sqimap_mailbox_list($imapConnection);
   $delimeter = sqimap_get_delimiter($imapConnection);

   $status = 0;
   $totalNew = 0;

   for ($i = 0;$i < count($boxes); $i++) {
      $line = "";
      $mailbox = $boxes[$i]["formatted"];

      if (! isset($boxes[$i]['unseen']))
         $boxes[$i]['unseen'] = '';
      if ($boxes[$i]["flags"]) {
         $noselect = false;
         for ($h = 0; $h < count($boxes[$i]["flags"]); $h++) {
            if (strtolower($boxes[$i]["flags"][$h]) == "noselect")
               $noselect = true;
         }
         if (! $noselect) {
            $status = $status + CheckNewMailboxSound($imapConnection, $mailbox,
			$boxes[$i]["unformatted"], $delimeter, $boxes[$i]["unseen"],
			$totalNew);
         }
      } else {
         $status = $status + CheckNewMailboxSound($imapConnection, $mailbox, $boxes[$i]["unformatted"],
			$delimeter, $boxes[$i]["unseen"], $totalNew);
      }
   
   }
   sqimap_logout($imapConnection);

   // If we found unseen messages, then we
   // will play the sound as follows:

   if ($newmail_changetitle) {
?>
<script language="javascript">
function ChangeTitleLoad() {
   changetitlenum = <?PHP echo $totalNew ?>;
   if (changetitlenum == 1)
      window.parent.document.title = changetitlenum + " New Message";
   else
      window.parent.document.title = changetitlenum + " New Messages";
   if (BeforeChangeTitle != null)
      BeforeChangeTitle();
}
BeforeChangeTitle = window.onload;
window.onload = ChangeTitleLoad;
</script>
<?PHP
   }
   if ($status > 0 && $newmail_enable == "on") {
      echo "<EMBED SRC=\"$newmail_media\" HIDDEN=TRUE AUTOSTART=TRUE>";
   }
   if ($status >0 && $newmail_popup == "on") {
?>
<SCRIPT LANGUAGE="JavaScript">
<!--
function PopupScriptLoad() {
   window.open("../plugins/newmail/newmail.php", "SMPopup",
      "width=200,height=130,scrollbars=no");
   if (BeforePopupScript != null)
      BeforePopupScript();
}
BeforePopupScript = window.onload;
window.onload = PopupScriptLoad;

// Idea by:  Nic Wolfe (Nic@TimelapseProductions.com)
// Web URL:  http://fineline.xs.mw
// More code from Tyler Akins
// End -->
</script>
<?php

   }
 }
}
?>
