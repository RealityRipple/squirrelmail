<?php

/**
 * paginator.tpl
 *
 * Template to create a message list paginator
 *
 * The following variables are available in this template:
//FIXME: need to clean (and document) this list, it is just a dump of the array keys of $t
 *    $sTemplateID
 *    $icon_theme_path
 *    $javascript_on
 *    $delayed_errors
 *    $frames
 *    $lang
 *    $page_title
 *    $header_tags
 *    $plugin_output
 *    $header_sent
 *    $body_tag_js
 *    $shortBoxName
 *    $sm_attribute_str
 *    $frame_top
 *    $urlMailbox
 *    $startMessage
 *    $hide_sm_attributions
 *    $uri
 *    $text
 *    $onclick
 *    $class
 *    $id
 *    $target
 *    $color
 *    $form_name
 *    $form_id
 *    $page_selector
 *    $page_selector_max
 *    $messagesPerPage
 *    $showall
 *    $end_msg
 *    $align
 *    $iNumberOfMessages
 *    $aOrder
 *    $aFormElements
 *    $sort
 *    $pageOffset
 *    $baseurl
 *    $aMessages
 *    $trash_folder
 *    $sent_folder
 *    $draft_folder
 *    $thread_link_str
 *    $php_self
 *    $mailbox
 *    $enablesort
 *    $icon_theme
 *    $use_icons
 *    $alt_index_colors
 *    $fancy_index_highlite
 *    $aSortSupported
 *    $show_label_columns
 *    $compact_paginator
 *    $aErrors
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** include functions */
include_once(SM_PATH . 'functions/template/paginator_util.php');

static $bScriptAdded;

extract($t);

if ($javascript_on && $compact_paginator && !isset($bScriptAdded)) {
    $bScriptAdded = true;
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

    if (isset($compact_paginator) && $compact_paginator) {
        $sPaginator = get_compact_paginator_str($mailbox, $pageOffset, $iNumberOfMessages, $messagesPerPage, $showall, $javascript_on, $page_selector);
    } else {
        $sPaginator = get_paginator_str($mailbox, $pageOffset, $iNumberOfMessages, $messagesPerPage, $showall, $page_selector, $page_selector_max);
    }
    // display the paginator string.
    echo $sPaginator;
