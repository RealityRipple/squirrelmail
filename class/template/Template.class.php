<?php
/**
  * Template.class.php
  *
  * This file contains an abstract (PHP 4, so "abstract" is relative)
  * class meant to define the basic template interface for the
  * SquirrelMail core application.  Subclasses should extend this
  * class with any custom functionality needed to interface a target
  * templating engine with SquirrelMail.
  *
  * @copyright 2003-2016 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  * @subpackage Template
  * @since 1.5.2
  *
  */

/** load template functions */
require(SM_PATH . 'functions/template/general_util.php');

/**
  * The SquirrelMail Template class.
  *
  * Basic template class for capturing values and pluging them into a template.
  * This class uses a similar API to Smarty.
  *
  * Methods that must be implemented by subclasses are as follows (see method
  * stubs below for further information about expected behavior):
  *
  *     assign()
  *     assign_by_ref()
  *     clear_all_assign()
  *     get_template_vars()
  *     append()
  *     append_by_ref()
  *     apply_template()
  *
  * @author Paul Lesniewski <paul at squirrelmail.org>
  * @package squirrelmail
  *
  */
class Template
{

    /**
      * The template ID
      *
      * @var string
      *
      */
    var $template_set_id = '';

    /**
      * The template set base directory (relative path from
      * the main SquirrelMail directory (SM_PATH))
      *
      * @var string
      *
      */
    var $template_dir = '';

    /**
      * The template engine (please use constants defined in constants.php)
      *
      * @var string
      *
      */
    var $template_engine = '';

    /**
      * The content type for this template set
      */
    var $content_type = '';

    /**
      * The fall-back template ID
      *
      * @var string
      *
      */
    var $fallback_template_set_id = '';

    /**
      * The fall-back template directory (relative
      * path from the main SquirrelMail directory (SM_PATH))
      *
      * @var string
      *
      */
    var $fallback_template_dir = '';

    /**
      * The fall-back template engine (please use
      * constants defined in constants.php)
      *
      * @var string
      *
      */
    var $fallback_template_engine = '';

    /**
      * Template file cache.  Structured as an array, whose keys
      * are all the template file names (with path information relative
      * to the template set's base directory, e.g., "css/style.css")
      * found in all parent template sets including the ultimate fall-back
      * template set.  Array values are sub-arrays with the
      * following key-value pairs:
      *
      *   PATH    --  file path, relative to SM_PATH
      *   SET_ID  --  the ID of the template set that this file belongs to
      *   ENGINE  --  the engine needed to render this template file
      *
      */
    var $template_file_cache = array();

    /**
      * Extra template engine class objects for rendering templates
      * that require a different engine than the one for the current
      * template set.  Keys should be the name of the template engine,
      * values are the corresponding class objects.
      *
      * @var array
      *
      */
    var $other_template_engine_objects = array();

    /**
      * Constructor
      *
      * Please do not call directly.  Use Template::construct_template().
      *
      * @param string $template_set_id the template ID
      *
      */
    function Template($template_set_id) {
//FIXME: find a way to test that this is ONLY ever called
//       from the construct_template() method (I doubt it
//       is worth the trouble to parse the current stack trace)
//        if (???)
//            trigger_error('Please do not use default Template() constructor.  Instead, use Template::construct_template().', E_USER_ERROR);

        $this->set_up_template($template_set_id);

    }

    /**
      * Construct Template
      *
      * This method should always be called instead of trying
      * to get a Template object from the normal/default constructor,
      * and is necessary in order to control the return value.
      *
      * @param string $template_set_id the template ID
      *
      * @return object The correct Template object for the given template set
      *
      * @static
      *
      */
    function construct_template($template_set_id) {

        $template = new Template($template_set_id);
        $template->override_plugins();
        return $template->get_template_engine_subclass();

    }

    /**
      * Set up internal attributes
      *
      * This method does most of the work for setting up
      * newly constructed objects.
      *
      * @param string $template_set_id the template ID
      *
      */
    function set_up_template($template_set_id) {

        // FIXME: do we want to place any restrictions on the ID like
        //        making sure no slashes included?
        // get template ID
        //
        $this->template_set_id = $template_set_id;


        $this->fallback_template_set_id = Template::get_fallback_template_set();


        // set up template directories
        //
        $this->template_dir
            = Template::calculate_template_file_directory($this->template_set_id);
        $this->fallback_template_dir
            = Template::calculate_template_file_directory($this->fallback_template_set_id);


        // determine content type, defaulting to text/html
        //
        $this->content_type = Template::get_template_config($this->template_set_id,
                                                            'content_type',
                                                            'text/html');


        // determine template engine
        // FIXME: assuming PHP template engine may not necessarily be a good thing
        //
        $this->template_engine = Template::get_template_config($this->template_set_id,
                                                               'template_engine',
                                                               SQ_PHP_TEMPLATE);


        // get template file cache
        //
        $this->template_file_cache = Template::cache_template_file_hierarchy($template_set_id);

    }

