<?php
/**
 * sqlite.class.php - A custom store for gtk for use with tree/icon views
 *
 * maps data stored in an sqlite db in a manner that gtk can use for tree/icon views
 *
 * This is released under the GPL, see docs/gpl.txt for details
 *
 * @author       Elizabeth Smith <emsmith@callicore.net>
 * @copyright    Elizabeth Smith (c)2006
 * @link         http://callicore.net/desktop
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: splash.class.php 120 2007-01-12 13:12:00Z emsmith $
 * @since        Php 5.1.0
 * @package      callicore
 * @subpackage   base
 * @category     lib
 * @filesource
 */

/**
 * CC_Sqlite - custom list store for data in an sqlite database
 *
 * Sets up a window with a pretty image backdrop and basic program info while showing
 * basic information about what is happening during setup
 */
class CC_Sqlite extends PhpGtkCustomTreeModel
{

	/**
	 * progressbar object reference for easy updating
	 * @var $progressbar object instanceof GtkProgressBar
	 */
	protected $progressbar;

	/**
	 * public function __construct
	 *
	 * creates and displays the splash window
	 * and all the widgets it contains
	 *
	 * @return void
	 */
	public function __construct()
	{

		return;
	}
}

$liststore = new CC_Sqlite();