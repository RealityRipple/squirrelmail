<?php
/**
 * left_main_advanced.tpl
 *
 * Displays an experimental mailbox-tree with dhtml behaviour.  
 * It only works on browsers which supports css and javascript. The used
 * javascript is experimental and doesn't support all browsers.
 * It has been tested on IE6 an Konquerer 3.0.0-2.
 * It is now tested and working on: (please test and update this list)
 * Windows: IE 5.5 SP2, IE 6 SP1, Gecko based (Mozilla, Firebird) and Opera7
 * XWindow: ?
 * Mac: ?
 * In the function ListAdvancedBoxes there is another var $use_folder_images.
 * setting this to true is only usefull if the images exists in ../images.
 *
 * Feel free to experiment with the code and report bugs and enhancements
 *
 * The following variables are avilable in this template:
 *      $clock           - formatted string containing last refresh
 *      $mailbox_listing - string containing HTML to display default mailbox tree
 *      $location_of_bar - string "left" or "right" indicating where the frame
 *                         is located.  Currently only used in
 *                         left_main_advanced.tpl
 *      $left_size       - width of left column in pixels.  Currently only used
 *                         in left_main_advanced.tpl
 *      $imapConnection  - IMAP connection handle.  Needed to allow plugins to 
 *                         read the mailbox.
 *      $icon_theme_path - Path to the desired icon theme.  If no icon theme has
 *                         been chosen, this will be the template directory.  If
 *                         the user has disabled icons, this will be NULL.
 *
 *      $unread_notification_enabled - Boolean TRUE if the user wants to see unread 
 *                             message count on mailboxes
 *      $unread_notification_cummulative - Boolean TRUE if the user has enabled
 *                             cummulative message counts.
 *      $unread_notification_allFolders - Boolean TRUE if the user wants to see
 *                             unread message count on ALL folders or just the
 *                             mailbox.
 *      $unread_notification_displayTotal - Boolean TRUE if the user wants to
 *                             see the total number of messages in addition to
 *                             the unread message count.
 *      $collapsable_folders_enabled - Boolean TRUE if the user has enabled collapsable
 *                             folders.
 *      $use_special_folder_color - Boolean TRUE if the use has chosen to tag
 *                             "Special" folders in a different color.
 *      $message_recycling_enabled - Boolean TRUE if messages that get deleted go to
 *                             the Trash folder.  FALSE if they are permanently
 *                             deleted.
 *      $trash_folder_name   - Name of the Trash folder.
 * 
 *      $mailboxes       - Associative array of current mailbox structure.
 *                         Provided so template authors know what they have to
 *                         work with when building a custom mailbox tree.
 *                         Array contains the following elements:
 *          $a['MailboxName']   = String containing the name of the mailbox
 *          $a['MailboxFullName'] = String containing full IMAP name of mailbox
 *          $a['MessageCount']  = integer of all messages in the mailbox
 *          $a['UnreadCount']   = integer of unseen message in the mailbox
 *          $a['ViewLink']      = array containing elements needed to view the
 *                                mailbox.  Elements are:
 *                                  'Target' = target frame for link
 *                                  'URL'    = target URL for link
 *          $a['IsRecent']      = boolean TRUE if the mailbox is tagged "recent"
 *          $a['IsSpecial']     = boolean TRUE if the mailbox is tagged "special"
 *          $a['IsRoot']        = boolean TRUE if the mailbox is the root mailbox
 *          $a['IsNoSelect']    = boolean TRUE if the mailbox is tagged "noselect"
 *          $a['IsCollapsed']   = boolean TRUE if the mailbox is currently collapsed
 *          $a['CollapseLink']  = array containg elements needed to expand/collapse
 *                                the mailbox.  Elements are:
 *                                  'Target' = target frame for link
 *                                  'URL'    = target URL for link
 *                                  'Icon'   = the icon to use, based on user prefs
 *          $a['ChildBoxes']    = array containing this same data structure for
 *                                each child folder/mailbox of the current
 *                                mailbox. 
 *          $a['CummulativeMessageCount']   = integer of total messages in all
 *                                            folders in this mailbox, exlcuding
 *                                            trash folders.
 *          $a['CummulativeUnreadCount']    = integer of total unseen messages
 *                                            in all folders in this mailbox,
 *                                            excluding trash folders.
 *
 * *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/*
 * Recursively parse the mailbox structure to build the navigation tree.
 * 
 * @param array $box Array containing mailbox data
 * @param array $settings Array containing perferences, etc, passed to template
 * @param integer $indent_factor Counter used to control indent spacing
 * @since 1.5.2
 * @author Steve Brown
 */