    /**
      * Determine what the ultimate fallback template set is.
      *
      * NOTE that if the fallback setting cannot be found in the
      * main SquirrelMail configuration settings that the value
      * of $default is returned.
      *
      * @param string $default The template set ID to use if
      *                        the fallback setting cannot be
      *                        found in SM config (optional;
      *                        defaults to "default").
      *
      * @return string The ID of the fallback template set.
      *
      * @static
      *
      */
    function get_fallback_template_set($default='default') {

// FIXME: do we want to place any restrictions on the ID such as
//        making sure no slashes included?

        // values are in main SM config file
        //
        global $templateset_fallback, $aTemplateSet;
        $aTemplateSet = (!isset($aTemplateSet) || !is_array($aTemplateSet)
                         ? array() : $aTemplateSet);
        $templateset_fallback = (!isset($templateset_fallback)
                                 ? $default : $templateset_fallback);

        // iterate through all template sets, is this a valid skin ID?
        //
        $found_it = FALSE;
        foreach ($aTemplateSet as $aTemplate) {
            if ($aTemplate['ID'] === $templateset_fallback) {
                $found_it = TRUE;
                break;
            }
        }

        if ($found_it)
            return $templateset_fallback;

        // FIXME: note that it is possible for $default to
        // point to an invalid (nonexistent) template set
        // and that error will not be caught here
        //
        return $default;

    }

    /**
      * Determine what the default template set is.
      *
      * NOTE that if the default setting cannot be found in the
      * main SquirrelMail configuration settings that the value
      * of $default is returned.
      *
      * @param string $default The template set ID to use if
      *                        the default setting cannot be
      *                        found in SM config (optional;
      *                        defaults to "default").
      *
      * @return string The ID of the default template set.
      *
      * @static
      *
      */
    function get_default_template_set($default='default') {

// FIXME: do we want to place any restrictions on the ID such as
//        making sure no slashes included?

        // values are in main SM config file
        //
        global $templateset_default, $aTemplateSet;
        $aTemplateSet = (!isset($aTemplateSet) || !is_array($aTemplateSet)
                         ? array() : $aTemplateSet);
        $templateset_default = (!isset($templateset_default)
                                 ? $default : $templateset_default);

        // iterate through all template sets, is this a valid skin ID?
        //
        $found_it = FALSE;
        foreach ($aTemplateSet as $aTemplate) {
            if ($aTemplate['ID'] === $templateset_default) {
                $found_it = TRUE;
                break;
            }
        }

        if ($found_it)
            return $templateset_default;

        // FIXME: note that it is possible for $default to
        // point to an invalid (nonexistent) template set
        // and that error will not be caught here
        //
        return $default;

    }

    /**
      * Determine what the RPC template set is.
      *
      * NOTE that if the default setting cannot be found in the
      * main SquirrelMail configuration settings that the value
      * of $default is returned.
      *
      * @param string $default The template set ID to use if
      *                        the default setting cannot be
      *                        found in SM config (optional;
      *                        defaults to "default_rpc").
      *
      * @return string The ID of the RPC template set.
      *
      * @static
      *
      */
    function get_rpc_template_set($default='default_rpc') {

// FIXME: do we want to place any restrictions on the ID such as
//        making sure no slashes included?

        // values are in main SM config file
        //
        global $rpc_templateset;
        $rpc_templateset = (!isset($rpc_templateset)
                         ? $default : $rpc_templateset);

        // FIXME: note that it is possible for this to
        // point to an invalid (nonexistent) template set
        // and that error will not be caught here
        //
        return $rpc_templateset;

    }

    /**
      * Allow template set to override plugin configuration by either
      * adding or removing plugins.
      *
      * NOTE: due to when this code executes, plugins activated here
      *       do not have access to the config_override and loading_prefs 
      *       hooks; instead, such plugins can use the 
      *       "template_plugins_override_after" hook defined below.
      *
      */
    function override_plugins() {

        global $disable_plugins, $plugins, $squirrelmail_plugin_hooks, $null;
        if ($disable_plugins) return;

        $add_plugins = Template::get_template_config($this->template_set_id,
                                                     'add_plugins', array());
        $remove_plugins = Template::get_template_config($this->template_set_id,
                                                        'remove_plugins', array());

//FIXME (?) we assume $add_plugins and $remove_plugins are arrays -- we could
//          error check here, or just assume that template authors or admins
//          won't screw up their config files


        // disable all plugins? (can still add some by using $add_plugins)
        //
        if (in_array('*', $remove_plugins)) {
            $plugins = array();
            $squirrelmail_plugin_hooks = array();
            $remove_plugins = array();
        }


        foreach ($add_plugins as $plugin_name) {
            // add plugin to global plugin array
            //
            $plugins[] = $plugin_name;


            // enable plugin -- emulate code from use_plugin() function
            // in SquirrelMail core, but also need to call the
            // "squirrelmail_plugin_init_<plugin_name>" function, which
            // in static configuration is not called (this inconsistency
            // could be a source of anomalous-seeming bugs in poorly
            // coded plugins)
            //
            if (file_exists(SM_PATH . "plugins/$plugin_name/setup.php")) {
                include_once(SM_PATH . "plugins/$plugin_name/setup.php");

                $function = "squirrelmail_plugin_init_$plugin_name";
                if (function_exists($function))
                    $function();
            }
        }

        foreach ($remove_plugins as $plugin_name) {
            // remove plugin from both global plugin & plugin hook arrays
            //
            $plugin_key = array_search($plugin_name, $plugins);
            if (!is_null($plugin_key) && $plugin_key !== FALSE) {
                unset($plugins[$plugin_key]);
                if (is_array($squirrelmail_plugin_hooks))
                    foreach (array_keys($squirrelmail_plugin_hooks) as $hookName) {
                        unset($squirrelmail_plugin_hooks[$hookName][$plugin_name]);
                    }
            }
        }

        do_hook('template_plugins_override_after', $null);

    }

