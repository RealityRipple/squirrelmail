<?php

/**
 * page_header.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Prints the page header (duh)
 *
 * $Id$
 */

require_once('../functions/strings.php');
require_once('../functions/imap_utf7_decode_local.php');
require_once('../functions/html.php');
require_once('../class/html.class.php');

/* Always set up the language before calling these functions */
function displayHtmlHeader( $title = 'SquirrelMail', $xtra = '', $do_hook = TRUE ) {

    global $theme_css, $custom_css, $base_uri;

    echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">' .
         "\n\n<HTML>\n<HEAD>\n";

    if ( !isset( $custom_css ) || $custom_css == 'none' ) {
        if ($theme_css != '') {
            echo "<LINK REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"$theme_css\">";
        }
    } else {
        echo '<LINK REL="stylesheet" TYPE="text/css" HREF="' .
             $base_uri . 'themes/css/'.$custom_css.'">';
    }
    
//    if ($do_hook) {
//       do_hook("generic_header");
//    }
    
    echo "\n<title>$title</title>$xtra</head>\n\n";
}

function initPage () {
    $page = new html();
    $page->addChild('','<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">');
    $page->addChild('HTML');
    return $page;
}

function initHead ($title = 'SquirrelMail', $session=false) {
   global $use_css, $compose_new_win, $compose_width, $compose_height, 
          $base_uri;

    if ($session != false) {
	$compose_uri = 'src/compose.php?mailbox='. urlencode($mailbox).'&attachedmessages=true&session='."$session";
    } else {
        $compose_uri = 'src/compose.php?newmessage=1';
	$session = 0;
    }


   $head = new html('head');
   $head->addChild('title', $title);
//   if ($use_css) {
//      $head->addChild('link','','','','',
//          array('REL' => 'stylesheet', 'TYPE' => 'text/css',
//	         'HREF' => $base_uri .'/css/read_body.css'));
// }
   if ($compose_new_win == '1') {
      if (!preg_match("/^[0-9]{3,4}$/", $compose_width)) {
          $compose_width = '640';
      }
      if (!preg_match("/^[0-9]{3,4}$/", $compose_height)) {
          $compose_height = '550';
      }
      $js = "function comp_in_new(comp_uri) {\n".
		     "       if (comp_uri =='') {\n".
		     '           comp_uri = "'.$base_uri.$compose_uri."\";\n".
		     '       }'. "\n".
                     '    var newwin = window.open(comp_uri' .
                     ', "_blank",'.
                     '"width='.$compose_width. ',height='.$compose_height.
                     '",scrollbars="yes",resizable="yes");'."\n".
                     "}\n\n";


      $head->scriptAdd($js);
      
      $js = 'function sendMDN() {'."\n".
              "mdnuri=window.location+'&sendreceipt=1';".
              "var newwin = window.open(mdnuri,'right');".
	     "\n}\n\n";
      $head->scriptAdd($js);	     	    
   }	     
   return $head;
}

function initBody($color, $javascript='') {
   global $use_css;
   
   if (!$use_css) {
      $body = new html('body','','','','',
        array('bgcolor' => $color[4], 'text' => $color[8], 'link' => $color[7],
	      'vlink' => $color[7], 'alink' => $color[7]), $javascript);
   } else {
      $body = new html('body','','','','','',$javascript);
   }
   return $body;
}


function getTop($color, $mailbox) {

   global $use_css, $languages, $squirrelmail_language, $frame_top, 
          $delimiter, $base_uri;

   if ( isset( $languages[$squirrelmail_language]['DIR']) ) {
       $dir = $languages[$squirrelmail_language]['DIR'];
   } else {
       $dir = 'ltr';
   }

   if ( $dir == 'ltr' ) {
       $rgt = 'right';
       $lft = 'left';
   } else {
       $rgt = 'left';
       $lft = 'right';
   }



   if (!$use_css) {
      $tbl_ar = array('bgcolor' => $color[4], 'border' => 0, 'width' => '100%',
                    'cellspacing' => 0, 'cellpadding' => 2);
      $row_ar = array('bgcolor' => $color[9]);
      $col_ar = array('align' => $lft);
   } else {
      $tbl_ar = array('border' => 0,
                    'cellspacing' => 0, 'cellpadding' => 2);
      $row_ar = '';
      $col_ar = array('align' => $lft);   		    
   }

    $shortBoxName = imap_utf7_decode_local(
		      readShortMailboxName($mailbox, $delimiter));
    if ( $shortBoxName == 'INBOX' ) {
        $shortBoxName = _("INBOX");
    }

   $top = new html('table','','','tp','tp',$tbl_ar);
   $row  = new html('tr','','','tp_r','tp_r',$row_ar);

   if ( $shortBoxName <> '' && strtolower( $shortBoxName ) <> 'none' ) {
        $row->addChild('td',_("Current Folder") .':','','tp_c','tp_mbx_k', 
	                array('align' => $lft));
	$row->addChild('td',$shortBoxName .'&nbsp;',array('b'=>true),'tp_c',
	               'tp_mbx_v',array('align' => $lft));
   }
   $col = new html('td','','','tp_c','tp_so',array('align' => $rgt));
   if ($frame_top) {
      $lnk_ar = array('href' => $base_uri.'src/signout.php', 'target' => $frame_top);
   } else {
      $lnk_ar = array('href' => $base_uri.'src/signout.php');
   }
   $col->addChild('a', _("Sign Out"),array('b'=>true),'','',$lnk_ar);
   $row->htmlAdd($col);
   $top->htmlAdd($row);
   
   return $top;
}

