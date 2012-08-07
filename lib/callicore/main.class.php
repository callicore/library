<?php
/**
 * main.class.php - main class for callicore desktop
 *
 * the main part of the program handles modules and extensions only
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <emsmith@callicore.net>
 * @copyright    Elizabeth Smith (c)2006
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
 * CC_Main - handles module running, manipulation and management
 *
 * Does basic handling for the main callicore interface including module viewing,
 * running, updating, et al.
 */
class CC_Main
{

	/**
	 * splash window gladxml object
	 * use get_widget to fish out the widgets you want
	 * @var $splash gladexml object
	 */
	protected $splash;

	/**
	 * total number of steps to increment in splash screen
	 * @var $startuptotal int
	 */
	protected $startuptotal = 4;

	/**
	 * main window design
	 * @var $main gladexml object
	 */
	protected $main;

	/**
	 * public function __construct
	 *
	 * runs startup method and then shows the main module window
	 *
	 * @return void
	 */
	public function __construct()
	{
		// startup work
		$this->startup();
		return;
		// get modules
		$this->updateSplashProgressBar('Loading Modules');
		// create window
		$this->updateSplashProgressBar('Setting Up Interface');
		// add modules to window
		// show main window
		// destroy splash window
		$splash = $this->splash->get_widget('main_splash_window');
		$splash->destroy();
		unset($splash);
		return;
	}

	//----------------------------------------------------------------
	//             Startup Functions
	//----------------------------------------------------------------

	/**
	 * protected function startup
	 *
	 * performs startup functionality while displaying the splash screen
	 * if you extend this class or add additional steps, update the $startuptotal var
	 * 1. Db maintenance
	 *    a) create datastore if it doesn't exist
	 *    b) update datastore to current db.xml file
	 * 2. Autoupdate
	 *    a) check for autoupdate on/off
	 *    b) do autoupdate
	 * @return void
	 */
	protected function startup()
	{
		// show splash
		$this->createSplashWindow();
		// db creation and/or update
		$this->updateSplashProgressBar('Datastore Verification', FALSE);
		// generic db instance to work with
		$db = CC_Db::factory('pdosqlite',
			array(
				'ext' => 'db3',
				'path' => CC_DIR . 'data',
				'prefix' => 'cc_',
			));
		// if the db isn't created, create it and import ddl
		if (!in_array('callicore', $db->listDbs()))
		{
			$this->updateSplashProgressBar('Creating Datastore', FALSE);
			$db->createDb('callicore');
			CC_Db::singleton()->upgrade(CC_DIR . 'db.xml');
		}
		else
		{
			// get registry active record
			$ar = new CC_Ar_Registry;
			$ar->name = 'db_version';
			$ar->module = 'main';
			$ar->find();
			// get db file version
			include('version.php');
			if ($ar->value !== $db_version)
			{
				$this->updateSplashProgressBar('Updating from ' . $ar->value
					. ' to ' . $db_version, FALSE);
				CC_Db::singleton()->upgrade(CC_DIR . 'db.xml');
			}
		}
		unset($db);
		$this->updateSplashProgressBar('Datastore Verified');
		// next we check for autoupdate
		$this->updateSplashProgressBar('Checking For Updates', FALSE);
		$ar = new CC_Module;
		$ar->name = 'main';
		$ar->find();
		if ($ar->autoupdate == TRUE)
		{
			$this->updateSplashProgressBar('Performing Autoupdate', FALSE);
			
			$this->updateSplashProgressBar('Updating to Version $version', FALSE);
		$this->updateSplashProgressBar('Update Error: $error', FALSE);
		$this->updateSplashProgressBar('Connecting to http://callicore.net', FALSE);
		
		}
		else
		{
			$this->updateSplashProgressBar('Autoupdate Deactivated', FALSE);
		}
		$this->updateSplashProgressBar('Autoupdate Complete');
		return;
	}

	/**
	 * protected function createSplashWindow
	 *
	 * read in main_splash_window from gladexml and connect up items needed
	 *
	 * @return void
	 */
	protected function createSplashWindow()
	{
		$this->splash = new GladeXML(CC_DIR . 'gui' . DIRECTORY_SEPARATOR . 'callicore.glade',
			'main_splash_window');
		// translateable messages
		$bar = $this->splash->get_widget('progressbar1');
		$message = new CC_String('Loading');
		$bar->set_text($message->__toString());
		unset($bar, $message);
		$this->splash->signal_autoconnect_instance($this);
		return;
	}

	/**
	 * public function destroySplashWindow
	 *
	 * callback triggered when window is destroyed, cleans up
	 *
	 * @return void
	 */
	public function destroySplashWindow()
	{
		unset($this->splash);
		return;
	}

	/**
	 * protected function updateSplashProgressBar
	 *
	 * moves fraction and changes text
	 *
	 * @param string $text text to change loading bar to
	 * @return void
	 */
	protected function updateSplashProgressBar($text, $increment = TRUE)
	{
		// translateable messages
		$bar = $this->splash->get_widget('progressbar1');
		$message = new CC_String($text);
		$bar->set_text($message->__toString());
		if ($increment == TRUE)
		{
			$bar->set_fraction($bar->get_fraction() + (1 / $this->startuptotal));
		}
		unset($bar, $message);
		// force and iteration to show it
		while (Gtk::events_pending())
		Gtk::main_iteration();
	}

	/**
	 * protected function autoUpdate
	 *
	 * automatically update the main application if needed
	 *
	 * @param type $name about
	 * @return type about
	 */
	protected function autoUpdate()
	{
	}
}
?>