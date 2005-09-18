<?php

/**
 * paginator.tpl
 *
 * Template and utility functions to create a paginator
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** include functions */
include_once(SM_PATH.'templates/util_paginator.php');

static $bScriptAdded;

extract($t);

if ($javascript_on && $compact_paginator &&!isset($bScriptAdded)) {
    $bScriptAdded = true;
?>

<!-- start of compact paginator javascript -->
<script language="JavaScript">
    function SubmitOnSelect(select, URL)
    {
        URL += select.options[select.selectedIndex].value;
        window.location.href = URL;
    }
</script>
<!-- end of compact paginator javascript -->

<?php
}

    if (isset($compact_paginator) && $compact_paginator) {
        $sPaginator = get_compact_paginator_str($mailbox, $pageOffset, $iNumberOfMessages, $messagesPerPage, $showall, $javascript_on, $page_selector);
    } else {
        $sPaginator = get_paginator_str($mailbox, $pageOffset, $iNumberOfMessages, $messagesPerPage, $showall, $page_selector, $page_selector_max);
    }
    // display the paginator string.
    echo $sPaginator;
?>