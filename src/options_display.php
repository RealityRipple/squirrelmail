<?php
   /**
    **  options_display.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Displays all optinos about display preferences
    **
    **  $Id$
    **/

   require_once('../src/validate.php');
   require_once('../functions/display_messages.php');
   require_once('../functions/imap.php');
   require_once('../functions/array.php');
   require_once('../functions/plugin.php');
   require_once('../functions/options.php');

   displayPageHeader($color, 'None');
   $language = getPref($data_dir, $username, 'language');
?>
   <br>
<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">
<tr><td bgcolor="<?php echo $color[0] ?>" align="center">

   <b><?php echo _("Options") . ' - ' . _("Display Preferences"); ?></b><br>

   <table width="100%" border="0" cellpadding="1" cellspacing="1">
   <tr><td bgcolor="<?php echo $color[4] ?>" align="center">

   <form name="f" action="options.php" method="post"><br>
      <table width="100%" cellpadding="2" cellspacing="0" border="0">
<?php

    /* Build a simple array into which we will build options. */
    $optvals = array(); 

    $theme_values = array();
    foreach ($theme as $theme_key => $theme_attributes) {
        $theme_values[$theme_attributes['PATH']] = $theme_attributes['NAME'];
    }
    $optvals[] = array(
        'name'    => 'chosen_theme',
        'caption' => _("Theme"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_ALL,
        'posvals' => $theme_values
    );

    $language_values = array();
    foreach ($languages as $lang_key => $lang_attributes) {
        if (isset($lang_attributes['NAME'])) {
            $language_values[$lang_key] = $lang_attributes['NAME'];
        }
    }
    $optvals[] = array(
        'name'    => 'language',
        'caption' => _("Language"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_ALL,
        'posvals' => $language_values
    );

    /* Set values for the "use javascript" option. */
    $optvals[] = array(
        'name'    => 'javascript_setting',
        'caption' => _("Use Javascript"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_ALL,
        'posvals' => array(SMPREF_JS_AUTODETECT => _("Autodetect"),
                           SMPREF_JS_ON         => _("Always"),
                           SMPREF_JS_OFF        => _("Never"))
    );

    $js_autodetect_results = SMPREF_JS_OFF;
    $optvals[] = array(
        'name'    => 'js_autodetect_results',
        'caption' => '',
        'type'    => SMOPT_TYPE_HIDDEN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[] = array(
        'name'    => 'show_num',
        'caption' => _("Number of Messages to Index"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[] = array(
        'name'    => 'alt_index_colors',
        'caption' => _("Enable Alternating Row Colors"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[] = array(
        'name'    => 'page_selector',
        'caption' => _("Enable Page Selector"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[] = array(
        'name'    => 'page_selector_max',
        'caption' => _("Maximum Number of Pages to Show"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[] = array(
        'name'    => 'wrap_at',
        'caption' => _("Wrap Incoming Text At"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[] = array(
        'name'    => 'editor_size',
        'caption' => _("Size of Editor Window"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[] = array(
        'name'    => 'location_of_buttons',
        'caption' => _("Location of Buttons when Composing"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array(SMPREF_LOC_TOP     => _("Before headers"),
                           SMPREF_LOC_BETWEEN => _("Between headers and message body"),
                           SMPREF_LOC_BOTTOM  => _("After message body"))
    );

    $optvals[] = array(
        'name'    => 'use_javascript_addr_book',
        'caption' => _("Addressbook Display Format"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array('1' => _("Javascript"),
                           '0' => _("HTML"))
    );

    $optvals[] = array(
        'name'    => 'show_html_default',
        'caption' => _("Show HTML Version by Default"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[] = array(
        'name'    => 'include_self_reply_all',
        'caption' => _("Remove Me from CC when I Reply All"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[] = array(
        'name'    => 'show_xmailer_default',
        'caption' => _("Enable Mailer Display"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    /* Build all these values into an array of SquirrelOptions objects. */
    $options = createOptionArray($optvals);

    /* Print the row for each option. */
    foreach ($options as $option) {
        if ($option->type != SMOPT_TYPE_HIDDEN) {
            echo "<TR>\n";
            echo '  <TD ALIGN="RIGHT" VALIGN="MIDDLE" NOWRAP>'
               . $option->caption . ":</TD>\n";
            echo '  <TD>' . $option->createHTMLWidget() . "</TD>\n";
            echo "</TR>\n";
        } else {
            echo $option->createHTMLWidget();
        }
    }

   echo '<tr><td colspan=2><hr noshade></td></tr>';
   do_hook('options_display_inside');
   OptionSubmit( 'submit_display' );
?>

      </table>
   </form>

   <?php do_hook('options_display_bottom'); ?>

    </td></tr>
    </table>

<SCRIPT LANGUAGE="JavaScript"><!--
  document.forms[0].new_js_autodetect_results.value = '<?php echo SMPREF_JS_ON; ?>';
// --></SCRIPT>

</td></tr>
</table>
</body></html>
