<?php

/**
 * left_main.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This is the code for the left bar. The left bar shows the folders
 * available, and has cookie information.
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/array.php');
require_once('../functions/imap.php');
require_once('../functions/plugin.php');
require_once('../functions/page_header.php');
require_once('../functions/html.php');

/* These constants are used for folder stuff. */
define('SM_BOX_UNCOLLAPSED', 0);
define('SM_BOX_COLLAPSED',   1);

/* --------------------- FUNCTIONS ------------------------- */

function formatMailboxName($imapConnection, $box_array) {

    global $folder_prefix, $trash_folder, $sent_folder,
           $color, $move_to_sent, $move_to_trash,
           $unseen_notify, $unseen_type, $collapse_folders,
           $draft_folder, $save_as_draft,
           $use_special_folder_color;

    $real_box = $box_array['unformatted'];
    $mailbox = str_replace('&nbsp;','',$box_array['formatted']);
    $mailboxURL = urlencode($real_box);

    /* Strip down the mailbox name. */
    if (ereg("^( *)([^ ]*)$", $mailbox, $regs)) {
        $mailbox = $regs[2];
    }

    $unseen = 0;

    if (($unseen_notify == 2 && $real_box == 'INBOX') ||
        $unseen_notify == 3) {
        $unseen = sqimap_unseen_messages($imapConnection, $real_box);
        if ($unseen_type == 1 && $unseen > 0) {
            $unseen_string = "($unseen)";
            $unseen_found = TRUE;
        } else if ($unseen_type == 2) {
            $numMessages = sqimap_get_num_messages($imapConnection, $real_box);
            $unseen_string = "<font color=\"$color[11]\">($unseen/$numMessages)</font>";
            $unseen_found = TRUE;
        }
    }

    $special_color = ($use_special_folder_color && isSpecialMailbox( $real_box ) );

    /* Start off with a blank line. */
    $line = '';

    /* If there are unseen message, bold the line. */
    if ($unseen > 0) { $line .= '<B>'; }

    /* Crate the link for this folder. */
    $line .= "<a href=\"right_main.php?PG_SHOWALL=0&amp;sort=0&amp;startMessage=1&amp;mailbox=$mailboxURL\" TARGET=\"right\">";
    if ($special_color) {
        $line .= "<font color=\"$color[11]\">";
    }
    if ( $mailbox == 'INBOX' ) {
        $line .= _("INBOX");
    } else {
        $line .= str_replace(' ','&nbsp;',$mailbox);
    }
    if ($special_color == TRUE)
        $line .= '</font>';
    $line .= '</a>';

    /* If there are unseen message, close bolding. */
    if ($unseen > 0) { $line .= "</B>"; }

    /* Print unseen information. */
    if (isset($unseen_found) && $unseen_found) {
        $line .= "&nbsp;<SMALL>$unseen_string</SMALL>";
    }

    if (($move_to_trash) && ($real_box == $trash_folder)) {
        if (! isset($numMessages)) {
            $numMessages = sqimap_get_num_messages($imapConnection, $real_box);
        }

        if ($numMessages > 0) {
            $urlMailbox = urlencode($real_box);
            $line .= "\n<small>\n" .
                    "&nbsp;&nbsp;(<A HREF=\"empty_trash.php\" style=\"text-decoration:none\">"._("purge")."</A>)" .
                    "</small>";
        }
    }

    /* Return the final product. */
    return ($line);
}

/**
 * Recursive function that computes the collapsed status and parent
 * (or not parent) status of this box, and the visiblity and collapsed
 * status and parent (or not parent) status for all children boxes.
 */
