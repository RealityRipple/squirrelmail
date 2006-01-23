<?php

/**
 * left_main.php
 *
 * This is the code for the left bar. The left bar shows the folders
 * available, and has cookie information.
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/plugin.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/date.php');

/* These constants are used for folder stuff. */
define('SM_BOX_UNCOLLAPSED', 0);
define('SM_BOX_COLLAPSED',   1);

/* --------------------- FUNCTIONS ------------------------- */


/**
 * Recursive function to output a tree of folders.
 * It is called on a list of boxes and iterates over that tree.
 *
 * @since 1.3.0
 */
function ListBoxes ($boxes, $j=0 ) {
    global $data_dir, $username, $color, $unseen_notify, $unseen_type,
           $move_to_trash, $trash_folder, $collapse_folders, $imapConnection,
           $use_icons, $icon_theme, $use_special_folder_color;

    // stop condition
    if (empty($boxes)) {
        return;
    }

    $pre = '<span style="white-space: nowrap;">';
    $end = '';
    $collapse = false;
    $unseen_found = false;
    $unseen = 0;

    $mailbox = $boxes->mailboxname_full;
    $leader = str_repeat('&nbsp;&nbsp;',$j);
    $mailboxURL = urlencode($mailbox);

    /* get unseen/total messages information */
    /* Only need to display info when option is set */
    if (isset($unseen_notify) && ($unseen_notify > 1) &&
        (($boxes->unseen !== false) || ($boxes->total !== false))) {

        if ($boxes->unseen !== false)
            $unseen = $boxes->unseen;

        /*
            Should only display unseen info if the folder is inbox
            or you set the option for all folders
        */

        if ((strtolower($mailbox) == 'inbox') || ($unseen_notify == 3)) {
            $unseen_string = $unseen;

            /* If users requests, display message count too */
            if (isset($unseen_type) && ($unseen_type == 2) && ($boxes->total !== false)) {
                $unseen_string .= '/' . $boxes->total;
            }

            $unseen_string = "<span class=\"highlight\">($unseen_string)</span>";

            /*
                Finally allow the script to display the values by setting a boolean.
                This can only occur if the unseen count is great than 0 (if you have
                unseen count only), or you have the message count too.
            */
            if (($unseen > 0) || (isset($unseen_type) && ($unseen_type ==2))) {
                $unseen_found = true;
            }
        }
    }

    if (isset($boxes->mbxs[0]) && $collapse_folders) {
        $collapse = getPref($data_dir, $username, 'collapse_folder_' . $mailbox);
        $collapse = ($collapse == '' ? SM_BOX_UNCOLLAPSED : $collapse);

        $link = '<a target="left" style="text-decoration:none" ' .'href="left_main.php?';
        if ($collapse) {
            if ($use_icons && $icon_theme != 'none') {
                $link .= "unfold=$mailboxURL\">$leader<img src=\"" . SM_PATH . 'images/plus.png" border="0" height="7" width="7" />&nbsp;';
            } else {
                $link .= "unfold=$mailboxURL\">$leader+&nbsp;";
            }
        } else {
            if ($use_icons && $icon_theme != 'none') {
                $link .= "fold=$mailboxURL\">$leader<img src=\"" . SM_PATH . 'images/minus.png" border="0" height="7" width="7" />&nbsp;';
            } else {
                $link .= "fold=$mailboxURL\">$leader-&nbsp;";
            }
        }
        $link .= '</a>';
        $pre .= $link;
    } else {
        $pre.= $leader . '&nbsp;&nbsp;';
    }

    /* If there are unseen message, bold the line. */
    if (($move_to_trash) && ($mailbox == $trash_folder)) {

        if ( !is_numeric($boxes->total) ) {
            $result = sqimap_status_messages($imapConnection, $mailbox);
            $boxes->total = $result['MESSAGES'];
            $boxes->unseen = $result['UNSEEN'];
        }
        
        $pre .= "<a href=\"right_main.php?PG_SHOWALL=0&amp;startMessage=1&amp;mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\">";
        if ($unseen > 0) {
            $pre .= '<b>';
            $end .= '</b>';
        }
        $end .= '</a>';
        if ($boxes->total > 0) {
            /* Print unseen information. */
            if ($unseen_found) {
                $end .= "&nbsp;<small>$unseen_string</small>";
            }
            $end .= "\n<small>" .
                 '&nbsp;&nbsp;[<a href="empty_trash.php">'._("Purge").'</a>]'.
                 '</small>';
        }
    } else {
        if (!$boxes->is_noselect) {
            $pre .= "<a href=\"right_main.php?PG_SHOWALL=0&amp;startMessage=1&amp;mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\">";
            if ($unseen > 0) {
                $pre .= '<b>';
                $end .= '</b>';
            }
            $end .= '</a>';
        }
        /* Print unseen information. */
        if ($unseen_found) {
            $end .= "&nbsp;<small>$unseen_string</small>";
        }

    }

    $span = '';
    $spanend = '';
    if ($use_special_folder_color && $boxes->is_special) {
        $span = "<span class=\"leftspecial\">";
        $spanend = "</span>";
    } elseif ( $boxes->is_noselect ) {
        $span = "<span class=\"leftnoselect\">";
        $spanend = '</span>';
    }


    // let plugins fiddle with end of line
    $end .= concat_hook_function('left_main_after_each_folder',
        array(isset($numMessages) ? $numMessages : '',
              $boxes->mailboxname_full, $imapConnection));

    $end .= '</span>';

    if (!$boxes->is_root) {
        echo "" . $span . $pre .
            str_replace(
                array(' ','<','>'),
                array('&nbsp;','&lt;','&gt;'),
                $boxes->mailboxname_sub) .
            $end. $spanend .'<br />' . "\n";
        $j++;
    }

    if (!$collapse || $boxes->is_root) {
        for ($i = 0; $i <count($boxes->mbxs); $i++) {
            ListBoxes($boxes->mbxs[$i],$j);
        }
    }
}

