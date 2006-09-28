<?php

require(SM_PATH . 'functions/template.php');

/**
  * Template.class.php
  *
  * This file contains an abstract (PHP 4, so "abstract" is relative) 
  * class meant to define the basic template interface for the 
  * SquirrelMail core application.  Subclasses should extend this
  * class with any custom functionality needed to interface a target
  * templating engine with SquirrelMail.
  * 
  * @copyright &copy; 2003-2006 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  * @subpackage Template
  * @since 1.5.2
  *
  */

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
  *     append()
  *     append_by_ref()
  *     apply_template()
  *
  * @author Paul Lesniewski
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
    var $template_id = '';

    /**
      * The template directory to use
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
      * The default template ID
      *
      * @var string
      *
      */
    var $default_template_id = '';

    /**
      * The default template directory
      *
      * @var string
      *
      */
    var $default_template_dir = '';

    /**
      * The default template engine (please use constants defined in constants.php)
      *
      * @var string
      *
      */
    var $default_template_engine = '';

    /**
      * Javascript files required by the template
      *
      * @var array
      *
      */
    var $required_js_files = array();

    /**
      * Constructor
      *
      * Please do not call directly.  Use Template::construct_template().
      *
      * @param string $template_id the template ID
      *
      */
    function Template($template_id) {
//FIXME: find a way to test that this is ONLY ever called 
//       from the construct_template() method (I doubt it
//       is worth the trouble to parse the current stack trace)
//        if (???)
//            trigger_error('Please do not use default Template() constructor.  Instead, use Template::construct_template().', E_USER_ERROR);

        $this->set_up_template($template_id);

    }

    /**
      * Construct Template
      *
      * This method should always be called instead of trying
      * to get a Template object from the normal/default constructor,
      * and is necessary in order to control the return value.
      *
      * @param string $template_id the template ID
      *
      * @return object The correct Template object for the given template set
      *
      */
    function construct_template($template_id) {

        $template = new Template($template_id);
        return $template->get_template_engine_subclass();

    }

    /**
      * Set up internal attributes 
      *
      * This method does most of the work for setting up 
      * newly constructed objects.
      *
      * @param string $template_id the template ID
      *
      */
    function set_up_template($template_id) {

        // FIXME: do we want to place any restrictions on the ID like
        //        making sure no slashes included?
        // get template ID
        //
        $this->template_id = $template_id;


        // FIXME: do we want to place any restrictions on the ID like
        //        making sure no slashes included?
        // get default template ID
        //
        global $templateset_default, $aTemplateSet;
        $aTemplateSet = (!isset($aTemplateSet) || !is_array($aTemplateSet) 
                         ? array() : $aTemplateSet);
        $templateset_default = (!isset($templateset_default) ? 0 : $templateset_default);
        $this->default_template_id = (!empty($aTemplateSet[$templateset_default]['ID'])
                                      ? $aTemplateSet[$templateset_default]['ID'] 
                                      : 'default');


        // set up template directories
        //
        $this->template_dir 
            = Template::calculate_template_file_directory($this->template_id);
        $this->default_template_dir 
            = Template::calculate_template_file_directory($this->default_template_id);


        // pull in the template config file and load javascript and 
        // css files needed for this template set
        //
        $template_config_file = SM_PATH . $this->get_template_file_directory() 
                              . 'config.php';
        if (!file_exists($template_config_file)) {

            trigger_error('No template configuration file was found where expected: ("' 
                        . $template_config_file . '")', E_USER_ERROR);

        } else {

            require($template_config_file);
            $this->required_js_files = is_array($required_js_files) 
                                     ? $required_js_files : array();

        }


        // determine template engine 
        //
        if (empty($template_engine)) {
            trigger_error('No template engine ($template_engine) was specified in template configuration file: ("' 
                        . $template_config_file . '")', E_USER_ERROR);
        } else {
            $this->template_engine = $template_engine;
        }

    }

    /**
      * Instantiate and return correct subclass for this template
      * set's templating engine.
      *
      * @return object The Template subclass object for the template engine.
      *
      */
    function get_template_engine_subclass() {

        $engine_class_file = SM_PATH . 'class/template/' 
                           . $this->template_engine . 'Template.class.php';

        if (!file_exists($engine_class_file)) {
            trigger_error('Unknown template engine (' . $this->template_engine 
                        . ') was specified in template configuration file',
                         E_USER_ERROR);
        }

        $engine_class = $this->template_engine . 'Template';
        require($engine_class_file);
        return new $engine_class($this->template_id);

    }

    /**
      * Determine the relative template directory path for 
      * the given template ID.
      *
      * @param string $template_id The template ID from which to build 
      *                            the directory path
      *
      * @return string The relative template path (based off of SM_PATH)
      *
      */
    function calculate_template_file_directory($template_id) {

        return 'templates/' . $template_id . '/';

    }

    /**
      * Determine the relative images directory path for 
      * the given template ID.
      *
      * @param string $template_id The template ID from which to build 
      *                            the directory path
      *
      * @return string The relative images path (based off of SM_PATH)
      *
      */
    function calculate_template_images_directory($template_id) {

        return 'templates/' . $template_id . '/images/';

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
      * Return the relative template directory path for the DEFAULT template set.
      *
      * @return string The relative path to the default template directory based
      *                from the main SquirrelMail directory (SM_PATH).
      *
      */
    function get_default_template_file_directory() {

        return $this->default_template_dir;

    }


    /**
      * Find the right template file.
      *
      * Templates are expected to be found in the template set directory,
      * for example:
      *     SM_PATH/templates/<template name>/
      * or, in the case of plugin templates, in a plugin directory in the 
      * template set directory, for example:
      *     SM_PATH/templates/<template name>/plugins/<plugin name>/
      * *OR* in a template directory in the plugin as a fallback, for example:
      *     SM_PATH/plugins/<plugin name>/templates/<template name>/
      * If the correct file is not found for the current template set, a 
      * default template is loaded, which is expected to be found in the 
      * default template directory, for example:
      *     SM_PATH/templates/<default template>/
      * or for plugins, in a plugin directory in the default template set,
      * for example:
      *     SM_PATH/templates/<default template>/plugins/<plugin name>/
      * *OR* in a default template directory in the plugin as a fallback,
      * for example:
      *     SM_PATH/plugins/<plugin name>/templates/<default template>/
      * *OR* if the plugin template still cannot be found, one last attempt
      * will be made to load it from a hard-coded default template directory
      * inside the plugin:
      *     SM_PATH/plugins/<plugin name>/templates/default/
      *
      * Plugin authors must note that the $filename MUST be prefaced
      * with "plugins/<plugin name>/" in order to correctly resolve the 
      * template file.
      *
      * Note that it is perfectly acceptable to load template files from
      * template subdirectories other than plugins; for example, JavaScript
      * templates found in the js/ subdirectory would be loaded by passing
      * "js/<javascript file name>" as the $filename.
      *
      * @param string $filename The name of the template file,
      *                         possibly prefaced with 
      *                         "plugins/<plugin name>/"
      *                         indicating that it is a plugin
      *                         template.
      *
      * @return string The full path to the template file; if 
      *                not found, an empty string.  The caller
      *                is responsible for throwing erros or 
      *                other actions if template file is not found.
      *
      */
    function get_template_file_path($filename) {

        // is the template found in the normal template directory?
        //
        $filepath = SM_PATH . $this->get_template_file_directory() . $filename;
        if (!file_exists($filepath)) {

            // no, so now we have to get the default template...
            // however, in the case of a plugin template, let's
            // give one more try to find the right template as
            // provided by the plugin
            //
            if (strpos($filename, 'plugins/') === 0) {

                $plugin_name = substr($filename, 8, strpos($filename, '/', 8) - 8);
                $filepath = SM_PATH . 'plugins/' . $plugin_name . '/'
                          . $this->get_template_file_directory() 
                          . substr($filename, strlen($plugin_name) + 9);

                // no go, we have to get the default template, 
                // first try the default SM template
                //
                if (!file_exists($filepath)) {

                    $filepath = SM_PATH 
                              . $this->get_default_template_file_directory() 
                              . $filename;

                    // still no luck?  get default template from the plugin
                    //
                    if (!file_exists($filepath)) {

                        $filepath = SM_PATH . 'plugins/' . $plugin_name . '/'
                                  . $this->get_default_template_file_directory() 
                                  . substr($filename, strlen($plugin_name) + 9);

                        // we're almost out of luck, try hard-coded default...
                        //
                        if (!file_exists($filepath)) {

                            $filepath = SM_PATH . 'plugins/' . $plugin_name 
                                      . '/templates/default/'
                                      . substr($filename, strlen($plugin_name) + 9);

                            // no dice whatsoever, return empty string
                            //
                            if (!file_exists($filepath)) {
                                $filepath = '';
                            }

                        }

                    }

                }


            // get default template for non-plugin templates
            //
            } else {

                $filepath = SM_PATH . $this->get_default_template_file_directory() 
                          . $filename;

                // no dice whatsoever, return empty string
                //
                if (!file_exists($filepath)) {
                    $filepath = '';
                }

            }

        }

        return $filepath;

    }

    /**
      * Return the list of javascript files required by this 
      * template set.  Only files that actually exist are returned.
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

//FIXME -- change this system so it just returns whatever is in js dir? 
//         bah, maybe not, but we might want to enhance this to pull in
//         js files not found in this or the default template from SM_PATH/js??? 
        $paths = array();
        foreach ($this->required_js_files as $file) {
            $file = $this->get_template_file_path('js/' . $file);
            if (!empty($file)) {
                if ($full_path) {
                    $paths[] = $file;
                } else {
                    $paths[] = basename($file);
                }
            }
        }

        return $paths;

    }

    /**
      * Return all standard stylsheets provided by the template.  
      *
      * All files found in the template set's "css" directory with
      * the extension ".css" except "rtl.css" (which is dealt with
      * separately) are returned.
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

        $directory = SM_PATH . $this->get_template_file_directory() . 'css';
        $files = list_files($directory, '.css', !$full_path);

        // need to leave out "rtl.css" 
        //
        $return_array = array();
        foreach ($files as $file) {

            if (strtolower(basename($file)) == 'rtl.css') {
                continue;
            }

            $return_array[] = $file;

        }

        return $return_array;

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
      * managed differently in the template set's target output
      * interface.
      *
      * @param mixed $headers A list of (or a single) header 
      *                       text to be sent.
      *
      */
    function header($headers)
    {

        if (!is_array($headers)) $headers = array($headers);

        foreach ($headers as $header) {
            header($header);
        }

    }

    /**
      * Generate a link to the right-to-left stylesheet for 
      * this template set, or use the one for the default 
      * template set if not found, or finally, fall back 
      * to SquirrelMail's own "rtl.css" if need be.
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

            trigger_error('The template "' . htmlspecialchars($file)
                          . '" could not be fetched!', E_USER_ERROR);

        } else {

            $aPluginOutput = array();
            $aPluginOutput = concat_hook_function('template_construct_' . $file,
                                                  array($aPluginOutput, $this));
            $this->assign('plugin_output', $aPluginOutput);

            $output = $this->apply_template($template);

            // CAUTION: USE OF THIS HOOK IS HIGHLY DISCOURAGED AND CAN
            // RESULT IN NOTICABLE PERFORMANCE DEGREDATION.  Plugins
            // using this hook will probably be rejected by the
            // SquirrelMail team.
            //
            $output = filter_hook_function('template_output', $output);

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