    /**
      * Instantiate and return correct subclass for this template
      * set's templating engine.
      *
      * @param string $template_set_id The template set whose engine
      *                                is to be used as an override
      *                                (if not given, this template
      *                                set's engine is used) (optional).
      *
      * @return object The Template subclass object for the template engine.
      *
      */
    function get_template_engine_subclass($template_set_id='') {

        if (empty($template_set_id)) $template_set_id = $this->template_set_id;
        // FIXME: assuming PHP template engine may not necessarily be a good thing
        $engine = Template::get_template_config($template_set_id,
                                                'template_engine', SQ_PHP_TEMPLATE);


        $engine_class_file = SM_PATH . 'class/template/'
                           . $engine . 'Template.class.php';

        if (!file_exists($engine_class_file)) {
            trigger_error('Unknown template engine (' . $engine
                        . ') was specified in template configuration file',
                         E_USER_ERROR);
        }

        $engine_class = $engine . 'Template';
        require_once($engine_class_file);
        return new $engine_class($template_set_id);

    }

    /**
      * Determine the relative template directory path for
      * the given template ID.
      *
      * @param string $template_set_id The template ID from which to build
      *                                the directory path
      *
      * @return string The relative template path (based off of SM_PATH)
      *
      * @static
      *
      */
    function calculate_template_file_directory($template_set_id) {

        return 'templates/' . $template_set_id . '/';

    }

    /**
      * Determine the relative images directory path for
      * the given template ID.
      *
      * @param string $template_set_id The template ID from which to build
      *                                the directory path
      *
      * @return string The relative images path (based off of SM_PATH)
      *
      * @static
      *
      */
    function calculate_template_images_directory($template_set_id) {

        return 'templates/' . $template_set_id . '/images/';

    }

    /**
      * Return the relative template directory path for this template set.
      *
      * @return string The relative path to the template directory based
      *                from the main SquirrelMail directory (SM_PATH).
      *
      */
    function get_template_file_directory() {

        return $this->template_dir;

    }

    /**
      * Return the template ID for the fallback template set.
      *
      * @return string The ID of the fallback template set.
      *
      */
    function get_fallback_template_set_id() {

        return $this->fallback_template_set_id;

    }

    /**
      * Return the relative template directory path for the
      * fallback template set.
      *
      * @return string The relative path to the fallback template
      *                directory based from the main SquirrelMail
      *                directory (SM_PATH).
      *
      */
    function get_fallback_template_file_directory() {

        return $this->fallback_template_dir;

    }

    /**
      * Return the content-type for this template set.
      *
      * @return string The content-type.
      *
      */
    function get_content_type() {

        return $this->content_type;

    }

    /**
      * Get template set config setting
      *
      * Given a template set ID and setting name, returns the
      * setting's value.  Note that settings are cached in
      * session, so "live" changes to template configuration
      * won't be reflected until the user logs out and back
      * in again.
      *
      * @param string  $template_set_id The template set for which
      *                                 to look up the setting.
      * @param string  $setting         The name of the setting to
      *                                 retrieve.
      * @param mixed   $default         When the requested setting
      *                                 is not found, the contents
      *                                 of this value are returned
      *                                 instead (optional; default
      *                                 is NULL).
      *                                 NOTE that unlike sqGetGlobalVar(),
      *                                 this function will also return
      *                                 the default value if the
      *                                 requested setting is found
      *                                 but is empty.
      * @param boolean $live_config     When TRUE, the target template
      *                                 set's configuration file is
      *                                 reloaded every time this
      *                                 method is called.  Default
      *                                 behavior is to only load the
      *                                 configuration file if it had
      *                                 never been loaded before, but
      *                                 not again after that (optional;
      *                                 default FALSE).  Use with care!
      *                                 Should mostly be used for
      *                                 debugging.
      *
      * @return mixed The desired setting's value or if not found,
      *               the contents of $default are returned.
      *
      * @static
      *
      */
    function get_template_config($template_set_id, $setting,
                                 $default=NULL, $live_config=FALSE) {

        sqGetGlobalVar('template_configuration_settings',
                       $template_configuration_settings,
                       SQ_SESSION,
                       array());

        if ($live_config) unset($template_configuration_settings[$template_set_id]);


        // NOTE: could use isset() instead of empty() below, but
        //       this function is designed to replace empty values
        //       as well as non-existing values with $default
        //
        if (!empty($template_configuration_settings[$template_set_id][$setting]))
           return $template_configuration_settings[$template_set_id][$setting];


        // if template set configuration has been loaded, but this
        // setting is not known, return $default
        //
        if (!empty($template_configuration_settings[$template_set_id]))
           return $default;


        // otherwise (template set configuration has not been loaded before),
        // load it into session and return the desired setting after that
        //
        $template_config_file = SM_PATH
                     . Template::calculate_template_file_directory($template_set_id)
                     . 'config.php';

        if (!file_exists($template_config_file)) {

            trigger_error('No template configuration file was found where expected: ("'
                        . $template_config_file . '")', E_USER_ERROR);

        } else {

            // we require() the file to let PHP do the variable value
            // parsing for us, and read the file in manually so we can
            // know what variable names are used in the config file
            // (settings can be different depending on specific requirements
            // of different template engines)... the other way this may
            // be accomplished is to somehow diff the symbol table
            // before/after the require(), but anyway, this code should
            // only run once for this template set...
            //
            require($template_config_file);
            $file_contents = implode("\n", file($template_config_file));


            // note that this assumes no template settings have
            // a string in them that looks like a variable name like $x
            // also note that this will attempt to grab things like
            // $Id found in CVS headers, so we try to adjust for that
            // by checking that the variable is actually set
            //
            preg_match_all('/\$(\w+)/', $file_contents, $variables, PREG_PATTERN_ORDER);
            foreach ($variables[1] as $variable) {
                if (isset($$variable))
                    $template_configuration_settings[$template_set_id][$variable]
                        = $$variable;
            }

            sqsession_register($template_configuration_settings,
                               'template_configuration_settings');

            // NOTE: could use isset() instead of empty() below, but
            //       this function is designed to replace empty values
            //       as well as non-existing values with $default
            //
            if (!empty($template_configuration_settings[$template_set_id][$setting]))
                return $template_configuration_settings[$template_set_id][$setting];
            else
                return $default;

        }

    }

