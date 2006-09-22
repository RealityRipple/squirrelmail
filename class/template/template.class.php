<?php

/**
 * Copyright 2003, Paul James
 *
 * This file contains some methods from the Smarty templating engine version
 * 2.5.0 by Monte Ohrt <monte@ispi.net> and Andrei Zmievski <andrei@php.net>.
 *
 * The SquirrelMail (Foowd) template implementation.
 * Derived from the foowd template implementation and adapted
 * for squirrelmail
 * @copyright &copy; 2005-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * The SquirrelMail (Foowd) template class.
 *
 * Basic template class for capturing values and pluging them into a template.
 * This class uses a similar API to Smarty.
 *
 * @author Paul James
 * @author Monte Ohrt <monte at ispi.net>
 * @author Andrei Zmievski <andrei at php.net>
 * @package squirrelmail
 */
class Template
{
  /**
   * The templates values array
   *
   * @var array
   */
  var $values = array();

  /**
   * The template directory to use
   *
   * @var string
   */
  var $template_dir = '';

  /**
   * The default template directory
   *
   * @var string
   */
  var $default_template_dir = 'templates/default/';

  /**
   * Template files provided by this template set
   *
   * @var array
   */
  var $templates_provided = array();

  /**
   * Javascript files required by the template
   *
   * @var array
   */
  var $required_js_files = array();

  /**
   * Javascript files provided by the template.  If a JS file is required, but
   * not provided, the js file by the same name will be included from the
   * default template directory.
   *
   * @var array
   */
  var $provided_js_files = array();

  /**
   * Additional stylesheets provided by the template.  This allows template
   * authors to provide additional CSS sheets to templates while using the 
   * default template set stylesheet for other definitions.
   */
  var $additional_css_sheets = array();

    /**
     * Constructor
     *
     * @param string $sTplDir where the template set is located
     */
    function Template($sTplDir) {
        $this->template_dir = $sTplDir;

        // Pull in the tempalte config file
        if (!file_exists($this->template_dir . 'template.php')) {
             trigger_error('No template.php could be found in the requested template directory ("'.$this->template_dir.'")', E_USER_ERROR);
        } else {
            include ($this->template_dir . 'template.php');
            $this->templates_provided = is_array($templates_provided) ? $templates_provided : array();
            $this->required_js_files = is_array($required_js_files) ? $required_js_files : array();
            $this->provided_js_files = is_array($provided_js_files) ? $provided_js_files: array();
            $this->additional_css_sheets = is_array($additional_css_sheets) ? $additional_css_sheets : array();
        }
  }


  /**
   * Assigns values to template variables
   *
   * @param array|string $tpl_var the template variable name(s)
   * @param mixed $value the value to assign
   */
  function assign($tpl_var, $value = NULL) {
    if (is_array($tpl_var))
    {
      foreach ($tpl_var as $key => $val)
      {
        if ($key != '')
          $this->values[$key] = $val;
      }
    }
    else
    {
      if ($tpl_var != '')
        $this->values[$tpl_var] = $value;
    }
  }

  /**
   * Assigns values to template variables by reference
   *
   * @param string $tpl_var the template variable name
   * @param mixed $value the referenced value to assign
   */
  function assign_by_ref($tpl_var, &$value)
  {
    if ($tpl_var != '')
      $this->values[$tpl_var] = &$value;
  }

  /**
   * Appends values to template variables
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
   */
  function append($tpl_var, $value = NULL, $merge = FALSE)
  {
    if (is_array($tpl_var))
    {
      //FIXME: $tpl_var is supposed to be a list of template var names,
      //       so we should be looking at the values NOT the keys!
      foreach ($tpl_var as $_key => $_val)
      {
        if ($_key != '')
        {
          if(isset($this->values[$_key]) && !is_array($this->values[$_key]))
            settype($this->values[$_key],'array');

          //FIXME: we should be iterating the $value array here not the values of the
          //       list of template variable names!  I think this is totally broken 
          // This might just be a matter of needing to clarify the method's API;
          // values may have been meant to be passed in $tpl_var in the case that
          // $tpl_var is an array.  Ugly and non-intuitive.
          // PROPOSAL: API should be as such:  
          //   if (is_string($tpl_var)) then $values are added/merged as already done
          //   if (is_array($tpl_var)) then $values is required to be an array whose
          //                                keys must match up with $tpl_var keys and
          //                                whose values are then what is added to
          //                                each template variable value (array or 
          //                                strings, doesn't matter)
          if($merge && is_array($_val))
          {
            foreach($_val as $_mkey => $_mval)
              $this->values[$_key][$_mkey] = $_mval;
          }
          else
            $this->values[$_key][] = $_val;
        }
      }
    }
    else
    {
      if ($tpl_var != '' && isset($value))
      {
        if(isset($this->values[$tpl_var]) && !is_array($this->values[$tpl_var]))
          settype($this->values[$tpl_var],'array');

        if($merge && is_array($value))
        {
          foreach($value as $_mkey => $_mval)
            $this->values[$tpl_var][$_mkey] = $_mval;
        }
        else
          $this->values[$tpl_var][] = $value;
      }
    }
  }