function compute_folder_children(&$parbox, $boxcount) {
    global $boxes, $data_dir, $username, $collapse_folders;
    $nextbox = $parbox + 1;

    /* Retreive the name for the parent box. */
    $parbox_name = $boxes[$parbox]['unformatted'];

    /* 'Initialize' this parent box to childless. */
    $boxes[$parbox]['parent'] = FALSE;

    /* Compute the collapse status for this box. */
    if( isset($collapse_folders) && $collapse_folders ) {
        $collapse = getPref($data_dir, $username, 'collapse_folder_' . $parbox_name);
        $collapse = ($collapse == '' ? SM_BOX_UNCOLLAPSED : $collapse);
    } else {
        $collapse = SM_BOX_UNCOLLAPSED;
    }
    $boxes[$parbox]['collapse'] = $collapse;

    /* Otherwise, get the name of the next box. */
    if (isset($boxes[$nextbox]['unformatted'])) {
        $nextbox_name = $boxes[$nextbox]['unformatted'];
    } else {
        $nextbox_name = '';
    }

    /* Compute any children boxes for this box. */
    while (($nextbox < $boxcount) &&
           (is_parent_box($boxes[$nextbox]['unformatted'], $parbox_name))) {

        /* Note that this 'parent' box has at least one child. */
        $boxes[$parbox]['parent'] = TRUE;

        /* Compute the visiblity of this box. */
        $boxes[$nextbox]['visible'] = ($boxes[$parbox]['visible'] &&
                                       ($boxes[$parbox]['collapse'] != SM_BOX_COLLAPSED));

        /* Compute the visibility of any child boxes. */
        compute_folder_children($nextbox, $boxcount);
    }

    /* Set the parent box to the current next box. */
    $parbox = $nextbox;
}

/**
 * Create the link for a parent folder that will allow that
 * parent folder to either be collapsed or expaned, as is
 * currently appropriate.
 */
function create_collapse_link($boxnum) {
    global $boxes;
    $mailbox = urlencode($boxes[$boxnum]['unformatted']);

    /* Create the link for this collapse link. */
    $link = '<a target="left" style="text-decoration:none" ' .
            'href="left_main.php?';
    if ($boxes[$boxnum]['collapse'] == SM_BOX_COLLAPSED) {
        $link .= "unfold=$mailbox\">+";
    } else {
        $link .= "fold=$mailbox\">-";
    }
    $link .= '</a>';

    /* Return the finished product. */
    return ($link);
}

/**
 * This simple function checks if a box is another box's parent.
 */
function is_parent_box($curbox_name, $parbox_name) {
    global $delimiter;

    /* Extract the name of the parent of the current box. */
    $curparts = explode($delimiter, $curbox_name);
    $curname = array_pop($curparts);
    $actual_parname = implode($delimiter, $curparts);
    $actual_parname = substr($actual_parname,0,strlen($parbox_name));

    /* Compare the actual with the given parent name. */
    return ($parbox_name == $actual_parname);
}