    /**
      * Obtain template file hierarchy from cache.
      *
      * If the file hierarchy does not exist in session, it is
      * constructed and stored in session before being returned
      * to the caller.
      *
      * @param string  $template_set_id  The template set for which
      *                                  the cache should be built.
      *                                  This function will save more
      *                                  than one set's files, so it
      *                                  may be called multiple times
      *                                  with different values for this
      *                                  argument.  When regenerating,
      *                                  all set caches are dumped.
      * @param boolean $regenerate_cache When TRUE, the file hierarchy
      *                                  is reloaded and stored fresh
      *                                  (optional; default FALSE).
      * @param array   $additional_files Must be in same form as the
      *                                  files in the file hierarchy
      *                                  cache.  These are then added
      *                                  to the cache (optional; default
      *                                  empty - no additional files).
      *
      * @return array Template file hierarchy array, whose keys
      *               are all the template file names for the given
      *               template set ID (with path information relative
      *               to the template set's base directory, e.g.,
      *               "css/style.css") found in all parent template
      *               sets including the ultimate fall-back template
      *               set.  Array values are sub-arrays with the
      *               following key-value pairs:
      *
      *                 PATH    --  file path, relative to SM_PATH
      *                 SET_ID  --  the ID of the template set that this file belongs to
      *                 ENGINE  --  the engine needed to render this template file
      *
      * @static
      *
      */
    function cache_template_file_hierarchy($template_set_id,
                                           $regenerate_cache=FALSE,
                                           $additional_files=array()) {

        sqGetGlobalVar('template_file_hierarchy', $template_file_hierarchy,
                       SQ_SESSION, array());


        if ($regenerate_cache) unset($template_file_hierarchy);

        if (!empty($template_file_hierarchy[$template_set_id])) {

            // have to add additional files if given before returning
            //
            if (!empty($additional_files)) {
                $template_file_hierarchy[$template_set_id]
                    = array_merge($template_file_hierarchy[$template_set_id],
                                  $additional_files);

                sqsession_register($template_file_hierarchy,
                                   'template_file_hierarchy');
            }

            return $template_file_hierarchy[$template_set_id];
        }


        // nothing in cache apparently, so go build it now
        //
        $template_file_hierarchy[$template_set_id] = Template::catalog_template_files($template_set_id);

        // additional files, if any
        //
        if (!empty($additional_files)) {
            $template_file_hierarchy[$template_set_id]
                = array_merge($template_file_hierarchy[$template_set_id],
                              $additional_files);
        }

        sqsession_register($template_file_hierarchy,
                           'template_file_hierarchy');

        return $template_file_hierarchy[$template_set_id];

    }

    /**
      * Traverse template hierarchy and catalogue all template
      * files (for storing in cache).
      *
      * Paths to all files in all parent, grand-parent, great grand
      * parent, etc. template sets (including the fallback template)
      * are catalogued; for identically named files, the file earlier
      * in the hierarchy (closest to this template set) is used.
      *
      * Refuses to traverse directories called ".svn"
      *
      * @param string $template_set_id The template set in which to
      *                                search for files
      * @param array  $file_list       The file list so far to be added
      *                                to (allows recursive behavior)
      *                                (optional; default empty array).
      * @param string $directory       The directory in which to search for
      *                                files (must be given as full path).
      *                                If empty, starts at top-level template
      *                                set directory (optional; default empty).
      *                                NOTE!  Use with care, as behavior is
      *                                unpredictable if directory given is not
      *                                part of correct template set.
      *
      * @return mixed The top-level caller will have an array of template
      *               files returned to it; recursive calls to this function
      *               do not receive any return value at all.  The format
      *               of the template file array is as described for the
      *               Template class attribute $template_file_cache
      *
      * @static
      *
      */
    function catalog_template_files($template_set_id, $file_list=array(), $directory='') {

        $template_base_dir = SM_PATH
                           . Template::calculate_template_file_directory($template_set_id);

        if (empty($directory)) {
            $directory = $template_base_dir;
        }


        // bail if we have been asked to traverse a Subversion directory
        //
        if (strpos($directory, '/.svn') === strlen($directory) - 5) return $file_list;


        $files_and_dirs = list_files($directory, '', FALSE, TRUE, FALSE, TRUE);

        // recurse for all the subdirectories in the template set
        //
        foreach ($files_and_dirs['DIRECTORIES'] as $dir) {
            $file_list = Template::catalog_template_files($template_set_id, $file_list, $dir);
        }

        // place all found files in the cache
        // FIXME: assuming PHP template engine may not necessarily be a good thing
        //
        $engine = Template::get_template_config($template_set_id,
                                                'template_engine', SQ_PHP_TEMPLATE);
        foreach ($files_and_dirs['FILES'] as $file) {

            // remove the part of the file path corresponding to the
            // template set's base directory
            //
            $relative_file = substr($file, strlen($template_base_dir));

            /**
             * only put file in cache if not already found in earlier template
             * PATH should be relative to SquirrelMail top directory
             */
            if (!isset($file_list[$relative_file])) {
                $file_list[$relative_file] = array(
                                                     'PATH'   => substr($file,strlen(SM_PATH)),
                                                     'SET_ID' => $template_set_id,
                                                     'ENGINE' => $engine,
                                                  );
            }

        }


        // now if we are currently at the top-level of the template
        // set base directory, we need to move on to the parent
        // template set, if any
        //
        if ($directory == $template_base_dir) {

            // use fallback when we run out of parents
            //
            $fallback_id = Template::get_fallback_template_set();
            $parent_id = Template::get_template_config($template_set_id,
                                                       'parent_template_set',
                                                       $fallback_id);

            // were we already all the way to the last level? just exit
            //
            // note that this code allows the fallback set to have
            // a parent, too, but can result in endless loops
            // if ($parent_id == $template_set_id) {
            //
            if ($fallback_id == $template_set_id) {
               return $file_list;
            }

            $file_list = Template::catalog_template_files($parent_id, $file_list);

        }

        return $file_list;

    }

