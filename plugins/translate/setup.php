<?php

/* Easy plugin that sends the body of the message to a new browser
window using the specified translator.  It can also translate your
outgoing message if you send it to someone in a different country. 

  Languages from i18n, incorporated in the auto-language selection:
    en - English
    no - Norwegian (Bokm&aring;l)
    no_NO_ny - Norwegian (Nynorsk)
    de - Deutsch
    ru - Russian KOI8-R
    pl - Polish
    sv - Swedish
    nl - Dutch
    pt_BR - Portuguese (Brazil)
    fr - French
    it - Italian
    cs - Czech
    es - Spanish
    ko - Korean
*/


/* Initialize the translation plugin */
function squirrelmail_plugin_init_translate() {
  global $squirrelmail_plugin_hooks;

  $squirrelmail_plugin_hooks['read_body_bottom']['translate'] = 'translate_read_form';
  $squirrelmail_plugin_hooks['options_register']['translate'] = 'translate_opt';
  $squirrelmail_plugin_hooks['options_save']['translate'] = 'translate_sav';
  $squirrelmail_plugin_hooks['loading_prefs']['translate'] = 'translate_pref';
  $squirrelmail_plugin_hooks['compose_button_row']['translate'] = 'translate_button';
}


/* Show the translation for a message you're reading */
function translate_read_form() {
    global $color, $translate_server;
    global $body, $translate_dir;
    global $translate_show_read;

    if (!$translate_show_read) {
        return;
    }
    
    $translate_dir = 'to';
            
    $new_body = $body;
    $pos = strpos($new_body,
            '">Download this as a file</A></CENTER><BR></SMALL>');
    if (is_int($pos)) {
        $new_body = substr($new_body, 0, $pos);
    }
                     
    $trans = get_html_translation_table('HTMLENTITIES');
    $trans[' '] = '&nbsp;';
    $trans = array_flip($trans);
    $new_body = strtr($new_body, $trans);

    $new_body = urldecode($new_body);
    $new_body = strip_tags($new_body);
              
    /* I really don't like this next part ... */
    $new_body = str_replace('"', "''", $new_body);
    $new_body = strtr($new_body, "\n", ' ');
    
    $function = 'translate_form_' . $translate_server;
    $function($new_body);
}

function translate_table_end() {                     
  ?></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
  </form>
  <?php
}


function translate_button() {
    global $translate_show_send;
  
    if (! $translate_show_send) {
        return;
    }
}


function translate_opt() {
    global $optionpages;
    $optionpages[] = array(
        'name' => 'Translation Options',
        'url'  => '../plugins/translate/options.php',
        'desc' => 'Which translator should be used when you get messages in a different language?',
        'js'   => false
    );
}

function translate_sav() {
    global $username,$data_dir;
    global $submit_translate, $translate_translate_server;
    global $translate_translate_location;
    global $translate_translate_show_read;
    global $translate_translate_show_send;
    global $translate_translate_same_window;
  
    if ($submit_translate) {
        if (isset($translate_translate_server)) {
            setPref($data_dir, $username, 'translate_server', $translate_translate_server);
        } else {
            setPref($data_dir, $username, 'translate_server', 'babelfish');
        }

        if (isset($translate_translate_location)) {
            setPref($data_dir, $username, 'translate_location', $translate_translate_location);
        } else {
            setPref($data_dir, $username, 'translate_location', 'center');
        }

        if (isset($translate_translate_show_read)) {
            setPref($data_dir, $username, 'translate_show_read', '1');
        } else {
            setPref($data_dir, $username, 'translate_show_read', '');
        }

        if (isset($translate_translate_show_send)) {
            setPref($data_dir, $username, 'translate_show_send', '1');
        } else {
            setPref($data_dir, $username, 'translate_show_send', '');
        }

        if (isset($translate_translate_same_window)) {
           setPref($data_dir, $username, 'translate_same_window', '1');
        } else {
            setPref($data_dir, $username, 'translate_same_window', '');
        }

        echo '<center>Translation options saved.</center>';
    }
}