function buildMailboxTree ($box, $settings, $indent_factor=0) {
    // Work in progress...
}

/* retrieve the template vars */
extract($t);

/*
 * Build an array to pass user prefs to the function that builds the tree in
 * order to avoid using globals, which are dirty, filthy things in templates. :)
 */         
$settings = array();
$settings['imapConnection'] = $imapConnection;
$settings['iconThemePath'] = $icon_theme_path;
$settings['unreadNotificationEnabled'] = $unread_notification_enabled;
$settings['unreadNotificationAllFolders'] = $unread_notification_allFolders;
$settings['unreadNotificationDisplayTotal'] = $unread_notification_displayTotal;
$settings['unreadNotificationCummulative'] = $unread_notification_cummulative;
$settings['useSpecialFolderColor'] = $use_special_folder_color;
$settings['messageRecyclingEnabled'] = $message_recycling_enabled;
$settings['trashFolderName'] = $trash_folder_name;
$settings['collapsableFoldersEnabled'] = $collapsable_folders_enabled;

?>
<body class="sqm_leftMain">
<script type="text/javascript" src="js/test.js"></script>
<script type="text/javascript">
<!--
/**
 * Advanced tree makes uses dTree JavaScript package by Geir Landrö heavily. 
 * See http://www.destroydrop.com/javascripts/tree/
 */
    function preload() {
      if (document.images) {
        var treeImages = new Array;
        var arguments = preload.arguments;
        for (var i = 0; i<arguments.length; i++) {
          treeImages[i] = new Image();
          treeImages[i].src = arguments[i];
        }
      }
    }
    var vTreeImg;
    var vTreeDiv;
    var vTreeSrc;
    function fTreeTimeout() {
      if (vTreeDiv.readyState == "complete")
        vTreeImg.src = vTreeSrc;
      else
        setTimeout("fTreeTimeout()", 100);
    }
    function hidechilds(img) {
      id = img.id + ".0000";
      form_id = "mbx[" + img.id +"F]";
      if (document.all) { //IE, Opera7
        div = document.all[id];
        if (div) {
           if (div.style.display == "none") {
              vTreeSrc = "../images/minus.png";
              style = "block";
              value = 0;
           }
           else {
              vTreeSrc = "../images/plus.png";
              style = "none";
              value = 1;
           }
           vTreeImg = img;
           vTreeDiv = div;
           if (typeof vTreeDiv.readyState != "undefined") //IE
              setTimeout("fTreeTimeout()",100);
           else //Non IE
              vTreeImg.src = vTreeSrc;
           div.style.display = style;
           document.all[form_id].value = value;
        }
      }
      else if (document.getElementById) { //Gecko
        div = document.getElementById(id);
        if (div) {
           if (div.style.display == "none") {
              src = "../images/minus.png";
              style = "block";
              value = 0;
           }
           else {
              src = "../images/plus.png";
              style = "none";
              value = 1;
           }
           div.style.display = style;
           img.src = src;
           document.getElementById(form_id).value = value;
        }
      }
    }
   function buttonover(el,on) {
      if (!on) {
         el.style.background="$color[0]";}
      else {
         el.style.background="$color[9]";}
   }
   function buttonclick(el,on) {
      if (!on) {
         el.style.border="groove";}
      else {
         el.style.border="ridge";}
   }
   function hideframe(hide) {
      left_size = "$left_size";
      if (document.all) {
        masterf = window.parent.document.all["fs1"];
        leftf = window.parent.document.all["left"];
        leftcontent = document.all["leftframe"];
        leftbutton = document.all["showf"];
      } else if (document.getElementById) {
        masterf = window.parent.document.getElementById("fs1");
        leftf = window.parent.document.getElementById("left");
        leftcontent = document.getElementById("leftframe");
        leftbutton = document.getElementById("showf");
      } else {
        return false;
      }
      if(hide) {
         new_col = calc_col("20");
         masterf.cols = new_col;
         document.body.scrollLeft=0;
         document.body.style.overflow="hidden";
         leftcontent.style.display = "none";
         leftbutton.style.display="block";
      } else {
         masterf.cols = calc_col(left_size);
         document.body.style.overflow="";
         leftbutton.style.display="none";
         leftcontent.style.display="block";
      }
   }
   function calc_col(c_w) {
    <?php
        if ($location_of_bar == 'right') {
            echo '     right=true;';
        } else {
            echo '     right=false;';
        }
    ?>
   if (right) {
         new_col = '*,'+c_w;
     } else {
         new_col = c_w+',*';
     }
     return new_col;
   }
   function resizeframe(direction) {
     if (document.all) {
        masterf = window.parent.document.all["fs1"];
     } else if (document.getElementById) {
        window.parent.document.getElementById("fs1");
     } else {
        return false;
     }
    <?php
        if ($location_of_bar == 'right') {
            echo '  colPat=/^\*,(\d+)$/;';
        } else {
            echo '  colPat=/^(\d+),.*$/;';
        }
    ?>
     old_col = masterf.cols;
     colPat.exec(old_col);
     if (direction) {
        new_col_width = parseInt(RegExp.$1) + 25;
     } else {
        if (parseInt(RegExp.$1) > 35) {
           new_col_width = parseInt(RegExp.$1) - 25;
        }
     }
     masterf.cols = calc_col(new_col_width);
   }