function getMenu($color,$mailbox) {
   global $use_css, $languages, $squirrelmail_language, $frame_top, $base_uri,
          $compose_new_win,$hide_sm_attributions;

   $urlMailbox = urlencode($mailbox);
   
   if ( isset( $languages[$squirrelmail_language]['DIR']) ) {
       $dir = $languages[$squirrelmail_language]['DIR'];
   } else {
       $dir = 'ltr';
   }

   if ( $dir == 'ltr' ) {
       $rgt = 'right';
       $lft = 'left';
   } else {
       $rgt = 'left';
       $lft = 'right';
   }

   if (!$use_css) {
      $tbl_ar = array('bgcolor' => $color[4], 'border' => 0, 'width' => '100%',
                    'cellspacing' => 0, 'cellpadding' => 2);
      $row_ar = array('bgcolor' => $color[4]);
      $col_ar = array('align' => $lft);
   } else {
      $tbl_ar = array('border' => 0,
                    'cellspacing' => 0, 'cellpadding' => 2);
      $row_ar = '';
      $col_ar = array('align' => $lft);   		    
   }
   
   $menu = new html('table','','','mn','mn',$tbl_ar);
   $row  = new html('tr','','','mn','mn_r',$row_ar);
   $col  = new html('td','','','mn','mn_c',$col_ar);
   $delimiter = new html('','&nbsp;&nbsp;');
   if ($compose_new_win == '1') {
      $col->addChild('a',_("Compose"),'','mn_c','mn_co',
         array('href' => 'javascript:void(0)'),
	 array('onclick'=> 'comp_in_new()'));
   } else {
      $col->addChild('a',_("Compose"),'','mn_c','mn_co',
         array('href' => $base_uri.'src/compose.php?mailbox='.$urlMailbox,
	       'target' => 'right'));
   }
   $col->htmlAdd($delimiter);
   $col->addChild('a',_("Addresses"),'','mn_c','mn_ad',
         array('href' => $base_uri.'src/addressbook.php',
	       'target' => 'right'));
   $col->htmlAdd($delimiter);
   $col->addChild('a',_("Folders"),'','mn_c','mn_fo',
         array('href' => $base_uri.'src/folders.php',
	       'target' => 'right'));
   $col->htmlAdd($delimiter);

   $col->addChild('a',_("Options"),'','mn_c','mn_op',
         array('href' => $base_uri.'src/options.php',
	       'target' => 'right'));
   $col->htmlAdd($delimiter);
   $col->addChild('a',_("Search"),'','mn_c','mn_se',
         array('href' => $base_uri.'src/search.php?mailbox='.$urlMailbox,
	       'target' => 'right'));
   $col->htmlAdd($delimiter);
   $col->addChild('a',_("Help"),'','mn_c','mn_he',
         array('href' => $base_uri.'src/help.php',
	       'target' => 'right'));
	       
   do_hook("menuline");
   $row->htmlAdd($col);
   if (!$hide_sm_attributions) {
       $col  = new html('td','','','mn','mn_sm',array('align'=>$rgt));
       $col->addChild('a','SquirrelMail','','','',
           array('href'=>'http://www.squirrelmail.org','target'=>'_blank'));
       $row->htmlAdd($col);
   }
   $menu->htmlAdd($row);
   return $menu;	   
}

function displayInternalLink($path, $text, $target='') {
    global $base_uri;

    if ($target != '') {
        $target = " target=\"$target\"";
    }
    echo '<a href="'.$base_uri.$path.'"'.$target.'>'.$text.'</a>';
}

