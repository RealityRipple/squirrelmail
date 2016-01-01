<?php

/**
 * abook_util.php
 *
 * The following functions are utility functions for templates. Do not
 * echo output in these functions.
 *
 * @copyright 2005-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */


/**
  * Display a column header with sort buttons
  *
  * @param string $field             Which field to display
  * @param array  $current_page_args All known query string arguments
  *                                  for the current page request; structured
  *                                  as an associative array of key/value pairs
  *
  * @author Steve Brown
  * @since 1.5.2
  */
function addAbookSort ($field, $current_page_args) {
    global $abook_sort_order, $nbsp;

    switch ($field) {
        case 'nickname':
            $str = _("Nickname");
            $alt = _("Sort by nickname");
            $down = 0;
            $up = 1;
            $has_sort = true;
            break;
        case 'fullname':
            $str = _("Name");
            $alt = _("Sort by name");
            $down = 2;
            $up = 3;
            $has_sort = true;
            break;
        case 'email':
            $str = _("E-mail");
            $alt = _("Sort by email");
            $down = 4;
            $up = 5;
            $has_sort = true;
            break;
        case 'info':
            $str = _("Info");
            $alt = _("Sort by info");
            $down = 6;
            $up = 7;
            $has_sort = true;
            break;
        default:
            return 'BAD SORT FIELD GIVEN: "'.$field.'"';
    }

    // show_abook_sort_button() creates a hyperlink (using hyperlink.tpl) that encompases an image, using a getImage() call
    return $str . ($has_sort ? $nbsp . show_abook_sort_button($abook_sort_order, $alt, $down, $up, $current_page_args) : '');
}


/**
  * Creates an address book paginator
  *
  * @param boolean $abook_page_selector     Whether or not to show the page selector
  * @param int     $abook_page_selector_max The maximum number of page links to show
  *                                         on screen
  * @param int     $page_number             What page is being viewed - 0 if not used
  * @param int     $page_size               Maximum number of addresses to be shown
  *                                         per page
  * @param int     $total_addresses         The total count of addresses in the backend
  * @param boolean $show_all                Whether or not all addresses are being shown
  * @param array  $current_page_args        All known query string arguments
  *                                         for the current page request; structured
  *                                         as an associative array of key/value pairs
  * @param boolean $compact                 Whether or not to build a smaller, 
  *                                         "compact" paginator
  *
  * @return string The paginator, ready for output
  *
  */
