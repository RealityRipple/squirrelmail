<?php
/**
 * SquirrelMail translate plugin sample configuration
 *
 * WARNING: This is only an example config. Don't use it for your 
 * configuration. Almisbar translation engine is not public.
 *
 * Copyright (c) 2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @version $Id$
 * @package plugins
 * @subpackage translate
 */

global $translate_default_engine;
$translate_default_engine='babelfish';

global $translate_babelfish_enabled;
$translate_babelfish_enabled=true;

global $translate_go_enabled;
$translate_go_enabled=false;

// Provides same options as babelfish. disabled
global $translate_dictionary_enabled;
$translate_dictionary_enabled=false;

global $translate_google_enabled;
$translate_google_enabled=true;

global $translate_intertran_enabled;
$translate_intertran_enabled=true;

global $translate_promt_enabled;
$translate_promt_enabled=true;

// interface looks Greek to me :)
global $translate_otenet_enabled;
$translate_otenet_enabled=false;

global $translate_gpltrans_enabled;
$translate_gpltrans_enabled=true;

// we managed to start gpltrans server
global $translate_gpltrans_url;
$translate_gpltrans_url='http://www.example.com/cgi-bin/gplTrans';

global $disable_compose_translate;
$disable_compose_translate=true;

/** Custom translation engine setup */

/**
 * Controls inclusion of custom translation engines.
 *
 * If you enable custon translation engines, you must include
 * translate_custom(), translate_custom_showtrad() and
 * $translate_custom_showoption() functions in your config.
 */
global $translate_custom_enabled;
$translate_custom_enabled=true;

/**
 * Add almisbar translation engine
 */
function translate_form_custom($message) {
    translate_new_form('http://www.almisbar.com/scripts/ata/txttrs.dll');
    echo '<p align="center">';
    echo '<input name="lang" type="hidden" value="eng" />';
    echo '<input name="auth" type="hidden" value="no" />';
    echo '<input name="text" type="hidden" value="' . $message . '" />';
    echo 'Al Misbar: <input type="submit" class="button" value="' . _("Translate") . '" />';

    echo '<br />';
    echo _("Translation Theme:") . '&nbsp;';
    echo '<select size="1" name="atatheme">'.
        '<option value="0">' . "General" .
        '<option value="M">' . "Entertainment & Music" .
        '<option value="H">' . "Sport" .
        '<option value="1">' . "Business" .
        '<option value="2">' . "Medical Science" .
        '<option value="3">' . "Engineering" .
        '<option value="4">' . "Technology" .
        '<option value="5">' . "Religion" .
        '<option value="6">' . "Law & Order" .
        '<option value="7">' . "Media & Journalism" .
        '<option value="8">' . "Humanities" .
        '<option value="9">' . "Agriculture" .
        '<option value="A">' . "Military" .
        '<option value="B">' . "Intelligence & Police" .
        '<option value="C">' . "Politics & Diplomacy" .
        '<option value="D">' . "Education" .
        '<option value="E">' . "Industry" .
        '<option value="F">' . "Oil & Minerals" .
        '<option value="G">' . "Arts & Literature" .
        '<option value="I">' . "Space & Astronomy" .
        '<option value="J">' . "Food & Drink" .
        '<option value="K">' . "Weather" .
        '<option value="L">' . "Government" .
        '<option value="N">' . "Science" .
        '</select>';
    echo '<br />';
    echo '<input checked="checked" id="option1" name="options" type="checkbox" value="translit" />';
    echo "Transliteration of abbreviations";
    echo '<input checked="checked" id="option2" name="options" type="checkbox" value="abbr" />';
    echo "Transliteration of proper nouns";
    echo '<input name="vowels" type="checkbox" value="a" />';
    echo "Show Harakat";
    echo '</p>';

    translate_table_end();
}

/**
 * Add info about almisbar
 *
 * String is not translated, because config file might be different
 */
function translate_custom_showtrad() {
    translate_showtrad_internal( 'Al Misbar',
        "English to Arabic translation (powered by Al-Mutarjim (TM) Al-Arabey and Al-Wafi v2 machine translation engine)",
       'http://www.almisbar.com/' );
}

/**
 * Add almisbar option
 */
function translate_custom_showoption() {
    translate_showoption_internal('server', 'custom', 'Al Misbar');
}
?>