function ListAdvancedBoxes ($boxes, $mbx, $j='ID.0000' ) {
    global $data_dir, $username, $color, $unseen_notify, $unseen_type,
        $move_to_trash, $trash_folder, $collapse_folders, $use_special_folder_color;

    if (empty($boxes)) {
        return;
    }

    /* use_folder_images only works if the images exist in ../images */
    $use_folder_images = true;

    $pre = '';
    $end = '';
    $collapse = false;
    $unseen_found = false;
    $unseen = 0;

    $mailbox = $boxes->mailboxname_full;
    $mailboxURL = urlencode($mailbox);

    /* get unseen/total messages information */
    /* Only need to display info when option is set */
    if (isset($unseen_notify) && ($unseen_notify > 1) &&
        (($boxes->unseen !== false) || ($boxes->total !== false))) {

        if ($boxes->unseen !== false)
            $unseen = $boxes->unseen;

        /*
            Should only display unseen info if the folder is inbox
            or you set the option for all folders
        */

        if ((strtolower($mailbox) == 'inbox') || ($unseen_notify == 3)) {
            $unseen_string = $unseen;

            /* If users requests, display message count too */
            if (isset($unseen_type) && ($unseen_type == 2) && ($boxes->total !== false)) {
                $unseen_string .= '/' . $boxes->total;
            }

            $unseen_string = "<span class=\"highlight\">($unseen_string)</span>";

            /*
                Finally allow the script to display the values by setting a boolean.
                This can only occur if the unseen count is great than 0 (if you have
                unseen count only), or you have the message count too.
            */
            if (($unseen > 0) || (isset($unseen_type) && ($unseen_type ==2))) {
                $unseen_found = true;
            }
        }
    }

    /* If there are unseen message, bold the line. */
    if ($unseen > 0) { $pre .= '<b>'; }

    /* color special boxes */
    if ($use_special_folder_color && $boxes->is_special) {
        $pre .= "<span class=\"highlight\">";
        $end .= '</span>';
    }

    /* If there are unseen message, close bolding. */
    if ($unseen > 0) { $end .= '</b>'; }

    /* Print unseen information. */
    if ($unseen_found) {
        $end .= "&nbsp;$unseen_string";
    }

    if (($move_to_trash) && ($mailbox == $trash_folder)) {
        $pre = "<a class=\"mbx_link\" href=\"right_main.php?PG_SHOWALL=0&amp;startMessage=1&amp;mailbox=$mailboxURL\" target=\"right\">" . $pre;
        $end .= '</a>';
        $end .= "\n<small>\n" .
                '&nbsp;&nbsp;[<a class="mbx_link" href="empty_trash.php">'._("Purge").'</a>]'.
                '</small>';
    } else {
        if (!$boxes->is_noselect) { /* \Noselect boxes can't be selected */
            $pre = "<a class=\"mbx_link\" href=\"right_main.php?PG_SHOWALL=0&amp;startMessage=1&amp;mailbox=$mailboxURL\" target=\"right\">" . $pre;
            $end .= '</a>';
        }
    }

    // let plugins fiddle with end of line
    global $imapConnection;
    $end .= concat_hook_function('left_main_after_each_folder',
        array(isset($numMessages) ? $numMessages : '',
              $boxes->mailboxname_full, $imapConnection));

    if (!$boxes->is_root) {
        if ($use_folder_images) {
            if ($boxes->is_inbox) {
                $folder_img = '../images/inbox.png';
            } else if ($boxes->is_sent) {
                $folder_img = '../images/senti.png';
            } else if ($boxes->is_trash) {
                $folder_img = '../images/delitem.png';
            } else if ($boxes->is_draft) {
                $folder_img = '../images/draft.png';
            } else if ($boxes->is_noinferiors) {
                $folder_img = '../images/folder_noinf.png';
            } else {
                $folder_img = '../images/folder.png';
            }
            $folder_img = '&nbsp;<img src="'.$folder_img.'" height="15" />&nbsp;';
        } else {
            $folder_img = '';
        }
        if (!isset($boxes->mbxs[0])) {
            echo '   ' . html_tag( 'div',
                            $pre . $folder_img .
                                str_replace( array(' ','<','>'),
                                             array('&nbsp;','&lt;','&gt;'),
                                             $boxes->mailboxname_sub) .
                                $end,
                            'left', '', 'class="mbx_sub" id="' .$j. '"' ) . "\n";
        } else {
            /* get collapse information */
            if ($collapse_folders) {
                $form_entry = $j.'F';
                if (isset($mbx) && isset($mbx[$form_entry])) {
                    $collapse = $mbx[$form_entry];
                    setPref($data_dir, $username, 'collapse_folder_'.$boxes->mailboxname_full ,
                        $collapse ? SM_BOX_COLLAPSED : SM_BOX_UNCOLLAPSED);
                } else {
                    $collapse = getPref($data_dir, $username, 'collapse_folder_' . $mailbox);
                    $collapse = ($collapse == '' ? SM_BOX_UNCOLLAPSED : $collapse);
                }
                $img_src = ($collapse ? '../images/plus.png' : '../images/minus.png');
                $collapse_link = '<a href="javascript:void(0)">' .
                    " <img src=\"$img_src\" border=\"1\" id=$j onclick=\"hidechilds(this)\" style=\"cursor:hand\" /></a>";
            } else {
                 $collapse_link='';
            }
            echo '   ' . html_tag( 'div',
                            $collapse_link . $pre . $folder_img . '&nbsp;'. $boxes->mailboxname_sub . $end ,
                            'left', '', 'class="mbx_par" id="' .$j. 'P"' ) . "\n";
            echo '   <input type="hidden" name="mbx['.$j. 'F]" value="'.$collapse.'" id="mbx['.$j.'F]" />'."\n";
        }
    }

    $visible = ($collapse ? ' style="display:none"' : ' style="display:block"');
    if (isset($boxes->mbxs[0]) && !$boxes->is_root) /* mailbox contains childs */
        echo html_tag( 'div', '', 'left', '', 'class="par_area" id='.$j.'.0000 '. $visible ) . "\n";

    if ($j !='ID.0000') {
       $j = $j .'.0000';
    }
    for ($i = 0; $i <count($boxes->mbxs); $i++) {
        $j++;
        ListAdvancedBoxes($boxes->mbxs[$i],$mbx,$j);
    }
    if (isset($boxes->mbxs[0]) && !$boxes->is_root) {
        echo '</div>'."\n\n";
    }
}




