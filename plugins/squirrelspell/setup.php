<?php

/**
 * SETUP.PHP
 * ---------
 * This is a standard Squirrelmail-1.2 API for plugins.
 */

/**
 * This function checks whether the user's USER_AGENT is known to
 * be broken. If so, returns true and the plugin is invisible to the
 * offending browser.
 */
function soupNazi(){
    global $HTTP_USER_AGENT;
    require ('../plugins/squirrelspell/sqspell_config.php');
    $soup_nazi = false;

    $soup_menu = explode(',', $SQSPELL_SOUP_NAZI);
    for ($i = 0; $i < sizeof($soup_menu); $i++) {
        if (stristr($HTTP_USER_AGENT, trim($soup_menu[$i]))) {
            $soup_nazi=true;
        }
    }
    return $soup_nazi;
}

function squirrelmail_plugin_init_squirrelspell() {
    /* Standard initialization API. */
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks["compose_button_row"]["squirrelspell"] = "squirrelspell_setup";
    $squirrelmail_plugin_hooks["options_register"]["squirrelspell"] = "squirrelspell_options";
    $squirrelmail_plugin_hooks["options_link_and_description"]["squirrelspell"] = "squirrelspell_options";
}

function squirrelspell_options() {
   // Gets added to the user's OPTIONS page.
   global $optionpages;

   if (soupNazi()) {
       return;
   }

   /* Register Squirrelspell with the $optionpages array. */
   $optionpages[] = array(
       'name' => 'SpellChecker Options',
       'url'  => '../plugins/squirrelspell/sqspell_options.php',
       'desc' => 'Here you may set up how your personal dictionary is stored,
                  edit it, or choose which languages should be available to
                  you when spell-checking.',
       'js'   => true
    );
}

function squirrelspell_setup() {
   /* Gets added to the COMPOSE buttons row. */
   if (soupNazi()) {
       return;
   }

?>
    <script type="text/javascript">
    <!--
        // using document.write to hide this functionality from people
        // with JavaScript turned off.
        document.write("<input type=\"button\" value=\"Check Spelling\" onclick=\"window.open('../plugins/squirrelspell/sqspell_interface.php', 'sqspell', 'status=yes,width=550,height=370,resizable=yes')\">");
    //-->
    </script>
<?php
}

?>