function listBoxes ($boxes, $j=0 ) {
    global $data_dir, $username, $startmessage, $color, $unseen_notify, $unseen_type,
    $move_to_trash, $trash_folder, $collapse_folders;
    $pre = '';
    $end = '';
    $collapse = false;
    if ($boxes) {
	$mailbox = $boxes->mailboxname_full;
	$leader = '';
	for ($k = 0; $k < $j; $k++) {
	    $leader.= '&nbsp&nbsp&nbsp';
	}
	$mailboxURL = urlencode($mailbox);

	/* get unseen/total messages information */
        if ($boxes->unseen) {
	    $unseen = $boxes->unseen;
    	    $unseen_string = "($unseen)";
	    if ($unseen>0) $unseen_found = TRUE;
    	    if ($boxes->total) {
		$numMessages = $boxes->total;
        	$unseen_string = "<font color=\"$color[11]\">($unseen/$numMessages)</font>";
	    }	    
	} else $unseen = 0;



	if (isset($boxes->mbxs[0]) && $collapse_folders) {
	    $collapse = getPref($data_dir, $username, 'collapse_folder_' . $mailbox);
    	    $collapse = ($collapse == '' ? SM_BOX_UNCOLLAPSED : $collapse);

	    $link = '<a target="left" style="text-decoration:none" ' .'href="left_main.php?';
	    if ($collapse) {
    		$link .= "unfold=$mailboxURL\">$leader +&nbsp";
	    } else {
    		$link .= "fold=$mailboxURL\">$leader -&nbsp";
	    }
	    $link .= '</a>';
	    $pre .= $link;
	} else {
	    $pre.= $leader . '&nbsp&nbsp&nbsp';
	}


	/* If there are unseen message, bold the line. */
	if ($unseen > 0) { $pre .= '<B>'; }

	if (($move_to_trash) && ($mailbox == $trash_folder)) {
    	    if (! isset($numMessages)) {
        	$numMessages = sqimap_get_num_messages($imapConnection, $mailbox);
    	    }

    	    if ($numMessages > 0) {
        	$urlMailbox = urlencode($mailbox);
        	$pre .= "\n<small>\n" .
                	"&nbsp;&nbsp;(<A HREF=\"empty_trash.php\" style=\"text-decoration:none\">"._("purge")."</A>)" .
                	"</small>";
    	    }
	} else {
	    if (!$boxes->is_noselect) {
		$pre .= "<a HREF=\"right_main.php?PG_SHOWALL=0&amp;sort=0&amp;startMessage=1&amp;mailbox=$mailboxURL\" TARGET=\"right\">";
		$end .= '</a>';
	    }
	}

	/* If there are unseen message, close bolding. */
	if ($unseen > 0) { $end .= "</B>"; }

	/* Print unseen information. */
	if (isset($unseen_found) && $unseen_found) {
    	    $end .= "&nbsp;<SMALL>$unseen_string</SMALL>";
	}

	$font = '';
	$fontend = '';
	if ($boxes->is_special) {
    	    $font = "<FONT COLOR=\"$color[11]\">";
	    $fontend = "</FONT>";    
	}
	
	if (!$boxes->is_root) { 
	    echo "" . $pre .$font. $boxes->mailboxname_sub .$fontend . $end. '<br>';
	    $j++;
	}
	if (!$collapse || $boxes->is_root) {
    	    for ($i = 0; $i <count($boxes->mbxs); $i++) {
    		listBoxes($boxes->mbxs[$i],$j);
    	    }
	}    
	
    }
}