  /**
   * Appends values to template variables by reference
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
   */
  function append_by_ref($tpl_var, &$value, $merge = FALSE)
  {
    if ($tpl_var != '' && isset($value))
    {
      if(!@is_array($this->values[$tpl_var]))
        settype($this->values[$tpl_var],'array');

      if ($merge && is_array($value))
      {
        foreach($value as $_key => $_val)
          $this->values[$tpl_var][$_key] = &$value[$_key];
      }
      else
        $this->values[$tpl_var][] = &$value;
    }
  }


    /**
     *
     * Return the relative template directory path for this template set.
     *
     * @return string The relative path to the template directory based
     *                from the main SquirrelMail directory (SM_PATH).
     *
     */
    function get_template_file_directory() {

//FIXME: temporarily parse off SM_PATH from the template dir class attribute until we can change the entire template subsystem such that the template dir is derived internally in this class from the template ID/name/attributes
return substr($this->template_dir, strlen(SM_PATH));
        return $this->template_dir;

    }


    /**
     *
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
     *
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
     *     SM_PATH/templates/default/
     * or for plugins, in a plugin directory in the default template set,
     * for example:
     *     SM_PATH/templates/default/plugins/<plugin name>/
     * *OR* in a default template directory in the plugin as a fallback,
     * for example:
     *     SM_PATH/plugins/<plugin name>/templates/default/
     *
     * Plugin authors must note that the $filename MUST be prefaced
     * with "plugins/<plugin name>/" in order to correctly resolve the 
     * template file.
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

                        // no dice whatsoever, return empty string
                        //
                        if (!file_exists($filepath)) {
                            $filepath = '';
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
     * Display the template
     *
     * @param string $file The template file to use
     */
    function display($file)
    {

        // get right template file
        //
        $template = $this->get_template_file_path($file);
        if (empty($template)) {

            trigger_error('The template "' . htmlspecialchars($file) 
                          . '" could not be displayed!', E_USER_ERROR);

        } else {

            $aPluginOutput = array();
            $aPluginOutput = concat_hook_function('template_construct_' . $file,
                                                  array($aPluginOutput, $this));
            $this->assign('plugin_output', $aPluginOutput);

            // pull in our config file ($t?  let's try to be more verbose please :-) )
            //
            $t = &$this->values; // place values array directly in scope

            ob_start();
            include($template);

            // CAUTION: USE OF THIS HOOK IS HIGHLY DISCOURAGED AND CAN
            // RESULT IN NOTICABLE PERFORMANCE DEGREDATION.  Plugins
            // using this hook will probably be rejected by the 
            // SquirrelMail team.
            //
            // Anyone hooking in here that wants to manipulate the output
            // buffer has to get the buffer, clean it and then echo the 
            // new buffer like this:
            // $buffer = ob_get_contents(); ob_clean(); .... echo $new_buffer;
            //
            // Don't need to pass buffer contents ourselves
            // do_hook_function('template_output', array(ob_get_contents()));
            //
            do_hook('template_output');

            ob_end_flush();

        }
    }

    /**
     * Return the results of applying a template.
     *
     * @param string $file The template file to use
     * @return string A string of the results
     */
    function fetch($file) {

        // get right template file
        //
        $template = $this->get_template_file_path($file);
        if (empty($template)) {

            trigger_error('The template "' . htmlspecialchars($file) 
                          . '" could not be fetched!', E_USER_ERROR);

        } else {

            $aPluginOutput = array();
            $aPluginOutput = concat_hook_function('template_construct_' . $file,
                                                  array($aPluginOutput, $this));
            $this->assign('plugin_output', $aPluginOutput);

            // pull in our config file ($t?  let's try to be more verbose please :-) )
            //
            $t = &$this->values; // place values array directly in scope

            ob_start();
            include($template);

            // CAUTION: USE OF THIS HOOK IS HIGHLY DISCOURAGED AND CAN
            // RESULT IN NOTICABLE PERFORMANCE DEGREDATION.  Plugins
            // using this hook will probably be rejected by the 
            // SquirrelMail team.
            //
            // Anyone hooking in here that wants to manipulate the output
            // buffer has to get the buffer, clean it and then echo the 
            // new buffer like this:
            // $buffer = ob_get_contents(); ob_clean(); .... echo $new_buffer;
            //
            // Don't need to pass buffer contents ourselves
            // do_hook_function('template_output', array(ob_get_contents()));
            //
            do_hook('template_output');

            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;

        }
    }

  /**
   * Return paths to the required javascript files.  Used when generating page
   * header.
   *
   * @return array $paths
   */
  function getJavascriptIncludes () {
    $paths = array();
    foreach ($this->required_js_files as $file) {
        if (in_array($file, $this->provided_js_files))
            $paths[] = './'.$this->template_dir.'js/'.basename($file);
        else $paths[] = SM_PATH .'templates/default/js/'.basename($file);
    }

    return $paths;
  }

  /**
   * Return any additional stylsheets provided by the template.  Used when
   * generating page headers.
   *
   * @return array $paths
   */
  function getAdditionalStyleSheets () {
    $paths = array();
    foreach ($this->additional_css_sheets as $css) {
        $css = basename($css);
        if (strtolower($css) == 'stylesheet.tpl') {
            continue;
        }
        $paths[] = $css;
    }
    return $paths;
  }
}
