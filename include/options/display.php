<?php

/**
 * options_display.php
 *
 * Displays all optinos about display preferences
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** Define the group constants for the display options page. */
define('SMOPT_GRP_GENERAL', 0);
define('SMOPT_GRP_MAILBOX', 1);
define('SMOPT_GRP_MESSAGE', 2);

/**
 * Icon themes and user CSS themes should probably both be moved to conf.pl
 * 
 * TODO: move to conf.pl
 **/
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

global $use_iframe;
if (! isset($use_iframe)) $use_iframe=false;

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
    global $theme, $fontsets, $language, $languages,$aTemplateSet,
    $default_use_mdn, $squirrelmail_language, $allow_thread_sort,
    $show_alternative_names, $use_icons, $use_iframe, $sTemplateID, 
    $oTemplate, $user_themes;

    /* Build a simple array into which we will build options. */
    $optgrps = array();
    $optvals = array();

    /******************************************************/
    /* LOAD EACH GROUP OF OPTIONS INTO THE OPTIONS ARRAY. */
    /******************************************************/

    /*** Load the General Options into the array ***/
    $optgrps[SMOPT_GRP_GENERAL] = _("General Display Options");
    $optvals[SMOPT_GRP_GENERAL] = array();

    /* load the template set option */
    $templateset_values = array();

    foreach ($aTemplateSet as $sKey => $aTemplateSetAttributes) {
        $templateset_values[$aTemplateSetAttributes['NAME']] = $aTemplateSetAttributes['ID'];
    }
    ksort($templateset_values);
    $templateset_values = array_flip($templateset_values);
    // display template options only when there is more than one template
    if (count($templateset_values)>1) {
        $optvals[SMOPT_GRP_GENERAL][] = array(
            'name'    => 'sTemplateID',
            'caption' => _("Skin"),
            'type'    => SMOPT_TYPE_STRLIST,
            'refresh' => SMOPT_REFRESH_ALL,
            'posvals' => $templateset_values,
            'save'    => 'save_option_template'
        );
    }

    /* Load the theme option. */

    /**
     * User themes start with a 'u_', template themes start with a 't_' to
     * differentiate which is which.  This seems kind of hackish, but we can
     * come up with a better solution later.
PL: No need for the prefixes.  Just use full paths, no?

SB: Don't think so.  If the user chooses a template theme than changes the 
    path to the template, it would error out, right?  Or should we worry about
    that?
     * 
     * TODO: Clean me.
     **/
    $theme_values = array();
    
    // Always provide the template default first.
    $theme_values['none'] = 'Template Default Theme';

    // List alternate themes provided by templates first
    $template_themes = array();
