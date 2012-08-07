<?php
/**
 * Application.php - \Callicore\Lib\Application
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
 * Namespace for library classes
 */
namespace Callicore\Lib;
use \Gobject;  // import base gobject class from php-gtk ext
use \Gtk; // gtk main and gtk main_quit usage

/**
 * Application - central processing for running the system
 *
 * Handles startup(__construct), shutdown(__destruct), setup (check for requirements)
 * initialization (main window, splash), uninitialization, updating (update check)
 * config loading, config saving,
 * plugin loading, plugin unloading
 * run, quit, and error
 */
abstract class Application extends Gobject {

    /**
     * string version
     *
     * @var string
     */
    const VERSION = '1.0.0-dev';

    /**
     * Magical array for defining gsignals for the object
     * Note that this will be "unset" by php-gtk after being loaded
     * @var array
     */
    public $__gsignals = array(
        'startup'       => array(GObject::SIGNAL_RUN_FIRST, GObject::TYPE_NONE, array(Gobject::TYPE_PHP_VALUE)),
        'load-config'   => array(GObject::SIGNAL_RUN_FIRST, GObject::TYPE_NONE, array(Gobject::TYPE_PHP_VALUE)),
        'load-plugin'   => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
        'update'        => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
        'init'          => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array(Gobject::TYPE_PHP_VALUE)),
        'run'           => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array(Gobject::TYPE_PHP_VALUE)),
        'quit'          => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
        'error'         => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
        'uninit'        => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
        'unload-plugin' => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
        'save-config'   => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array(Gobject::TYPE_PHP_VALUE)),
        'shutdown'      => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
    );

    /**
     * A name for the application - if no name is given will default to Callicore
     *
     * @var string
     */
    protected $name = 'Callicore';

    /**
     * Loaded plugin objects - readonly
     *
     * @var object instanceof SplObjectStore with instanceof \Callicore\Lib\Plugin
     */
    private $plugins;

    /**
     * Configuration storage object - readonly
     *
     * @var object instanceof \Callicore\Lib\Config
     */
    private $config;

    /**
     * Translation storage object - readonly
     *
     * @var object instanceof \Callicore\Lib\Translate
     */
    private $translate;

    /**
     * Error class - readonly
     *
     * @var object instanceof \Callicore\Lib\Error
     */
    private $error;

    /**
     * Sets up the application so it's ready to run
     *
     * @return void
     */
    final public function __construct() {

        $this->check_requirements();

        // we can do nothing without registering our gtk type
        GObject::register_type(get_called_class());
        parent::__construct();

        // setup is complete, startup
        $this->emit('startup', $this);

        // actually load in configuration at $appdata/$appname.ini
        $this->config = $config = new Config(Util::getFolder('appdata', $this->name), $this->name);
        $this->emit('load-config', $config);

        // TODO: autoupdating & updating
        // TODO: plugin loading
    }

        /**
     * Actually runs the program
     *
     * @return void
     */
    final public function run() {

        // load up the translation stuff
        $this->translate = new Translate($this);

        $this->emit('init', $this);

        // call the abstract main method
        $this->main();

        // start up gtk main loop
        $this->emit('run', $this);
        Gtk::main();

    }

    /**
     * Actually quits the program
     *
     * @return void
     */
    final public function quit() {
        $this->emit('quit');
        $this->emit('uninit');
        Gtk::main_quit();
    }

    /**
     * Abstract method use to set up the application
     * must be defined by the extending class
     *
     * @return void
     */
    abstract function main();

    /**
     * "read-only" properties
     *
     * @param string $offset item to be retrieved
     * @return mixed
     */
    public function __get($offset) {
        if ($offset == 'name') {
            return $this->name;
        } elseif ($offset == 'config') {
            return $this->config;
        } elseif ($offset == 'translate') {
            return $this->translate;
        } else {
            return null;
        }
    }

    /**
     * Simple helper method that sets up very basic 
     *
     * @return void
     */
    protected function check_requirements() {

        // turn erroring way up
        error_reporting(-1);

        $this->error = $error = new Error;
        $error->set_stderr_handlers();

        // absolute bare minimum requirements
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            trigger_error('You must use php 5.4.0 or higher' . PHP_EOL, E_USER_ERROR);
        }

        // cli sapi required
        if (PHP_SAPI !== 'cli') {
            trigger_error('Callicore applications require using the CLI SAPI' . PHP_EOL, E_USER_ERROR);
        }

        // required extensions
        $have = get_loaded_extensions();
        $needed = array('standard', 'pcre', 'date', 'Reflection', 'tokenizer',
                        'SPL', 'php-gtk', 'gettext', 'iconv', 'cairo');
        if (stristr(PHP_OS, 'win32')) {
            $needed[] = 'com';
        }
        $diff = array_diff($needed, $have);
        // attempt to dl ones that aren't already loaded
        foreach($diff as $key => $ext) {
            if (Util::dl($ext, $this->name) == true) {
                unset($diff[$key]);
            }
        }
        if (!empty($diff)) {
            trigger_error('The following extensions must be present - either built into php or loaded via your php-cli.ini - for Callicore to function: '
                          . implode(', ', $diff) . PHP_EOL, E_USER_ERROR);
        }

        if ($message = Gtk::check_version(2, 24, 0)) {
            trigger_error('Callicore requires GTK+ 2.24 or higher, ' . $message . PHP_EOL, E_USER_ERROR);
        }

        if ($error->has_error()) {
            die(256);
        }

        $error->restore_handlers();

    }

    /**
     * Does any shutdown specific handling
     * This is final so the signals are sure to be emitted
     *
     * @return void
     */
    final public function __destruct() {

       // TODO: unload plugins

        // save configuration settings
        $this->emit('save-config', $this->config);

        unset($this->config, $this->translate);

        // emit the shutdown signal
        $this->emit('shutdown');

        // restore error handling and close it up
        $this->error->restore_handlers();
        unset($this->error);
    }

}