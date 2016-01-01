<?php
/**
 * read_headers.tpl
 *
 * Template to display the envelope headers when viewing a message.
 * 
 * The following variables are available in this template:
 * 
 *    $headers_to_display - Array containing the list of all elements that need
 *                          to be displayed.  The index of each element is the 
 *                          translated name of the field to be displayed.  The 
 *                          value of each element is the value to be displayed 
 *                          for that field.  Many values can be controled through
 *                          additional templates.
 * 
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes **/

/** extract template variables **/
extract($t);

/** Begin template **/
?>
<div class="readHeaders">
<table cellspacing="0" class="spacer">
 <tr>
  <td>
  </td>
 </tr>
</table>
<table cellspacing="0" class="table2">
 <?php

    // detect if we want to show reduced header or regular (expanded) header list
    // default is to show the reduced header when the preview pane is turned on
    //
    // note that this is an example of a template page making use of its own
    // value placed on the page's query string; we like to keep things like
    // that in the core, but for template-specific add-on functionalities, this
    // kind of thing is perfectly acceptable
    //
    global $data_dir, $username, $PHP_SELF;
    $use_previewPane = getPref($data_dir, $username, 'use_previewPane', 0);
    $show_preview_pane = checkForJavascript() && $use_previewPane;
    if (!sqGetGlobalVar('expand_header', $expand_header, SQ_FORM, 0))
        if (!$show_preview_pane)
            $expand_header = 1;

    // show reduced (collapsed) header
    if (!$expand_header) {

        $subject = (!empty($headers_to_display[_("Subject")]) 
                    ? $headers_to_display[_("Subject")] : _("(no subject)"));
        $date = (!empty($headers_to_display[_("Date")]) 
                 ? $headers_to_display[_("Date")] : _("Unknown date"));
        $from = (!empty($headers_to_display[_("From")]) 
                    ? $headers_to_display[_("From")] : _("Unknown sender"));
        // if available, print "real" name instead of email addr
        if (strpos($from,"&lt;") !== FALSE) {
            list($from, $ignore) = $parts = explode('&lt;', $from);
            $from = trim($from);
            $from = preg_replace('/^(&#32;)+|(&#32;)+$/', '', $from);
            $from = preg_replace('/^(&quot;)+|(&quot;)+$/', '', $from);
            $from = preg_replace('/&quot;$/', '', trim($from));
        }
        // i18n: The parameters are: subject, sender, and date.
        $reduced_header = sprintf(_("%s from %s on %s"), "<b>$subject</b>", "<b>$from</b>", "<b>$date</b>");
        $expand_link = str_replace('&expand_header=0', '', $PHP_SELF) . '&expand_header=1';

        echo '<tr><td colspan="2" align="center" valign="top">'
           . '<a href="' . $expand_link . '">' 
           . getIcon($icon_theme_path, 'plus.png', '-', _("Expand Header"))
           . '</a> '
           . $reduced_header
           . '</td></tr>';

    // show normal/full/expanded header listing
    } else {

        $collapse_link = str_replace('&expand_header=1', '', $PHP_SELF) . '&expand_header=0';
        $first_time = TRUE;
        foreach ($headers_to_display as $field_name=>$value) {
            if (empty($value)) {
                # Skip enpty headers
                continue;
            }
        ?>
 <tr class="field_<?php echo $field_name; ?>">
  <td class="fieldName">
<?php 
        if ($first_time) 
            echo '<a href="' . $collapse_link . '">' 
               . getIcon($icon_theme_path, 'minus.png', '-', _("Collapse Header"))
               . '</a> ';
        echo $field_name . ':';
        $first_time = FALSE; 
?>
  </td>
  <td class="fieldValue">
   <?php echo $value; ?>
  </td>
 </tr>
<?php 
        }
    } 
    if (!empty($plugin_output['read_body_header'])) echo $plugin_output['read_body_header']; 
?>
</table>
<table cellspacing="0" class="spacer">
 <tr>
  <td>
  </td>
 </tr>
</table>
</div>
<?php
// do a conditional refresh of message list if needed
// "pp_rr" = "preview pane read refresh"
// "pp_rr_force" = force pp_rr even if this is not the first time the message has been read
if ($show_preview_pane
 && (sqGetGlobalVar('pp_rr_force', $pp_rr_force, SQ_FORM)
 || (sqGetGlobalVar('pp_rr', $pp_rr, SQ_FORM) && $first_time_reading))) {
    echo "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\nif (self.name == 'bottom') { refresh_message_list(); }\n// -->\n</script>\n";
}