    /**
      * Look for a template file in a plugin; add to template
      * file cache if found.
      *
      * The file is searched for in the following order:
      *
      *  - A directory for the current template set within the plugin:
      *       SM_PATH/plugins/<plugin name>/templates/<template name>/
      *  - In a directory for one of the current template set's ancestor
      *    (inherited) template sets within the plugin:
      *       SM_PATH/plugins/<plugin name>/templates/<parent template name>/
      *  - In a directory for the fallback template set within the plugin:
      *       SM_PATH/plugins/<plugin name>/templates/<fallback template name>/
      *
      * @param string $plugin          The name of the plugin
      * @param string $file            The name of the template file
      * @param string $template_set_id The ID of the template for which
      *                                to start looking for the file
      *                                (optional; default is current
      *                                template set ID).
      *
      * @return boolean TRUE if the template file was found, FALSE otherwise.
      *
      */
    function find_and_cache_plugin_template_file($plugin, $file, $template_set_id='') {

        if (empty($template_set_id))
            $template_set_id = $this->template_set_id;

        $file_path = SM_PATH . 'plugins/' . $plugin . '/'
                   . $this->calculate_template_file_directory($template_set_id)
                   . $file;

        if (file_exists($file_path)) {
            // FIXME: assuming PHP template engine may not necessarily be a good thing
            $engine = $this->get_template_config($template_set_id,
                                                 'template_engine', SQ_PHP_TEMPLATE);
            $file_list = array('plugins/' . $plugin . '/' . $file => array(
                                         'PATH'   => substr($file_path, strlen(SM_PATH)),
                                         'SET_ID' => $template_set_id,
                                         'ENGINE' => $engine,
                                                                          )
                              );
            $this->template_file_cache
                = $this->cache_template_file_hierarchy($this->template_set_id,
                                                       FALSE,
                                                       $file_list);
            return TRUE;
        }


        // not found yet, try parent template set
        // (use fallback when we run out of parents)
        //
        $fallback_id = $this->get_fallback_template_set();
        $parent_id = $this->get_template_config($template_set_id,
                                                'parent_template_set',
                                                $fallback_id);

        // were we already all the way to the last level? just exit
        //
        // note that this code allows the fallback set to have
        // a parent, too, but can result in endless loops
        // if ($parent_id == $template_set_id) {
        //
        if ($fallback_id == $template_set_id) {
            return FALSE;
        }

        return $this->find_and_cache_plugin_template_file($plugin, $file, $parent_id);

    }

