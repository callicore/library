<?php
/**
 * Util.php - \Callicore\Lib\Util
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
 * Util - misc helper functions and information
 * This is an entirely static class
 */
final class Util {

    /**
     * Cached retreived OS folder locations
     * @var array
     */
    static private $folders = array(
                                    'home' => null,
                                    'temp' => null,
                                    'appdata' => null,
                                    'documents' => null,
                                    'config' => null);

    /**
     * Returns special folder absolute paths
     *
     * @param string $folder name of item to retrieve
     * @return string
     */
    static public function getFolder($folder, $appname = 'Callicore') {
        // all lookups are case insensitive
        $folder = strtolower($folder);

        // temp or tmp are both valid
        if ($folder === 'temp' || $folder === 'tmp'){
            if (!self::$folders['temp']) {
                // grab and cache the temp directory
                self::$folders['temp'] = sys_get_temp_dir();
            }
            return self::$folders['temp'];

        // Profile and HOME are the same thing
        } elseif ($folder === 'profile' || $folder === 'home') {
            if (!self::$folders['home']) {
                // grab and cache the home directory
                self::$folders['home'] = self::get_home_dir();
            }
            return self::$folders['home'];

        // config or appdata is where application specific stuff goes
        } elseif ($folder === 'config' || $folder === 'appdata') {
            if (!self::$folders['config']) {
                // grab and cache the home directory
                self::$folders['config'] = self::get_app_dir($appname);
            }
            return self::$folders['config'];

        // documents directory - "My Documents" or "Document"
        } elseif ($folder === 'documents') {
            if (!self::$folders['documents']) {
                // grab and cache the documents directory
                self::$folders['documents'] = self::get_documents_dir($appname);
            }
            return self::$folders['documents'];

        } else {
            return null;
        }
    }

    /**
     * finds application data folder dependent on env variables and OS
     * helper function for getFolders, not to be used by itself
     *
     * @return string
     */
    static protected function get_home_dir()
    {
        if (isset($_ENV['APPDATA'])) {
            return $_ENV['APPDATA'] . DIRECTORY_SEPARATOR;
        } elseif (isset($_ENV['HOME'])) {
            return $_ENV['HOME'] . DIRECTORY_SEPARATOR;
        } else {
            return __DIR__;
        }
    }

    /**
     * returns the application data storage location depending on OS
     * Windows puts stuff in %APPDATA%/$appname
     * Darwin puts things in $HOME/Library/Application Support/$appname
     * everything else gets ~/.strtolower($appname)
     * helper function for getFolders, not to be used by itself
     * 
     * @return string
     */
    static protected function get_app_dir($appname)
    {
        $home = self::getFolder('home');
        if (stristr(PHP_OS, 'win')) {
            return $home . 'Callicore' . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR;
        } elseif (stristr(PHP_OS, 'darwin') || stristr(PHP_OS, 'mac')) {
            return $home . 'Library/Application Support/Callicore/' . $appname . DIRECTORY_SEPARATOR;
        } else {
            return $home . '.callicore/.' . strtolower($appname) . DIRECTORY_SEPARATOR;
        }
    }

    /**
     * finds documents folder dependent on env variables and OS
     * helper function for getFolders, not to be used by itself
     *
     * @return string
     */
    static protected function get_documents_dir($appname)
    {
        // we always use wscript and com on windows because we want "my documents"
        if (stristr(PHP_OS, 'win')) {
            $shell = new \COM('WScript.Shell'); // note the absolute path, we can't use this
            $documents = $shell->SpecialFolders('MyDocuments');
            unset ($shell);
            return $documents;
        } else {
            $home = self::getFolder('appdata', $appname);
            if (file_exists($home . 'Documents')) {
                return $home . 'Documents' . DIRECTORY_SEPARATOR;
            } else {
                return $home;
            }
        }
    }

    /**
     * creates command string and pipes it to exec to open a file with an external
     * app dependent on OS and desktop Windows
     *
     * @param string $file file to open
     * @return string
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
        if (stristr(PHP_OS, 'win')) {
            $shell = new \COM('WScript.Shell'); // note the absolute path, we can't use because doesn't exist on non-win
            $data = $shell->Run($cmd);
            unset($shell);
            return $data;
        } else {
            return exec($cmd);
        }
    }

    /**
     * checks for and attempts to load a php extension
     *
     * @param string $ext extension to load
     * @return bool
     */
    static public function dl($ext) {
        // is the extension loaded?
        if(extension_loaded($ext)) {
            return true;
        }
        // let's try to dl it
        if((bool)ini_get('enable_dl') && !(bool)ini_get('safe_mode')) {
            // get absolute path to dl
            $path = realpath(ini_get('extension_dir'));
            // we can't rely on PHP_SHLIB_SUFFIX because it screws up on MAC
            if(stristr(PHP_OS, 'win') && file_exists($path . DIRECTORY_SEPARATOR . 'php_' . $ext . '.dll')
                && dl($path . DIRECTORY_SEPARATOR . 'php_' . $ext . '.dll')) {
                return true;
            } elseif(file_exists($path . DIRECTORY_SEPARATOR . $ext . '.so') && dl($path . DIRECTORY_SEPARATOR . $ext . '.so')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Uri hook helper function
     *
     * @param object $button gtklinkbutton
     * @param string $link uri to open
     * @return bool
     */
    static public function uri_hook($button, $link) {
        self::launch($link);
        return true; // will stop any bubbling
    }

    /**
     * Makes sure this is never instantiated
     */
    private function __construct() {}
}