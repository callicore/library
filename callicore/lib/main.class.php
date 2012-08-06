<?php
/**
 * main.class.php - default setup for a "main" window for a program
 *
 * general purpose window with a statusbar, menubar and single toolbar along
 * with a main layout area
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <emsmith@callicore.net>
 * @copyright    Elizabeth Smith (c)2006
 * @link         http://callicore.net/writer
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: main.class.php 127 2007-01-27 18:47:25Z emsmith $
 * @since        Php 5.2.0
 * @package      callicore
 * @subpackage   desktop
 * @category     lib
 * @filesource
 */

/**
 * CC_Main - basic window layout for main window
 *
 * takes care of status bar building, some generalized actions, and even registers
 * a standard quit item while building a generic "main window" ui layout
 */
abstract class CC_Main extends CC_Window
{
	/**
	 * main vbox for window
	 * @var $vbox object instanceof GtkVBox
	 */
	public $vbox;

	/**
	 * main menu for the window
	 * @var $menu object instanceof GtkMenu
	 */
	public $menu;

	/**
	 * toolbar for the window
	 * @var $toolbar object instanceof CC_Toolbar
	 */
	public $toolbar;

	/**
	 * status bar for window
	 * @var $statusbar object instanceof GtkStatusBar
	 */
	public $statusbar;

	//-------- Items that should be overridden -------------

	/**
	 * website url to open
	 * @var $website string
	 */
	protected $website = 'http://callicore.net/desktop';

	/**
	 * menu bar definition
	 * @var $menubar array
	 */
	protected $menubar = array(
		'_File' => array(
			'file:quit',
		),
		'_View' => array(
			//'toolbar:toggle',
			'view:fullscreen',
		),
		'_Help' => array(
			'help:help',
			'help:website',
			'separator',
			'help:about',
		),
	);

	/**
	 * options available for toolbar
	 * @var $tooloptions array
	 */
	protected $tooloptions = array(
		'file:quit',
		'view:fullscreen',
		'help:help',
		'help:website',
		'help:about'
	);

	/**
	 * default toolbar layout
	 * @var $tooldefault array
	 */
	protected $tooldefault = array(
		'file:quit',
		'view:fullscreen',
		'separator',
		'help:help',
		'help:website',
		'expander',
		'help:about'
	);

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
		CC_Wm::add_window($this);

		$this->register_actions();
		//$this->build_toolbar();
		$this->build_menu();
		$this->build_statusbar();

		$vbox = $this->vbox = new GtkVBox();
		$vbox->pack_start($this->menu, false, false);
		//$vbox->pack_start($this->toolbar, false, false);
		$vbox->pack_end($this->statusbar, false, false);
		$this->add($vbox);