function translate_pref() { 
    global $username, $data_dir;
    global $translate_server, $translate_location;
    global $translate_show_send, $translate_show_read;
    global $translate_same_window;

    $translate_server = getPref($data_dir, $username, 'translate_server');
    if ($translate_server == '') {
        $translate_server = 'babelfish';
    }
    
    $translate_location = getPref($data_dir, $username, 'translate_location');
    if ($translate_location == '') {
        $translate_location = 'center';
    }
    
    $translate_show_send = getPref($data_dir, $username, 'translate_show_send');
    $translate_show_read = getPref($data_dir, $username, 'translate_show_read');
    $translate_same_window = getPref($data_dir, $username, 'translate_same_window');
}


/**
 * This function could be sped up.
 * It basically negates the process if a ! is found in the beginning and
 * matches a * at the end with 0 or more characters.
 */
function translate_does_it_match_language($test) {
    global $squirrelmail_language;
    $true = 1;
    $false = 0;
    $index = 0;
    $smindex = 0;
  
    if (! $test || ! $squirrelmail_language) {
        return $false;
    }
      
    if ($test[$index] == '!') {
        $index ++;
        $true = 0;
        $false = 1;
    }
    
    if (($index == 0) && ($test == $squirrelmail_language)) {
        return $true;
    }
      
    while ($test[$index]) {
        if ($test[$index] == '*') {
            return $true;
        }
        if ($test[$index] != $squirrelmail_language[$smindex]) {
            return $false;
        }
        $index ++;
        $smindex ++;
    }
      
    return $false;
}


function translate_lang_opt($from, $to, $value, $text) {
    global $translate_dir;
    
    echo '  <option value="' . $value . '"';
    
    if (translate_does_it_match_language($to) && ($translate_dir == 'to')) {
        echo ' SELECTED';
    }

    if (translate_does_it_match_language($from) && ($translate_dir == 'from')) {
        echo ' SELECTED';
    }
        
    echo '>' . $text . "</option>\n";
}


function translate_new_form($action) {
    global $translate_dir, $translate_new_window, $translate_location;
    global $color, $translate_same_window;

    echo '<form action="';
  
    if ($translate_dir == 'to') {
        echo $action;
    } else {
        echo 'translate.php';
    }
  
    echo '" method="post"';
  
    if (!$translate_same_window) {
        echo ' target="_blank"';
    }
  
    echo ">\n";

    ?><table align="<?php echo $translate_location ?>" cellpadding=3 cellspacing=0 border=0 bgcolor=<?php echo $color[10] ?>>
    <tr>
      <td>
        <table cellpadding=2 cellspacing=1 border=0 bgcolor="<?php echo $color[5] ?>">
          <tr>
            <td><?php
}

function translate_form_babelfish($message) {
    translate_new_form('http://babelfish.altavista.com/translate.dyn');
?>
    <input type="hidden" name="doit" value="done">
    <input type="hidden" name="BabelFishFrontPage" value="yes">
    <input type="hidden" name="bblType" value="urltext">
    <input type="hidden" name="urltext" value="<?php echo $message; ?>">
    <select name="lp"><?php
        translate_lang_opt('en',  'fr',  'en_fr', 'English to French');
        translate_lang_opt('',    'de',  'en_de', 'English to German');
        translate_lang_opt('',    'it',  'en_it', 'English to Italian');
        translate_lang_opt('',    'pt*', 'en_pt', 'English to Portuguese');
        translate_lang_opt('',    'es',  'en_es', 'English to Spanish');
        translate_lang_opt('fr',  'en',  'fr_en', 'French to English');
        translate_lang_opt('de',  '',    'de_en', 'German to English');
        translate_lang_opt('it',  '',    'it_en', 'Italian to English');
        translate_lang_opt('pt*', '',    'pt_en', 'Portuguese to English');
        translate_lang_opt('es',  '',    'es_en', 'Spanish to English');
        translate_lang_opt('',    '',    'de_fr', 'German to French');
        translate_lang_opt('',    '',    'fr_de', 'French to German');
        translate_lang_opt('ru',  '',    'ru_en', 'Russian to English');
?></select>
    Babelfish: <input type="Submit" value="Translate">
<?php
    translate_table_end();
}

