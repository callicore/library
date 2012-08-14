<?php
/**
 * Splash.php - \Callicore\Lib\Widget\Splash
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
use Callicore\Lib\Application as App; // translate and config information
use GtkWindow; // extend main window
use Gtk; // some constants
use Gdk; // some constants
use GtkProgressBar; // see our progress
use GtkVBox; // packing
use GtkHBox; // packing
use GdkPixbuf; // pretty png window
use GdkPixmap; // pretty png window
use GtkLabel; // for our text
use GtkAlignment; // aligning our main vbox

/**
 * Splash - basically a wrapper around GtkWindow
 *
 * Sets up a window with a pretty image backdrop and basic program info while showing
 * basic information about what is happening during setup
 */
class Splash extends GtkWindow
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
    public function __construct($steps, $app)
    {

        $this->steps = (int) $steps;

        // Window Features
        parent::__construct();
        $this->set_position(Gtk::WIN_POS_CENTER);
        $this->set_title(App::_('%s :: %s', $app->name(), 'Loading'));
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
        $this->progressbar->set_text(App::_('Loading...'));
        $this->progressbar->set_fraction(0);
        $vbox->pack_end($this->progressbar, 0, false);
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
        if($mask instanceof GdkPixmap) {
            $this->shape_combine_mask($mask, 0, 0);
        }
        $this->window->set_back_pixmap($pixmap, false);
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
        $hbox->pack_start(new GtkLabel($this->translate->_($license)), false, false);
        $this->vbox->pack_end($hbox, false, false);
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
        if (is_null($version)) {
            $version = Callicore\Lib\Application::VERSION;
        }
        $this->hbox->pack_end(new GtkLabel(App::_($version)), false, false);
    }

    /**
     * public function set_copyright
     *
     * sets author information string (top left)
     *
     * @param string $copyright string to use
     * @return void
     */
    public function set_copyright($copyright = null)
    {
        if (is_null($copyright)) {
            $copyright = 'Copyright (c) ' . date('Y');
        }
        $this->hbox->pack_start(new GtkLabel(App::_($copyright)), false, false);
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
        $this->progressbar->set_text($this->translate->_($text));
        $this->progressbar->set_fraction($this->progressbar->get_fraction() +
                (1 / $this->steps));
        while (Gtk::events_pending()) {
            Gtk::main_iteration();
        }
    }

    /**
     * public function align
     *
     * Put the vbox into a gtkalignment container to add padding
     * Useful for images with larger shape masks
     *
     * @param string $text text to fill
     * @return type about
     */
    public function align($top, $bottom, $left, $right)
    {
        $this->remove($this->vbox);
        $align = new GtkAlignment(0.5, 0.5, 1, 1);
        $align->set_padding($top, $bottom, $left, $right);
        $this->add($align);
        $align->add($this->vbox);
    }
}