    /**
      * Find the right template file.
      *
      * The template file is taken from the template file cache, thus
      * the file is taken from the current template, one of its
      * ancestors or the fallback template.
      *
      * Note that it is perfectly acceptable to load template files from
      * template subdirectories.  For example, JavaScript templates found
      * in the js/ subdirectory would be loaded by passing
      * "js/<javascript file name>" as the $filename.
      *
      * Note that the caller can also ask for ALL files in a directory
      * (and those in the same directory for all ancestor template sets)
      * by giving a $filename that is a directory name (ending with a
      * slash).
      *
      * If not found and the file is a plugin template file (indicated
      * by the presence of "plugins/" on the beginning of $filename),
      * the target plugin is searched for a substitue template file
      * before just returning nothing.
      *
      * Plugin authors must note that the $filename MUST be prefaced
      * with "plugins/<plugin name>/" in order to correctly resolve the
      * template file.
      *
      * @param string $filename The name of the template file,
      *                         possibly prefaced with
      *                         "plugins/<plugin name>/"
      *                         indicating that it is a plugin
      *                         template, or ending with a
      *                         slash, indicating that all files
      *                         for that directory name should
      *                         be returned.
      * @param boolean $directories_ok When TRUE, directory names
      *                                are acceptable search values,
      *                                and when returning a list of
      *                                directory contents, sub-directory
      *                                names will also be included
      *                                (optional; default FALSE).
      *                                NOTE that empty directories
      *                                are NOT included in the cache!
      * @param boolean $directories_only When TRUE, only directory names
      *                                  are included in the returned
      *                                  results.  (optional; default
      *                                  FALSE).  Setting this argument
      *                                  to TRUE forces $directories_ok
      *                                  to TRUE as well.
      *                                  NOTE that empty directories
      *                                  are NOT included in the cache!
      *
      * @return mixed The full path to the template file or a list
      *               of all files in the given directory if $filename
      *               ends with a slash; if not found, an empty string
      *               is returned.  The caller is responsible for
      *               throwing errors or other actions if template
      *               file is not found.
      *
      */
    function get_template_file_path($filename,
                                    $directories_ok=FALSE,
                                    $directories_only=FALSE) {

        if ($directories_only) $directories_ok = TRUE;


        // only looking for directory listing first...
        //
        // return list of all files in a directory (and that
        // of any ancestors)
        //
        if ($filename{strlen($filename) - 1} == '/') {

            $return_array = array();
            foreach ($this->template_file_cache as $file => $file_info) {

                // only want files in the requested directory
                // (AND not in a subdirectory!)
                //
                if (!$directories_only && strpos($file, $filename) === 0
                 && strpos($file, '/', strlen($filename)) === FALSE)
                    $return_array[] = SM_PATH . $file_info['PATH'];

                // directories too?  detect by finding any
                // array key that matches a file in a sub-directory
                // of the directory being processed
                //
                if ($directories_ok && strpos($file, $filename) === 0
                 && ($pos = strpos($file, '/', strlen($filename))) !== FALSE
                 && strpos($file, '/', $pos + 1) === FALSE) {
                    $directory_name = SM_PATH
                                    . substr($file_info['PATH'],
                                             0,
                                             strrpos($file_info['PATH'], '/'));
                    if (!in_array($directory_name, $return_array))
                        $return_array[] = $directory_name;
                }

            }
            return $return_array;

        }


        // just looking for singular file or directory below...
        //
        // figure out what to do with files not found
        //
        if ($directories_only || empty($this->template_file_cache[$filename]['PATH'])) {

            // if looking for directories...
            // have to iterate through cache and detect
            // directory by matching any file inside of it
            //
            if ($directories_ok) {
                foreach ($this->template_file_cache as $file => $file_info) {
                    if (strpos($file, $filename) === 0
                     && ($pos = strpos($file, '/', strlen($filename))) !== FALSE
                     && strpos($file, '/', $pos + 1) === FALSE) {
                        return SM_PATH . substr($file_info['PATH'],
                                                0,
                                                strrpos($file_info['PATH'], '/'));
                    }
                }

                if ($directories_only) return '';
            }

            // plugins get one more chance
            //
            if (strpos($filename, 'plugins/') === 0) {

                $plugin_name = substr($filename, 8, strpos($filename, '/', 8) - 8);
                $file = substr($filename, strlen($plugin_name) + 9);

                if (!$this->find_and_cache_plugin_template_file($plugin_name, $file))
                    return '';
                //FIXME: technically I guess we should check for directories
                //       here too, but that's overkill (no need) presently
                //       (plugin-provided alternate stylesheet dirs?!?  bah.)

            }

            // nothing... return empty string (yes, the else is intentional!)
            //
            else return '';

        }

        return SM_PATH . $this->template_file_cache[$filename]['PATH'];

    }

    /**
      * Get template engine needed to render given template file.
      *
      * If at all possible, just returns a reference to $this, but
      * some template files may require a different engine, thus
      * an object for that engine (which will subsequently be kept
      * in this object for future use) is returned.
      *
      * @param string $filename The name of the template file,
      *
      * @return object The needed template object to render the template.
      *
      */
    function get_rendering_template_engine_object($filename) {

        // for files that we cannot find engine info for,
        // just return $this
        //
        if (empty($this->template_file_cache[$filename]['ENGINE']))
            return $this;


        // otherwise, compare $this' engine to the file's engine
        //
        $engine = $this->template_file_cache[$filename]['ENGINE'];
        if ($this->template_engine == $engine)
            return $this;


        // need to load another engine... if already instantiated,
        // and stored herein, return that
        // FIXME: this assumes same engine setup in all template
        //        set config files that have same engine in common
        //        (but keeping a separate class object for every
        //        template set seems like overkill... for now we
        //        won't do that unless it becomes a problem)
        //
        if (!empty($this->other_template_engine_objects[$engine])) {
            $rendering_engine = $this->other_template_engine_objects[$engine];


        // otherwise, instantiate new engine object, add to cache
        // and return it
        //
        } else {
            $template_set_id = $this->template_file_cache[$filename]['SET_ID'];
            $this->other_template_engine_objects[$engine]
                = $this->get_template_engine_subclass($template_set_id);
            $rendering_engine = $this->other_template_engine_objects[$engine];
        }


        // now, need to copy over all the assigned variables
        // from $this to the rendering engine (YUCK! -- we need
        // to discourage template authors from creating
        // situations where engine changes occur)
        //
        $rendering_engine->clear_all_assign();
        $rendering_engine->assign($this->get_template_vars());


        // finally ready to go
        //
        return $rendering_engine;

    }

    /**
      * Return all JavaScript files provided by the template.
      *
      * All files found in the template set's "js" directory (and
      * that of its ancestors) with the extension ".js" are returned.
      *
      * @param boolean $full_path When FALSE, only the file names
      *                           are included in the return array;
      *                           otherwise, path information is
      *                           included (relative to SM_PATH)
      *                           (OPTIONAL; default only file names)
      *
      * @return array The required file names/paths.
      *
      */
    function get_javascript_includes($full_path=FALSE) {

        // since any page from a parent template set
        // could end up being loaded, we have to load
        // all js files from ancestor template sets,
        // not just this set
        //
        //$directory = SM_PATH . $this->get_template_file_directory() . 'js';
        //$js_files = list_files($directory, '.js', !$full_path);
        //
        $js_files = $this->get_template_file_path('js/');


        // parse out .js files only
        //
        $return_array = array();
        foreach ($js_files as $file) {

            if (substr($file, strlen($file) - 3) != '.js') continue;

            if ($full_path) {
                $return_array[] = $file;
            } else {
                $return_array[] = basename($file);
            }

        }

        return $return_array;

    }