function ListAdvancedBoxes ($boxes, $mbx, $j='ID.0000' ) {
    global $data_dir, $username, $startmessage, $color, $unseen_notify, $unseen_type,
    $move_to_trash, $trash_folder, $collapse_folders;

    /* use_folder_images only works if the images exist in ../images */
    $use_folder_images = true;

    $pre = '';
    $end = '';
    $collapse = false;
    
    if ($boxes) {
	$mailbox = $boxes->mailboxname_full;
	$mailboxURL = urlencode($mailbox);

	/* get unseen/total messages information */
        if ($boxes->unseen) {
	    $unseen = $boxes->unseen;
    	    $unseen_string = "($unseen)";
	    if ($unseen>0) $unseen_found = TRUE;
    	    if ($boxes->total) {
		$numMessages = $boxes->total;
        	$unseen_string = "<font color=\"$color[11]\">($unseen/$numMessages)</font>";
	    }	    
	} else $unseen = 0;

	/* If there are unseen message, bold the line. */
	if ($unseen > 0) { $pre .= '<B>'; }

	/* color special boxes */
	if ($boxes->is_special) {
    	    $pre .= "<FONT COLOR=\"$color[11]\">";
	    $end .= "</FONT>";    
	}

	/* If there are unseen message, close bolding. */
	if ($unseen > 0) { $end .= "</B>"; }

	/* Print unseen information. */
	if (isset($unseen_found) && $unseen_found) {
    	    $end .= "&nbsp;$unseen_string";
	}

	if (($move_to_trash) && ($mailbox == $trash_folder)) {
    	    if (! isset($numMessages)) {
        	$numMessages = $boxes->total;
    	    }
    	    if ($numMessages > 0) {
        	$urlMailbox = urlencode($mailbox);
        	$pre .= "\n<small>\n" .
                	"&nbsp;&nbsp;(<a class=\"mbx_link\" HREF=\"empty_trash.php\">"._("purge")."</a>)" .
                	"</small>";
    	    }
	} else {
	    if (!$boxes->is_noselect) { /* \Noselect boxes can't be selected */
		$pre .= "<a class=\"mbx_link\" HREF=\"right_main.php?PG_SHOWALL=0&amp;sort=0&amp;startMessage=1&amp;mailbox=$mailboxURL\" TARGET=\"right\">";
		$end .= '</a>';
	    }
	}

	if (!$boxes->is_root) {
	    if ($use_folder_images) {
	      if ($boxes->is_inbox) {
		$folder_img = '../images/inbox.gif';
	      } else if ($boxes->is_sent) {
		$folder_img = '../images/senti.gif';
	      } else if ($boxes->is_trash) {
		$folder_img = '../images/delitem.gif';
	      } else if ($boxes->is_draft) {
		$folder_img = '../images/draft.gif';
	      } else $folder_img = '../images/folder.gif';
	      $folder_img = '&nbsp;<img src="'.$folder_img.'" height="15" valign="center">&nbsp;';
	    } else $folder_img = '';
	    if (!isset($boxes->mbxs[0])) {
	        echo '   ' . html_tag( 'div',
	                        $pre . $folder_img . $boxes->mailboxname_sub . $end ,
	                'left', '', 'class="mbx_sub" id="' .$j. '"' )
		        . "\n";
	    } else {
    		/* get collapse information */
		if ($collapse_folders) {
		    $link = '<a target="left" style="text-decoration:none" ' .'href="left_main.php?';
		    $form_entry = $j.'F';
		    if (isset($mbx) && isset($mbx[$form_entry])) {
		        $collapse = $mbx[$form_entry];
			if ($collapse) {
    			    setPref($data_dir, $username, 'collapse_folder_'.$boxes->mailboxname_full , SM_BOX_COLLAPSED);
			} else {
    			    setPref($data_dir, $username, 'collapse_folder_'.$boxes->mailboxname_full , SM_BOX_UNCOLLAPSED);
			}
		    } else {
			$collapse = getPref($data_dir, $username, 'collapse_folder_' . $mailbox);
    			$collapse = ($collapse == '' ? SM_BOX_UNCOLLAPSED : $collapse);
		    }
		    if ($collapse) {
    			$link = '<a href="javascript:void(0)">'." <img src=\"../images/plus.gif\" border=\"1\" id=$j onclick=\"hidechilds(this)\"></A>";
		    } else {
    			$link = '<a href="javascript:void(0)">'."<img src=\"../images/minus.gif\" border=\"1\" id=$j onclick=\"hidechilds(this)\"></a>";
		    }
		    $collapse_link = $link;
		} else $collapse_link='';
	        echo '   ' . html_tag( 'div',
	                        $collapse_link . $pre . $folder_img . '&nbsp;'. $boxes->mailboxname_sub . $end ,
	                'left', '', 'class="mbx_par" id="' .$j. 'P"' )
		        . "\n";
		echo '   <INPUT TYPE="hidden" name=mbx['.$j. 'F] value="'.$collapse.'" id="mbx['.$j.'F]">'."\n";
	    }
	}
	if ($collapse) {
	    $visible = ' STYLE="display:none;"';
	} else {
	    $visible = ' STYLE="display:block;"';
	}

	if (isset($boxes->mbxs[0]) && !$boxes->is_root) /* mailbox contains childs */
	    echo html_tag( 'div', '', 'left', '', 'class="par_area" id='.$j.'.0000 '. $visible ) . "\n";

	    if ($j !='ID.0000') {
	       $j = $j .'.0000';
	}
    	for ($i = 0; $i <count($boxes->mbxs); $i++) {
	    $j++;
    	    listAdvancedBoxes($boxes->mbxs[$i],$mbx,$j);
    	}
	if (isset($boxes->mbxs[0]) && !$boxes->is_root ) echo '</div>'."\n\n";
    }
}