//-->
</script>
<style type="text/css">
<!--
  body {
     margin: 0px 0px 0px 0px;
     padding: 5px 5px 5px 5px;
  }
  img {
     vertical-align: middle;
  }
  .button {
     border:outset;
     border-color: <?php echo $color[9]; ?>;
     background: <?php echo $color[0]; ?>;
     color: <?php echo $color[6]; ?>;
     width:99%;
     heigth:99%;
  }
  .mbx_par {
     font-size:1.0em;
     margin-left:4px;
     margin-right:0px;
     white-space: nowrap;
  }
  a.mbx_link {
      text-decoration: none;
      background-color: <?php echo $color[0]; ?>;
      display: inline;
  }
  a:hover.mbx_link {
      background-color: <?php echo $color[9]; ?>;
  }
  a.mbx_link img {
      border-style: none;
  }
  .mbx_sub {
     padding-left:5px;
     padding-right:0px;
     margin-left:4px;
     margin-right:0px;
     font-size:0.9em;
     white-space: nowrap;
  }
  .par_area {
     margin-top:0px;
     margin-left:4px;
     margin-right:0px;
     padding-left:10px;
     padding-bottom:5px;
     border-left: solid;
     border-left-width:0.1em;
     border-left-color: <?php echo $color[9]; ?>;
     border-bottom: solid;
     border-bottom-width:0.1em;
     border-bottom-color: <?php echo $color[9]; ?>;
     display: block;
  }
  .mailboxes {
     padding-bottom:3px;
     margin-right:4px;
     padding-right:4px;
     margin-left:4px;
     padding-left:4px;
     border: groove;
     border-width:0.1em;
     border-color: <?php echo $color[9]; ?>;
     background: <?php echo $color[0]; ?>;
     font-size: smaller;
  }
-->
</style>
<div class="sqm_leftMain">
<?php
$right_pos = $left_size - 20;
?>
<div style="position:absolute; top:0; border:0.1em solid blue;">
 <div id="hidef" style="width=20;font-size:12"><a href="javascript:hideframe(true)"><b>&lt;&lt;</b></a></div>
 <div id="showf" style="width=20;font-size:12;display:none;"><a href="javascript:hideframe(false)"><b>&gt;&gt;</b></a></div>
 <div id="incrf" style="width=20;font-size:12"><a href="javascript:resizeframe(true)"><b>&gt;</b></a></div>
 <div id="decrf" style="width=20;font-size:12"><a href="javascript:resizeframe(false)"><b>&lt;</b></a></div>
</div>
<div id="leftframe">
<br />
<br />
<br />
<?php do_hook('left_main_before'); ?>
<table class="sqm_wrapperTable" cellspacing="0">
 <tr>
  <td>
   <table cellspacing="0">
    <tr>
     <td style="text-align:center">
      <span class="sqm_folderHeader"><?php echo _("Folders"); ?></span><br />
      <span class="sqm_clock"><?php echo $clock; ?></span>
      <span class="sqm_refreshButton"><small>[<a href="../src/left_main.php" target="left"><?php echo _("Check Mail"); ?></a>]</small></span>
     </td>
    </tr>
   </table>
   <br />
   <?php echo $mailbox_listing; ?>
  </tr>
 </td>
</table>
<?php do_hook('left_main_after'); ?>
</div>    
<?php var_dump($template_dir); ?>