    /**
      * Return all alternate stylesheets provided by template.
      *
      * All (non-empty) directories found in the template set's
      * "css/alternates" directory (and that of its ancestors)
      * are returned.
      *
      * Note that prettified names are constructed herein by
      * taking the directory name, changing underscores to spaces
      * and capitalizing each word in the resultant name.
      *
      * @param boolean $full_path When FALSE, only the file names
      *                           are included in the return array;
      *                           otherwise, path information is
      *                           included (relative to SM_PATH)
      *                           (OPTIONAL; default only file names)
      *
      * @return array A list of the available alternate stylesheets,
      *               where the keys are the file names (formatted
      *               according to $full_path) for the stylesheets,
      *               and the values are the prettified version of
      *               the file names for display to the user.
      *
      */
    function get_alternative_stylesheets($full_path=FALSE) {

        // since any page from a parent template set
        // could end up being loaded, we will load
        // all alternate css files from ancestor
        // template sets, not just this set
        //
        $css_directories = $this->get_template_file_path('css/alternates/', TRUE, TRUE);


        // prettify names
        //
        $return_array = array();
        foreach ($css_directories as $directory) {

            // CVS and SVN directories are not wanted
            //
            if ((strpos($directory, '/CVS') === strlen($directory) - 4)
             || (strpos($directory, '/.svn') === strlen($directory) - 5)) continue;

            $pretty_name = ucwords(str_replace('_', ' ', basename($directory)));

            if ($full_path) {
                $return_array[$directory] = $pretty_name;
            } else {
                $return_array[basename($directory)] = $pretty_name;
            }

        }

        return $return_array;

    }

    /**
      * Return all standard stylsheets provided by the template.
      *
      * All files found in the template set's "css" directory (and
      * that of its ancestors) with the extension ".css" except
      * "rtl.css" (which is dealt with separately) are returned.
      *
      * @param boolean $full_path When FALSE, only the file names
      *                           are included in the return array;
      *                           otherwise, path information is
      *                           included (relative to SM_PATH)
      *                           (OPTIONAL; default only file names)
      *
      * @return array The required file names/paths.
      *
      */
    function get_stylesheets($full_path=FALSE) {

        // since any page from a parent template set
        // could end up being loaded, we have to load
        // all css files from ancestor template sets,
        // not just this set
        //
        //$directory = SM_PATH . $this->get_template_file_directory() . 'css';
        //$css_files = list_files($directory, '.css', !$full_path);
        //
        $css_files = $this->get_template_file_path('css/');


        // need to leave out "rtl.css"
        //
        $return_array = array();
        foreach ($css_files as $file) {

            if (substr($file, strlen($file) - 4) != '.css') continue;
            if (strtolower(basename($file)) == 'rtl.css') continue;

            if ($full_path) {
                $return_array[] = $file;
            } else {
                $return_array[] = basename($file);
            }

        }


        // return sheets for the current template set
        // last so we can enable any custom overrides
        // of styles in ancestor sheets
        //
        return array_reverse($return_array);

    }

    /**
      * Generate links to all this template set's standard stylesheets
      *
      * Subclasses can override this function if stylesheets are
      * created differently for the template set's target output
      * interface.
      *
      * @return string The stylesheet links as they should be sent
      *                to the browser.
      *
      */
    function fetch_standard_stylesheet_links()
    {

        $sheets = $this->get_stylesheets(TRUE);
        return $this->fetch_external_stylesheet_links($sheets);

    }

    /**
      * Push out any other stylesheet links as provided (for
      * stylesheets not included with the current template set)
      *
      * Subclasses can override this function if stylesheets are
      * created differently for the template set's target output
      * interface.
      *
      * @param mixed $sheets List of the desired stylesheets
      *                      (file path to be used in stylesheet
      *                      href attribute) to output (or single
      *                      stylesheet file path).
FIXME: We could make the incoming array more complex so it can
       also contain the other parameters for create_css_link()
       such as $name, $alt, $mtype, and $xhtml_end
       But do we need to?
      *
      * @return string The stylesheet links as they should be sent
      *                to the browser.
      *
      */
    function fetch_external_stylesheet_links($sheets)
    {

        if (!is_array($sheets)) $sheets = array($sheets);
        $output = '';

        foreach ($sheets as $sheet) {
            $output .= create_css_link($sheet);
        }

        return $output;

    }

    /**
      * Send HTTP header(s) to browser.
      *
      * Subclasses can override this function if headers are
      * managed differently in the engine's target output
      * interface.
      *
      * @param mixed $headers A list of (or a single) header
      *                       text to be sent.
      * @param boolean $replace Whether or not to replace header(s)
      *                         previously sent header(s) of the
      *                         same type (this parameter may be
      *                         ignored in some implementations
      *                         of this class if the target interface
      *                         does not support this functionality)
      *                         (OPTIONAL; default = TRUE, always replace).
      *
      */
    function header($headers, $replace=TRUE)
    {

        if (!is_array($headers)) $headers = array($headers);

        foreach ($headers as $header) {
            $this->assign('header', $header);
            header($this->fetch('header.tpl'), $replace);
        }

    }

