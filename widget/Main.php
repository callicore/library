<?php
/**
 * Main.php - \Callicore\Lib\Widget\Main
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
 * Namespace for all the baseline library functionality
 */
namespace Callicore\Lib\Widget;
use Callicore\Lib\Application as App; // app data
use GtkWindow; // extend main window
use Gdk; // for some constants
use Gobject; // for registering our type

/**
 * The main window has some built in helpers for
 * a common main layout, including statusbars,
 * accelerators, actions, and more
 *
 * The main layouter can be done by extending the class
 * and implmenting the layout method
 * you can use the gtkbuilder trait for layout as well
 */
abstract class Main extends Window {

}
GObject::register_type('Callicore\Lib\Widget\Main');