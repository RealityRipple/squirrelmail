<?php

/**
 * options_display.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Displays all optinos about display preferences
 *
 * @version $Id$
 * @package squirrelmail
 */

/** Define the group constants for the display options page. */
define('SMOPT_GRP_GENERAL', 0);
define('SMOPT_GRP_MAILBOX', 1);
define('SMOPT_GRP_MESSAGE', 2);

// load icon themes if in use
global $use_icons;
if ($use_icons) {
    global $icon_themes;
    $dirName = SM_PATH . 'images/themes';
    if (is_readable($dirName) && is_dir($dirName)) {
        $d = dir($dirName);
        while($dir = $d->read()) {
            if ($dir != "." && $dir != "..") {
                if (is_dir($dirName."/".$dir) && file_exists("$dirName/$dir/theme.php"))
                    include("$dirName/$dir/theme.php");
            }
        }
    }
}

/**
 * This function builds an array with all the information about
 * the options available to the user, and returns it. The options
 * are grouped by the groups in which they are displayed.
 * For each option, the following information is stored:
 * - name: the internal (variable) name
 * - caption: the description of the option in the UI
 * - type: one of SMOPT_TYPE_*
 * - refresh: one of SMOPT_REFRESH_*
 * - size: one of SMOPT_SIZE_*
 * - save: the name of a function to call when saving this option
 * @return array all option information
 */
