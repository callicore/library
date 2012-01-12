<?php
/**
 * Window.php - \Callicore\Lib\Window
 *
 * This is released under the MIT, see license.txt for details
 *
 * @author       Elizabeth Smith <auroraeosrose@php.net>
 * @copyright    Elizabeth Smith (c)2009
 * @link         http://callicore.net
 * @license      http://www.opensource.org/licenses/mit-license.php MIT
 * @version      $Id: Api.php 17 2009-04-25 21:30:35Z auroraeosrose $
 * @since        Php 5.3.0
 * @package      callicore
 * @subpackage   twitter
 * @filesource
 */

/**
 * Namespace for all the baseline library functionality
 */
namespace Callicore\Lib;
use GtkWindow; // extend main window
use Gdk; // for some constants

/**
 * Window extends GtkWindow to make window state "rememberable" from instance
 * to instance
 *
 * A window should extend this class and it's state will be autoremembered
 * This is usually only useful for top-level windows of applications
 */
abstract class Window extends GtkWindow {

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

        if (empty($this->name)) {
            $this->set_name(get_called_class());
        }

        $config = Application::getInstance()->config;
        if (!isset($config[$this->name])) {
            $config[$this->name] = array();
        }

        // default size and location
        list($width, $height) = $this->get_size_request();
        $width = isset($config[$this->name]['height']) ? (int) $config[$this->name]['height'] : $width;
        $height = isset($config[$this->name]['width']) ? (int) $config[$this->name]['width'] : $height;
        $this->set_default_size($width, $height);

        $x = isset($config[$this->name]['x']) ? (int) $config[$this->name]['x'] : null;
        $y = isset($config[$this->name]['y']) ? (int) $config[$this->name]['y'] : null;
        if (!is_null($x) && !is_null($y)) {
            $this->move($x, $y);
        }

        $this->fullscreen = isset($config[$this->name]['fullscreen']) ? (bool) $config[$this->name]['fullscreen'] : false;
        $this->maximized = isset($config[$this->name]['maximized']) ? (bool) $config[$this->name]['maximized'] : false;
        $this->minimized = isset($config[$this->name]['minimized']) ? (bool) $config[$this->name]['minimized'] : false;

        $this->connect('window-state-event', array($this, 'on_state_event'));
        $this->connect_simple_after('delete-event', array($this, 'on_state_save'));
        $this->id = $this->connect_simple('show', array($this, 'on_state_restore'));
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
        $config = Application::getInstance()->config;

        // unmax/min/fullscreen
        $config[$this->name]['fullscreen'] = (bool) $this->fullscreen;
        $config[$this->name]['maximized'] = (bool) $this->maximized;
        $config[$this->name]['minimized'] = (bool) $this->minimized;

        if ($this->minimized) {
            $this->deiconify();
        }
        if ($this->maximized) {
            $this->unmaximize();
        }
        if ($this->fullscreen) {
            $this->unfullscreen();
        }

        // size
        list($height, $width) = $this->get_size();
        $config[$this->name]['height'] = (int) $height;
        $config[$this->name]['width'] = (int) $width;

        // position
        list($x, $y) = $this->get_position();
        $config[$this->name]['x'] = (int) $x;
        $config[$this->name]['y'] = (int) $y;

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
        if ($this->fullscreen) {
            $this->fullscreen();
        } elseif ($this->maximized) {
            $this->maximize();
        }
        if ($this->minimized) {
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