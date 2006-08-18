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
   * authors (namely me to begin with :p) to provide additional CSS sheets
   * to templates while using the default template set stylesheet for other
   * definitions.
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
   */
  function append($tpl_var, $value = NULL, $merge = FALSE)
  {
    if (is_array($tpl_var))
    {
      foreach ($tpl_var as $_key => $_val)
      {
        if ($_key != '')
        {
          if(isset($this->values[$_key]) && !is_array($this->values[$_key]))
            settype($this->values[$_key],'array');

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
   * Display the template
   *
   * @param string $file The template file to use
   */
  function display($file)
  {
    /**
     * We want to make sure that certain variables are always passed to the
     * templates b/c they are critical to certain template functions.
     */
    $always_include = array('icon_theme_path', 'sTplDir');
    foreach ($always_include as $var) {
        if (!isset($this->values[$var]) && isset($GLOBALS[$var])) {
            $this->assign($var, $GLOBALS[$var]);
        }
    }
    
    // Pull in our config file
    $t = &$this->values; // place values array directly in scope

    $template = in_array($file, $this->templates_provided) ? $this->template_dir . $file : SM_PATH .'templates/default/'. $file;
    if (!file_exists($template)) {
        trigger_error('The template "'.htmlspecialchars($file).'" could not be displayed!', E_USER_ERROR);
    } else {
        ob_start();
        include($template);
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
    $t = &$this->values; // place values array directly in scope

    $template = in_array($file, $this->templates_provided) ? $this->template_dir . $file : SM_PATH .'templates/default/'. $file;
    if (!file_exists($template)) {
        trigger_error('The template "'.htmlspecialchars($file).'" could not be fetched!', E_USER_ERROR);
    } else {
        ob_start();
        include($template);
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