function get_abook_paginator($abook_page_selector, $abook_page_selector_max,
                             $page_number, $page_size, $total_addresses,
                             $show_all, $current_page_args, $compact) {

    // if showing all, just show pagination link
    //
    if ($show_all)
    {
        unset($current_page_args['show_all']);
        return '[' . make_abook_paginator_link(1, _("Paginate"), $current_page_args) . ']';
    }


    // if we don't have enough information to build the paginator, return nothing
    //
    if (empty($page_number) || empty($page_size) || empty($total_addresses))
        return '';


    // calculate some values we need below
    //
    $show_elipses_before = FALSE;
    $show_elipses_after = FALSE;
    global $nbsp;
    $sep = '|';
    $paginator_string = '[';
    $total_pages = ceil($total_addresses / $page_size);
    if ($page_number > $total_pages) $page_number = $total_pages;
    $spacing = ($compact ? $nbsp : $nbsp . $nbsp);


    // only enough addresses for one page anyway?  no pagination needed
    //
    if ($total_pages < 2) return '';


    // build "Show All" link
    //
    $show_all_string = '['
                     . make_abook_paginator_link(1, _("All"), 
                                                 array_merge($current_page_args, array('show_all' => 1)))
                     . ']';


    // build next/previous links for compact paginator
    //
    if ($compact)
    {
        if ($page_number > 1)
            $paginator_string .= make_abook_paginator_link(1,
                                                           _("<<"),
                                                           $current_page_args)
                               . ']['
                               . make_abook_paginator_link($page_number - 1,
                                                           _("<"),
                                                           $current_page_args)
                               . '][';
        else
            // i18n: "<<" is for the first page in the paginator. "<" is for the previous page.
            $paginator_string .= _("<<") . '][' . _("<") . '][';
        if ($page_number < $total_pages)
            $paginator_string .= make_abook_paginator_link($page_number + 1,
                                                           _(">"),
                                                           $current_page_args)
                               . ']['
                               . make_abook_paginator_link($total_pages,
                                                           _(">>"),
                                                           $current_page_args)
                               . ']';
        else
            // i18n: ">>" is for the last page in the paginator. ">" is for the next page.
            $paginator_string .= _(">") . '][' . _(">>") . ']';
    }


    // build next/previous links for regular paginator
    //
    else
    {
        if ($page_number > 1)
            $paginator_string .= make_abook_paginator_link($page_number - 1,
                                                           _("Previous"),
                                                           $current_page_args);
        else
            $paginator_string .= _("Previous");
        $paginator_string .= $nbsp . $sep . $nbsp;
        if ($page_number < $total_pages)
            $paginator_string .= make_abook_paginator_link($page_number + 1,
                                                           _("Next"),
                                                           $current_page_args);
        else
            $paginator_string .= _("Next");
        $paginator_string .= ']';
    }


    // paginator is turned off - just show previous/next links
    //
    if (!$abook_page_selector)
    {
        return $paginator_string . $spacing . $show_all_string;
    }


    $paginator_string .= $spacing;


    if ($total_pages <= $abook_page_selector_max)
    {
        $start_page = 1;
        $end_page = $total_pages;
    }
    else
    {
        $pages_to_show = ($abook_page_selector_max % 2 ? $abook_page_selector_max : $abook_page_selector_max - 1);
        $end_page = $page_number + floor($pages_to_show / 2);
        $start_page = $page_number - floor($pages_to_show / 2);
        if (!($abook_page_selector_max % 2)) $start_page--;

        if ($start_page < 1)
        {
            $end_page += 1 - $start_page;
            $start_page = 1;
        }
        else if ($end_page > $total_pages)
        {
            $start_page -= $end_page - $total_pages;
            $end_page = $total_pages;
        }


        // do we need to insert elipses?
        //
        if (1 < $start_page)
        {
            $start_page++;
            $show_elipses_before = TRUE;
        }
        if ($total_pages > $end_page)
        {
            $end_page--;
            $show_elipses_after = TRUE;
        }
    }


    // now build the actual (compact) paginator
    //
    if ($compact)
    {
        $aValues = array();
        for ($i = 1; $i <= $total_pages; $i++)
            $aValues[$i] = $i . '/' . $total_pages;
        $page_uri = sqm_baseuri() . 'src/addressbook.php';
        $temp_page_number = $current_page_args['page_number'];
        unset($current_page_args['page_number']);
        $page_uri = set_uri_vars($page_uri, array_diff($current_page_args, array('page_number' => 0)), FALSE);
        $current_page_args['page_number'] = $temp_page_number;
        $paginator_string .= addSelect('page_number', $aValues,
                                       $page_number, TRUE,
                                       (checkForJavascript()
                                        ? array('onchange' => 'SubmitOnSelect(this, \''
                                                                                  . $page_uri
                                                                                  . '&page_number='
                                                                                  . '\')')
                                        : array()));

        // need a submit button when select widget cannot submit itself
        //
        if (!checkForJavascript())
        {
            $paginator_string .= addSubmit(_("Go"), 'paginator_submit');
        }
    }


    // now build the actual (regular) paginator
    //
    else
    {
        $paginator_string .= '[' . $nbsp;
        if ($show_elipses_before)
            $paginator_string .= make_abook_paginator_link(1, 1, $current_page_args)
                            . $nbsp . '...' . $nbsp;
        for ($x = $start_page; $x <= $end_page; $x++)
        {
            if ($x == $page_number)
                $paginator_string .= $x . $nbsp;
            else
                $paginator_string .= make_abook_paginator_link($x, $x, $current_page_args) . $nbsp;
        }
        if ($show_elipses_after)
            $paginator_string .= '...' . $nbsp
                              . make_abook_paginator_link($total_pages, $total_pages, $current_page_args)
                              . $nbsp;
        $paginator_string .= ']';
    }
    $paginator_string .= $spacing . $show_all_string;


    return $paginator_string;

}


/**
  * Build a page (pagination) link for use with the address book list page
  *
  * @param int    $page_number       The page number for the link
  * @param string $text              The link text
  * @param array  $current_page_args All known query string arguments
  *                                  for the current page request; structured
  *                                  as an associative array of key/value pairs
  *
  */
function make_abook_paginator_link($page_number, $text, $current_page_args) {

    $uri = sqm_baseuri() . 'src/addressbook.php';

    $current_page_args['page_number'] = $page_number;
    $uri = set_uri_vars($uri, $current_page_args, FALSE);

    return create_hyperlink($uri, $text);

}


