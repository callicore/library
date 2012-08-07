<?php
/**
 * wm.class.php - window manager
 *
 * handles window groups and multiple gtkwindow object easily
 *
 * This is released under the GPL, see docs/gpl.txt for details
 *
 * @author       Leon Pegg <leon.pegg@gmail.com>
 * @author       Elizabeth M Smith <emsmith@callicore.net>
 * @copyright    Leon Pegg (c)2006
 * @link         http://callicore.net/desktop
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: wm.class.php 64 2006-12-10 18:09:56Z emsmith $
 * @since        Php 5.1.0
 * @package      callicore
 * @subpackage   desktop
 * @category     lib
 * @filesource
 */

/**
* Class for handling multiple GtkWindow objects
*
* contains all static methods
*/
class CC_Wm
{
	/**
	 * GtkWindow object array
	 *      [string GtkWindow_Name]
	 *          array(
	 *            GtkWindow - Store GtkWindow object
	 *          )
	 *
	 * @var array
	 */
	protected static $windows = array();

	/**
	 * GtkWindowGroup - for making grabs work properly (see GtkWidget::grab_add)
	 *
	 * @var object instanceof GtkWindowGroup
	 */
	protected static $window_group;

	/**
	 * public function __construct
	 *
	 * forces only static calls
	 *
	 * @return void
	 */
	public function __construct()
	{
		throw new CC_Exception('CC_WM contains only static methods and cannot be constructed');
	}

	/**
	 * Adds a GtkWindow object to CC_WM::$windows
	 *
	 * @param object $window instanceof GtkWindow
	 * @return bool
	 */
	public static function add_window(GtkWindow $window)
	{
		$name = $window->get_name();
		if ($name !== '' && !array_key_exists($name, self::$windows))
		{
			if (!is_object(self::$window_group))
			{
				self::$window_group = new GtkWindowGroup();
			}
			self::$window_group->add_window($window);
			self::$windows[$name] = $window;
			return true;
		}
		return false;
	}

	/**
	 * Removes a GtkWindow object from CC_WM::$windows
	 *
	 * @param string $name
	 * @return object instanceof GtkWindow
	 */
	public static function remove_window($name)
	{
		if ($name !== '' && array_key_exists($name, self::$windows))
		{
			$window = self::$windows[$$name];
			unset(self::$windows[$name]);
			self::$window_group->remove_window($window);
			return $window;
		}
		return false;
	}

	/**
	 * Retrives GtkWindow object from CC_WM::$windows
	 *
	 * @param string $name
	 * @return object instanceof GtkWindow
	 */
	public static function get_window($name)
	{
		if ($name !== '' && array_key_exists($name, self::$windows))
		{
			return self::$windows[$name];
		}
		return false;
	}

	/**
	 * Retrives CC_WM::$windows infomation array
	 *   Structure:
	 *      [int window_id]
	 *          array(
	 *            name   - Name of GtkWindow
	 *            class  - Name of GtkWindow class
	 *          )
	 *
	 * @return array
	 */
	public static function list_windows()
	{
		$list = array();
		foreach (self::$windows as $name => $class)
		{
			$list[] = array('name' => $name, 'class' => $get_class($class));
		}
		return $list;
	}

	/**
	 * Shows all windows in the manager
	 */
	public static function show_all_windows()
	{
		foreach (self::$windows as $window)
		{
			$window->show_all();
		}
	}

	/**
	 * hides all the windows in the manager
	 */
	public static function hide_all_windows()
	{
		foreach (self::$windows as $window)
		{
			$window->hide_all();
		}
	}

	/**
	 * See if a specific window exists
	 *
	 * @param string $name name of window to check for
	 * @return bool
	 */
	public static function is_window($name)
	{
		return array_key_exists($name, self::$windows);
	}

	/**
	 * public function hide_all
	 *
	 * overrides hide_all for windows manager integration
	 *
	 * @return void
	 */
	public function hide_all()
	{
		if (class_exists('CC_Wm') && CC_Wm::is_window($this->get_name()) &&
			$this->is_modal)
		{
			parent::grab_remove();
			$return = parent::hide_all();
			$this->is_modal = false;
			return $return;
		}
		else
		{
			return parent::hide_all();
		}
	}

	/**
	 * public function show_all
	 *
	 * overrides show_all for windows manager integration
	 *
	 * @return void
	 */
	public function show_all($modal = false)
	{
		if (class_exists('CC_Wm') && CC_Wm::is_window($this->get_name()))
		{
			$return = parent::show_all();
			parent::grab_add();
			$this->is_modal = true;
			return $return;
		}
		else
		{
			return parent::show_all();
		}
	}
}