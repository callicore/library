<?php
/**
 * window.class.php - window abstract class
 *
 * general purpose window code, handles menubar, toolbar and statusbar
 * setup as well as general actions and other items
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <emsmith@callicore.net>
 * @copyright    Elizabeth Smith (c)2006
 * @link         http://callicore.net/writer
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: window.class.php 120 2007-01-12 13:12:00Z emsmith $
 * @since        Php 5.2.0
 * @package      callicore
 * @subpackage   desktop
 * @category     lib
 * @filesource
 */

/**
 * CC_Window - basic window items taken care of
 *
 * Streamlines creation of similiar windows and callbacks - handles automatic
 * saving and returning x and y position, as well as height and width on windows
 */
abstract class CC_Window extends GtkWindow
{
	/**
	 * windows can be minimized, maximized and fullscreened
	 * @var $minimized bool
	 */
	protected $minimized = false;

	/**
	 * windows can be minimized, maximized and fullscreened
	 * @var $maximized bool
	 */
	protected $maximized = false;

	/**
	 * windows can be minimized, maximized and fullscreened
	 * @var $fullscreen bool
	 */
	protected $fullscreen = false;

	/**
	 * when used with window manager, window is modal
	 * @var $is_modal bool
	 */
	protected $is_modal = false;

	/**
	 * we keep track of our restore state event and remove the signal after
	 * using it once, we really don't want to be messing with state on every show
	 * @var $id bool
	 */
	private $id;

	//----------------------------------------------------------------
	//             Setup
	//----------------------------------------------------------------

	/**
	 * public function __construct
	 *
	 * Create a new CharacterWindow instance and build internal gui items
	 *
	 * @return void
	 */
	public function __construct()
	{

		parent::__construct();

		if (empty($this->name))
		{
			$this->set_name(str_replace('cc_', '', strtolower(get_class($this))));
		}

		// size and config restoration cannot be done during show signal
		$name = $this->name;
		if (is_null(CC_Config::$class))
		{
			CC_Config::$class = 'CC_Ini';
		}
		$config = CC_Config::instance();
		if (!isset($config->cc))
		{
			$config->cc = new stdclass();
		}

		// default size and location
		$width = isset($config->cc->{$name . '_height'}) ? (int) $config->cc->{$name . '_height'} : 800;
		$height = isset($config->cc->{$name . '_width'}) ? (int) $config->cc->{$name . '_width'} : 600;
		$this->set_default_size($width, $height);

		$x = isset($config->cc->{$name . '_x'}) ? (int) $config->cc->{$name . '_x'} : null;
		$y = isset($config->cc->{$name . '_y'}) ? (int) $config->cc->{$name . '_y'} : null;
		if (!is_null($x) && !is_null($y))
		{
			$this->move($x, $y);
		}

		$this->fullscreen = isset($config->cc->{$name . '_fullscreen'}) ? (bool) $config->cc->{$name . '_fullscreen'} : false;
		$this->maximized = isset($config->cc->{$name . '_maximized'}) ? (bool) $config->cc->{$name . '_maximized'} : false;
		$this->minimized = isset($config->cc->{$name . '_minimized'}) ? (bool) $config->cc->{$name . '_minimized'} : false;

		$this->connect('window-state-event', array($this, 'on_state_event'));
		$this->connect_simple_after('delete-event', array($this, 'on_state_save'));
		$this->id = $this->connect_simple('show', array($this, 'on_state_restore'));
		return;
	}

	/**
	 * public function set_title
	 *
	 * overload set_title
	 *
	 * @param string $name title to set
	 * @return void
	 */
	public function set_title($title, $raw = false)
	{
		if ($raw == false)
		{
			parent::set_title($title);
		}
		else
		{
			parent::set_title(CC::i18n('%s :: %s', CC::$program, $title));
		}
		return;
	}

	//----------------------------------------------------------------
	//             Overrides for window manager class
	//----------------------------------------------------------------

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

	//----------------------------------------------------------------
	//             Callbacks
	//----------------------------------------------------------------

	/**
	 * public function on_state_save
	 *
	 * saves window state
	 *
	 * @return void
	 */
	public function on_state_save()
	{
		$name = $this->name;
		$config = CC_Config::instance();

		// unmax/min/fullscreen
		$config->cc->{$name . '_fullscreen'} = (bool) $this->fullscreen;
		$config->cc->{$name . '_maximized'} = (bool) $this->maximized;
		$config->cc->{$name . '_minimized'} = (bool) $this->minimized;

		if ($this->minimized)
		{
			$this->deiconify();
		}
		if ($this->maximized)
		{
			$this->unmaximize();
		}
		if ($this->fullscreen)
		{
			$this->unfullscreen();
		}

		// size
		list($height, $width) = $this->get_size();
		$config->cc->{$name . '_height'} = (int) $height;
		$config->cc->{$name . '_width'} = (int) $width;

		// position
		list($x, $y) = $this->get_position();
		$config->cc->{$name . '_x'} = (int) $x;
		$config->cc->{$name . '_y'} = (int) $y;

		$this->destroy();
		return;
	}

	/**
	 * public function on_state_restore
	 *
	 * restores a window state - has to be done as show handler or fullscreen bails
	 *
	 * @return void
	 */
	public function on_state_restore()
	{
		if ($this->fullscreen)
		{
			$this->fullscreen();
		}
		elseif ($this->maximized)
		{
			$this->maximize();
		}
		if ($this->minimized)
		{
			$this->iconify();
		}
		$this->disconnect($this->id);
		return;
	}

	/**
	 * public function on_state_event
	 *
	 * keeps track of minimized/maximized/fullscreen or normal windows states
	 *
	 * @return void
	 */
	public function on_state_event($widget, $event)
	{
		$this->minimized = ($event->new_window_state & Gdk::WINDOW_STATE_ICONIFIED) ? true : false;
		$this->maximized = ($event->new_window_state & Gdk::WINDOW_STATE_MAXIMIZED) ? true : false;
		$this->fullscreen = ($event->new_window_state & Gdk::WINDOW_STATE_FULLSCREEN) ? true : false;
	}
}
?>