/* -------------------- MAIN ------------------------ */

global $delimiter, $default_folder_prefix, $left_size;

// open a connection on the imap port (143)
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10); // the 10 is to hide the output

/**
 * Using stristr since older preferences may contain "None" and "none".
 */
if (isset($left_refresh) && ($left_refresh != '') &&
    !stristr($left_refresh, "none")){
    $xtra =  "\n<META HTTP-EQUIV=\"Expires\" CONTENT=\"Thu, 01 Dec 1994 16:00:00 GMT\">\n" .
             "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n".
             "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"$left_refresh;URL=left_main.php\">\n";
} else {
    $xtra = '';
}

/**
 * $advanced_tree and $oldway are boolean vars which are default set to default 
 * SM behaviour. 
 * Setting $oldway to false causes left_main.php to use the new  experimental 
 * way of getting the mailbox-tree.
 * Setting $advanced tree to true causes SM to display a experimental 
 * mailbox-tree with dhtml behaviour.
 * It only works on browsers which supports css and javascript. The used 
 * javascript is experimental and doesn't support all browsers. It is tested on 
 * IE6 an Konquerer 3.0.0-2.
 * In the function ListAdvancedBoxes there is another var $use_folder_images.
 * setting this to true is only usefull if the images exists in ../images.
 * 
 * Feel free to experiment with the code and report bugs and enhancements
 * to marc@its-projects.nl
 **/ 

$advanced_tree = false; /* set this to true if you want to see a nicer mailboxtree */
$oldway = true; /* default SM behaviour */

if ($advanced_tree) {
$xtra .= <<<ECHO
<script language="Javascript" TYPE="text/javascript">

<!--

    function hidechilds(el) {
    	id = el.id+".0000";
        form_id = "mbx[" + el.id +"F]";
	if (document.all) {
	    ele = document.all[id];
	    if (ele) {
	       if(ele.style.display == "none") {
                  ele.style.display = "block";
	          ele.style.visibility = "visible"
                  el.src="../images/minus.gif";
                  document.all[form_id].value=0;
               } else {
                  ele.style.display = "none";
	          ele.style.visibility = "hidden"
	          el.src="../images/plus.gif";
	          document.all[form_id].value=1;
	       }
	    }
	} else if (document.getElementById) {
            ele = document.getElementById(id);
	    if (ele) {
	       if(ele.style.display == "none") {
	          ele.style.display = "block";
	          ele.style.visibility = "visible"
	          el.src="../images/minus.gif";
                  document.getElementById(form_id).value=0;
	       } else {
	          ele.style.display = "none";
	          ele.style.visibility = "hidden"
	          el.src="../images/plus.gif";
                  document.getElementById(form_id).value=1;
	       }
	    }   
	}
   }
   
   function preload() {
     if (!document.images) return;
     var ar = new Array();
     var arguments = preload.arguments;
     for (var i = 0; i<arguments.length; i++) {
        ar[i] = new Image();
	ar[i].src = arguments[i];
     }
   }        
	    	  
   function buttonover(el,on) {
      if (!on) {
         el.style.borderColor="blue";}
      else {
         el.style.borderColor="orange";}
   }

   function buttonclick(el,on) {
      if (!on) { 
         el.style.border="groove"}
      else {
         el.style.border="ridge";}
   }

   function hideframe(hide) {
   
ECHO;
$xtra .= "      left_size = \"$left_size\";\n";
$xtra .= <<<ECHO
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
	 document.body.style.overflow='hidden';
	 leftcontent.style.display = 'none';
	 leftbutton.style.display='block';
      } else {
         masterf.cols = calc_col(left_size);
	 document.body.style.overflow='';
	 leftbutton.style.display='none';
	 leftcontent.style.display='block';
	 
      }
   }
   
   function calc_col(c_w) {

ECHO;
   if ($location_of_bar == 'right') {
       $xtra .= '     right=true;';
   } else {    
       $xtra .= '     right=false;';
   }
   $xtra .= "\n";
$xtra .= <<<ECHO
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
     
ECHO;
   if ($location_of_bar == 'right') {
       $xtra .= '  colPat=/^\*,(\d+)$/;';
   } else {    
       $xtra .= '  colPat=/^(\d+),.*$/;';
   }
   $xtra .= "\n";
  
$xtra .= <<<ECHO
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

ECHO;

/* style definitions */

$xtra .= <<<ECHO

<STYLE TYPE="text/css">
<!--
  body {
     margin: 0px 0px 0px 0px;
     padding: 5px 5px 5px 5px;
  }

  .button {
     border:outset;
     border-color:blue;
     background:white;
     width:99%;
     heigth:99%;
  }

  .mbx_par {
     font-size:0.8em;
     margin-left:4px;
     margin-right:0px;
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
     font-size:0.7em;
  }

  .par_area {
     margin-top:0px;
     margin-left:4px;
     margin-right:0px;
     padding-left:10px;
     padding-bottom:5px;
     border-left: solid;
     border-left-width:0.1em;
     border-left-color:blue;
     border-bottom: solid;
     border-bottom-width:0.1em;
     border-bottom-color:blue;
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
     border-color:green;
     background: $color[0];
  }

-->

</STYLE>

ECHO;

}




