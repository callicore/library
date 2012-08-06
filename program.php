<?php
/**
 * program.php - Callicore::Lib::Program
 *
 * This is released under the GPL, see docs/gpl.txt for details
 *
 * @author       Elizabeth Smith <auroraeosrose@php.net>
 * @copyright    Elizabeth Smith (c)2008
 * @link         http://callicore.net
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: program.php 2 2008-07-27 17:21:32Z auroraeosrose $
 * @since        Php 5.3.0
 * @package      callicore
 * @subpackage   lib
 * @filesource
 */

/**
 * Namespace for all the baseline library functionality
 */
namespace Callicore::Lib;

/**
 * Program is a gobject - and an abstract class
 *
 * A program should extend this class, setting the appropriate information
 * and then call run
 */
abstract class Program extends GObject {

	const VERSION = '0.1.0-dev';

	/**
	 * store an instance of the program for random stuff to use
	 * @var object instanceof Program
	 */
	static private $program;

	/**
	 * Magical array for defining gsignals for the object
	 * @var array
	 */
	public $__gsignals = array(
		'startup'           => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
		'shutdown'          => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
		'main'              => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
		'main_quit'         => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
		'load-plugin'       => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
		'unload-plugin'     => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
		'initialize'        => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
		'uninitialize'      => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array()),
		'load-settings'     => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array(GObject::TYPE_PHP_VALUE)),
		'save-settings'     => array(GObject::SIGNAL_RUN_LAST, GObject::TYPE_NONE, array(GObject::TYPE_STRING)),
	);

	/**
	 * Loaded plugins list
	 * @var array
	 */
	private $plugins = array();

	/**
	 * Configuration settings
	 * @var array
	 */
	private $config = array();

	/**
	 * location of configuration file
	 * @var string
	 */
	private $config_file;

	/**
	 * current charset for program
	 * @var string
	 */
	private $charset;

	/**
	 * current locale for program
	 * @var string
	 */
	private $locale;


	/**
	 * location of locale files
	 * this is protected so an extending class can override it
	 * @var string
	 */
	protected $locale_dir;

	/**
	 * Sets up the program so it's ready to run
	 *
	 * @param string $program name of the program
	 * @return void
	 */
	public function __construct($program) {

		// Make sure the underlying C code gets called
		parent::__construct();

		$this->program = $program;
		self::$program = $this;

		// emit startup notification
		$this->emit('startup');
		// read in our configuration settings
		$this->load_settings();
		// load up our plugins
		$this->load_plugins();
		// perform our initialization
		$this->initialize();
		// actually run the program
		$this->main();
	}

	/**
	 * Emits shutdown calls
	 *
	 * @return void
	 */
	public function destroy() {
		// we're stopping the run
		$this->main_quit();
		// notify for any uninitalization
		$this->uninitialize();
		// unload our plugins
		//$this->unload_plugins();
		// save settings
		$this->save_settings();
		// emit shutdown notification
		$this->emit('shutdown');
	}

	/**
	 * Creates the main window for the program
	 * The extending class needs to provide the logic
	 * for this item
	 *
	 * @return object instanceof GtkWindow
	 */
	abstract protected function create_main_window();

	//----------------------------------------------------------------
	//            Default signal hooks
	//----------------------------------------------------------------

	/**
	 * By default the message class is used for error and exception reporting
	 * this way if you hook a signal to program and return true you can avoid
	 * calling this initalize
	 *
	 * @return void
	 */
	public function __do_initialize() {
		set_error_handler(array('Callicore::Lib::Message', 'error'));
		set_exception_handler(array('Callicore::Lib::Message', 'exception'));
	}

	//----------------------------------------------------------------
	//            Final functions
	//----------------------------------------------------------------

	/**
	 * Loads in configuration information
	 * If you want to do something before/after settings are loaded
	 * connect to the 'load-settings' signal instead!
	 *
	 * @return void
	 */
	final protected function load_settings() {
		$path = self::get_appdata();

		// Windows puts stuff in %APPDATA%/Callicore
		if (stristr(PHP_OS, 'win')) {
			$path = $path . DIRECTORY_SEPARATOR . 'Callicore' . DIRECTORY_SEPARATOR;
		// darwin puts things in $HOME/Library/Application Support/Callicore/
		} elseif (stristr(PHP_OS, 'darwin') || stristr(PHP_OS, 'mac')) {
			$path = $path . 'Library/Application Support/Callicore' . DIRECTORY_SEPARATOR;
		// most *nix want ~/.callicore
		} else {
			$path = $path . '.callicore' . DIRECTORY_SEPARATOR;
		}
		if (!file_exists($path)) {
			mkdir($path, 077, true);
		}
		$this->config_file = $path . $this->program . '.ini';

		if (file_exists($this->config_file)) {
			$config = parse_ini_file($this->config_file, true);
			foreach($config as $key => $value) {
				$key = explode('.', $key, 2);
				$this->config[$key[0]][$key[1]] = $value;
			}
		}

		$this->emit('load-settings', $this->config);
	}

	/**
	 * Saves configuration information
	 * If you want to do something before/after settings are saved
	 * connect to the 'save-settings' signal instead!
	 *
	 * @return void
	 */
	final protected function save_settings() {
		$string = ';Preferences and Configuration for Callicore ' . $this->program
			. PHP_EOL . '; Saved ' . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;
		// undo the program/widget nesting
		$config = array();
		foreach($this->config as $program => $data) {
			foreach($data as $widget => $info) {
				foreach($info as $key => $value) {
					$config[$program . '.' . $widget][$key] = $value;
				}
			}
		}
		unset($this->config);

		foreach ($config as $section => $data) {
			$string .= '[' . preg_replace('/[' . preg_quote('{}|&~![()"') . ']/', '', $section) . ']' . PHP_EOL;
			foreach ($data as $key => $value) {
				if (is_bool($value)) {
					$string .= preg_replace('/[' . preg_quote('{}|&~![()"') . ']/', '', $key)
						. ' = ' . (($value == true) ? 'TRUE' : 'FALSE') . PHP_EOL;
				} elseif (is_scalar($value)) {
					$string .= preg_replace('/[' . preg_quote('{}|&~![()"') . ']/', '', $key)
						. ' = "' . str_replace('"', '', $value) . '"' . PHP_EOL;
				} else {
					$key = preg_replace('/[' . preg_quote('{}|&~![()"') . ']/', '', $key) . '[]';
					foreach ($value as $var)
					{
						if(!is_scalar($var)) {
							throw new Exception('Data can only nest arrays two deep due to limitations in ini files');
						}
						$string .= $key . '[] = "' . str_replace('"', '', $value) . '"' . PHP_EOL;
					}
				}
			}
		}
		file_put_contents($this->config_file, $string);
		$this->emit('save-settings', $string);
	}

	/**
	 * Loads in all plugins registered in config information
	 * You can hook to the 'load-plugin' signal - it is called
	 * for every plugin loaded
	 *
	 * @return void
	 */
	final protected function load_plugins() {
		'todo: read config plugins, create objects for each and emit signal for each,
		fix signal so it expects a plugin object as data';
	}

	/**
	 * Does basic setup for the program, hook to the initalize signal
	 * if you need additional initalization
	 *
	 * @return void
	 */
	final protected function initialize() {
		 'autoupdate needs to go here!';
		// set defaults
		$this->charset = ini_get('php-gtk.codepage');
		$this->locale = setlocale(LC_ALL, null);
		// grab from configure
		$config =& self::getConfig(__CLASS__);
		if (isset($config['charset'])) {
			if ($config['charset'] !== $this->charset) {
				$this->charset = $config['charset'];
				ini_set('php-gtk.codepage', $this->charset);
			}
		}
		if (isset($config['locale'])) {
			if ($config['locale'] !== $this->locale) {
				$this->locale = $config['locale'];
				setlocale(LC_ALL, $this->locale);
			}
		}
		if (isset($config['locale_dir'])) {
			$this->locale_dir = $config['locale_dir'];
		} elseif (is_null($this->locale_dir)) {
			$this->locale_dir = __DIR__ . '/locale';
		}
		// setup gettext
		bind_textdomain_codeset($this->program, $this->charset);
		bindtextdomain($this->program, $this->locale_dir);
		textdomain($this->program);
		$this->emit('initialize');
	}

	/**
	 * Unhooks any initalized information, makes sure charset and locale are
	 * stored properly in the config
	 *
	 * @return void
	 */
	final protected function uninitialize() {
		// save configuration settings for program
		$config =& self::getConfig(__CLASS__);
		$config['charset'] = $this->charset;
		$config['locale'] = $this->locale;
		$config['locale_dir'] = $this->locale_dir;
		$this->emit('uninitialize');
	}

	/**
	 * Runs the program - calls the abstract methods for generating
	 * the UI, shows the UI, and then starts the gtk main loop
	 *
	 * @return void
	 */
	final protected function main() {
		$window = $this->create_main_window();
		$window->connect_simple('destroy', array($this, 'destroy'));
		$window->show_all();
		$this->emit('main');
		Gtk::main();
	}

	/**
	 * Quits the gtk main loop
	 *
	 * @return void
	 */
	final protected function main_quit() {
		$this->emit('main_quit');
		Gtk::main_quit();
	}

	//----------------------------------------------------------------
	//             static helper functions
	//----------------------------------------------------------------

	/**
	 * checks for and attempts to load a php extension
	 *
	 * @param string $ext extension to load
	 * @return bool
	 */
	static public function ext($ext) {
		// is the extension loaded?
		if(extension_loaded($ext)) {
			return true;
		}
		// let's try to dl it
		if((bool)ini_get('enable_dl') && !(bool)ini_get('safe_mode')) {
			// get absolute path to dl
			$path = realpath(ini_get('extension_dir'));
			// we can't rely on PHP_SHLIB_SUFFIX because it screws up on MAC
			if(stristr(PHP_OS, 'win') && file_exists($path . DS . 'php_' . $ext . '.dll')
				&& dl($path . DS . 'php_' . $ext . '.dll')) {
				return true;
			} elseif(file_exists($path . DS . $ext . '.so') && dl($path . DS . $ext . '.so')) {
				return true;
			}
		}
		return false;
	}

	/**
	 * public function get_documents
	 *
	 * finds documents folder dependent on env variables and OS
	 *
	 * @return void
	 */
	public function get_documents()
	{
		// we always use wscript and com on windows because we want "my documents"
		if (stristr(PHP_OS, 'win'))
		{
			$shell = new COM('WScript.Shell');
			$documents = $shell->SpecialFolders('MyDocuments');
			unset ($shell);
		} else {
			$documents = isset($_ENV['HOME']) ? $_ENV['HOME'] : __DIR__;
			if (file_exists($home . '/Documents')) {
				$documents = $documents . '/Documents/';
			}
			return $documents;
		}
	}

	/**
	 * public function get_appdata
	 *
	 * finds application data folder dependent on env variables and OS
	 *
	 * @return void
	 */
	public function get_appdata()
	{
		if (isset($_ENV['APPDATA'])) {
			$path = $_ENV['APPDATA'] . DIRECTORY_SEPARATOR;
		} elseif (isset($_ENV['HOME'])) {
			$path = $_ENV['HOME'] . DIRECTORY_SEPARATOR;
		} else {
			$path = __DIR__;
		}
		return $path;
	}

	/**
	 * public function launch
	 *
	 * creates command string and pipes it to exec to open a file with an external
	 * app dependent on OS and desktop Windows
	 *
	 * @param string $file file to open
	 * @return bool
	 */
	static public function launch($file)
	{
		if (stristr(PHP_OS, 'winnt')) {
			$cmd = 'cmd /c start "" "' . $file . '"';
		} elseif (stristr(PHP_OS, 'win32')) {
			$cmd = 'command /c start "" "' . $file . '"';
		} elseif (stristr(PHP_OS, 'darwin') || stristr(PHP_OS, 'mac')) {
			$cmd = 'open "' . $file . '"';
		} else {
			// try to use desktop launch standard
			if (isset($_ENV['DESKTOP_LAUNCH'])) {
				$cmd = $_ENV['DESKTOP_LAUNCH'] . '"' . $file . '"';
			} elseif ((isset($_ENV['KDE_FULL_SESSION']) && $_ENV['KDE_FULL_SESSION'] == 'true') ||
			(isset($_ENV['KDE_MULTIHEAD']) && $_ENV['KDE_MULTIHEAD'] == 'true')) {
				$cmd = 'kfmclient exec "' . $file . '"';
			} elseif (isset($_ENV['GNOME_DESKTOP_SESSION_ID']) || isset($_ENV['GNOME_KEYRING_SOCKET'])) {
				$cmd = 'gnome-open "' . $file . '"';
			} else {
				$cmd = $file;
			}
		}
		return exec($cmd);
	}

	/**
	 * wrapper for gettext + sprintf/vsprintf
	 *
	 * @param string $string string to translate
	 * @return string translated string
	 */
	static public function _($string) {
		$args = func_get_args();
		array_shift($args);
		if (!empty($args) && count($args) == 1 && is_array($args[0])) {
			$args = $args[0];
		}
		return vsprintf(gettext($string), $args);
	}

	/**
	 * uses iconv to convert a string to the program charset
	 *
	 * @param string $string string to translate
	 * @return string translated string
	 */
	static public function convert($string, $charset) {
		return iconv($charset, ini_get('php-gtk.codepage') . '//TRANSLIT', $string);
	}

	/**
	 * autoload implementation for callicore based programs
	 *
	 * @param string $class class to include
	 * @return bool
	 */
	static public function autoload($class) {
		$array = explode('::', strtolower($class));
		if (array_shift($array) !== 'callicore') {
			return false;
		}
		// create partial filename
		$file = array_pop($array);
		// if we have no path left, add it back
		if (empty($array)) {
			$path = $file;
		} else {
			$path = implode('/', $array);
		}
		$filename = __DIR__ . '/../' . $path . '/' . $file . '.php';
		if (!file_exists($filename)) {
			echo "File $filename could not be loaded\n";
			return false;
		}
		include $filename;
		return true;
	}

	/**
	 * starts up the program by running it - NOTICE: these messages are NOT
	 * translated or translatable, they simply bail out with simple exceptions and
	 * english messages
	 *
	 * @param string $program program to run
	 * @return void
	 */
	static public function run($program) {
		if (version_compare(PHP_VERSION, '5.3.0-dev', '<')) {
			throw new Exception('You must use php 5.3.0 or higher');
		}

		$have = get_loaded_extensions();
		$needed = array('standard', 'pcre', 'date', 'Reflection', 'tokenizer', 'SPL', 'php-gtk', 'gettext', 'iconv');
		if (stristr(PHP_OS, 'win32')) {
			$needed[] = 'com';
		}
		$diff = array_diff($needed, $have);
		if (!empty($diff)) {
			throw new Exception('The following extensions must be present - either built into to php or loaded via your php.ini - for Callicore to function: ' . implode(', ', $diff));
		}

		error_reporting(E_ALL | E_STRICT);
		ini_set('display_errors', false);
		ini_set('log_errors', true);
		spl_autoload_register(array(__CLASS__, 'autoload'));

		$class = 'Callicore::' . ucfirst(strtolower($program));
		new $class($program);
	}

	/**
	 * grab the configuration for a program
	 *
	 * @return object instanceof Program
	 */
	static public function &getConfig($widget = null) {
		$config =& self::$program->config;
		$program = self::$program->program;
		if (!isset($config[$program])) {
			$config[$program] = array();
		}
		if (is_null($widget)) {
			return $config[$program];
		} else {
			if (!isset($config[$program][$widget])) {
				$config[$program][$widget] = array();
			}
			return $config[$program][$widget];
		}
	}
}
GObject::register_type(__NAMESPACE__ . '::Program');
?>
