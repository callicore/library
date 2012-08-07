<?php
/**
 * splash.class.php - Splash screen display window
 *
 * creates, displays, updates progress bar on splash screen during startup
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
 * @subpackage   desktop
 * @category     lib
 * @filesource
 */

/**
 * CC_Splash - basically a wrapper around GtkWindow
 *
 * Sets up a window with a pretty image backdrop and basic program info while showing
 * basic information about what is happening during setup
 */
class CC_Splash extends GtkWindow
{

	/**
	 * progressbar object reference for easy updating
	 * @var $progressbar object instanceof GtkProgressBar
	 */
	protected $progressbar;

	/**
	 * total startup steps - needed for incrementing progressbar
	 * @var $steps int
	 */
	protected $steps;

	/**
	 * main vbox
	 * @var $vbox instanceof GtkVBox
	 */
	protected $vbox;

	/**
	 * empty hbox
	 * @var $hbox instanceof GtkHBox
	 */
	protected $hbox;

	/**
	 * public function __construct
	 *
	 * creates and displays the splash window
	 * and all the widgets it contains
	 *
	 * @return void
	 */
	public function __construct($steps, $program)
	{

		$this->steps = (int) $steps;

		// Window Features
		parent::__construct();
		$this->set_position(Gtk::WIN_POS_CENTER);
		$this->set_title(CC::i18n('%s :: %s', $program, 'Loading'));
		$this->set_resizable(false);
		$this->set_decorated(false);
		$this->set_skip_taskbar_hint(true);
		$this->set_skip_pager_hint(true);
		$this->set_type_hint(Gdk::WINDOW_TYPE_HINT_SPLASHSCREEN);

		// Main VBox Container
		$vbox = $this->vbox = new GtkVbox();
		$vbox->set_border_width(10);
		$this->add($vbox);

		// Empty top bar
		$hbox = $this->hbox = new GtkHBox();
		$vbox->pack_start($hbox, false, false);

		// Progressbar on Bottom
		$this->progressbar = new GtkProgressBar();
		$this->progressbar->set_text(CC::i18n('Loading...'));
		$this->progressbar->set_fraction(0);
		$vbox->pack_end($this->progressbar, false, false);
		return;
	}

	/**
	 * public function parent
	 *
	 * although the splashscreen is created first,
	 * it changes to a transient for the main window
	 * so we use this to set that relationship
	 *
	 * @param object instanceof GtkWindow $parent GtkWindow object parent
	 * @return void
	 */
	public function parent($parent)
	{
		$this->set_transient_for($parent);
		$this->set_destroy_with_parent(true);
		return;
	}

	/**
	 * public function set_image
	 *
	 * sets a background image for the splash screen
	 *
	 * @param string $image absolute path to splash background
	 * @return void
	 */
	public function set_image($image)
	{
		$pixbuf = GdkPixbuf::new_from_file($image);
		list($pixmap, $mask) = $pixbuf->render_pixmap_and_mask();
		list($width, $height) = $pixmap->get_size();
		$this->set_app_paintable(true);
		$this->set_size_request($width, $height);
		$this->realize();
		if($mask instanceof GdkPixmap)
		{
			$this->shape_combine_mask($mask, 0, 0);
		}
		$this->window->set_back_pixmap($pixmap, false);
		return;
	}

	/**
	 * public function set_license
	 *
	 * sets license information string (on bottom center of window)
	 *
	 * @param string $license license string to use
	 * @return void
	 */
	public function set_license($license = 'GPL License')
	{
		// License info just above progressbar
		$hbox = new GtkHBox();
		$hbox->pack_start(new GtkLabel(CC::i18n($license)), false, false);
		$this->vbox->pack_end($hbox, false, false);
		return;
	}

	/**
	 * public function set_version
	 *
	 * sets version information string (top right)
	 *
	 * @param string $version string to use
	 * @return void
	 */
	public function set_version($version = null)
	{
		if(is_null($version))
		{
			$version = 'version ' . CC::VERSION;
		}
		$this->hbox->pack_end(new GtkLabel(CC::i18n($version)), false, false);
		return;
	}

	/**
	 * public function set_copyright
	 *
	 * sets author information string (top left)
	 *
	 * @param string $copyright string to use
	 * @return void
	 */
	public function set_copyright($copyright = 'Copyright (c) 2006')
	{
		$this->hbox->pack_start(new GtkLabel(CC::i18n($copyright)), false, false);
		return;
	}

	/**
	 * public function update
	 *
	 * update the progressbar text and position
	 *
	 * @param string $text text to fill
	 * @return type about
	 */
	public function update($text)
	{
		$this->progressbar->set_text(CC::i18n($text));
		$this->progressbar->set_fraction($this->progressbar->get_fraction() +
			(1 / $this->steps));
		while (Gtk::events_pending())
		Gtk::main_iteration();
		return;
	}
}