/* -------------------- MAIN ------------------------ */

/* get globals */
sqgetGlobalVar('username', $username, SQ_SESSION);
sqgetGlobalVar('key', $key, SQ_COOKIE);
sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);
sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);

sqgetGlobalVar('fold', $fold, SQ_GET);
sqgetGlobalVar('unfold', $unfold, SQ_GET);

/* end globals */

// open a connection on the imap port (143)
// why hide the output?
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, true);

/**
 * Using stristr since very old preferences may contain "None" and "none".
 */
if (!empty($left_refresh) &&
    !stristr($left_refresh, 'none')){
    $xtra =  "\n<meta http-equiv=\"Expires\" content=\"Thu, 01 Dec 1994 16:00:00 GMT\" />\n" .
             "<meta http-equiv=\"Pragma\" content=\"no-cache\" />\n".
             "<meta http-equiv=\"REFRESH\" content=\"$left_refresh;URL=left_main.php\" />\n";
} else {
    $xtra = '';
}

/**
 * $advanced_tree and is a boolean var which is default set to default
 * SM behaviour.
 * Setting $advanced tree to true causes SM to display a experimental
 * mailbox-tree with dhtml behaviour.
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
 **/

/* set this to true if you want to see a nicer mailboxtree */
if (empty($advanced_tree)) {
    $advanced_tree=false;
}

