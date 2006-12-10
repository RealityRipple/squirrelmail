<?php

/**
 * Message Details plugin - main setup script
 *
 * Plugin to view the RFC822 raw message output and the bodystructure of a message
 *
 * @author Marc Groot Koerkamp
 * @copyright &copy; 2002 Marc Groot Koerkamp, The Netherlands
 * @copyright &copy; 2002-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage message_details
 **/

/**
 * Initialize the plugin
 * @access private
 */
function squirrelmail_plugin_init_message_details()
{
  global $squirrelmail_plugin_hooks;

  $squirrelmail_plugin_hooks['read_body_header_right']['message_details'] = 'show_message_details';
}

/**
 * Add message details link in message view
 * @access private
 */
function show_message_details(&$links) {
    global $passed_id, $mailbox, $ent_num,
           $javascript_on;
    
    if (strlen(trim($mailbox)) < 1) {
        $mailbox = 'INBOX';
    }

    $params = '?passed_ent_id=' . $ent_num .
              '&mailbox=' . urlencode($mailbox) .
              '&passed_id=' . $passed_id;

    $url = $javascript_on ? 'javascript:MessageSource();' : 
        '../plugins/message_details/message_details_main.php' .
        $params;

    /* Output the link. */
    $links[] = array('URL' => $url,
        'Text' => _("View Message details") );

    if ($javascript_on) {
        echo '<script type="text/javascript">' . "\n" .
                '<!--' . "\n" .
                "  function MessageSource() {\n" .
                '    window.open("../plugins/message_details/message_details_main.php' .
                        $params . '","MessageDetails","width=800,height=600");' . "\n".
                "  }\n" .
                "// -->\n" .
                "</script>\n\n";
    }
}

