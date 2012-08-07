<?php
/**
 * Error.php - \Callicore\Lib\Error
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

/**
 * Error - error and exception management class
 *
 * contains functionality for managing errors in several ways
 * including logging to a file, setting up debugging, displaying
 * gtk dialogs and other fun stuff
 */
class Error {

    /**
     * simple boolean for did we get an error triggered
     *
     * @var bool
     */
    private $has_error = false;

    /**
     * have we bumped into an error?
     *
     * @return bool
     */
    public function has_error() {
        return $this->has_error;
    }

    /**
     * Usually error handling is gtk dialog + logging
     * but for very basic OH NO we have a very basic handler
     *
     * @return void
     */
    public function restore_handlers() {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Usually error handling is gtk dialog + logging
     * but for very basic OH NO we have a very basic handler
     *
     * @return void
     */
    public function set_stderr_handlers() {
        set_error_handler(array($this, 'stderr_error'));
        set_exception_handler(array($this, 'stderr_exception'));
    }

    /**
     * Error handler to write issues to stderr
     * Used before setup of callicore
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return void
     */
    public function stderr_error($errno, $errstr , $errfile, $errline) {
        $this->has_error = true;

        fwrite(STDERR, wordwrap(
            'Error #' . $errno . ': ' . $errstr . ' on ' . $errfile . ':' . $errline . PHP_EOL . PHP_EOL,
            75, PHP_EOL, true));
    }

    /**
     * Default exception handler for before callicore setup
     *
     * @param object $e instanceof \Exception
     * @return void
     */
    public function stderr_exception($e) {
        $this->std_error($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
    }
}