if ($advanced_tree) {
$xtra .= <<<HEREDOC
<script language="Javascript" type="text/javascript">
<!--
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
HEREDOC;
   if ($location_of_bar == 'right') {
       $xtra .= '     right=true;';
   } else {
       $xtra .= '     right=false;';
   }
$xtra .= <<<HEREDOC
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
HEREDOC;
   if ($location_of_bar == 'right') {
       $xtra .= '  colPat=/^\*,(\d+)$/;';
   } else {
       $xtra .= '  colPat=/^(\d+),.*$/;';
   }
$xtra .= <<<HEREDOC
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

HEREDOC;

/* style definitions */

$xtra .= <<<HEREDOC
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
     border-color: $color[9];
     background:$color[0];
     color:$color[6];
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
      background-color: $color[0];
      display: inline;
  }
  a:hover.mbx_link {
      background-color: $color[9];
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
     border-left-color:$color[9];
     border-bottom: solid;
     border-bottom-width:0.1em;
     border-bottom-color:$color[9];
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
     border-color:$color[9];
     background: $color[0];
     font-size: smaller;
  }
-->
</style>

HEREDOC;
}

// get mailbox list and cache it
$mailboxes=sqimap_get_mailboxes($imapConnection,false,$show_only_subscribed_folders);

displayHtmlHeader( 'SquirrelMail', $xtra );

sqgetGlobalVar('auto_create_done',$auto_create_done,SQ_SESSION);
/* If requested and not yet complete, attempt to autocreate folders. */
if ($auto_create_special && !isset($auto_create_done)) {
    $autocreate = array($sent_folder, $trash_folder, $draft_folder);
    foreach( $autocreate as $folder ) {
        if (($folder != '') && ($folder != 'none')) {
            // use $mailboxes array for checking if mailbox exists
            if ( !sqimap_mailbox_exists($imapConnection, $folder, $mailboxes)) {
                sqimap_mailbox_create($imapConnection, $folder, '');
            } else {
                // check for subscription is useless and expensive, just
                // surpress the NO response. Unless we're on Mecury, which
                // will just subscribe a folder again if it's already
                // subscribed.
                if ( strtolower($imap_server_type) != 'mercury32' ||
                    !sqimap_mailbox_is_subscribed($imapConnection, $folder) ) {
                    sqimap_subscribe($imapConnection, $folder, false);
                }
            }
        }
    }

    /* Let the world know that autocreation is complete! Hurrah! */
    $auto_create_done = TRUE;
    sqsession_register($auto_create_done, 'auto_create_done');
}

if ($advanced_tree) {
    echo "\n<body" .
            ' onload="preload(\'../images/minus.png\',\'../images/plus.png\')"' .
            " class=\"leftmain\">\n<div class=\"leftmain\">\n";
} else {
    echo "\n<body class=\"leftmain\">\n<div class=\"leftmain\">\n";
}

do_hook('left_main_before');
if ($advanced_tree) {
   /* nice future feature, needs layout !! volunteers?   */
   $right_pos = $left_size - 20;
/*   echo '<div style="position:absolute;top:0;border=solid;border-width:0.1em;border-color:blue;"><div id="hidef" style="width=20;font-size:12"><a href="javascript:hideframe(true)"><b>&lt;&lt;</b></a></div>';
   echo '<div id="showf" style="width=20;font-size:12;display:none;"><a href="javascript:hideframe(false)"><b>&gt;&gt;</b></a></div>';
   echo '<div id="incrf" style="width=20;font-size:12"><a href="javascript:resizeframe(true)"><b>&gt;</b></a></div>';
   echo '<div id="decrf" style="width=20;font-size:12"><a href="javascript:resizeframe(false)"><b>&lt;</b></a></div></div>';
   echo '<div id="leftframe"><br /><br />';*/
}

