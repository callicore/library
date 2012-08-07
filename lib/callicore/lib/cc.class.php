<?php
/**
 * cc.class.php - callicore's base class
 *
 * handles a few base functions - run decides which program to run and
 * starts it, i18n is used for translation, requirements checked and
 * autoloading is set up
 *
 * This is released under the GPL, see docs/gpl.txt for details
 *
 * @author       Elizabeth Smith <emsmith@callicore.net>
 * @copyright    Elizabeth Smith (c)2006
 * @link         http://callicore.net/desktop
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: cc.class.php 96 2007-01-05 17:41:47Z emsmith $
 * @since        Php 5.1.0
 * @package      callicore
 * @subpackage   desktop
 * @category     lib
 * @filesource
 */

/**
 * CC - checks settings and manages common properties
 *
 * Basically a wrapper for important callicore functions
 * STATIC ONLY
 */
class CC
{
	/**
	 * @const VERSION app version number
	 */
	const VERSION = '1.0.0-alpha';

	/**
	 * Icon sizes - these SHOULD be constants - but
	 * you can't set class constants after the class is defined
	 * so they're static vars instead - annoying
	 *
	 * sizes are 16, 18, 20, 14, 32, 48, 64 and 128 px square
	 *
	 * @var GtkEnum
	 */
	static public $MENU = Gtk::ICON_SIZE_MENU;
	static public $SMALL_TOOLBAR = Gtk::ICON_SIZE_SMALL_TOOLBAR;
	static public $BUTTON = Gtk::ICON_SIZE_BUTTON;
	static public $LARGE_TOOLBAR = Gtk::ICON_SIZE_LARGE_TOOLBAR;
	static public $DND = Gtk::ICON_SIZE_DND;
	static public $DIALOG = Gtk::ICON_SIZE_DIALOG;
	static public $LARGE;
	static public $IMAGE;

	/**
	 * @var string program to run
	 */
	static public $program;

	/**
	 * @var string root callicore dir
	 */
	static public $dir;

	/**
	 * public function __construct
	 *
	 * forces only static calls
	 *
	 * @return void
	 */
	public function __construct()
	{
		throw new CC_Exception('CC contains only static methods and cannot be constructed');
	}

	/**
	 * static public function run
	 *
	 * checks for requirements, figures out the right program to run, registers
	 * base autoload, and does basic theme setup
	 *
	 * @param string $program 
	 * @return void
	 */
	static public function run($program)
	{
		// global defines for the duration of the program
		// I got tired of typing DIRECTORY_SEPARATOR all the time
		define('DS', DIRECTORY_SEPARATOR, true);
		define('EOL', PHP_EOL, true);
		// CC root - whack off one dir
		self::$dir = dirname((dirname(__FILE__))) . DS;
		self::$program = ucfirst(strtolower($program));
		// set up translation
		if (extension_loaded('gettext'))
		{
			// we use system locale
			bindtextdomain('Callicore', self::$dir . 'locale');
			textdomain('Callicore');
		}
		if (version_compare(PHP_VERSION, '5.1.0', '<'))
		{
			throw new CC_Exception('You must use php 5.1.0 or higher');
		}
		$have = get_loaded_extensions();
		$needed = array('standard', 'pcre', 'date', 'Reflection', 'tokenizer', 'SPL', 'php-gtk');
		if (stristr(PHP_OS, 'win32'))
		{
			$needed[] = 'com';
		}
		$diff = array_diff($needed, $have);
		if (!empty($diff))
		{
			throw new CC_Exception('%s : %s', 'The following extensions must be present - either built into to php or loaded via your php.ini - for Callicore to function', implode(', ', $diff));
		}

		self::$LARGE = Gtk::icon_size_register('gtk-large',64, 64);
		self::$IMAGE = Gtk::icon_size_register('gtk-image',128, 128);
		Gtk::rc_parse(self::$dir . 'stock_icons' . DS . 'stock.rc');
		Gtk::rc_parse(self::$dir . 'cc_icons' . DS . 'callicore.rc');

		spl_autoload_register(array(__CLASS__, 'autoload'));

		$class = 'CC_' . self::$program;
		new $class();
		return;
	}

