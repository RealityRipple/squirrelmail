<?php

   /**
    **  setup.php -- Squirrelspell setup file
    **
    **  Copyright (c) 1999-2001 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  This is a standard Squirrelmail-1.2 API for plugins.
    **
    **  $Id$
    **/

    /**
     * This function checks whether the user's USER_AGENT is known to
     * be broken. If so, returns true and the plugin is invisible to the
     * offending browser.
     */
    function soupNazi(){

        global $HTTP_USER_AGENT, $SQSPELL_SOUP_NAZI;
        
        require_once('../plugins/squirrelspell/sqspell_config.php');

        $soup_menu = explode( ',', $SQSPELL_SOUP_NAZI );
        return( in_array( trim( $HTTP_USER_AGENT ), $soup_menu ) );
    }

    function squirrelmail_plugin_init_squirrelspell() {
        /* Standard initialization API. */
        global $squirrelmail_plugin_hooks;

        $squirrelmail_plugin_hooks['compose_button_row']['squirrelspell'] = 'squirrelspell_setup';
        $squirrelmail_plugin_hooks['optpage_register_block']['squirrelspell'] = 'squirrelspell_optpage_register_block';
        $squirrelmail_plugin_hooks['options_link_and_description']['squirrelspell'] = 'squirrelspell_options';
    }

    function squirrelspell_optpage_register_block() {
       // Gets added to the user's OPTIONS page.
       global $optpage_blocks;

       if ( !soupNazi() ) {

           /* Register Squirrelspell with the $optionpages array. */
           $optpage_blocks[] = array(
               'name' => _("SpellChecker Options"),
               'url'  => '../plugins/squirrelspell/sqspell_options.php',
               'desc' => _("Here you may set up how your personal dictionary is stored, edit it, or choose which languages should be available to you when spell-checking."),
               'js'   => TRUE
            );
        }
    }

    function squirrelspell_setup() {
        /* Gets added to the COMPOSE buttons row. */
        if ( !soupNazi() ) {
            /*
            ** using document.write to hide this functionality from people
            ** with JavaScript turned off.        
            */
            echo "<script type=\"text/javascript\">\n".
                    "<!--\n".
                    'document.write("<input type=\"button\" value=\"' .
                        _("Check Spelling") . '\" onclick=\"window.open(\'../plugins/squirrelspell/sqspell_interface.php\', \'sqspell\', \'status=yes,width=550,height=370,resizable=yes\')\">");'. "\n" .
                    "//-->\n".
                    "</script>\n";
        }
    }

?>