		$this->connect_simple('destroy',array('gtk','main_quit'));
		$this->connect_simple('delete-event', array($this, 'on_quit'));
		return;
	}

	/**
	 * protected function register_actions
	 *
	 * creates generic window actions
	 *
	 * @todo add additional generic actions
	 * @return void
	 */
	protected function register_actions()
	{
		$actions = CC_Actions::instance();
		$this->add_accel_group($actions->get_accel());

		$actions->add_action('file',
			array(
				'type' => 'action',
				'name' => 'quit',
				'label' => '_Quit',
				'short-label' => '_Quit',
				'tooltip' => 'Exit program',
				'accel' => '<Ctrl>q',
				'callback' => array($this, 'on_quit'),
				'image' => 'gtk-quit',
				)
		);

		$actions->add_action('view',
			array(
				'type' => 'toggle',
				'name' => 'fullscreen',
				'label' => '_Fullscreen',
				'short-label' => '_Fullscreen',
				'tooltip' => 'Change to fullscreen mode',
				'accel' => 'F11',
				'callback' => array($this, 'on_fullscreen'),
				'image' => 'gtk-fullscreen',
				'active' => $this->fullscreen,
				)
		);

		$actions->add_actions('help', array(
			array(
				'type' => 'action',
				'name' => 'help',
				'label' => '_Help',
				'short-label' => '_Help',
				'tooltip' => 'Open help',
				'accel' => 'F1',
				'callback' => array($this, 'on_help'),
				'image' => 'gtk-help',
				),
			array(
				'type' => 'action',
				'name' => 'website',
				'label' => '_Website',
				'short-label' => '_Website',
				'tooltip' => 'Visit website',
				'callback' => array($this, 'on_website'),
				'image' => 'cc-browser',
				),
			array(
				'type' => 'action',
				'name' => 'about',
				'label' => '_About',
				'short-label' => '_About',
				'tooltip' => 'About Callicore',
				'callback' => array($this, 'on_about'),
				'image' => 'gtk-about',
				),
		));

		return;
	}

	//----------------------------------------------------------------
	//             UI building
	//----------------------------------------------------------------

	/**
	 * protected function build_toolbar
	 *
	 * todo - pass default and options and name to new toolbar
	 *
	 * @return void
	 */
	protected function build_toolbar()
	{
		$this->toolbar = new CC_Toolbar($this->name, $this->tooldefault, $this->tooloptions);
		return;
	}

	/**
	 * protected function build_menu
	 *
	 * build a default menubar
	 * file quit, view options
	 *
	 * @return void
	 */
	protected function build_menu()
	{
		$menu = $this->menu = new GtkMenuBar();
		$actions = CC_Actions::instance();

		foreach ($this->menubar as $name => $def)
		{
			$item = new GtkMenuItem(CC::i18n($name));
			$menu->add($item);
			$submenu = new GtkMenu();
			$item->set_submenu($submenu);
			foreach ($def as $name)
			{
				if ($name == 'separator')
				{
					$submenu->append(new GtkSeparatorMenuItem());
				}
				else
				{
					list($group, $action) = explode(':', $name);
					$submenu->append($actions->create_menu_item($group, $action));
				}
			}

		}
		return;
	}

	/**
	 * protected function build_statusbar
	 *
	 * exposes frame and label for easy manipulation, and sets a name
	 *
	 * @return void
	 */
	protected function build_statusbar()
	{
		$this->statusbar = new GtkStatusbar();
		if (empty($this->statusbar->name))
		{
			$this->statusbar->set_name($this->get_name());
		}
		// constructs a gtkframe with a gtklabel inside
		$children = $this->statusbar->get_children();
		$this->statusbar->frame = $children[0];
		$this->statusbar->label = $children[0]->child;
		$this->statusbar->label->set_padding(3, 0);
		$this->statusbar->label->set_label(CC::i18n('Ready'));
		$this->statusbar->label->set_use_markup(TRUE);
		return;
	}

	//----------------------------------------------------------------
	//             Callbacks
	//----------------------------------------------------------------

	/**
	 * public function on_quit
	 *
	 * exits the program
	 *
	 * @return void
	 */
	public function on_quit()
	{
		$dialog = new CC_Message('Are you sure you want to quit?', 'Exit...',
			CC_Message::QUESTION, CC::$program);
		if ($dialog->run() == Gtk::RESPONSE_YES)
		{
			$this->on_state_save();
			$this->destroy();
			return false; // stop event propogation
		}
		else
		{
			$dialog->destroy();
			return true;
		}
	}

	/**
	 * public function on_fullscreen
	 *
	 * toggles fullscreen on and off
	 *
	 * @return void
	 */
	public function on_fullscreen()
	{
		$actions = CC_Actions::instance();
		if ($this->fullscreen)
		{
			$this->unfullscreen();
			$actions->get_action('view', 'fullscreen')->set_property('stock-id', 'gtk-fullscreen');
			$actions->get_action('view', 'fullscreen')->set_property('tooltip', 'Change to fullscreen mode');
		}
		else
		{
			$this->fullscreen();
			$actions->get_action('view', 'fullscreen')->set_property('stock-id', 'gtk-leave-fullscreen');
			$actions->get_action('view', 'fullscreen')->set_property('tooltip', 'Return to regular mode');
		}
		return;
	}

	/**
	 * public function on_help
	 *
	 * displays some kind of help for the app
	 *
	 * @return void
	 */
	abstract public function on_help();

	/**
	 * public function on_website
	 *
	 * launches a website url
	 *
	 * @return void
	 */
	public function on_website()
	{
		CC_Os::instance()->launch($this->website);
		return;
	}

	/**
	 * public function on_about
	 *
	 * displays information about the app
	 *
	 * @return void
	 */
	abstract public function on_about();
}
?>