function load_optpage_data_display() {
    global $theme, $language, $languages, $js_autodetect_results, $javascript_setting,
    $compose_new_win, $default_use_mdn, $squirrelmail_language, $allow_thread_sort,
    $optmode, $show_alternative_names, $available_languages, $use_icons;

    /* Build a simple array into which we will build options. */
    $optgrps = array();
    $optvals = array();

    /******************************************************/
    /* LOAD EACH GROUP OF OPTIONS INTO THE OPTIONS ARRAY. */
    /******************************************************/

    /*** Load the General Options into the array ***/
    $optgrps[SMOPT_GRP_GENERAL] = _("General Display Options");
    $optvals[SMOPT_GRP_GENERAL] = array();

    /* Load the theme option. */
    $theme_values = array();
    foreach ($theme as $theme_key => $theme_attributes) {
        $theme_values[$theme_attributes['NAME']] = $theme_attributes['PATH'];
    }
    ksort($theme_values);
    $theme_values = array_flip($theme_values);
    $optvals[SMOPT_GRP_GENERAL][] = array(
        'name'    => 'chosen_theme',
        'caption' => _("Theme"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_ALL,
        'posvals' => $theme_values,
        'save'    => 'save_option_theme'
    );

    $css_values = array( 'none' => _("Default" ) );
    $css_dir = SM_PATH . 'themes/css';
    if (is_readable($css_dir) && is_dir($css_dir)) {
        $handle=opendir($css_dir);
        while ($file = readdir($handle) ) {
            if ( substr( $file, -4 ) == '.css' ) {
                $css_values[$file] = substr( $file, 0, strlen( $file ) - 4 );
            }
        }
        closedir($handle);
    }

    if ( count( $css_values ) > 1 ) {

        $optvals[SMOPT_GRP_GENERAL][] = array(
            'name'    => 'custom_css',
            'caption' => _("Custom Stylesheet"),
            'type'    => SMOPT_TYPE_STRLIST,
            'refresh' => SMOPT_REFRESH_ALL,
            'posvals' => $css_values
        );

    }

    // config.php can be unupdated.
    if (! isset($available_languages) || $available_languages=="" ) {
     $available_languages="ALL"; }

    $language_values = array();
    if ( strtoupper($available_languages)=='ALL') {
	foreach ($languages as $lang_key => $lang_attributes) {
    	    if (isset($lang_attributes['NAME'])) {
        	$language_values[$lang_key] = $lang_attributes['NAME'];
		if ( isset($show_alternative_names) &&
		  $show_alternative_names &&
		  isset($lang_attributes['ALTNAME']) ) {
		    $language_values[$lang_key] .= " / " . $lang_attributes['ALTNAME'];
		}
    	    }
	}
    } else if (strtoupper($available_languages)!='NONE') {
	// admin can set list of available languages in config
	$available_languages_array=explode (" ",$available_languages);
        foreach ($available_languages_array as $lang_key ) {
    	    if (isset($languages[$lang_key]['NAME'])) {
        	$language_values[$lang_key] = $languages[$lang_key]['NAME'];
    		if ( isset($show_alternative_names) &&
		 $show_alternative_names &&
		 isset($languages[$lang_key]['ALTNAME']) ) {
    		    $language_values[$lang_key] .= " / " . $languages[$lang_key]['ALTNAME'];
        	}
    	    }
	}
    }
    asort($language_values);
    $language_values =
        array_merge(array('' => _("Default")), $language_values);
    $language = $squirrelmail_language;
    if (strtoupper($available_languages)!='NONE') {
	// if set to 'none', interface will use only default language
	$optvals[SMOPT_GRP_GENERAL][] = array(
    	    'name'    => 'language',
    	    'caption' => _("Language"),
    	    'type'    => SMOPT_TYPE_STRLIST,
    	    'refresh' => SMOPT_REFRESH_ALL,
    	    'posvals' => $language_values,
	    'htmlencoded' => true
	);
    }

    /* Set values for the "use javascript" option. */
    $optvals[SMOPT_GRP_GENERAL][] = array(
        'name'    => 'javascript_setting',
        'caption' => _("Use Javascript"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_ALL,
        'posvals' => array(SMPREF_JS_AUTODETECT => _("Autodetect"),
                           SMPREF_JS_ON         => _("Always"),
                           SMPREF_JS_OFF        => _("Never")),
        'save'    => 'save_option_javascript_autodetect',
        'script'  => 'onclick="document.forms[0].new_js_autodetect_results.value = \'' . SMPREF_JS_ON . '\';"'
    );

    $optvals[SMOPT_GRP_GENERAL][] = array(
        'name'    => 'js_autodetect_results',
        'caption' => '',
        'type'    => SMOPT_TYPE_HIDDEN,
        'refresh' => SMOPT_REFRESH_NONE
        //'post_script' => $js_autodetect_script,
    );

    /*** Load the General Options into the array ***/
    $optgrps[SMOPT_GRP_MAILBOX] = _("Mailbox Display Options");
    $optvals[SMOPT_GRP_MAILBOX] = array();

    $optvals[SMOPT_GRP_MAILBOX][] = array(
        'name'    => 'show_num',
        'caption' => _("Number of Messages to Index"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY
    );

    $optvals[SMOPT_GRP_MAILBOX][] = array(
        'name'    => 'alt_index_colors',
        'caption' => _("Enable Alternating Row Colors"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    if ($use_icons) {
        global $icon_themes, $icon_theme;
        $temp = array();
        for ($count = 0; $count < sizeof($icon_themes); $count++) {
            $temp[$count] = $icon_themes[$count]['NAME'];
            if ($icon_theme == $icon_themes[$count]['PATH'])
                $value = $count;
        }
        if (sizeof($icon_themes) > 0) {
            $optvals[SMOPT_GRP_MAILBOX][] = array(
                'name'          => 'icon_theme',
                'caption'       => _("Message Flags Icon Theme"),
                'type'          => SMOPT_TYPE_STRLIST,
                'refresh'       => SMOPT_REFRESH_NONE,
                'posvals'       => $temp,
                'initial_value' => $value,
                'save'          => 'icon_theme_save'
            );
        }
    }

    $optvals[SMOPT_GRP_MAILBOX][] = array(
        'name'    => 'show_flag_buttons',
        'caption' => _("Show Flag / Unflag Buttons"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_MAILBOX][] = array(
        'name'    => 'page_selector',
        'caption' => _("Enable Page Selector"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_MAILBOX][] = array(
        'name'    => 'page_selector_max',
        'caption' => _("Maximum Number of Pages to Show"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY
    );

    $optvals[SMOPT_GRP_MAILBOX][] = array(
        'name'    => 'show_full_date',
        'caption' => _("Always Show Full Date"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_MAILBOX][] = array(
        'name'    => 'truncate_sender',
        'caption' => _("Length of From/To Field (0 for full)"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY
    );

    $optvals[SMOPT_GRP_MAILBOX][] = array(
        'name'    => 'truncate_subject',
        'caption' => _("Length of Subject Field (0 for full)"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY
    );

    $optvals[SMOPT_GRP_MAILBOX][] = array(
        'name'    => 'show_recipient_instead',
        'caption' => _("Show recipient name if the message is from your default identity"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY
    );


    /*** Load the General Options into the array ***/
    $optgrps[SMOPT_GRP_MESSAGE] = _("Message Display and Composition");
    $optvals[SMOPT_GRP_MESSAGE] = array();

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'wrap_at',
        'caption' => _("Wrap Incoming Text At"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'editor_size',
        'caption' => _("Width of Editor Window"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'editor_height',
        'caption' => _("Height of Editor Window"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'location_of_buttons',
        'caption' => _("Location of Buttons when Composing"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array(SMPREF_LOC_TOP     => _("Before headers"),
                           SMPREF_LOC_BETWEEN => _("Between headers and message body"),
                           SMPREF_LOC_BOTTOM  => _("After message body"))
    );


    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'use_javascript_addr_book',
        'caption' => _("Addressbook Display Format"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array('1' => _("Javascript"),
                           '0' => _("HTML"))
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'show_html_default',
        'caption' => _("Show HTML Version by Default"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'enable_forward_as_attachment',
        'caption' => _("Enable Forward as Attachment"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'forward_cc',
        'caption' => _("Include CCs when Forwarding Messages"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'include_self_reply_all',
        'caption' => _("Include Me in CC when I Reply All"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'show_xmailer_default',
        'caption' => _("Enable Mailer Display"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'attachment_common_show_images',
        'caption' => _("Display Attached Images with Message"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'pf_cleandisplay',
        'caption' => _("Enable Printer Friendly Clean Display"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    if ($default_use_mdn) {
        $optvals[SMOPT_GRP_MESSAGE][] = array(
            'name'    => 'mdn_user_support',
            'caption' => _("Enable Mail Delivery Notification"),
            'type'    => SMOPT_TYPE_BOOLEAN,
            'refresh' => SMOPT_REFRESH_NONE
        );
    }

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'compose_new_win',
        'caption' => _("Compose Messages in New Window"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_ALL
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'compose_width',
        'caption' => _("Width of Compose Window"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_ALL,
        'size'    => SMOPT_SIZE_TINY
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'compose_height',
        'caption' => _("Height of Compose Window"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_ALL,
        'size'    => SMOPT_SIZE_TINY
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'sig_first',
        'caption' => _("Append Signature before Reply/Forward Text"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'body_quote',
        'caption' => _("Prefix for Original Message when Replying"),
        'type'    => SMOPT_TYPE_STRING,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'save'    => 'save_option_reply_prefix'
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'reply_focus',
        'caption' => _("Cursor Position when Replying"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array('' => _("To: field"),
                           'focus' => _("Focus in body"),
                           'select' => _("Select body"),
                           'none' => _("No focus"))
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'strip_sigs',
        'caption' => _("Strip signature when replying"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'internal_date_sort',
        'caption' => _("Sort by Received Date"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_ALL
    );
    if ($allow_thread_sort == TRUE) {
        $optvals[SMOPT_GRP_MESSAGE][] = array(
            'name'    => 'sort_by_ref',
            'caption' => _("Enable Thread Sort by References Header"),
            'type'    => SMOPT_TYPE_BOOLEAN,
            'refresh' => SMOPT_REFRESH_ALL
        );
    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'delete_prev_next_display',
        'caption' => _("Show 'Delete & Prev/Next' Links"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_ALL
    );

    }
    /* Assemble all this together and return it as our result. */
    $result = array(
        'grps' => $optgrps,
        'vals' => $optvals
    );
    return ($result);
}

/******************************************************************/
/** Define any specialized save functions for this option page. ***/
/******************************************************************/

function save_option_header($option) {
}

/**
 * This function saves a new theme setting.
 * It updates the theme array.
 */
function save_option_theme($option) {
    global $theme;

    /* Do checking to make sure $new_theme is in the array. */
    $theme_in_array = false;
    for ($i = 0; $i < count($theme); ++$i) {
        if ($theme[$i]['PATH'] == $option->new_value) {
            $theme_in_array = true;
            break;
        }
    }

    if (!$theme_in_array) {
        $option->new_value = '';
    }

    /* Save the option like normal. */
    save_option($option);
}

/**
 * This function saves the javascript detection option.
 */
function save_option_javascript_autodetect($option) {
    global $data_dir, $username;

    save_option($option);
    checkForJavascript(TRUE);
}

/**
 * This function saves the user's icon theme setting
 */
function icon_theme_save($option) {

    global $icon_themes, $data_dir, $username;


    // Don't assume the new value is there, double check
    // and only save if found
    //
    if (isset($icon_themes[$option->new_value]['PATH']))
        setPref($data_dir, $username, 'icon_theme', $icon_themes[$option->new_value]['PATH']);
    else
       setPref($data_dir, $username, 'icon_theme', 'none');

}

/**
 * This function saves the reply prefix (body_quote) character(s)
 */
function save_option_reply_prefix($option) {
    global $data_dir, $username, $new_javascript_setting;

    // save as "NONE" if it was blanked out
    //
    if (empty($option->new_value)) $option->new_value = 'NONE';


    // Save the option like normal.
    //
    save_option($option);

}

?>