function displayPageHeader($color, $mailbox, $xtra='', $session=false) {

    global $delimiter, $hide_sm_attributions, $base_uri, $PHP_SELF, $frame_top,
           $compose_new_win, $username, $datadir, $compose_width, $compose_height,
           $attachemessages, $session;

    $module = substr( $PHP_SELF, ( strlen( $PHP_SELF ) - strlen( $base_uri ) ) * -1 );
    if ($qmark = strpos($module, '?')) {
        $module = substr($module, 0, $qmark);
    }
    if (!isset($frame_top)) {
        $frame_top = '_top';
    }

    /*
        Locate the first displayable form element
    */

    if ($session != false) {
	$compose_uri = 'src/compose.php?mailbox='. urlencode($mailbox).'&attachedmessages=true&session='."$session";
    } else {
        $compose_uri = 'src/compose.php?newmessage=1';
	$session = 0;
    }
   
    switch ( $module ) {
    case 'src/read_body.php':
            if ($compose_new_win == '1') {
                if (!preg_match("/^[0-9]{3,4}$/", $compose_width)) {
                    $compose_width = '640';
                }
                if (!preg_match("/^[0-9]{3,4}$/", $compose_height)) {
                    $compose_height = '550';
                }
                $js = "\n".'<script language="JavaScript" type="text/javascript">' .
                    "\n<!--\n";
                $js .= "function comp_in_new(new_mes, comp_uri) {\n".
		     '    if (new_mes) { '."\n".
		     "       comp_uri = \"".$base_uri."src/compose.php?newmessage=1\";\n".
		     '    } else { '."\n".
		     "       if (comp_uri =='') {\n".
		     '           comp_uri = "'.$base_uri.$compose_uri."\";\n".
		     '       }'. "\n".
		     '    }'. "\n".
                     '    var newwin = window.open(comp_uri' .
                     ', "_blank",
                "width='.$compose_width.",height=$compose_height".
                     ",scrollbars=yes,resizable=yes\");\n".
                     "}\n";

        $js .= "// -->\n".
        	 "</script>\n";
        displayHtmlHeader ('Squirrelmail', $js);
            }
        displayHtmlHeader();
        $onload = $xtra;
        break;
    case 'src/compose.php':
        $js = '<script language="JavaScript" type="text/javascript">' .
             "\n<!--\n" .
             "function checkForm() {\n".
                "var f = document.forms.length;\n".
                "var i = 0;\n".
                "var pos = -1;\n".
                "while( pos == -1 && i < f ) {\n".
                    "var e = document.forms[i].elements.length;\n".
                    "var j = 0;\n".
                    "while( pos == -1 && j < e ) {\n".
                        "if ( document.forms[i].elements[j].type == 'text' ) {\n".
                            "pos = j;\n".
                        "}\n".
                        "j++;\n".
                    "}\n".
                "i++;\n".
                "}\n".
                "if( pos >= 0 ) {\n".
                    "document.forms[i-1].elements[pos].focus();\n".
                "}\n".
            "}\n";
	    
        $js .= "// -->\n".
        	 "</script>\n";
        $onload = "onLoad=\"checkForm();\"";
        displayHtmlHeader ('Squirrelmail', $js);
        break;   

    default:
        $js = '<script language="JavaScript" type="text/javascript">' .
             "\n<!--\n" .
             "function checkForm() {\n".
                "var f = document.forms.length;\n".
                "var i = 0;\n".
                "var pos = -1;\n".
                "while( pos == -1 && i < f ) {\n".
                    "var e = document.forms[i].elements.length;\n".
                    "var j = 0;\n".
                    "while( pos == -1 && j < e ) {\n".
                        "if ( document.forms[i].elements[j].type == 'text' ) {\n".
                            "pos = j;\n".
                        "}\n".
                        "j++;\n".
                    "}\n".
                "i++;\n".
                "}\n".
                "if( pos >= 0 ) {\n".
                    "document.forms[i-1].elements[pos].focus();\n".
                "}\n".
		"$xtra\n".
            "}\n";
	    
            if ($compose_new_win == '1') {
                if (!preg_match("/^[0-9]{3,4}$/", $compose_width)) {
                    $compose_width = '640';
                }
                if (!preg_match("/^[0-9]{3,4}$/", $compose_height)) {
                    $compose_height = '550';
                }
                $js .= "function comp_in_new(new_mes, comp_uri) {\n".
		     '    if (new_mes) { '."\n".
		     "       comp_uri = \"".$base_uri."src/compose.php?newmessage=1\";\n".
		     '    } else { '."\n".
		     "       if (comp_uri =='') {\n".
		     '           comp_uri = "'.$base_uri.$compose_uri."\";\n".
		     '       }'. "\n".
		     '    }'. "\n".
                     '    var newwin = window.open(comp_uri' .
                     ', "_blank",
                "width='.$compose_width.",height=$compose_height".
                     ",scrollbars=yes,resizable=yes\");\n".
                     "}\n";
            }
        $js .= "// -->\n".
        	 "</script>\n";
        $onload = "onLoad=\"checkForm();\"";
        displayHtmlHeader ('Squirrelmail', $js);
        break;   

    }

    echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\" $onload>\n\n";
    /** Here is the header and wrapping table **/
    $shortBoxName = imap_utf7_decode_local(
		      readShortMailboxName($mailbox, $delimiter));
    if ( $shortBoxName == 'INBOX' ) {
        $shortBoxName = _("INBOX");
    }
    echo "<a name=\"pagetop\"></a>\n"
        . html_tag( 'table', '', '', $color[4], 'border="0" width="100%" cellspacing="0" cellpadding="2"' ) ."\n"
        . html_tag( 'tr', '', '', $color[9] ) ."\n"
        . html_tag( 'td', '', 'left' ) ."\n";
    if ( $shortBoxName <> '' && strtolower( $shortBoxName ) <> 'none' ) {
        echo '         ' . _("Current Folder") . ": <b>$shortBoxName&nbsp;</b>\n";
    } else {
        echo '&nbsp;';
    }
    echo  "      </td>\n"
        . html_tag( 'td', '', 'right' ) ."<b>\n";
    displayInternalLink ('src/signout.php', _("Sign Out"), $frame_top);
    echo "</b></td>\n"
        . "   </tr>\n"
        . html_tag( 'tr', '', '', $color[4] ) ."\n"
        . html_tag( 'td', '', 'left' ) ."\n";
    $urlMailbox = urlencode($mailbox);
    if ($compose_new_win == '1') {
        echo "<a href=\"javascript:void(0)\" onclick=\"comp_in_new(true,'')\">". _("Compose"). '</a>';
    }
    else {
        displayInternalLink ("src/compose.php?mailbox=$urlMailbox", _("Compose"), 'right');
    } 
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ("src/addressbook.php", _("Addresses"), 'right');
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ("src/folders.php", _("Folders"), 'right');
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ("src/options.php", _("Options"), 'right');
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ("src/search.php?mailbox=$urlMailbox", _("Search"), 'right');
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ("src/help.php", _("Help"), 'right');
    echo "&nbsp;&nbsp;\n";

    do_hook("menuline");

    echo "      </td>\n"
        . html_tag( 'td', '', 'right' ) ."\n";
    echo ($hide_sm_attributions ? '&nbsp;' :
            '<a href="http://www.squirrelmail.org/" target="_blank">SquirrelMail</a>');
    echo "</td>\n".
        "   </tr>\n".
        "</table>\n\n";
}