function translate_form_go($message) {
    translate_new_form('http://translator.go.com/cb/trans_entry');
?>
    <input type=hidden name=input_type value=text>
    <select name=lp><?php
        translate_lang_opt('en', 'es', 'en_sp', 'English to Spanish');
        translate_lang_opt('',   'fr', 'en_fr', 'English to French');
        translate_lang_opt('',   'de', 'en_ge', 'English to German');
        translate_lang_opt('',   'it', 'en_it', 'English to Italian');
        translate_lang_opt('',   'pt', 'en_pt', 'English to Portuguese');
        translate_lang_opt('es', 'en', 'sp_en', 'Spanish to English');
        translate_lang_opt('fr', '',   'fr_en', 'French to English');
        translate_lang_opt('de', '',   'ge_en', 'German to English');
        translate_lang_opt('it', '',   'it_en', 'Italian to English');
        translate_lang_opt('pt', '',   'pt_en', 'Portuguese to English');
?></select>
    <input type="hidden" name="text" value="<?php echo $message ?>">
    Go.com: <input type="Submit" value="Translate">
<?php
    translate_table_end();
}

function translate_form_intertran($message) {
    translate_new_form('http://www.tranexp.com:2000/InterTran');
?>
    <INPUT TYPE="hidden" NAME="topframe" VALUE="yes">
    <INPUT TYPE="hidden" NAME="type" VALUE="text">
    <input type="hidden" name="text" value="<?php echo $message ?>">
    <SELECT name="from"><?PHP
        translate_lang_opt('pt_BR', '',    'pob', 'Brazilian Portuguese');
        translate_lang_opt('',      '',    'bul', 'Bulgarian (CP 1251)');
        translate_lang_opt('',      '',    'cro', 'Croatian (CP 1250)');
        translate_lang_opt('cs',    '',    'che', 'Czech (CP 1250)');
        translate_lang_opt('',      '',    'dan', 'Danish');
        translate_lang_opt('nl',    '',    'dut', 'Dutch');
        translate_lang_opt('en',    '!en', 'eng', 'English');
        translate_lang_opt('',      '',    'spe', 'European Spanish');
        translate_lang_opt('',      '',    'fin', 'Finnish');
        translate_lang_opt('fr',    '',    'fre', 'French');
        translate_lang_opt('de',    '',    'ger', 'German');
        translate_lang_opt('',      '',    'grk', 'Greek');
        translate_lang_opt('',      '',    'hun', 'Hungarian (CP 1250)');
        translate_lang_opt('',      '',    'ice', 'Icelandic');
        translate_lang_opt('it',    '',    'ita', 'Italian');
        translate_lang_opt('',      '',    'jpn', 'Japanese (Shift JIS)');
        translate_lang_opt('',      '',    'spl', 'Latin American Spanish');
        translate_lang_opt('no*',   '',    'nor', 'Norwegian');
        translate_lang_opt('pl',    '',    'pol', 'Polish (ISO 8859-2)');
        translate_lang_opt('',      '',    'poe', 'Portuguese');
        translate_lang_opt('',      '',    'rom', 'Romanian (CP 1250)');
        translate_lang_opt('ru',    '',    'rus', 'Russian (CP 1251)');
        translate_lang_opt('',      '',    'sel', 'Serbian (CP 1250)');
        translate_lang_opt('',      '',    'slo', 'Slovenian (CP 1250)');
        translate_lang_opt('es',    '',    'spa', 'Spanish');
        translate_lang_opt('sv',    '',    'swe', 'Swedish');
        translate_lang_opt('',      '',    'wel', 'Welsh');
?></SELECT> to <SELECT name="to"><?PHP
        translate_lang_opt('',    'pt_BR', 'pob', 'Brazilian Portuguese');
        translate_lang_opt('',    '',      'bul', 'Bulgarian (CP 1251)');
        translate_lang_opt('',    '',      'cro', 'Croatian (CP 1250)');
        translate_lang_opt('',    'cs',    'che', 'Czech (CP 1250)');
        translate_lang_opt('',    '',      'dan', 'Danish');
        translate_lang_opt('',    'nl',    'dut', 'Dutch');
        translate_lang_opt('!en', 'en',    'eng', 'English');
        translate_lang_opt('',    '',      'spe', 'European Spanish');
        translate_lang_opt('',    '',      'fin', 'Finnish');
        translate_lang_opt('',    'fr',    'fre', 'French');
        translate_lang_opt('',    'de',    'ger', 'German');
        translate_lang_opt('',    '',      'grk', 'Greek');
        translate_lang_opt('',    '',      'hun', 'Hungarian (CP 1250)');
        translate_lang_opt('',    '',      'ice', 'Icelandic');
        translate_lang_opt('',    'it',    'ita', 'Italian');
        translate_lang_opt('',    '',      'jpn', 'Japanese (Shift JIS)');
        translate_lang_opt('',    '',      'spl', 'Latin American Spanish');
        translate_lang_opt('',    'no*',   'nor', 'Norwegian');
        translate_lang_opt('',    'pl',    'pol', 'Polish (ISO 8859-2)');
        translate_lang_opt('',    '',      'poe', 'Portuguese');
        translate_lang_opt('',    '',      'rom', 'Romanian (CP 1250)');
        translate_lang_opt('',    'ru',    'rus', 'Russian (CP 1251)');
        translate_lang_opt('',    '',      'sel', 'Serbian (CP 1250)');
        translate_lang_opt('',    '',      'slo', 'Slovenian (CP 1250)');
        translate_lang_opt('',    'es',    'spa', 'Spanish');
        translate_lang_opt('',    'sv',    'swe', 'Swedish');
        translate_lang_opt('',    '',      'wel', 'Welsh');
?></SELECT>
    InterTran: <input type=submit value="Translate">
<?php
    translate_table_end();
}

