<?php

/**
 * setup.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * $Id$
 * @package plugins
 * @subpackage translate
 */

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


/**
 * Initialize the translation plugin
 * @return void
 */
function squirrelmail_plugin_init_translate() {
  global $squirrelmail_plugin_hooks;

  $squirrelmail_plugin_hooks['read_body_bottom']['translate'] = 'translate_read_form';
  $squirrelmail_plugin_hooks['optpage_register_block']['translate'] = 'translate_optpage_register_block';
  $squirrelmail_plugin_hooks['loading_prefs']['translate'] = 'translate_pref';
  $squirrelmail_plugin_hooks['compose_button_row']['translate'] = 'translate_button';
}


/* Show the translation for a message you're reading */
function translate_read_form() {
    global $color, $translate_server;
    global $message, $translate_dir;
    global $translate_show_read;
    global $imapConnection, $wrap_at, $passed_id, $mailbox;

    if (!$translate_show_read) {
        return;
    }
    
    $translate_dir = 'to';

$trans_ar = $message->findDisplayEntity(array(), array('text/plain'));
$body = '';
if ($trans_ar[0] != '') {
  for ($i = 0; $i < count($trans_ar); $i++) {
    $body .= formatBody($imapConnection, $message, $color, $wrap_at, $trans_ar[$i], $passed_id, $mailbox);
  }
    $hookResults = do_hook('message_body', $body);
    $body = $hookResults[1];
  } else {
    $body = 'Message can\'t be translated';
}

    $new_body = $body;
    $pos = strpos($new_body,
            '">'. _("Download this as a file") . '</A></CENTER><BR></SMALL>');
    if (is_int($pos)) {
        $new_body = substr($new_body, 0, $pos);
    }

    $trans = get_html_translation_table(HTML_ENTITIES);
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


function translate_optpage_register_block() {
    global $optpage_blocks;
    $optpage_blocks[] = array(
        'name' => _("Translation Options"),
        'url'  => '../plugins/translate/options.php',
        'desc' => _("Which translator should be used when you get messages in a different language?"),
        'js'   => false
    );
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
      
    while (isset($test[$index]) && $test[$index]) {
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

    $ret = '  <option value="' . $value . '"';

    if (translate_does_it_match_language($to) && ($translate_dir == 'to')) {
        $ret .= ' SELECTED';
    }

    if (translate_does_it_match_language($from) && ($translate_dir == 'from')) {
        $ret .= ' SELECTED';
    }

    $ret .= '>' . $text . "</option>\n";

    return( $ret );
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
    translate_new_form('http://babelfish.altavista.com/babelfish/tr');
?>
    <input type="hidden" name="doit" value="done">
    <input type="hidden" name="intl" value="1">
    <input type="hidden" name="tt" value="urltext">
    <input type="hidden" name="urltext" value="<?php echo $message; ?>">
    <select name="lp"><?php
        echo translate_lang_opt('en',  'zh',  'en_zh',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Chinese"))) .
	     translate_lang_opt('en',  'fr',  'en_fr',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("French"))) .
             translate_lang_opt('',    'de',  'en_de',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("German"))) .
             translate_lang_opt('',    'it',  'en_it',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Italian"))) .
	     translate_lang_opt('en',  'ja',  'en_ja',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Japanese"))) .
	     translate_lang_opt('en',  'ko',  'en_ko',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Korean"))) .
             translate_lang_opt('',    'pt*', 'en_pt',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Portuguese"))) .
             translate_lang_opt('',    'es',  'en_es',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Spanish"))) .
             translate_lang_opt('zh',  'en',  'zh_en',
                                sprintf( _("%s to %s"),
                                         _("Chinese"),
                                         _("English"))) .
             translate_lang_opt('fr',  'en',  'fr_en',
                                sprintf( _("%s to %s"),
                                         _("French"),
                                         _("English"))) .
             translate_lang_opt('de',  '',    'de_en',
                                sprintf( _("%s to %s"),
                                         _("German"),
                                         _("English"))) .
             translate_lang_opt('it',  '',    'it_en',
                                sprintf( _("%s to %s"),
                                         _("Italian"),
                                         _("English"))) .
             translate_lang_opt('ja',  'en',  'ja_en',
                                sprintf( _("%s to %s"),
                                         _("Japanese"),
                                         _("English"))) .
             translate_lang_opt('ko',  'en',  'ko_en',
                                sprintf( _("%s to %s"),
                                         _("Korean"),
                                         _("English"))) .
             translate_lang_opt('pt*', '',    'pt_en',
                                sprintf( _("%s to %s"),
                                         _("Portuguese"),
                                         _("English"))) .
             translate_lang_opt('es',  '',    'es_en',
                                sprintf( _("%s to %s"),
                                         _("Spanish"),
                                         _("English"))) .
             translate_lang_opt('',    '',    'de_fr',
                                sprintf( _("%s to %s"),
                                         _("German"),
                                         _("French"))) .
             translate_lang_opt('',    '',    'fr_de',
                                sprintf( _("%s to %s"),
                                         _("French"),
                                         _("German"))) .
             translate_lang_opt('ru',  '',    'ru_en',
                                sprintf( _("%s to %s"),
                                         _("Russian"),
                                         _("English")));
    echo '</select>'.
         'Babelfish: <input type="Submit" value="' . _("Translate") . '">';

    translate_table_end();
}

function translate_form_go($message) {
    translate_new_form('http://translator.go.com/cb/trans_entry');
?>
    <input type=hidden name=input_type value=text>
    <select name=lp><?php
        echo translate_lang_opt('en', 'es', 'en_sp',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Spanish"))) .
             translate_lang_opt('',   'fr', 'en_fr',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("French"))) .
             translate_lang_opt('',   'de', 'en_ge',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("German"))) .
             translate_lang_opt('',   'it', 'en_it',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Italian"))) .
             translate_lang_opt('',   'pt', 'en_pt',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Portuguese"))) .
             translate_lang_opt('es', 'en', 'sp_en',
                                sprintf( _("%s to %s"),
                                         _("Spanish"),
                                         _("English"))) .
             translate_lang_opt('fr', '',   'fr_en',
                                sprintf( _("%s to %s"),
                                         _("French"),
                                         _("English"))) .
             translate_lang_opt('de', '',   'ge_en',
                                sprintf( _("%s to %s"),
                                         _("German"),
                                         _("English"))) .
             translate_lang_opt('it', '',   'it_en',
                                sprintf( _("%s to %s"),
                                         _("Italian"),
                                         _("English"))) .
             translate_lang_opt('pt', '',   'pt_en',
                                sprintf( _("%s to %s"),
                                         _("Portuguese"),
                                         _("English")));
    echo '</select>'.
         "<input type=\"hidden\" name=\"text\" value=\"$message\">".
         'Go.com: <input type="Submit" value="' . _("Translate") . '">';

    translate_table_end();
}

function translate_form_intertran($message) {
    translate_new_form('http://www.tranexp.com:2000/InterTran');
    echo '<INPUT TYPE="hidden" NAME="topframe" VALUE="yes">'.
         '<INPUT TYPE="hidden" NAME="type" VALUE="text">'.
         "<input type=\"hidden\" name=\"text\" value=\"$message\">";

    $left = '<SELECT name="from">' .
        translate_lang_opt('pt_BR', '',    'pob', _("Brazilian Portuguese")).
        translate_lang_opt('',      '',    'bul', _("Bulgarian") . ' (CP 1251)').
        translate_lang_opt('',      '',    'cro', _("Croatian") . ' (CP 1250)').
        translate_lang_opt('cs',    '',    'che', _("Czech") . ' (CP 1250)').
        translate_lang_opt('',      '',    'dan', _("Danish")).
        translate_lang_opt('nl',    '',    'dut', _("Dutch")).
        translate_lang_opt('en',    '!en', 'eng', _("English")).
        translate_lang_opt('',      '',    'spe', _("European Spanish")).
        translate_lang_opt('',      '',    'fin', _("Finnish")).
        translate_lang_opt('fr',    '',    'fre', _("French")).
        translate_lang_opt('de',    '',    'ger', _("German")).
        translate_lang_opt('',      '',    'grk', _("Greek")).
        translate_lang_opt('',      '',    'hun', _("Hungarian") . ' (CP 1250)').
        translate_lang_opt('',      '',    'ice', _("Icelandic")).
        translate_lang_opt('it',    '',    'ita', _("Italian")).
        translate_lang_opt('',      '',    'jpn', _("Japanese") . ' (Shift JIS)').
        translate_lang_opt('',      '',    'spl', _("Latin American Spanish")).
        translate_lang_opt('no*',   '',    'nor', _("Norwegian")).
        translate_lang_opt('pl',    '',    'pol', _("Polish") . ' (ISO 8859-2)').
        translate_lang_opt('',      '',    'poe', _("Portuguese")).
        translate_lang_opt('',      '',    'rom', _("Romanian") . ' (CP 1250)').
        translate_lang_opt('ru',    '',    'rus', _("Russian") . ' (CP 1251)').
        translate_lang_opt('',      '',    'sel', _("Serbian") . ' (CP 1250)').
        translate_lang_opt('',      '',    'slo', _("Slovenian") . ' (CP 1250)').
        translate_lang_opt('es',    '',    'spa', _("Spanish")).
        translate_lang_opt('sv',    '',    'swe', _("Swedish")).
        translate_lang_opt('',      '',    'tur', _("Turkish") . ' (CP 1254)').
        translate_lang_opt('',      '',    'wel', _("Welsh")).
        '</SELECT>';

    $right = '<SELECT name="to">'.
        translate_lang_opt('',    'pt_BR', 'pob', _("Brazilian Portuguese")).
        translate_lang_opt('',    '',      'bul', _("Bulgarian") . ' (CP 1251)').
        translate_lang_opt('',    '',      'cro', _("Croatian") . ' (CP 1250)').
        translate_lang_opt('',    'cs',    'che', _("Czech") . ' (CP 1250)').
        translate_lang_opt('',    '',      'dan', _("Danish")).
        translate_lang_opt('',    'nl',    'dut', _("Dutch")).
        translate_lang_opt('!en', 'en',    'eng', _("English")).
        translate_lang_opt('',    '',      'spe', _("European Spanish")).
        translate_lang_opt('',    '',      'fin', _("Finnish")).
        translate_lang_opt('',    'fr',    'fre', _("French")).
        translate_lang_opt('',    'de',    'ger', _("German")).
        translate_lang_opt('',    '',      'grk', _("Greek")).
        translate_lang_opt('',    '',      'hun', _("Hungarian") . ' (CP 1250)').
        translate_lang_opt('',    '',      'ice', _("Icelandic")).
        translate_lang_opt('',    'it',    'ita', _("Italian")).
        translate_lang_opt('',    '',      'jpn', _("Japanese") . ' (Shift JIS)').
        translate_lang_opt('',    '',      'spl', _("Latin American Spanish")).
        translate_lang_opt('',    'no*',   'nor', _("Norwegian")).
        translate_lang_opt('',    'pl',    'pol', _("Polish") . ' (ISO 8859-2)').
        translate_lang_opt('',    '',      'poe', _("Portuguese")).
        translate_lang_opt('',    '',      'rom', _("Romanian") . ' (CP 1250)').
        translate_lang_opt('',    'ru',    'rus', _("Russian") . ' (CP 1251)').
        translate_lang_opt('',    '',      'sel', _("Serbian") . ' (CP 1250)').
        translate_lang_opt('',    '',      'slo', _("Slovenian") . ' (CP 1250)').
        translate_lang_opt('',    'es',    'spa', _("Spanish")).
        translate_lang_opt('',    'sv',    'swe', _("Swedish")).
        translate_lang_opt('',      '',    'tur', _("Turkish") . ' (CP 1254)').
        translate_lang_opt('',    '',      'wel', _("Welsh")).
        '</SELECT>';
    printf( _("%s to %s"), $left, $right );
    echo 'InterTran: <input type=submit value="' . _("Translate") . '">';

    translate_table_end();
}

function translate_form_gpltrans($message) {
    translate_new_form('http://www.translator.cx/cgi-bin/gplTrans');
    echo '<select name="language">'.
        translate_lang_opt('nl', 'nl', 'dutch_dict',      _("Dutch")).
        translate_lang_opt('fr', 'fr', 'french_dict',     _("French")).
        translate_lang_opt('de', 'de', 'german_dict',     _("German")).
        translate_lang_opt('',   '',   'indonesian_dict', _("Indonesian")).
        translate_lang_opt('it', 'it', 'italian_dict',    _("Italian")).
        translate_lang_opt('',   '',   'latin_dict',      _("Latin")).
        translate_lang_opt('pt', 'pt', 'portuguese_dict', _("Portuguese")).
        translate_lang_opt('es', 'es', 'spanish_dict',    _("Spanish")).
        '</select>';
    echo '<select name="toenglish">';
    echo '<option value="yes" >'. _("to English") . '</option>';
    echo '<option value="no" selected>' . _("from English") . '</option></select>';
    echo "<input type=hidden name=text value=\"$message\">".
        'GPLTrans: <input type="submit" value="' . _("Translate") . '">';

    translate_table_end();
}

function translate_form_dictionary($message) {
    translate_new_form('http://dictionary.reference.com/translate/text.html');
    list($usec, $sec) = explode(" ",microtime());
    $time = $sec . (float)$usec*100000000;
    echo "<input type=hidden name=text value=\"$message\">".
         "<input type=hidden name=r value=\"$time\">".
         '<SELECT NAME="lp">'.
         translate_lang_opt('en',  'fr', 'en_fr',
                            sprintf( _("%s to %s"),
                                     _("English"),
                                     _("French"))) .
         translate_lang_opt('',    'ge', 'en_ge',
                            sprintf( _("%s to %s"),
                                     _("English"),
                                     _("German"))) .
         translate_lang_opt('',    'it', 'en_it',
                            sprintf( _("%s to %s"),
                                     _("English"),
                                     _("Italian"))) .
         translate_lang_opt('',    'pt*', 'en_pt',
                            sprintf( _("%s to %s"),
                                     _("English"),
                                     _("Portuguese"))) .
         translate_lang_opt('',    'es', 'en_es',
                            sprintf( _("%s to %s"),
                                     _("English"),
                                     _("Spanish"))) .
         translate_lang_opt('fr',  '', 'fr_en',
                            sprintf( _("%s to %s"),
                                     _("French"),
                                     _("English"))) .
         translate_lang_opt('',    '', 'fr_ge',
                            sprintf( _("%s to %s"),
                                     _("French"),
                                     _("German"))) .
         translate_lang_opt('',    '', 'ge_fr',
                            sprintf( _("%s to %s"),
                                     _("German"),
                                     _("French"))) .
         translate_lang_opt('de',  '', 'ge_en',
                            sprintf( _("%s to %s"),
                                     _("German"),
                                     _("English"))) .
         translate_lang_opt('it',  '', 'it_en',
                            sprintf( _("%s to %s"),
                                     _("Italian"),
                                     _("English"))) .
         translate_lang_opt('pt*', '', 'pt_en',
                            sprintf( _("%s to %s"),
                                     _("Portuguese"),
                                     _("English"))) .
         translate_lang_opt('es',  '', 'es_en',
                            sprintf( _("%s to %s"),
                                     _("Spanish"),
                                     _("English"))) .
         '</SELECT>'.
         'Dictionary.com: <INPUT TYPE="submit" VALUE="'._("Translate").'">';

  translate_table_end();
}

function translate_form_otenet($message) {
    translate_new_form('http://systran.otenet.gr/cgi-bin/systran.cgi');
?>
    <input type="hidden" name="doit" value="done">
    <INPUT NAME="partner" VALUE="OTEnet-en" type="hidden">
    <input type="hidden" name="urltext" value="<?php echo $message; ?>">
    <select name="lp" size="1"><?php
        echo translate_lang_opt('en',  'el',  'en_el',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Greek"))) .
             translate_lang_opt('el',  'en',  'el_en',
                                sprintf( _("%s to %s"),
                                         _("Greek"), 
                                         _("English"))) .
	     translate_lang_opt('fr',  'el',  'fr_el',
                                sprintf( _("%s to %s"),
                                         _("French"),
                                         _("Greek"))) .
	     translate_lang_opt('el',  'fr',  'el_fr',
                                sprintf( _("%s to %s"),
                                         _("Greek"),
                                         _("French"))) .
	     translate_lang_opt('#',  '',  '', "----------------") .
	     translate_lang_opt('en',  'fr',  'en_fr',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("French"))) .
	     translate_lang_opt('fr',  'en',  'fr_en',
                                sprintf( _("%s to %s"),
                                         _("French"),
                                         _("English"))) .
	     translate_lang_opt('en',  'de',  'en_de',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("German"))) .
	     translate_lang_opt('de',  'en',  'de_en',
                                sprintf( _("%s to %s"),
                                         _("German"),
                                         _("English"))) .
	     translate_lang_opt('en',  'es',  'en_es',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Spanish"))) .
	     translate_lang_opt('es',  'en',  'es_en',
                                sprintf( _("%s to %s"),
                                         _("Spanish"),
                                         _("English"))) .
	     translate_lang_opt('en',  'it',  'en_it',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Italian"))) .
	     translate_lang_opt('it',  'en',  'it_en',
                                sprintf( _("%s to %s"),
                                         _("Italian"),
                                         _("English"))) .
	     translate_lang_opt('en',  'pt',  'en_pt',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Portuguese"))) .
	     translate_lang_opt('pt',  'en',  'pt_en',
                                sprintf( _("%s to %s"),
                                         _("Portuguese"),
                                         _("English"))) .
	     translate_lang_opt('fr',  'de',  'fr_de',
                                sprintf( _("%s to %s"),
                                         _("French"),
                                         _("German"))) .
	     translate_lang_opt('de',  'fr',  'de_fr',
                                sprintf( _("%s to %s"),
                                         _("German"),
                                         _("French"))) .
	     translate_lang_opt('fr',  'es',  'fr_es',
                                sprintf( _("%s to %s"),
                                         _("French"),
                                         _("Spanish"))) .
	     translate_lang_opt('es',  'fr',  'es_fr',
                                sprintf( _("%s to %s"),
                                         _("Spanish"),
                                         _("French"))) .
	     translate_lang_opt('fr',  'nl',  'fr_nl',
                                sprintf( _("%s to %s"),
                                         _("French"),
                                         _("Dutch"))) .
	     translate_lang_opt('nl',  'fr',  'nl_fr',
                                sprintf( _("%s to %s"),
                                         _("Dutch"),
                                         _("French"))) ;
    echo '</select>'.
         'OTEnet: <input type="Submit" value="' . _("Translate") . '">';
    translate_table_end();

}

function translate_form_promt($message) {
    translate_new_form('http://www.translate.ru/text.asp#tr_form');
    echo '<input type="hidden" name="status" value="translate">';
    echo "<input type=\"hidden\" name=\"source\" value=\"$message\">";
    echo _("Interface language")." : ";
    echo "<select size=\"1\" name=\"lang\">\n";
    echo "<option value=\"en\">" . _("English") . "</option>\n";
    echo "<option value=\"ru\">" . _("Russian") . "</option>\n";
    echo "<option value=\"de\">" . _("German") . "</option>\n";
    echo "<option value=\"fr\">" . _("French") . "</option>\n";
    echo "<option value=\"es\">" . _("Spanish") . "</option>\n";
    echo "</select><br>\n";
    echo _("Translation direction")." : ";
    echo '<select size="1" id="direction" name="direction">';
        echo translate_lang_opt('en',  'ru',  'er',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Russian"))) .
             translate_lang_opt('ru',  'en',  're',
                                sprintf( _("%s to %s"),
                                         _("Russian"), 
                                         _("English"))) .
	     translate_lang_opt('de',  'ru',  'gr',
                                sprintf( _("%s to %s"),
                                         _("German"),
                                         _("Russian"))) .
	     translate_lang_opt('ru',  'de',  'rg',
                                sprintf( _("%s to %s"),
                                         _("Russian"),
                                         _("German"))) .
	     translate_lang_opt('fr',  'ru',  'fr',
                                sprintf( _("%s to %s"),
                                         _("French"),
                                         _("Russian"))) .
	     translate_lang_opt('ru',  'fr',  'rf',
                                sprintf( _("%s to %s"),
                                         _("Russian"),
                                         _("French"))) .
	     translate_lang_opt('es',  'ru',  'sr',
                                sprintf( _("%s to %s"),
                                         _("Spanish"),
                                         _("Russian"))) .
	     translate_lang_opt('it',  'ru',  'ir',
                                sprintf( _("%s to %s"),
                                         _("Italian"),
                                         _("Russian"))) .
	     translate_lang_opt('en',  'de',  'eg',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("German"))) .
	     translate_lang_opt('de',  'en',  'ge',
                                sprintf( _("%s to %s"),
                                         _("German"),
                                         _("English"))) .
	     translate_lang_opt('en',  'es',  'es',
                                sprintf( _("%s to %s"),
                                         _("English"),
                                         _("Spanish"))) .
	     translate_lang_opt('es',  'en',  'se',
                                sprintf( _("%s to %s"),
                                         _("Spanish"),
                                         _("English"))) ;
    echo "</select><br>\n";
    echo "<input type=\"hidden\" name=\"template\" value=\"General\">\n";
    echo 'PROMT: <input type="submit" value="' . _("Translate") . '">';

    translate_table_end();
}

?>
