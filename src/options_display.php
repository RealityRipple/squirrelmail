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

    $optvals[] = array(
        'name'    => 'use_javascript_addr_book',
        'caption' => _("Addressbook Display Format"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array('1' => _("Javascript"),
                           '0' => _("HTML"))
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
        'name'    => 'location_of_bar',
        'caption' => _("Location of Folder List"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_ALL,
        'posvals' => array(SMPREF_LOC_LEFT  => _("Left"),
                           SMPREF_LOC_RIGHT => _("Right"))
    );

    $left_size_values = array();
    for ($lsv = 100; $lsv <= 300; $lsv += 10) {
        $left_size_values[$lsv] = "$lsv " . _("pixels");
    }
    $optvals[] = array(
        'name'    => 'left_size',
        'caption' => _("Width of Folder List"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_ALL,
        'posvals' => $left_size_values
    );

    $minute_str = _("Minutes");
    $left_refresh_values = array(SMPREF_NONE => _("Never"));
    foreach (array(30,60,120,180,300,600) as $lr_val) {
        if ($lr_val < 60) {
            $left_refresh_values[$lr_val] = "$lr_val " . _("Seconds");
        } else if ($lr_val == 60) {
            $left_refresh_values[$lr_val] = "1 " . _("Minute");
        } else {
            $left_refresh_values[$lr_val] = ($lr_val/60) . " $minute_str";
        }
    }
    $optvals[] = array(
        'name'    => 'left_refresh',
        'caption' => _("Auto Refresh Folder List"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => $left_refresh_values
    );

    /* Build all these values into an array of SquirrelOptions objects. */
    $options = createOptionArray($optvals);

    /* Print the row for each option. */
    foreach ($options as $option) {
        if ($option->type != SMOPT_TYPE_HIDDEN) {
            echo "<TR>\n";
            echo '  <TD ALIGN="RIGHT" VALIGN="MIDDLE" NOWRAP><font color=red><b>'
               . $option->caption . "</b></font>:</TD>\n";
            echo '  <TD>' . $option->createHTMLWidget() . "</TD>\n";
            echo "</TR>\n";
        } else {
            echo $option->createHTMLWidget();
        }
    }

    /*** NOT YET CONVERTED TO OPTION OBJECTS ***/

   OptionRadio( _("Use alternating row colors?"),
                'altIndexColors',
                array( 1 => _("Yes"),
                       0 => _("No") ),
                $alt_index_colors );
   OptionCheck( _("Show HTML version by default"),
                'showhtmldefault',
                $show_html_default,
                _("Yes, show me the HTML version of a mail message, if it is available.") );
   OptionCheck( _("Include Self"),
                'includeselfreplyall',
                getPref($data_dir, $username, 'include_self_reply_all', FALSE ),
                _("Don't remove me from the CC addresses when I use \"Reply All\"") );
   $psw = getPref($data_dir, $username, 'page_selector_max', 10 );
   OptionCheck( _("Page Selector"),
                'pageselector',
                !getPref($data_dir, $username, 'page_selector', FALSE ),
                _("Show page selector") .
                " <input name=pageselectormax size=3  value=\"$psw\"> &nbsp;" .
                _("pages max") );

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
