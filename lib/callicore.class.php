<?php
/**
 * callicore.class.php - base class full of static methods
 *
 * an all static class that sets up the application environment
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth M Smith <emsmith@callicore.net>
 * @copyright    Elizabeth M Smith (c)2006
 * @link         http://callicore.net
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id$
 * @since        Php 5.2.0
 * @package      callicore
 * @subpackage   main
 * @category     lib
 * @filesource
 */

/**
 * CC - class with all static methods
 *
 * Instatiation is "deactivated" by making this an abstract class, all the
 * static methods still get called just fine - a rather nasty but effective
 * trick :)
 */
abstract class CC
{

	/**
	 * static public function main
	 *
	 * main directory setting, autoloads registered, requirements checked
	 * and settings fixed and applied - sets up the application
	 *
	 * @param string $dir absolute path to main callicore directory
	 * @return void
	 */
	static public function main()
	{
		// cwd may be weird

/**
 * Global Scope - define CC_DIR (main callicore directory) and array shorcut
 */
define('CC_DIR', dirname(__FILE__) . DIRECTORY_SEPERATOR , TRUE);

		// check to make sure CC_DIR is defined, trigger error because autoload is broken
		if (!defined('CC_DIR'))
		{
			trigger_error('Please run callicore using the run.php-gtk script or define CC_DIR');
		}
		// initialize autoloads
		spl_autoload_register(array('CC', 'loadClass'));
		spl_autoload_register(array('CC', 'loadModuleClass'));
		spl_autoload_register(array('CC_Db', 'loadDbClass'));
		spl_autoload_register(array('CC', 'loadArClass'));
		// die if requirements are not met
		if (self::checkRequirements() == TRUE)
		{
			die;
		}
		self::fixSettings();
		// db object settings
		CC_Db::defaultDriver('pdosqlite');
		CC_Db::defaultConfig(
			array(
				'ext' => 'db3',
				'db' => 'callicore',
				'path' => CC_DIR . 'data',
				'prefix' => 'cc_',
			)
		);
		// start up program
		new CC_Main();
	}

