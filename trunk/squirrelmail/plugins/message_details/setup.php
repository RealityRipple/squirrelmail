<?php
/** Message Source  
*
* Plugin to view the RFC822 raw message output and the bodystructure of a message
*
* Copyright (c) 2002 Marc Groot Koerkamp, The Netherlands
* Licensed under the GNU GPL. For full terms see the file COPYING.
* 
* $Id$
**/


function squirrelmail_plugin_init_message_details()
{
  global $squirrelmail_plugin_hooks;

  do_hook('read_body_header_right');
  $squirrelmail_plugin_hooks['read_body_header_right']['message_details'] = 'show_message_details';
}

function show_message_details() {
    global $passed_id, $mailbox, $ent_num, $color,
           $javascript_on;

    if (strlen(trim($mailbox)) < 1) {
        $mailbox = 'INBOX';
    }

    $params = '?passed_ent_id=' . $ent_num .
              '&mailbox=' . urlencode($mailbox) .
              '&passed_id=' . $passed_id;

    $print_text = _("View Message details");

    $result = '';
    /* Output the link. */
    if ($javascript_on) {
        $result = '<script type="text/javascript" language="javascript">' . "\n" .
                '<!--' . "\n" .
                "  function MessageSource() {\n" .
                '    window.open("../plugins/message_details/message_details_main.php' .
                        $params . '","MessageDetails","width=800,height=600");' . "\n".
                "  }\n" .
                "// -->\n" .
                "</script>\n" .
                "&nbsp;|&nbsp;<A HREF=\"javascript:MessageSource();\">$print_text</A>\n";
    } 
    echo $result;
}
 
?>
