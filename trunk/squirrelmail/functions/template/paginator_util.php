<?php

/**
 * paginator_util.php
 *
 * The following functions are utility functions for templates. Do not
 * echo output in these functions.
 *
 * @copyright &copy; 2005-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */


/** Load forms functions, needed for addsubmit(). */
include_once(SM_PATH . 'functions/forms.php');


 /**
  * Generate a paginator link.
  *
  * @param string  $box Mailbox name
  * @param integer $start_msg Message Offset
  * @param string  $text text used for paginator link
  * @return string
  */
function get_paginator_link($box, $start_msg, $text) {
    sqgetGlobalVar('PHP_SELF',$php_self,SQ_SERVER);
    return create_hyperlink("$php_self?startMessage=$start_msg&amp;mailbox=$box", $text);
}


/**
 * This function computes the comapact paginator string.
 *
 * @param string  $box           mailbox name
 * @param integer $iOffset       offset in total number of messages
 * @param integer $iTotal        total number of messages
 * @param integer $iLimit        maximum number of messages to show on a page
 * @param bool    $bShowAll      whether or not to show all messages at once 
 *                               ("show all" == non paginate mode)
 * @param bool    $javascript_on whether or not javascript is currently enabled
 * @param bool    $page_selector whether or not to show the page selection widget
 *
 * @return string $result   paginate string with links to pages
 *
 */
function get_compact_paginator_str($box, $iOffset, $iTotal, $iLimit, $bShowAll, $javascript_on, $page_selector) {

    global $oTemplate;

    // keeps count of how many times
    // the paginator is used, avoids
    // duplicate naming of <select> 
    // and GO button
    static $display_iterations = 0; 
    $display_iterations++;

    sqgetGlobalVar('PHP_SELF',$php_self,SQ_SERVER);

    /* Initialize paginator string chunks. */
    $prv_str = '';
    $nxt_str = '';
    $pg_str  = '';
    $all_str = '';

    $box = urlencode($box);

    /* Create simple strings that will be creating the paginator. */
    /* This will be used as a space. */
    $spc = $oTemplate->fetch('non_breaking_space.tpl');
    /* This will be used as a seperator. */
    $sep = '|';

    /* Make sure that our start message number is not too big. */
    $iOffset = min($iOffset, $iTotal);

    /* Compute the starting message of the previous and next page group. */
    $next_grp = $iOffset + $iLimit;
    $prev_grp = $iOffset - $iLimit;

    if (!$bShowAll) {
        /* Compute the basic previous and next strings. */
        if (($next_grp <= $iTotal) && ($prev_grp >= 0)) {
            $prv_str = get_paginator_link($box, $prev_grp, '<');
            $nxt_str = get_paginator_link($box, $next_grp, '>');
        } else if (($next_grp > $iTotal) && ($prev_grp >= 0)) {
            $prv_str = get_paginator_link($box, $prev_grp, '<');
            $nxt_str = '>';
        } else if (($next_grp <= $iTotal) && ($prev_grp < 0)) {
            $prv_str = '<';
            $nxt_str = get_paginator_link($box, $next_grp, '>');
        }

        /* Page selector block. Following code computes page links. */
        if ($iLimit != 0 && $page_selector && ($iTotal > $iLimit)) {
            /* Most importantly, what is the current page!!! */
            $cur_pg = intval($iOffset / $iLimit) + 1;

            /* Compute total # of pages and # of paginator page links. */
            $tot_pgs = ceil($iTotal / $iLimit);  /* Total number of Pages */

            $last_grp = (($tot_pgs - 1) * $iLimit) + 1;
        }
    } else {
        $pg_str = create_hyperlink("$php_self?showall=0&amp;startMessage=1&amp;mailbox=$box", _("Paginate"));
    }

    /* Put all the pieces of the paginator string together. */
    /**
     * Hairy code... But let's leave it like it is since I am not certain
     * a different approach would be any easier to read. ;)
     */
    $result = '';
    if ( $prv_str || $nxt_str ) {

        /* Compute the 'show all' string. */
        $all_str = create_hyperlink("$php_self?showall=1&amp;startMessage=1&amp;mailbox=$box", _("Show All"));

        $result .= '[' . get_paginator_link($box, 1, '<<') . ']';
        $result .= '[' . $prv_str . ']';

        $pg_url = $php_self . '?mailbox=' . $box;

        $result .= '[' . $nxt_str . ']';
        $result .= '[' . get_paginator_link($box, $last_grp, '>>') . ']';

        if ($page_selector) {
            $options = array();
            for ($p = 0; $p < $tot_pgs; $p++) {
                $options[(($p*$iLimit)+1) . '_' . $box] = ($p+1) . "/$tot_pgs";
            }
            $result .= $spc . addSelect('startMessage_' . $display_iterations, 
                                        $options, 
                                        ((($cur_pg-1)*$iLimit)+1), 
                                        TRUE, 
                                        ($javascript_on ? array('onchange' => 'JavaScript:SubmitOnSelect(this, \'' . $pg_url . '&startMessage=\')') : array()));

            if ($javascript_on) {
//FIXME: What in the world?  Two issues here: for one, $javascript_on is supposed
//       to have already detected whether or not JavaScript is available and enabled.
//       Secondly, we need to rid ourselves of any HTML output in the core.  This
//       is being removed (but left in case the original author points out why it
//       should not be) and we'll trust $javascript_on to do the right thing.
//                $result .= '<noscript language="JavaScript">'
//                . addSubmit(_("Go"), 'paginator_submit_' . $display_iterations)
//                . '</noscript>';
            } else {
                $result .= addSubmit(_("Go"), 'paginator_submit_' . $display_iterations);
            }
        }
    }

    $result .= ($pg_str  != '' ? '['.$pg_str.']' .  $spc : '');
    $result .= ($all_str != '' ? $spc . '['.$all_str.']' . $spc . $spc : '');

    /* If the resulting string is blank, return a non-breaking space. */
    if ($result == '') {
        $result = '&nbsp;';
    }
    /* Return our final magical paginator string. */
    return ($result);
}