	/**
	 * static public function loadClass
	 *
	 * load a lib file for callicore
	 *
	 * @param string $class classname to load
	 * @return bool
	 */
	static public function loadClass($class)
	{
		$array = explode('_', strtolower($class));
		// Format must be CC_NAME
		if (!isset($array[0]) || !isset($array[1]) || $array[0] === 'CC' || isset($array[2]))
		{
			return FALSE;
		}
		if (file_exists(CC_DIR . 'lib' . DIRECTORY_SEPARATOR . $array[1] . '.interface.php'))
		{
			include(CC_DIR . 'lib' . DIRECTORY_SEPARATOR . $array[1] . '.interface.php');
			return TRUE;
		}
		elseif (file_exists(CC_DIR . 'lib' . DIRECTORY_SEPARATOR . $array[1] . '.abstract.php'))
		{
			include(CC_DIR . 'lib' . DIRECTORY_SEPARATOR . $array[1] . '.abstract.php');
			return TRUE;
		}
		elseif (file_exists(CC_DIR . 'lib' . DIRECTORY_SEPARATOR . $array[1] . '.class.php'))
		{
			include(CC_DIR . 'lib' . DIRECTORY_SEPARATOR . $array[1] . '.class.php');
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * static public function loadClass
	 *
	 * load a lib file for a callicore module
	 *
	 * @param string $class classname to load
	 * @return bool
	 */
	static public function loadModuleClass($class)
	{
		$array = explode('_', strtolower($class));
		// Format must be CC_Module_Name
		if (!isset($array[0]) || strcmp('CC', $array[0]) || isset($array[1]) || isset($array[2]))
		{
			return FALSE;
		}
		if (file_exists(CC_DIR . $array[1] . DIRECTORY_SEPARATOR . $array[2] . '.interface.php'))
		{
			include(CC_DIR . $array[1] . DIRECTORY_SEPARATOR . $array[2] . '.interface.php');
			return TRUE;
		}
		elseif (file_exists(CC_DIR . $array[1] . DIRECTORY_SEPARATOR . $array[2] . '.abstract.php'))
		{
			include(CC_DIR . $array[1] . DIRECTORY_SEPARATOR . $array[2] . '.abstract.php');
			return TRUE;
		}
		elseif (file_exists(CC_DIR . $array[1] . DIRECTORY_SEPARATOR . $array[2] . '.class.php'))
		{
			include(CC_DIR . $array[1] . DIRECTORY_SEPARATOR . $array[2] . '.class.php');
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * static private function loadArClass
	 *
	 * autoload for ar classes
	 *
	 * @param string $driver
	 * @return void
	 */
	static public function loadArClass($class)
	{
		$array = explode('_', strtolower($class));
		// Format must be CC_Ar_Name
		if (!isset($array[0]) || $array[0] !== 'cc' || !isset($array[1])
			|| ($array[1] !== 'activerecord' && $array[1] !== 'arfield' &&
			$array[1] !== 'arcollection' && $array[1] !== 'ar'))
		{
			return FALSE;
		}
		$path = CC_DIR . 'lib' . DIRECTORY_SEPARATOR . 'ar' . DIRECTORY_SEPARATOR;
		// are we looking for activerecord, arfield or arcollection
		if ($array[1] === 'arfield')
		{
			$path .= 'arfield.class.php';
		}
		elseif ($array[1] === 'arcollection')
		{
			$path .= 'arcollection.class.php';
		}
		elseif($array[1] === 'activerecord')
		{
			$path .= 'activerecord.abstract.php';
		}
		else
		{
			$path .= $array[2] . '.class.php';
		}
		if (file_exists($path))
		{
			include($path);
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * static private function checkRequirements
	 *
	 * Check for callicore requirements
	 *
	 * @return bool
	 */
	static private function checkRequirements()
	{
		// check php version
		if (version_compare(PHP_VERSION, '5.2-dev', '<'))
		{
			trigger_error('Callicore Desktop requires php version 5.2-dev or later.  You are using '
				. PHP_VERSION, E_USER_ERROR);
			return TRUE;
		}
		// check sapi
		if (PHP_SAPI !== 'cli')
		{
			trigger_error('Callicore Desktop requires the php command line interface(cli).  You are using '
				. PHP_SAPI, E_USER_ERROR);
			return TRUE;
		}
		// required php extensions
		$error = FALSE;
		$required = array('date', 'hash', 'iconv', 'pcre', 'standard', 'PDO',
			'pdo_sqlite', 'gettext', 'gd', 'SimpleXML', 'php-gtk');
		$list = get_loaded_extensions();
		$missing = array();
		foreach ($required as $ext)
		{
			if (!in_array($ext, $list, TRUE))
			{
				$error = TRUE;
				$missing[] = $ext;
			}
		}
		if ($error == TRUE)
		{
			trigger_error('Callicore Desktop requires the following php extensions, '
				. implode(', ', $missing) . '.  Please load them in your php.ini',
				E_USER_ERROR);
		}
		return $error;
	}

	/**
	 * Private static function fixSettings()
	 * 
	 * This will make sure zend compat is off since this requires ZE2, and will
	 * flush out/undo any current buffering
	 * This will also unset any and everything possible in the global scope and is
	 * called even when register globals is off, in case someone is doing something
	 * very very strange
	 *
	 * @return void
	 */
	private static function fixSettings()
	{
		ini_set('zend.ze1_compatibility_mode', FALSE);
		ini_set('zlib.output_compression', 0);
		set_magic_quotes_runtime(0);
		// clean any existing buffers for sanity
		while (ob_get_level() > 0)
		{
			ob_end_clean();
		}
		//allowed vars
		$safelist = array('_GET', '_POST', '_COOKIE', '_SERVER', '_FILES');
		foreach ($GLOBALS as $name => $value)
		{
			if (array_search($name, $safelist) === FALSE)
			{
				unset($$name);
			}
		}
		return;
	}
}
?>