/*
 * Since this requires mods to the template class, I'm holding off on alternate
 * template styles until Paul finishes template inheritence.
 *      -- SB, 2006-09-30
 * 
    $template_themes = $oTemplate->get_alternative_stylesheets();
    asort($template_provided);
    foreach ($template_provided as $sheet=>$name) {
        $theme_values['t_'.$sheet] = 'Template Theme - '.htmlspecialchars($name);
    }
*/
    // Next, list styles provided in SM_PATH/css/
    asort($user_themes);
    foreach ($user_themes as $style) {
        if ($style['PATH'] == 'none')
            continue;
        $theme_values['u_'.$style['PATH']] = 'User Theme - '.htmlspecialchars($style['NAME']);
    }

    if (count($user_themes) + count($template_themes) > 1) {
        $optvals[SMOPT_GRP_GENERAL][] = array(
            'name'    => 'chosen_theme',
            'caption' => _("Theme"),
            'type'    => SMOPT_TYPE_STRLIST,
            'refresh' => SMOPT_REFRESH_ALL,
            'posvals' => $theme_values,
            'save'    => 'css_theme_save'
        );
    }

    /* Icon theme selection */
    if ($use_icons) {
        global $icon_themes, $icon_theme;

        $temp = array();
        $value = 0;
        for ($count = 0; $count < sizeof($icon_themes); $count++) {
            $temp[$icon_themes[$count]['PATH']] = $icon_themes[$count]['NAME'];
        }
        if (sizeof($icon_themes) > 0) {
            $optvals[SMOPT_GRP_GENERAL][] = array(
                'name'          => 'icon_theme',
                'caption'       => _("Icon Theme"),
                'type'          => SMOPT_TYPE_STRLIST,
                'refresh'       => SMOPT_REFRESH_NONE,
                'posvals'       => $temp,
                'save'          => 'icon_theme_save'
            );
        }
    }

    $fontset_values = array();
    $fontset_list = array();

    if (!empty($fontsets) && is_array($fontsets)) {

        foreach (array_keys($fontsets) as $fontset_key) {
            $fontset_list[$fontset_key]=$fontset_key;
        }
        ksort($fontset_list);
    }

    if (count($fontset_list) > 1) {
        $fontset_list = array_merge(array('' => _("Default font style")), $fontset_list);
        $optvals[SMOPT_GRP_GENERAL][] = array(
            'name'    => 'chosen_fontset',
            'caption' => _("Font style"),
            'type'    => SMOPT_TYPE_STRLIST,
            'refresh' => SMOPT_REFRESH_ALL,
            'posvals' => $fontset_list
        );
    }

    $optvals[SMOPT_GRP_GENERAL][] = array(
        'name'    => 'chosen_fontsize',
        'caption' => _("Font size"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_ALL,
        'posvals' => array('' => _("Default font size"),
                           '8' => '8 px',
                           '10' => '10 px',
                           '12' => '12 px',
                           '14' => '14 px')
    );

    $language_values = array();
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

    asort($language_values);
    $language_values =
        array_merge(array('' => _("Default")), $language_values);
    $language = $squirrelmail_language;

    // add language selection only when more than 2 languages are available
    // (default, English and some other)
    if (count($language_values)>2) {
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

    $optvals[SMOPT_GRP_GENERAL][] = array(
        'name'    => 'hour_format',
        'caption' => _("Hour Format"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => array(SMPREF_TIME_12HR => _("12-hour clock"),
                           SMPREF_TIME_24HR => _("24-hour clock"))
    );

    /*** Load the General Options into the array ***/
    $optgrps[SMOPT_GRP_MAILBOX] = _("Mailbox Display Options");
    $optvals[SMOPT_GRP_MAILBOX] = array();

    $optvals[SMOPT_GRP_MAILBOX][] = array(
        'name'    => 'show_num',
        'caption' => _("Number of Messages per Page"),
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

    $optvals[SMOPT_GRP_MAILBOX][] = array(
        'name'    => 'fancy_index_highlite',
        'caption' => _("Enable Fancy Row Mouseover Highlighting"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_MAILBOX][] = array(
        'name'    => 'show_flag_buttons',
        'caption' => _("Show Flag / Unflag Buttons"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_MAILBOX][] = array(
        'name'    => 'show_copy_buttons',
        'caption' => _("Enable Message Copy Buttons"),
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
        'name'    => 'compact_paginator',
        'caption' => _("Use Compact Page Selector"),
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
/*
FIXME!
  disabled because the template doesn't support it (yet?)
    $optvals[SMOPT_GRP_MAILBOX][] = array(
        'name'    => 'show_recipient_instead',
        'caption' => _("Show recipient name if the message is from your default identity"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY
    );
*/

    if ($allow_thread_sort == TRUE) {
        $optvals[SMOPT_GRP_MAILBOX][] = array(
            'name'    => 'sort_by_ref',
            'caption' => _("Enable Thread Sort by References Header"),
            'type'    => SMOPT_TYPE_BOOLEAN,
            'refresh' => SMOPT_REFRESH_ALL
        );
    }



    /*** Load the General Options into the array ***/
    $optgrps[SMOPT_GRP_MESSAGE] = _("Message Display Options");
    $optvals[SMOPT_GRP_MESSAGE] = array();

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'wrap_at',
        'caption' => _("Wrap Incoming Text At"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY
    );

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'show_html_default',
        'caption' => _("Show HTML Version by Default"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    if ($use_iframe) {
        // Type is set to string in order to be able to use 100%.
        $optvals[SMOPT_GRP_MESSAGE][] = array(
            'name'    => 'iframe_height',
            'caption' => _("Height of inline frame"),
            'type'    => SMOPT_TYPE_STRING,
            'size'    => SMOPT_SIZE_TINY,
            'refresh' => SMOPT_REFRESH_NONE
        );
    }
    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'enable_forward_as_attachment',
        'caption' => _("Enable Forward as Attachment"),
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

    if ($default_use_mdn) {
        $optvals[SMOPT_GRP_MESSAGE][] = array(
            'name'    => 'mdn_user_support',
            'caption' => _("Enable Mail Delivery Notification"),
            'type'    => SMOPT_TYPE_BOOLEAN,
            'refresh' => SMOPT_REFRESH_NONE
        );
    }

    $optvals[SMOPT_GRP_MESSAGE][] = array(
        'name'    => 'delete_prev_next_display',
        'caption' => _("Show 'Delete &amp; Prev/Next' Links"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_ALL
    );

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

/**
 * This function saves a new template setting.
 * It updates the template array.
 */
function save_option_template($option) {
    global $aTemplateSet;

    /* Do checking to make sure new template is in the available templates array. */
    $templateset_in_array = false;
    for ($i = 0; $i < count($aTemplateSet); ++$i) {
        if ($aTemplateSet[$i]['ID'] == $option->new_value) {
            $templateset_in_array = true;
            break;
        }
    }

    if (!$templateset_in_array) {
        $option->new_value = '';
    } else {

        // clear template cache when changing template sets
        // (in order to do so, we have to change the global
        // template set ID now... should not be a problem --
        // in fact, if we don't do it now, very anomalous
        // problems occur)
        //
        global $sTemplateID;
        $sTemplateID = $option->new_value;
        Template::cache_template_file_hierarchy(TRUE);

    }

    /* Save the option like normal. */
    save_option($option);
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
    $found = false;
    while (!$found && (list($index, $data) = each($icon_themes))) {
        if ($data['PATH'] == $option->new_value)
            $found = true;
    }
    if ($found)
        setPref($data_dir, $username, 'icon_theme', $option->new_value);
    else
       setPref($data_dir, $username, 'icon_theme', 'none');

}

function css_theme_save ($option) {
    global $user_themes, $data_dir, $username;

    // Don't assume the new value is there, double check
    // and only save if found
    //
    $found = false;
    reset($user_themes);
    while (!$found && (list($index, $data) = each($user_themes))) {
        if ('u_'.$data['PATH'] == $option->new_value)
            $found = true;
    }
    if ($found)
        setPref($data_dir, $username, 'chosen_theme', $option->new_value);
    else
       setPref($data_dir, $username, 'chosen_theme', 'none');
}