echo "\n\n" . html_tag( 'table', '', 'left', '', 'border="0" cellspacing="0" cellpadding="0" width="99%"' ) .
    html_tag( 'tr' ) .
    html_tag( 'td', '', 'left' ) .
    html_tag( 'table', '', '', '', 'border="0" cellspacing="0" cellpadding="0"' ) .
    html_tag( 'tr' ) .
    html_tag( 'td', '', 'center' ) .
    '<font size="4"><b>'. _("Folders") . "</b><br /></font>\n\n";

if ($date_format != 6) {
    /* First, display the clock. */
    if ($hour_format == 1) {
        $hr = 'H:i';
        if ($date_format == 4) {
            $hr .= ':s';
        }
    } else {
        if ($date_format == 4) {
            $hr = 'g:i:s a';
        } else {
            $hr = 'g:i a';
        }
    }

    switch( $date_format ) {
    case 0:
        $clk = date('Y-m-d '.$hr. ' T', time());
        break;
    case 1:
        $clk = date('m/d/y '.$hr, time());
        break;
    case 2:
        $clk = date('d/m/y '.$hr, time());
        break;
    case 4:
    case 5:
        $clk = date($hr, time());
        break;
    default:
        $clk = getDayAbrv( date( 'w', time() ) ) . date( ', ' . $hr, time() );
    }
    $clk = str_replace(' ','&nbsp;',$clk);

    echo '<small><span style="white-space: nowrap;">'
       . str_replace(' ', '&nbsp;', _("Last Refresh"))
       . ":</span><br /><span style=\"white-space: nowrap;\">$clk</span></small><br />\n";
}

/* Next, display the refresh button. */
echo '<div style="white-space: nowrap;"><small>[<a href="../src/left_main.php" target="left">'.
     _("Check mail") . "</a>]</small></div></td></tr></table><br />\n\n";

/* Lastly, display the folder list. */

if ( $collapse_folders ) {
    /* If directed, collapse or uncollapse a folder. */
    if (isset($fold)) {
        setPref($data_dir, $username, 'collapse_folder_' . $fold, SM_BOX_COLLAPSED);
    } else if (isset($unfold)) {
        setPref($data_dir, $username, 'collapse_folder_' . $unfold, SM_BOX_UNCOLLAPSED);
    }
}

/* Get unseen/total display prefs */
$unseen_type = getPref( $data_dir , $username , 'unseen_type' );
$unseen_notify = getPref( $data_dir , $username , 'unseen_notify' );

if (empty($unseen_type)) {
    if (!empty($default_unseen_type)) {
        $unseen_type = $default_unseen_type;
    } else {
        $unseen_type = 1;
    }
}

if (empty($unseen_notify)) {
    if (!empty($default_unseen_notify)) {
        $unseen_notify = $default_unseen_notify;
    } else {
        $unseen_notify = 0;
    }
}
/**
 * pass $mailboxes now instead of $imapconnection - sqimap_get_mailboxes() has been separated from
 * sqimap_mailbox_tree() so that the cached mailbox list can be used elsewhere in left_main and beyond
 */
$boxes = sqimap_mailbox_tree($imapConnection,$mailboxes,$show_only_subscribed_folders);

if (isset($advanced_tree) && $advanced_tree) {
    echo '<form name="collapse" action="left_main.php" method="post" ' .
         'enctype="multipart/form-data">'."\n";
    echo '<button type="submit" class="button" onmouseover="buttonover(this,true)" onmouseout="buttonover(this,false)" onmousedown="buttonclick(this,true)" onmouseup="buttonclick(this,false)">'. _("Save folder tree") .'</button><br /><br />';
    echo '<div id="mailboxes" class="mailboxes">'."\n\n";
    sqgetGlobalVar('mbx', $mbx, SQ_POST);
    if (!isset($mbx)) $mbx=NULL;
    ListAdvancedBoxes($boxes, $mbx);
    echo '</div>';
    echo '</form>'."\n";
} else {
    ListBoxes($boxes);
}

do_hook('left_main_after');
sqimap_logout($imapConnection);

?>
</td></tr></table>
</div></body></html>