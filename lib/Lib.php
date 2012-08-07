<?php
/**
 * Lib.php - \Callicore\Lib bootstrap for library
 *
 * This is released under the MIT, see license.txt for details
 *
 * @author       Elizabeth M Smith <auroraeosrose@php.net>
 * @copyright    Elizabeth M Smith (c)2009
 * @link         http://callicore.net
 * @license      http://www.opensource.org/licenses/mit-license.php MIT
 * @version      $Id: Lib.php 24 2009-04-27 02:04:10Z auroraeosrose $
 * @since        Php 5.3.0
 * @package      callicore
 * @subpackage   lib
 * @filesource
 */

/**
 * Namespace for library classes
 */
namespace Callicore\Lib;

/**
 * Current Library verion
 * @const string
 */
const VERSION = '0.1.0-dev';

/**
 * Current API verion
 * @const double
 */
const API = 1.0;

/**
 * Current library location
 * @const string
 */
const DIR = __DIR__;

/**
 * autoload implementation for library
 *
 * @param string $class class to include
 * @return bool
 */
function autoload($class) {
    // only Callicore\Lib classes
    if (strncmp('Callicore\Lib', $class, 13) !== 0) {
        return false;
    }

    // split on namespace and pop off callicore/lib
    $array = explode('\\', $class);
    unset($array[0], $array[1]);

    // create partial filename
    $file = array_pop($array);
    // if we have no path left, add it back
    if (empty($array)) {
        $path = '';
    } else {
        $path = implode('/', $array);
    }
    $filename = __DIR__ . '/' . $path . '/' . $file . '.php';
    if (!file_exists($filename)) {
        trigger_error("File $filename could not be loaded", E_USER_WARNING);
        return false;
    }
    include $filename;
    return true;
}

/**
 * register the autoload
 */
\spl_autoload_register(__NAMESPACE__ . '\autoload');