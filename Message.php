<?php
/**
 * Message.php - Callicore\Lib\Message
 *
 * This is released under the MIT, see license.txt for details
 *
 * @author       Elizabeth Smith <auroraeosrose@php.net>
 * @copyright    Elizabeth Smith (c)2009
 * @link         http://callicore.net
 * @license      http://www.opensource.org/licenses/mit-license.php MIT
 * @version      $Id: Config.php 13 2009-04-25 20:04:04Z auroraeosrose $
 * @since        Php 5.3.0
 * @package      callicore
 * @subpackage   lib
 * @filesource
 */

/**
 * Namespace for all the baseline library functionality
 */
namespace Callicore\Lib;
use \Gtk; // we use constants from here
use \GtkMessageDialog; // import the gtkmessagedialog class

/**
 * Message extends GtkMessageDialog and makes simple messages easy to generate
 *
 * It also provides exception and error logging and handling through message dialogs
 */
class Message extends GtkMessageDialog
{
	/**
	 * @const ERROR error message box
	 */
	const ERROR = Gtk::MESSAGE_ERROR;

	/**
	 * @const WARNING warning message box
	 */
	const WARNING = Gtk::MESSAGE_WARNING;

	/**
	 * @const INFO information message box
	 */
	const INFO = Gtk::MESSAGE_INFO;

	/**
	 * @const QUESTION information message box
	 */
	const QUESTION = Gtk::MESSAGE_QUESTION;

	/**
	 * static - pretty print names for constants
	 * @var $protected array
	 */
	static protected $messages = array(
		self::ERROR => 'ERROR',
		self::WARNING => 'WARNING',
		self::INFO => 'INFO',
	);

	/**
	 * Creates a new message dialog given text, title, and type
	 *
	 * @param string $message text to display in upper area
	 * @param string $title text to display as dialog title
	 * @param int $type one of self::INFO, self::WARNING, self::ERROR
	 * @return void
	 */
	public function __construct($message, $title = 'Default Message', $type = self::INFO)
	{
		if ($type !== self::ERROR && $type !== self::WARNING && $type !== self::QUESTION) {
			$type = self::INFO;
		}

		if($type == self::QUESTION) {
			parent::__construct(null, 0, $type, Gtk::BUTTONS_YES_NO, '');
		} else {
			parent::__construct(null, 0, $type, Gtk::BUTTONS_CLOSE, '');
		}

		$this->set_position(Gtk::WIN_POS_CENTER);
		$this->set_title($title);
		$this->set_markup($message);

		return;
	}

	/**
	 * although the message may be triggered by an error handler
	 * manual messages may wish to set a parent window
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
	 * error handler - changes php error into message dialog and logs it
	 *
	 * @param int $code error code
	 * @param string $message text for error
	 * @param string $file file where error occured
	 * @param int $line line where error occured
	 * @return void
	 */
	static public function error($code, $message, $file, $line)
	{
		switch ($code)
		{
			case (E_STRICT):
			case (E_NOTICE):
			case (E_USER_NOTICE):
			{
				$level = self::INFO;
				$title = 'Information';
				$text = '<b><big>A PHP Information Notice has been Detected</big></b>'
					. "\n" . '<i>The following Information has been detected and logged :</i>'
					. "\n$message\n" . 'The application will now continue.';
				break;
			}
			case (E_WARNING):
			case (E_USER_WARNING):
			{
				$level = self::WARNING;
				$title = 'Warning';
				$text = '<b><big>A PHP Warning has been Detected</big></b>'
					. "\n" . '<i>The following Warning has been detected and logged :</i>'
					. "\n$message\n" . 'The application will now continue.';
				break;
			}
			default:
			{
				$level = self::ERROR;
				$title = 'Error';
				$text = '<b><big>A PHP Error has been Detected</big></b>'
					. "\n" . '<i>The following Error has been detected and logged :</i>'
					. "\n$message\n" . 'The application will now terminate.';
			}
		}
		self::error_log($level, "$message: $code FILE: $file -  LINE $line");
		$win = new Message($text, $title, $level);
		$win->run();
		$win->destroy();
		if ($level == self::ERROR) {
			exit;
		}
		return;
	}

	/**
	 * static public function exception
	 *
	 * last ditch exception handler grabs exception data and passes
	 * it to the error handler
	 *
	 * @param object $e instanceof Exception
	 * @return void
	 */
	static public function exception($e)
	{
		self::error(Gtk::MESSAGE_ERROR, $e->getMessage(), $e->getFile(), $e->getLine());
		return;
	}

	/**
	 * static protected function log
	 *
	 * writes to the error log, useful for debugging
	 *
	 * @param int $level one of self::INFO, self::WARNING, self::ERROR
	 * @param string $message message to log
	 * @return void
	 */
	static protected function error_log($level, $message)
	{
		$log = Util::getFolder('appdata') . 'error.log';
		if (file_exists($log) && filesize($log) > 536870912) {
			rename($log, $path . date('Y-m-d') . 'error.log.bak');
		}
		file_put_contents($log, self::$messages[$level] . ' ' . date('Y-m-d H:i:s')
			. ' --> ' . $message . PHP_EOL, FILE_APPEND);
		return;
	}
}