/* blatently copied/truncated/modified from the above function */
function compose_Header($color, $mailbox) {

    global $delimiter, $hide_sm_attributions, $base_uri, $PHP_SELF, $frame_top, $compose_new_win;


    $module = substr( $PHP_SELF, ( strlen( $PHP_SELF ) - strlen( $base_uri ) ) * -1 );
    if (!isset($frame_top)) {
        $frame_top = '_top';
    }

    /*
        Locate the first displayable form element
    */
    switch ( $module ) {
    case 'src/search.php':
        $pos = getPref($data_dir, $username, 'search_pos', 0 ) - 1;
        $onload = "onLoad=\"document.forms[$pos].elements[2].focus();\"";
        displayHtmlHeader (_("Compose"));
        break;
    default:
        $js = '<script language="JavaScript" type="text/javascript">' .
             "\n<!--\n" .
             "function checkForm() {\n".
                "var f = document.forms.length;\n".
                "var i = 0;\n".
                "var pos = -1;\n".
                "while( pos == -1 && i < f ) {\n".
                    "var e = document.forms[i].elements.length;\n".
                    "var j = 0;\n".
                    "while( pos == -1 && j < e ) {\n".
                        "if ( document.forms[i].elements[j].type == 'text' ) {\n".
                            "pos = j;\n".
                        "}\n".
                        "j++;\n".
                    "}\n".
                "i++;\n".
                "}\n".
                "if( pos >= 0 ) {\n".
                    "document.forms[i-1].elements[pos].focus();\n".
                "}\n".
            "}\n";
        $js .= "// -->\n".
        	 "</script>\n";
        $onload = "onLoad=\"checkForm();\"";
        displayHtmlHeader (_("Compose"), $js);
        break;   

    }

    echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\" $onload>\n\n";
}
?>