	/**
	 * static public function ext
	 *
	 * checks for and attempts to load a php extension
	 * if require is true die with an error message
	 *
	 * @param string $ext extension to load
	 * @return void
	 */
	static public function ext($ext)
	{
		// is the extension loaded?
		if(extension_loaded($ext))
		{
			return;
		}
		// let's try to dl it
		if((bool)ini_get('enable_dl') && !(bool)ini_get('safe_mode'))
		{
			// get absolute path to dl
			$path = realpath(ini_get('extension_dir'));
			// we can't rely on PHP_SHLIB_SUFFIX because it screws up on MAC
			if(stristr(PHP_OS, 'win') && file_exists($path . DS . 'php_' . $ext . '.dll')
				&& dl($path . DS . 'php_' . $ext . '.dll'))
			{
				return;
			}
			elseif(file_exists($path . DS . $ext . '.so') && dl($path . DS . $ext . '.so'))
			{
				return;
			}
		}
		// loading did not work - throw a fit
		throw new CC_Exception('The %s extension must be present - either built into to php or loaded via your php.ini - for Callicore to function', $ext);
	}

	//----------------------------------------------------------------
	//             static helper functions
	//----------------------------------------------------------------

	/**
	 * public static function i18n
	 *
	 * wrapper for gettext + sprintf/vsprintf
	 *
	 * @param string $string string to translate
	 * @return string translated string
	 */
	public static function i18n($string)
	{
		$args = func_get_args();
		array_shift($args);
		if (!empty($args) && count($args) == 1 && is_array($args[0]))
		{
			$args = $args[0];
		}
		if (function_exists('gettext'))
		{
			// if we have args, the first item is format only and not translated
			if (is_array($args) && !empty($args))
			{
				$args = array_map('gettext', $args);
			}
			else
			{
				$string = gettext($string);
			}
		}
		return vsprintf($string, $args);
	}

	/**
	 * public static function icon
	 *
	 * uses add_builtin_icon and render_icon to make set_default_icon_name work
	 *
	 * @param string $icon icon name to use as default
	 * @return void
	 */
	public static function icon($icon)
	{
		$theme = GtkIconTheme::get_for_screen(GdkScreen::get_default());
		$window = new GtkWindow();

		$theme->add_builtin_icon($icon, self::$MENU,
			$window->render_icon($icon, self::$MENU));
		$theme->add_builtin_icon($icon, self::$SMALL_TOOLBAR,
			$window->render_icon($icon, self::$SMALL_TOOLBAR));
		$theme->add_builtin_icon($icon, self::$BUTTON,
			$window->render_icon($icon, self::$BUTTON));
		$theme->add_builtin_icon($icon, self::$LARGE_TOOLBAR,
			$window->render_icon($icon, self::$LARGE_TOOLBAR));
		$theme->add_builtin_icon($icon, self::$DND,
			$window->render_icon($icon, self::$DND));
		$theme->add_builtin_icon($icon, self::$DIALOG,
			$window->render_icon($icon, self::$DIALOG));
		$theme->add_builtin_icon($icon, self::$LARGE,
			$window->render_icon($icon, self::$LARGE));
		$theme->add_builtin_icon($icon, self::$IMAGE,
			$window->render_icon($icon, self::$IMAGE));

		GtkWindow::set_default_icon_name($icon);
		return;
	}

	/**
	 * static public function autoload
	 *
	 * if you have a program class the same name as a base class the program
	 * one will always be loaded first (as long as it wasn't previously loaded)
	 * you can add additional autoloads with your programs via spl_register_autoload
	 *
	 * @param string $class class to include
	 * @return bool
	 */
	static public function autoload($class)
	{
		preg_match_all('/[A-Z][a-z0-9_]*/', str_replace('CC_', '', $class), $matches);
		$array = array_map('strtolower', $matches[0]);
		$file = array_pop($array) . '.class.php';
		if (!empty($array))
		{
			$array[] = '';
		}

		$program = self::$dir . 'programs' . DS . strtolower(self::$program) . DS . 'lib' . DS . implode(DS, $array) . $file;
		$lib = self::$dir . 'lib' . DS . implode(DS, $array) . $file;

		if (file_exists($program))
		{
			include $program;
			$return = true;
		}
		elseif (file_exists($lib))
		{
			include $lib;
			$return = true;
		}
		else
		{
			echo $program;
			$return = false;
		}
		return $return;
	}
}

/**
 * CC_Exception - exception class that handles translating messages
 *
 * Included with CC because these are the only two required classes
 */
class CC_Exception extends Exception
{
	/**
	 * public function __construct
	 *
	 * wraps php exception class to do translation of the message
	 * using CC::i18n
	 *
	 * @return void
	 */
	public function __construct($message = null, $code = 0)
	{
		$args = func_get_args();
		parent::__construct(call_user_func_array(array('CC', 'i18n'), $args));
		return;
	}
}