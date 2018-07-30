<?php

/**
 * SquirrelMail Plugin Hook Registration File
 * Auto-generated using the configure script, conf.pl
 */

global $squirrelmail_plugin_hooks;

$squirrelmail_plugin_hooks['optpage_register_block']['administrator'] 
    = 'squirrelmail_administrator_optpage_register_block';
$squirrelmail_plugin_hooks['left_main_before']['filters'] 
    = 'start_filters_hook';
$squirrelmail_plugin_hooks['right_main_after_header']['filters'] 
    = 'start_filters_hook';
$squirrelmail_plugin_hooks['optpage_register_block']['filters'] 
    = 'filters_optpage_register_block_hook';
$squirrelmail_plugin_hooks['special_mailbox']['filters'] 
    = 'filters_special_mailbox';
$squirrelmail_plugin_hooks['rename_or_delete_folder']['filters'] 
    = 'update_for_folder_hook';
$squirrelmail_plugin_hooks['template_construct_login_webmail.tpl']['filters'] 
    = 'start_filters_hook';
$squirrelmail_plugin_hooks['folder_status']['filters'] 
    = 'filters_folder_status';
$squirrelmail_plugin_hooks['folder_status']['newmail'] 
    = 'newmail_folder_status';
$squirrelmail_plugin_hooks['template_construct_left_main.tpl']['newmail'] 
    = 'newmail_plugin';
$squirrelmail_plugin_hooks['optpage_register_block']['newmail'] 
    = 'newmail_optpage_register_block';
$squirrelmail_plugin_hooks['options_save']['newmail'] 
    = 'newmail_sav';
$squirrelmail_plugin_hooks['loading_prefs']['newmail'] 
    = 'newmail_pref';
$squirrelmail_plugin_hooks['optpage_set_loadinfo']['newmail'] 
    = 'newmail_set_loadinfo';
$squirrelmail_plugin_hooks['login_cookie']['secure_login'] 
    = 'secure_login_check';
$squirrelmail_plugin_hooks['webmail_top']['secure_login'] 
    = 'secure_login_logout';
$squirrelmail_plugin_hooks['configtest']['secure_login'] 
    = 'sl_check_configuration';
$squirrelmail_plugin_hooks['read_body_header']['smime'] 
    = 'smime_header_verify';
$squirrelmail_plugin_hooks['template_construct_read_headers.tpl']['smime'] 
    = 'smime_header_verify';
$squirrelmail_plugin_hooks['configtest']['smime'] 
    = 'smime_check_configuration';
$squirrelmail_plugin_hooks['optpage_register_block']['spamcop'] 
    = 'spamcop_options';
$squirrelmail_plugin_hooks['loading_prefs']['spamcop'] 
    = 'spamcop_load';
$squirrelmail_plugin_hooks['read_body_header_right']['spamcop'] 
    = 'spamcop_show_link';
$squirrelmail_plugin_hooks['compose_send']['spamcop'] 
    = 'spamcop_while_sending';


