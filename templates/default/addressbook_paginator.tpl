<?php

/**
  * addressbook_paginator.tpl
  *
  * Template to create an address book list paginator
  * 
  * The following variables are available in this template:
  * 
  * boolean $abook_compact_paginator Whether or not to show smaller paginator
  * boolean $abook_page_selector     Whether or not to use the paginator
  * int     $abook_page_selector_max How many page links to show on screen
  *                                  in the non-compact paginator format
  * int     $page_number             What page is being viewed - 0 if not used
  * int     $page_size               Maximum number of addresses to be shown
  *                                  per page
  * int     $total_addresses         The total count of addresses in the backend
  * boolean $show_all                Whether or not all addresses are being shown
  * boolean $abook_compact_paginator Whether or not pagination should be shown
  *                                  using the smaller, "compact" paginator
  * array   $current_page_args       All known query string arguments for the
  *                                  current page request, for use when constructing
  *                                  links pointing back to same page (possibly
  *                                  changing one of them); structured as an
  *                                  associative array of key/value pairs
  *
  * @copyright 1999-2016 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  * @subpackage templates
  */

/** add required includes **/
include_once(SM_PATH . 'functions/template/abook_util.php');

static $bAlreadyExecuted;

/** extract template variables **/
extract($t);

/** Begin template **/

if (!isset($bAlreadyExecuted)) {
    $bAlreadyExecuted = true;
    ?><input type="hidden" name="current_page_number" value="<?php echo $page_number; ?>" />
      <input type="hidden" name="show_all" value="<?php echo $show_all; ?>" /><?php

    if ($javascript_on && $abook_compact_paginator) {
?>

<!-- start of compact paginator javascript -->
<script type="text/javascript">
    function SubmitOnSelect(select, URL)
    {
        URL += select.options[select.selectedIndex].value;
        window.location.href = URL;
    }
</script>
<!-- end of compact paginator javascript -->

<?php
    }
}

//FIXME: added <small> tag just to buy needed space on crowded nav bar -- should we remove <small> and find another solution for un-crowding the nav bar?
    echo '<small>' . get_abook_paginator($abook_page_selector, $abook_page_selector_max, $page_number, $page_size, $total_addresses, $show_all, $current_page_args, $abook_compact_paginator) . '</small>';