/**
 * This function computes the paginator string.
 *
 * @param string  $box               mailbox name
 * @param integer $iOffset           offset in total number of messages
 * @param integer $iTotal            total number of messages
 * @param integer $iLimit            maximum number of messages to show on a page
 * @param bool    $bShowAll          whether or not to show all messages at once 
 *                                   ("show all" == non paginate mode)
 * @param bool    $page_selector     whether or not to show the page selection widget
 * @param integer $page_selector_max maximum number of pages to show on the screen
 *
 * @return string $result   paginate string with links to pages
 *
 */
function get_paginator_str($box, $iOffset, $iTotal, $iLimit, $bShowAll,$page_selector, $page_selector_max) {

    global $oTemplate;
    sqgetGlobalVar('PHP_SELF',$php_self,SQ_SERVER);

    /* Initialize paginator string chunks. */
    $prv_str = '';
    $nxt_str = '';
    $pg_str  = '';
    $all_str = '';

    $box = urlencode($box);

    /* Create simple strings that will be creating the paginator. */
    /* This will be used as a space. */
    $spc = $oTemplate->fetch('non_breaking_space.tpl');
    /* This will be used as a seperator. */
    $sep = '|';

    /* Make sure that our start message number is not too big. */
    $iOffset = min($iOffset, $iTotal);

    /* Compute the starting message of the previous and next page group. */
    $next_grp = $iOffset + $iLimit;
    $prev_grp = $iOffset - $iLimit;

    if (!$bShowAll) {
        /* Compute the basic previous and next strings. */

        if (($next_grp <= $iTotal) && ($prev_grp >= 0)) {
            $prv_str = get_paginator_link($box, $prev_grp, _("Previous"));
            $nxt_str = get_paginator_link($box, $next_grp, _("Next"));
        } else if (($next_grp > $iTotal) && ($prev_grp >= 0)) {
            $prv_str = get_paginator_link($box, $prev_grp, _("Previous"));
            $nxt_str = _("Next");
        } else if (($next_grp <= $iTotal) && ($prev_grp < 0)) {
            $prv_str = _("Previous");
            $nxt_str = get_paginator_link($box, $next_grp, _("Next"));
        }

        /* Page selector block. Following code computes page links. */
        if ($iLimit != 0 && $page_selector && ($iTotal > $iLimit)) {
            /* Most importantly, what is the current page!!! */
            $cur_pg = intval($iOffset / $iLimit) + 1;

            /* Compute total # of pages and # of paginator page links. */
            $tot_pgs = ceil($iTotal / $iLimit);  /* Total number of Pages */

            $vis_pgs = min($page_selector_max, $tot_pgs - 1);   /* Visible Pages    */

            /* Compute the size of the four quarters of the page links. */

            /* If we can, just show all the pages. */
            if (($tot_pgs - 1) <= $page_selector_max) {
                $q1_pgs = $cur_pg - 1;
                $q2_pgs = $q3_pgs = 0;
                $q4_pgs = $tot_pgs - $cur_pg;

            /* Otherwise, compute some magic to choose the four quarters. */
            } else {
                /*
                * Compute the magic base values. Added together,
                * these values will always equal to the $pag_pgs.
                * NOTE: These are DEFAULT values and do not take
                * the current page into account. That is below.
                */
                $q1_pgs = floor($vis_pgs/4);
                $q2_pgs = round($vis_pgs/4, 0);
                $q3_pgs = ceil($vis_pgs/4);
                $q4_pgs = round(($vis_pgs - $q2_pgs)/3, 0);

                /* Adjust if the first quarter contains the current page. */
                if (($cur_pg - $q1_pgs) < 1) {
                    $extra_pgs = ($q1_pgs - ($cur_pg - 1)) + $q2_pgs;
                    $q1_pgs = $cur_pg - 1;
                    $q2_pgs = 0;
                    $q3_pgs += ceil($extra_pgs / 2);
                    $q4_pgs += floor($extra_pgs / 2);

                /* Adjust if the first and second quarters intersect. */
                } else if (($cur_pg - $q2_pgs - ceil($q2_pgs/3)) <= $q1_pgs) {
                    $extra_pgs = $q2_pgs;
                    $extra_pgs -= ceil(($cur_pg - $q1_pgs - 1) * 3/4);
                    $q2_pgs = ceil(($cur_pg - $q1_pgs - 1) * 3/4);
                    $q3_pgs += ceil($extra_pgs / 2);
                    $q4_pgs += floor($extra_pgs / 2);

                /* Adjust if the fourth quarter contains the current page. */
                } else if (($cur_pg + $q4_pgs) >= $tot_pgs) {
                    $extra_pgs = ($q4_pgs - ($tot_pgs - $cur_pg)) + $q3_pgs;
                    $q3_pgs = 0;
                    $q4_pgs = $tot_pgs - $cur_pg;
                    $q1_pgs += floor($extra_pgs / 2);
                    $q2_pgs += ceil($extra_pgs / 2);

                /* Adjust if the third and fourth quarter intersect. */
                } else if (($cur_pg + $q3_pgs + 1) >= ($tot_pgs - $q4_pgs + 1)) {
                    $extra_pgs = $q3_pgs;
                    $extra_pgs -= ceil(($tot_pgs - $cur_pg - $q4_pgs) * 3/4);
                    $q3_pgs = ceil(($tot_pgs - $cur_pg - $q4_pgs) * 3/4);
                    $q1_pgs += floor($extra_pgs / 2);
                    $q2_pgs += ceil($extra_pgs / 2);
                }
            }

            /*
            * I am leaving this debug code here, commented out, because
            * it is a really nice way to see what the above code is doing.
            * echo "qts =  $q1_pgs/$q2_pgs/$q3_pgs/$q4_pgs = "
            *    . ($q1_pgs + $q2_pgs + $q3_pgs + $q4_pgs) . '<br />';
            */

            /* Print out the page links from the compute page quarters. */

            /* Start with the first quarter. */
            if (($q1_pgs == 0) && ($cur_pg > 1)) {
                $pg_str .= "...$spc";
            } else {
                for ($pg = 1; $pg <= $q1_pgs; ++$pg) {
                    $start = (($pg-1) * $iLimit) + 1;
                    $pg_str .= get_paginator_link($box, $start, $pg) . $spc;
                }
                if ($cur_pg - $q2_pgs - $q1_pgs > 1) {
                    $pg_str .= "...$spc";
                }
            }

            /* Continue with the second quarter. */
            for ($pg = $cur_pg - $q2_pgs; $pg < $cur_pg; ++$pg) {
                $start = (($pg-1) * $iLimit) + 1;
                $pg_str .= get_paginator_link($box, $start, $pg) . $spc;
            }

            /* Now print the current page. */
            $pg_str .= $cur_pg . $spc;

            /* Next comes the third quarter. */
            for ($pg = $cur_pg + 1; $pg <= $cur_pg + $q3_pgs; ++$pg) {
                $start = (($pg-1) * $iLimit) + 1;
                $pg_str .= get_paginator_link($box, $start, $pg) . $spc;
            }

            /* And last, print the forth quarter page links. */
            if (($q4_pgs == 0) && ($cur_pg < $tot_pgs)) {
                $pg_str .= "...$spc";
            } else {
                if (($tot_pgs - $q4_pgs) > ($cur_pg + $q3_pgs)) {
                    $pg_str .= "...$spc";
                }
                for ($pg = $tot_pgs - $q4_pgs + 1; $pg <= $tot_pgs; ++$pg) {
                    $start = (($pg-1) * $iLimit) + 1;
                    $pg_str .= get_paginator_link($box, $start,$pg) . $spc;
                }
            }

            $last_grp = (($tot_pgs - 1) * $iLimit) + 1;
        }
    } else {
        $pg_str = create_hyperlink("$php_self?showall=0&amp;startMessage=1&amp;mailbox=$box", _("Paginate"));
    }

    /* Put all the pieces of the paginator string together. */
    /**
     * Hairy code... But let's leave it like it is since I am not certain
     * a different approach would be any easier to read. ;)
     */
    $result = '';
    if ( $prv_str || $nxt_str ) {

        /* Compute the 'show all' string. */
        $all_str = create_hyperlink("$php_self?showall=1&amp;startMessage=1&amp;mailbox=$box", _("Show All"));

        $result .= '[';
        $result .= ($prv_str != '' ? $prv_str . $spc . $sep . $spc : '');
        $result .= ($nxt_str != '' ? $nxt_str : '');
        $result .= ']' . $spc ;
    }

    $result .= ($pg_str  != '' ? $spc . '['.$spc.$pg_str.']' .  $spc : '');
    $result .= ($all_str != '' ? $spc . '['.$all_str.']' . $spc . $spc : '');

    /* If the resulting string is blank, return a non-breaking space. */
    if ($result == '') {
        $result = $oTemplate->fetch('non_breaking_space.tpl');
    }
    /* Return our final magical compact paginator string. */
    return ($result);
}