function translate_form_gpltrans($message) {
    translate_new_form('http://www.translator.cx/cgi-bin/gplTrans');
?><select name="toenglish"><?php
    translate_lang_opt('en',  '!en', 'no',  'From English');
    translate_lang_opt('!en', 'en',  'yes', 'To English');
?></select><select name="language">
<?php
    translate_lang_opt('nl', 'nl', 'dutch_dict',      'Dutch');
    translate_lang_opt('fr', 'fr', 'french_dict',     'French');
    translate_lang_opt('de', 'de', 'german_dict',     'German');
    translate_lang_opt('',   '',   'indonesian_dict', 'Indonesian');
    translate_lang_opt('it', 'it', 'italian_dict',    'Italian');
    translate_lang_opt('',   '',   'latin_dict',      'Latin');
    translate_lang_opt('pt', 'pt', 'portuguese_dict', 'Portuguese');
    translate_lang_opt('es', 'es', 'spanish_dict',    'Spanish');
?></select>
    <input type="hidden" name="text" value="<?php echo $message ?>">
    GPLTrans: <input type="submit" value="Translate">
<?php
    translate_table_end();
}

function translate_form_dictionary($message) {
    translate_new_form('http://translate.dictionary.com:8800/systran/cgi');
?><INPUT TYPE=HIDDEN NAME=partner VALUE=LEXICO>
    <input type=hidden name=urltext value="<?php echo $message ?>">
<SELECT NAME="lp"><?php
    translate_lang_opt('en',  'fr', 'en_fr', 'English to French');
    translate_lang_opt('',    'de', 'en_de', 'English to German');
    translate_lang_opt('',    'it', 'en_it', 'English to Italian');
    translate_lang_opt('',    'pt*', 'en_pt', 'English to Portuguese');
    translate_lang_opt('',    'es', 'en_sp', 'English to Spanish');
    translate_lang_opt('fr',  '', 'fr_en', 'French to English');
    translate_lang_opt('',    '', 'fr_ge', 'French to German');
    translate_lang_opt('',    '', 'ge_fr', 'German to French');
    translate_lang_opt('de',  '', 'de_en', 'German to English');
    translate_lang_opt('it',  '', 'it_en', 'Italian to English');
    translate_lang_opt('pt*', '', 'pt_en', 'Portuguese to English');
    translate_lang_opt('es',  '', 'sp_en', 'Spanish to English');
?></SELECT>
    Dictionary.com: <INPUT TYPE="submit" VALUE="Translate">
<?php
  translate_table_end();
}
?>
