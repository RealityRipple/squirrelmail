<?php

/**
 * files.php
 *
 * This file includes various helper functions for working
 * with the server filesystem.
 *
 * @copyright 2008-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */


/**
 * Generates a unique file in a specific directory and
 * returns the file name (without the path).
 *
 * @param directory The directory within which to create the file
 *
 * @return mixed FALSE when a failure occurs, otherwise a string
 *               containing the filename of the file only (not
 *               its full path)
 *
 * @since 1.5.2
 *
 */
function sq_create_tempfile($directory)
{

    // give up after 1000 tries
    $maximum_tries = 1000;

    // using PHP >= 4.3.2 we can be truly atomic here
    $filemods = check_php_version(4, 3, 2) ? 'x' : 'w';

    for ($try = 0; $try < $maximum_tries; ++$try) {

        $localfilename = GenerateRandomString(32, '', 7);
        $full_localfilename = $directory . DIRECTORY_SEPARATOR . $localfilename;

        // filename collision. try again
        if ( file_exists($full_localfilename) ) {
            continue;
        }

        // try to open for (binary) writing
        $fp = @fopen( $full_localfilename, $filemods);

        if ($fp !== FALSE) {
            // success! make sure it's not readable, close and return filename
            chmod($full_localfilename, 0600);
            fclose($fp);
            return $localfilename;
        }

    }

    // we tried as many times as we could but didn't succeed.
    return FALSE;

}


/**
  * PHP's is_writable() is broken in some versions due to either
  * safe_mode or because of problems correctly determining the
  * actual file permissions under Windows.  Under safe_mode or
  * Windows, we'll try to actually write something in order to
  * see for sure...
  *
  * @param string $path The full path to the file or directory to
  *                     be tested
  *
  * @return boolean Whether or not the file or directory exists
  *                 and is writable
  *
  * @since 1.5.2
  *
  **/
function sq_is_writable($path) {

   global $server_os;


   // under *nix with safe_mode off, use the native is_writable()
   //
   if ($server_os == '*nix' && !(bool)ini_get('safe_mode'))
      return is_writable($path);


   // if it's a directory, that means we have to create a temporary
   // file therein
   //
   $delete_temp_file = FALSE;
   if (@is_dir($path) && ($temp_filename = @sq_create_tempfile($path)))
   {
      $path .= DIRECTORY_SEPARATOR . $temp_filename;
      $delete_temp_file = TRUE;
   }


   // try to open the file for writing (without trying to create it)
   //
   if (!@is_dir($path) && ($FILE = @fopen($path, 'r+')))
   {
      @fclose($FILE);

      // delete temp file if needed
      //
      if ($delete_temp_file)
         @unlink($path);

      return TRUE;
   }


   // delete temp file if needed
   //
   if ($delete_temp_file)
      @unlink($path);

   return FALSE;

}


/**
  * Find files and/or directories in a given directory optionally
  * limited to only those with the given file extension.  If the
  * directory is not found or cannot be opened, no error is generated;
  * only an empty file list is returned.
FIXME: do we WANT to throw an error or a notice or... or return FALSE?
  *
  * @param string $directory_path         The path (relative or absolute)
  *                                       to the desired directory.
  * @param mixed  $extension              The file extension filter - either
  *                                       an array of desired extension(s),
  *                                       or a comma-separated list of same
  *                                       (optional; default is to return 
  *                                       all files (dirs).
  * @param boolean $return_filenames_only When TRUE, only file/dir names
  *                                       are returned, otherwise the
  *                                       $directory_path string is
  *                                       prepended to each file/dir in
  *                                       the returned list (optional;
  *                                       default is filename/dirname only)
  * @param boolean $include_directories   When TRUE, directories are
  *                                       included (optional; default
  *                                       DO include directories).
  * @param boolean $directories_only      When TRUE, ONLY directories
  *                                       are included (optional; default
  *                                       is to include files too).
  * @param boolean $separate_files_and_directories When TRUE, files and
  *                                                directories are returned
  *                                                in separate lists, so
  *                                                the return value is
  *                                                formatted as a two-element
  *                                                array with the two keys
  *                                                "FILES" and "DIRECTORIES",
  *                                                where corresponding values
  *                                                are lists of either all
  *                                                files or all directories
  *                                                (optional; default do not
  *                                                split up return array).
  * @param boolean $only_sm               When TRUE, a security check will
  *                                       limit directory access to only
  *                                       paths within the SquirrelMail 
  *                                       installation currently being used
  *                                       (optional; default TRUE)
  *
  * @return array The requested file/directory list(s).
  *
  * @since 1.5.2
  *
  */
function list_files($directory_path, $extensions='', $return_filenames_only=TRUE,
                    $include_directories=TRUE, $directories_only=FALSE,
                    $separate_files_and_directories=FALSE, $only_sm=TRUE) {

    $files = array();
    $directories = array();


    // make sure requested path is under SM_PATH if needed
    //
    if ($only_sm) {
        if (strpos(realpath($directory_path), realpath(SM_PATH)) !== 0) {
            //plain_error_message(_("Illegal filesystem access was requested"));
            echo _("Illegal filesystem access was requested");
            exit;
        }
    }


    // validate given directory
    //
    if (empty($directory_path)
     || !is_dir($directory_path)
     || !($DIR = opendir($directory_path))) {
        return $files;
    }


    // ensure extensions is an array and is properly formatted 
    //
    if (!empty($extensions)) {
        if (!is_array($extensions))
            $extensions = explode(',', $extensions);
        $temp_extensions = array();
        foreach ($extensions as $ext)
            $temp_extensions[] = '.' . trim(trim($ext), '.');
        $extensions = $temp_extensions;
    } else $extensions = array();


    $directory_path = rtrim($directory_path, '/');


    // parse through the files
    //
    while (($file = readdir($DIR)) !== false) {

        if ($file == '.' || $file == '..') continue;

        if (!empty($extensions))
            foreach ($extensions as $ext)
                if (strrpos($file, $ext) !== (strlen($file) - strlen($ext)))
                    continue 2;

        // only use is_dir() if we really need to (be as efficient as possible)
        //
        $is_dir = FALSE;
        if (!$include_directories || $directories_only
                                  || $separate_files_and_directories) {
            if (is_dir($directory_path . '/' . $file)) {
                if (!$include_directories) continue;
                $is_dir = TRUE;
                $directories[] = ($return_filenames_only
                               ? $file
                               : $directory_path . '/' . $file);
            }
            if ($directories_only) continue;
        }

        if (!$separate_files_and_directories
         || ($separate_files_and_directories && !$is_dir)) {
            $files[] = ($return_filenames_only
                     ? $file
                     : $directory_path . '/' . $file);
        }

    }
    closedir($DIR);


    if ($directories_only) return $directories;
    if ($separate_files_and_directories) return array('FILES' => $files,
                                                      'DIRECTORIES' => $directories);
    return $files;

}


/**
 * Determine if there are lines in a file longer than a given length
 *
 * @param string $filename   The full file path of the file to inspect
 * @param int    $max_length If any lines in the file are GREATER THAN
 *                           this number, this function returns TRUE.
 *
 * @return boolean TRUE as explained above, otherwise, (no long lines
 *                 found) FALSE is returned.
 *
 */
function file_has_long_lines($filename, $max_length) {

    $FILE = @fopen($filename, 'rb');

    if ($FILE) {
        while (!feof($FILE)) {
            $buffer = fgets($FILE, 4096);
            if (strlen($buffer) > $max_length) {
                fclose($FILE);
                return TRUE;
            }
        }
        fclose($FILE);
    }

    return FALSE;
}