    /**
      * Generate a link to the right-to-left stylesheet for
      * this template set by getting the "rtl.css" file from
      * this template set, its parent (or grandparent, etc.)
      * template set, the fall-back template set, or finally,
      * fall back to SquirrelMail's own "rtl.css" if need be.
      *
      * Subclasses can override this function if stylesheets are
      * created differently for the template set's target output
      * interface.
      *
      * @return string The stylesheet link as it should be sent
      *                to the browser.
      *
      */
    function fetch_right_to_left_stylesheet_link()
    {

        // get right template file
        //
        $sheet = $this->get_template_file_path('css/rtl.css');

        // fall back to SquirrelMail's own default stylesheet
        //
        if (empty($sheet)) {
            $sheet = SM_PATH . 'css/rtl.css';
        }

        return create_css_link($sheet);

    }

    /**
      * Display the template
      *
      * @param string $file The template file to use
      *
      */
    function display($file)
    {

        echo $this->fetch($file);

    }

    /**
      * Applies the template and returns the resultant content string.
      *
      * @param string $file The template file to use
      *
      * @return string The template contents after applying the given template
      *
      */
    function fetch($file) {

        // get right template file
        //
        $template = $this->get_template_file_path($file);


        // special case stylesheet.tpl falls back to SquirrelMail's
        // own default stylesheet
        //
        if (empty($template) && $file == 'css/stylesheet.tpl') {
            $template = SM_PATH . 'css/default.css';
        }


        if (empty($template)) {

            trigger_error('The template "' . sm_encode_html_special_chars($file)
                          . '" could not be fetched!', E_USER_ERROR);

        } else {

            $aPluginOutput = array();
            $temp = array(&$aPluginOutput, &$this);
            $aPluginOutput = concat_hook_function('template_construct_' . $file,
                                                  $temp, TRUE);
            $this->assign('plugin_output', $aPluginOutput);

            //$output = $this->apply_template($template);
            $rendering_engine = $this->get_rendering_template_engine_object($file);
            $output = $rendering_engine->apply_template($template);

            // CAUTION: USE OF THIS HOOK IS HIGHLY DISCOURAGED AND CAN
            // RESULT IN NOTICABLE PERFORMANCE DEGREDATION.  Plugins
            // using this hook will probably be rejected by the
            // SquirrelMail team.
            //
            do_hook('template_output', $output);

            return $output;

        }

    }

    /**
      * Assigns values to template variables
      *
      * Note: this is an abstract method that must be implemented by subclass.
      *
      * @param array|string $tpl_var the template variable name(s)
      * @param mixed $value the value to assign
      *
      */
    function assign($tpl_var, $value = NULL) {

        trigger_error('Template subclass (' . $this->template_engine . 'Template.class.php) needs to implement the assign() method.', E_USER_ERROR);

    }

    /**
      * Assigns values to template variables by reference
      *
      * Note: this is an abstract method that must be implemented by subclass.
      *
      * @param string $tpl_var the template variable name
      * @param mixed $value the referenced value to assign
      *
      */
    function assign_by_ref($tpl_var, &$value) {

        trigger_error('Template subclass (' . $this->template_engine . 'Template.class.php) needs to implement the assign_by_ref() method.', E_USER_ERROR);

    }

    /**
      * Clears the values of all assigned varaiables.
      *
      */
    function clear_all_assign() {

        trigger_error('Template subclass (' . $this->template_engine . 'Template.class.php) needs to implement the clear_all_assign() method.', E_USER_ERROR);

    }

    /**
      * Returns assigned variable value(s).
      *
      * @param string $varname If given, the value of that variable
      *                        is returned, assuming it has been
      *                        previously assigned.  If not specified
      *                        an array of all assigned variables is
      *                        returned. (optional)
      *
      * @return mixed Desired single variable value or list of all
      *               assigned variable values.
      *
      */
    function get_template_vars($varname=NULL) {

        trigger_error('Template subclass (' . $this->template_engine . 'Template.class.php) needs to implement the get_template_vars() method.', E_USER_ERROR);

    }

    /**
      * Appends values to template variables
      *
      * Note: this is an abstract method that must be implemented by subclass.
      *
      * @param array|string $tpl_var the template variable name(s)
      * @param mixed $value the value to append
      * @param boolean $merge when $value is given as an array,
      *                       this indicates whether or not that
      *                       array itself should be appended as
      *                       a new template variable value or if
      *                       that array's values should be merged
      *                       into the existing array of template
      *                       variable values
      *
      */
    function append($tpl_var, $value = NULL, $merge = FALSE) {

        trigger_error('Template subclass (' . $this->template_engine . 'Template.class.php) needs to implement the append() method.', E_USER_ERROR);

    }

    /**
      * Appends values to template variables by reference
      *
      * Note: this is an abstract method that must be implemented by subclass.
      *
      * @param string $tpl_var the template variable name
      * @param mixed $value the referenced value to append
      * @param boolean $merge when $value is given as an array,
      *                       this indicates whether or not that
      *                       array itself should be appended as
      *                       a new template variable value or if
      *                       that array's values should be merged
      *                       into the existing array of template
      *                       variable values
      *
      */
    function append_by_ref($tpl_var, &$value, $merge = FALSE) {

        trigger_error('Template subclass (' . $this->template_engine . 'Template.class.php) needs to implement the append_by_ref() method.', E_USER_ERROR);

    }

    /**
      * Applys the template and generates final output destined
      * for the user's browser
      *
      * Note: this is an abstract method that must be implemented by subclass.
      *
      * @param string $filepath The full file path to the template to be applied
      *
      * @return string The output for the given template
      *
      */
    function apply_template($filepath) {

        trigger_error('Template subclass (' . $this->template_engine . 'Template.class.php) needs to implement the apply_template() method.', E_USER_ERROR);

    }

}

