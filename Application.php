<?php
/**
 * Application.php - \Callicore\Lib\Application
 *
 * This is released under the MIT, see license.txt for details
 *
 * @author       Elizabeth Smith <auroraeosrose@php.net>
 * @copyright    Elizabeth Smith (c)2009
 * @link         http://callicore.net
 * @license      http://www.opensource.org/licenses/mit-license.php MIT
 * @version      $Id: Application.php 23 2009-04-26 02:24:03Z auroraeosrose $
 * @since        Php 5.3.0
 * @package      callicore
 * @subpackage   lib
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
 * Handles startup(__construct), shutdown(__destruct), initialization, uninitialization,
 * config loading, config saving, plugin loading and unloading
 * actual running of the application
 *
 * TODO: plugin and autoupdate management
 */
abstract class Application extends Gobject {

    /**
     * Magical array for defining gsignals for the object
     * Note that this will be "unset" by php-gtk after being loaded
     * @var array
     */
    public $__gsignals = array(
        'startup'           => array(GObject::SIGNAL_RUN_FIRST, GObject::TYPE_NONE, array()),
        'shutdown'          => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
        'load-config'       => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array(Gobject::TYPE_PHP_VALUE)),
        'save-config'       => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array(Gobject::TYPE_PHP_VALUE)),
        'init'              => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
        'uninit'            => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
        'run'               => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
        'quit'              => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
        'load-plugin'       => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
        'unload-plugin'     => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
    );

    /**
     * Application is a singleton class, this is where the instance is stored
     *
     * @var object instanceof Application
     */
    static private $instance;

    /**
     * A name for the application - if no name is given will default to Callicore
     *
     * @var string
     */
    protected $name = 'Callicore';

    /**
     * Loaded plugin objects
     *
     * @var object instanceof \Callicore\Lib\Registry
     */
    private $plugins;

    /**
     * Configuration storage object
     *
     * @var object instanceof \Callicore\Lib\Config
     */
    private $config;

    /**
     * Translation storage object
     *
     * @var object instanceof \Callicore\Lib\Translate
     */
    private $translate;

    /**
     * Main window for program
     *
     * @var object instanceof \Callicore\Lib\Window\Main
     */
    private $window;

    /**
     * Sets up the application so it's ready to run
     *
     * @return void
     */
    final public function __construct() {
        // set our default "write to stderr" handler
        set_error_handler(array($this, 'error'));
        set_exception_handler(array($this, 'exception'));

        if (self::$instance) {
            throw new \Exception('Application ' . self::$instance->name . ' already created, use Application::getInstance()', E_USER_WARNING);
            return;
        }

        // this must be called to set up the gobject stuff properly
        parent::__construct();
        self::$instance = $this;

        // perform startup activities
        if (version_compare(PHP_VERSION, '5.3.0-dev', '<')) {
            trigger_error("You must use php 5.3.0 or higher\n", E_USER_ERROR);
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
            if (Util::ext($ext) == true) {
                unset($diff[$key]);
            }
        }
        // blow up if the extensions are STILL not loaded
        if (!empty($diff)) {
            trigger_error('The following extensions must be present - either built into php or loaded via your php.ini - for Callicore to function: ' . implode(', ', $diff), E_USER_ERROR);
        }

        // GTK 2.12 is needed for
        if ($error = Gtk::check_version(2, 12, 0)) {
            trigger_error('Callicore requires GTK+ 2.12 or higher, ' . $error, E_USER_ERROR);
        }

        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', false);
        ini_set('log_errors', true);

        // emit the startup signal
        $this->emit('startup');

        // actually load in configuration at $appdata/$appname.ini
        $this->config = new Config(Util::getFolder('appdata'), $this->name);

        // emit configuration loaded signal
        $this->emit('load-config', $this->config);

        // grab the plugins list
        // foreach plugin, do a load (call a separate load-plugin method)
    }

    /**
     * Actually runs the program
     *
     * @return void
     */
    final public function run() {
        // load up the translation stuff
        $this->translate = new Translate();
        // register GTK window error handlers
        set_error_handler(array(__NAMESPACE__ . '\Message', 'error'));
        set_exception_handler(array(__NAMESPACE__ . '\Message', 'exception'));
        $this->emit('init');

        // call the abstract main method
        $this->main();

        // start up gtk main loop
        $this->emit('run');
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
     * name, config, and translate are "read-only"
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
     * Error handler to write issues to stderr
     * Used before initialization of the program, when configuration settings
     * for logging/etc are not available
     *
     * @return string
     */
    public function error($errno, $errstr , $errfile, $errline) {
        fwrite(STDERR, wordwrap(
            'Error #' . $errno . ': ' . $errstr . ' on ' . $errfile . ':' . $errline . PHP_EOL . PHP_EOL,
            75, PHP_EOL, true));
    }

    /**
     * Default exception handler - changes an exception to an error, used before
     * initialization when configuration settings are not available
     *
     * @return string
     */
    public function exception($e) {
        $this->handler($e->getCode(), $e->getMessage(), $e->getLine(), $e->getFile());
    }

    /**
     * Returns the application object
     *
     * @return object instaceof Application
     */
    static public function getInstance() {
        if (!self::$instance) {
            trigger_error('Application not already created, create the object before attempting to fetch it.', E_USER_ERROR);
            return;
        }
        return self::$instance;
    }

    /**
     * Does any shutdown specific handling
     * This is final so the signals are sure to be emitted
     *
     * @return void
     */
    final public function __destruct() {
        // set our default "write to stderr" handlers
        set_error_handler(array($this, 'error'));
        set_exception_handler(array($this, 'exception'));

        // unload any plugins
        //foreach ($this->plugins as $plugin) {
            // call a seperate unload plugin method, so you can load/unload during run
            //$this->emit('unload-plugin', $plugin);
        //}

        // save configuration settings
        $this->emit('save-config', $this->config);

        unset($this->config);

        // emit the shutdown signal
        $this->emit('shutdown');
    }
}

/**
 * Calls underlying C code
 */
GObject::register_type(__NAMESPACE__ . '\Application');