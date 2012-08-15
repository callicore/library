<?php
/**
 * callicore.php - include all file for library, use instead of phar for development
 *
 * This is released under the MIT, see license.txt for details
 *
 * @author       Elizabeth M Smith <auroraeosrose@gmail.com>
 * @copyright    Elizabeth M Smith (c) 2009-2012
 * @link         http://callicore.net
 * @license      http://www.opensource.org/licenses/mit-license.php MIT
 * @since        Php 5.4.0 GTK 2.24.0
 * @package      callicore
 * @subpackage   library
 * @filesource
 */

/**
 * Figure out our library location
 */
defined('CALLICORE_LIB') || define('CALLICORE_LIB', (getenv('CALLICORE_LIB') ? getenv('CALLICORE_LIB') : __DIR__ . DIRECTORY_SEPARATOR));

/**
 * Include all library items
 */
include CALLICORE_LIB . 'lib' . DIRECTORY_SEPARATOR . 'Util.php';
include CALLICORE_LIB . 'lib' . DIRECTORY_SEPARATOR . 'Error.php';
include CALLICORE_LIB . 'lib' . DIRECTORY_SEPARATOR . 'Config.php';
include CALLICORE_LIB . 'lib' . DIRECTORY_SEPARATOR . 'Translate.php';
include CALLICORE_LIB . 'lib' . DIRECTORY_SEPARATOR . 'Application.php';

/**
 * Include all widget items
 */
include CALLICORE_LIB . 'widget' . DIRECTORY_SEPARATOR . 'Window.php';
include CALLICORE_LIB . 'widget' . DIRECTORY_SEPARATOR . 'Main.php';
include CALLICORE_LIB . 'widget' . DIRECTORY_SEPARATOR . 'Builder.php';
include CALLICORE_LIB . 'widget' . DIRECTORY_SEPARATOR . 'Splash.php';