displayHtmlHeader( 'SquirrelMail', $xtra );

/* If requested and not yet complete, attempt to autocreate folders. */
if ($auto_create_special && !isset($auto_create_done)) {
    $autocreate = array($sent_folder, $trash_folder, $draft_folder);
    foreach( $autocreate as $folder ) {
        if (($folder != '') && ($folder != 'none')) {
            if ( !sqimap_mailbox_exists($imapConnection, $folder)) {
                sqimap_mailbox_create($imapConnection, $folder, '');
            } else if (!sqimap_mailbox_is_subscribed($imapConnection, $folder)) {
                sqimap_subscribe($imapConnection, $folder);
            }
        }
    }

    /* Let the world know that autocreation is complete! Hurrah! */
    $auto_create_done = TRUE;
    session_register('auto_create_done');
}

echo "\n<BODY BGCOLOR=\"$color[3]\" TEXT=\"$color[6]\" LINK=\"$color[6]\" VLINK=\"$color[6]\" ALINK=\"$color[6]\">\n";

do_hook('left_main_before');
if ($advanced_tree && $advanced_tree_frame_ctrl) {
   /* nice future feature, needs layout !! volunteers?   */  
   $right_pos = $left_size - 20;
   echo '<div style="position:absolute;top:0;border=solid;border-width:0.1em;border-color:blue;"><div ID="hidef" style="width=20;font-size:12"><A HREF="javascript:hideframe(true)"><b><<</b></a></div>';
   echo '<div ID="showf" style="width=20;font-size:12;display:none;"><A HREF="javascript:hideframe(false)"><b>>></b></a></div>';
   echo '<div ID="incrf" style="width=20;font-size:12"><A HREF="javascript:resizeframe(true)"><b>></b></a></div>';
   echo '<div ID="decrf" style="width=20;font-size:12"><A HREF="javascript:resizeframe(false)"><b><</b></a></div></div>';
   echo '<div ID="leftframe"><br><br>';
}

echo "\n\n" . html_tag( 'table', '', '', '', 'border="0" cellspacing="0" cellpadding="0" width="100%"' ) . 
    html_tag( 'tr' ) . 
    html_tag( 'td', '', 'left' ) . 
    '<center><font size="4"><b>'. _("Folders") . "</b><br></font>\n\n";

if ($date_format != 6) {
    /* First, display the clock. */
    if ($hour_format == 1) {
        $hr = 'G:i';
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
        $clk = substr( getDayName( date( 'w', time() ) ), 0, 3 ) . date( ', ' . $hr, time() );
    }
    $clk = str_replace(' ','&nbsp;',$clk);

    echo '<center><small>' . str_replace(' ','&nbsp;',_("Last Refresh")) .
         ": $clk</small></center>";
}

