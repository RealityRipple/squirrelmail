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
    $line .= "<A HREF=\"right_main.php?PG_SHOWALL=0&amp;sort=0&amp;startMessage=1&amp;mailbox=$mailboxURL\" TARGET=\"right\" STYLE=\"text-decoration:none\">";
    if ($special_color) {
        $line .= "<FONT COLOR=\"$color[11]\">";
    }
    if ( $mailbox == 'INBOX' ) {
        $line .= _("INBOX");
    } else {
        $line .= str_replace(' ','&nbsp;',$mailbox);
    }
    if ($special_color == TRUE)
        $line .= "</FONT>";
    $line .= '</A>';

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
		$pre .= "<A HREF=\"right_main.php?PG_SHOWALL=0&amp;sort=0&amp;startMessage=1&amp;mailbox=$mailboxURL\" TARGET=\"right\" STYLE=\"text-decoration:none\">";
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

function ListAdvancedBoxes ($boxes, $mbx, $j='ID.0' ) {
    global $data_dir, $username, $startmessage, $color, $unseen_notify, $unseen_type,
    $move_to_trash, $trash_folder, $collapse_folders;

    /* use_folder_images only works if the images exist in ../images */
    $use_folder_images = false;

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

	if (($move_to_trash) && ($mailbox == $trash_folder)) {
    	    if (! isset($numMessages)) {
        	$numMessages = $boxes->total;
    	    }
    	    if ($numMessages > 0) {
        	$urlMailbox = urlencode($mailbox);
        	$pre .= "\n<small>\n" .
                	"&nbsp;&nbsp;(<A HREF=\"empty_trash.php\" style=\"text-decoration:none\">"._("purge")."</A>)" .
                	"</small>";
    	    }
	} else {
	    if (!$boxes->is_noselect) { /* \Noselect boxes can't be selected */
		$pre .= "<A HREF=\"right_main.php?PG_SHOWALL=0&amp;sort=0&amp;startMessage=1&amp;mailbox=$mailboxURL\" TARGET=\"right\" STYLE=\"text-decoration:none\">";
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

	/* color special boxes */
	if ($boxes->is_special) {
    	    $font = "<FONT COLOR=\"$color[11]\">";
	    $fontend = "</FONT>";    
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
	      $folder_img = '&nbsp<img src="'.$folder_img.'" heigth="15" valign="center">&nbsp';
	    } else $folder_img = '';
	    if (!isset($boxes->mbxs[0])) {
		echo '   <div class="mbx_sub" id='.$j. ' onmouseover="changerowcolor(this,true)" onmouseout="changerowcolor(this,false)">' . $folder_img .$pre .$font. $boxes->mailboxname_sub .$fontend . $end. '</div>'."\n";
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
    			$link = '<a href="javascript:hidechilds(this)">'." <img src=\"../images/plus.gif\" border=\"1\" id=$j onclick=\"hidechilds(this)\"></A>";
		    } else {
    			$link = '<a href="javascript:hidechilds(this)">'."<img src=\"../images/minus.gif\" border=\"1\" id=$j onclick=\"hidechilds(this)\"></a>";
		    }
		    $collapse_link = $link;
		} else $collapse_link='';
		echo '   <div class="mbx_par" id='.$j. 'P onmouseover="changerowcolor(this,true)" onmouseout="changerowcolor(this,false)">' . $collapse_link . $folder_img .$pre.  $font. '&nbsp '. $boxes->mailboxname_sub .$fontend . $end. '</div>'."\n";
		echo '   <INPUT TYPE="hidden" name=mbx['.$j. 'F] value="'.$collapse.'">'."\n";
	    }
	}
	if ($collapse) {
	    $visible = ' STYLE="display:none;"';
	} else {
	    $visible = ' STYLE="display:inline;"';
	}

	if (isset($boxes->mbxs[0]) && !$boxes->is_root) /* mailbox contains childs */
	    echo '<div class="par_area" id='.$j.'.0 '. $visible .'>'."\n"; 
	    if ($j !='ID.0') {
	       $j = $j .'.0';
	}
    	for ($i = 0; $i <count($boxes->mbxs); $i++) {
	    $j++;
    	    listAdvancedBoxes($boxes->mbxs[$i],$mbx,$j);
    	}
	if (isset($boxes->mbxs[0]) && !$boxes->is_root ) echo '</div>'."\n\n";
    }
}




/* -------------------- MAIN ------------------------ */

global $delimiter, $default_folder_prefix;

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
<script language="Javascript">

<!--'

    function hidechilds(el) {
    	id = el.id +".0";
        form_id = "mbx[" + el.id +"F]";
	if (document.all) {
	    ele = document.all[id];
	    if(ele.style.display == 'none') {
               ele.style.display = 'inline';
               document.all[el.id].src="../images/minus.gif";
               document.all[form_id].value=0;
            } else {
               ele.style.display = 'none';
	       document.all[el.id].src="../images/plus.gif";
	       document.all[form_id].value=1;
	    }
	} else if (document.getElementById) {
	    id = el.id+".0";
            ele = document.getElementById(id);
	    img_ele = document.getElementById(el.id);
	    if(ele.style.display == 'none') {
	       ele.style.display = 'inline';
	       img_ele.src="../images/minus.gif";
               document.getElementById(form_id).value=0;
	    } else {
	       ele.style.display = 'none';
	       img_ele.src="../images/plus.gif";
               document.getElementById(form_id).value=1;
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
	    	  
   function changerowcolor(el,on) {
      id = el.id;
      if (document.all) {
         if (!on) {  document.all[id].style.background="#FFFFFF"; }
         else {  document.all[id].style.background="#AAAAAA"; }
      } else if (document.getElementById) {
         if (!on) {  document.getElementById(id).style.background="#FFFFFF"; }
         else {  document.getElementById(id).style.background="#AAAAAA"; }
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
-->
   
</script>

ECHO;

/* style definitions */

$xtra .= <<<ECHO

<STYLE>
<!--
  .button {
     border:outset;
     border-color:blue;
     background-color:white;
     width:99%;
     heigth:99%;
  }

  .mbx_par {
     width:99%;
     heigth:99%;
     font-size:0.8em;
  }

  .mbx_sub {
     width:99%;
     heigth:99%;
     padding-left:5px;
     margin-left:4px;
     font-size:0.7em;
  }

  .par_area {
     margin-left:5px;
     margin-right:5px;
     margin-bottom:5px;
     width:99%;
     heigth:99%;
     padding-left:10px;
     padding-bottom:10px;
     border-left: solid;
     border-left-width:0.1em;
     border-left-color:blue;
     border-bottom: solid;
     border-bottom-width:0.1em;
     border-bottom-color:blue;
     display:inline;
  }

  .mailboxes {
     padding-right:0.1em;
     padding-bottom:3px;
     width:99%;
     heigth:99%;
     border: groove;
     border-width:0.1em;
     border-color:green;
     background-color:white;
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


echo '<CENTER><FONT SIZE=4><B>'. _("Folders") . "</B><BR></FONT>\n\n";

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

    echo '<CENTER><SMALL>' . str_replace(' ','&nbsp;',_("Last Refresh")) .
         ": $clk</SMALL></CENTER>";
}

/* Next, display the refresh button. */
echo '<SMALL>(<A HREF="../src/left_main.php" TARGET="left">'.
     _("refresh folder list") . '</A>)</SMALL></CENTER><BR>';

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

echo "</BODY></HTML>\n";

?>