/* Next, display the refresh button. */
echo '<small>(<a href="../src/left_main.php" target="left">'.
     _("refresh folder list") . '</a>)</small></center><br>';

/* Lastly, display the folder list. */
if ( $collapse_folders ) {
    /* If directed, collapse or uncollapse a folder. */
    if (isset($fold)) {
        setPref($data_dir, $username, 'collapse_folder_' . $fold, SM_BOX_COLLAPSED);
    } else if (isset($unfold)) {
        setPref($data_dir, $username, 'collapse_folder_' . $unfold, SM_BOX_UNCOLLAPSED);
    }
}

if ($oldway) {  /* normal behaviour SM */
 
$boxes = sqimap_mailbox_list($imapConnection);
/* Prepare do do out collapsedness and visibility computation. */
$curbox = 0;
$boxcount = count($boxes);

/* Compute the collapsedness and visibility of each box. */

while ($curbox < $boxcount) {
    $boxes[$curbox]['visible'] = TRUE;
    compute_folder_children($curbox, $boxcount);
}


for ($i = 0; $i < count($boxes); $i++) {
    if ( $boxes[$i]['visible'] ) {
        $mailbox = $boxes[$i]['formatted'];
        $mblevel = substr_count($boxes[$i]['unformatted'], $delimiter) + 1;

        /* Create the prefix for the folder name and link. */
        $prefix = str_repeat('  ',$mblevel);
        if (isset($collapse_folders) && $collapse_folders && $boxes[$i]['parent']) {
            $prefix = str_replace(' ','&nbsp;',substr($prefix,0,strlen($prefix)-2)).
                      create_collapse_link($i) . '&nbsp;';
        } else {
            $prefix = str_replace(' ','&nbsp;',$prefix);
        }
        $line = "<NOBR><TT>$prefix</TT>";

        /* Add the folder name and link. */
        if (! isset($color[15])) {
            $color[15] = $color[6];
        }

        if (in_array('noselect', $boxes[$i]['flags'])) {
            if( isSpecialMailbox( $boxes[$i]['unformatted']) ) {
                $line .= "<FONT COLOR=\"$color[11]\">";
            } else {
                $line .= "<FONT COLOR=\"$color[15]\">";
            }
            if (ereg("^( *)([^ ]*)", $mailbox, $regs)) {
                $mailbox = str_replace('&nbsp;','',$mailbox);
                $line .= str_replace(' ', '&nbsp;', $mailbox);
            }
            $line .= '</FONT>';
        } else {
            $line .= formatMailboxName($imapConnection, $boxes[$i]);
        }

        /* Put the final touches on our folder line. */
        $line .= "</NOBR><BR>\n";

        /* Output the line for this folder. */
        echo $line;
    }
}
} else {  /* expiremental code */ 
    $boxes = sqimap_mailbox_tree($imapConnection);
    if (isset($advanced_tree) && $advanced_tree) {
	echo '<FORM name=collapse action="left_main.php" METHOD=POST' .
            'ENCTYPE="multipart/form-data"'."\n";
	echo '<small><button type="submit" class="button" onmouseover="buttonover(this,true)" onmouseout="buttonover(this,false)" onmousedown="buttonclick(this,true)" onmouseup="buttonclick(this,false)">'. _("Save folder tree") .'</button><br><br>';
	echo '<DIV ID=mailboxes CLASS=mailboxes>'."\n\n";
	if (!isset($mbx)) $mbx=NULL; 
	    ListAdvancedBoxes($boxes, $mbx);
	echo '</div></small>'."\n";
	echo '</FORM>'."\n";
    } else {
	ListBoxes($boxes);
    }
} /* if ($oldway) else ... */
do_hook('left_main_after');
sqimap_logout($imapConnection);

echo '</td></tr></table>' . "\n".
    "</div></body></html>\n";

?>
