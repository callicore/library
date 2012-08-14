<?php
/**
 * Config.php - \Callicore\Lib\Config
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
use \ArrayObject; // import base arrayobject class from SPL

/**
 * Config - basic ini file based configuration storage and manipulation
 *
 * Based on arrayobject and can be used as array or object
 */
class Config extends ArrayObject {

    /**
     * absolute path to the currently loaded configuration file
     *
     * @var string
     */
    protected $filename;

    /**
     * application name
     *
     * @var string
     */
    protected $appname;

    /**
     * Loads in an ini file as arrayobject data
     *
     * @return void
     */
    public function __construct($appdata_path, $app_name) {
        // if the appdata dir doesn't exist, create it
        if (!file_exists($appdata_path)) {
            mkdir($appdata_path, 0755, true);
        }

        $this->filename = $appdata_path . $app_name . '.ini';
        $this->appname = $app_name;

        if (file_exists($this->filename)) {
            $config = parse_ini_file($this->filename, true);
        } else {
            touch($this->filename);
            $config = array();
        }

        parent::__construct($config);
    }

    /**
     * Saves configuration information by writing out to an ini file
     *
     * @return void
     */
    public function __destruct() {

        $string = ';Preferences and Configuration for Callicore ' . $this->appname
            . PHP_EOL . '; Saved ' . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;
        // undo the program/widget nesting
        $array = $this->getArrayCopy();

        foreach ($array as $section => $data) {
            if (!is_array($data)) {
                if (is_bool($data)) {
                    $string .= preg_replace('/[' . preg_quote('{}|&~![()"') . ']/', '', $section)
                        . ' = ' . (($data == true) ? 'TRUE' : 'FALSE') . PHP_EOL;
                } elseif (is_scalar($data)) {
                    $string .= preg_replace('/[' . preg_quote('{}|&~![()"') . ']/', '', $section)
                        . ' = "' . str_replace('"', '', $data) . '"' . PHP_EOL;
                }
            }
        }

        foreach ($array as $section => $data) {
            if (!is_array($data)) {
                continue;
            }

            $string .= '[' . preg_replace('/[' . preg_quote('{}|&~![()"') . ']/', '', $section) . ']' . PHP_EOL;
            foreach ($data as $key => $value) {
                if (is_bool($value)) {
                    $string .= preg_replace('/[' . preg_quote('{}|&~![()"') . ']/', '', $key)
                        . ' = ' . (($value == true) ? 'TRUE' : 'FALSE') . PHP_EOL;
                } elseif (is_scalar($value)) {
                    $string .= preg_replace('/[' . preg_quote('{}|&~![()"') . ']/', '', $key)
                        . ' = "' . str_replace('"', '', $value) . '"' . PHP_EOL;
                } else {
                    foreach ($value as $var)
                    {
                        if(!is_scalar($var)) {
                            trigger_error('Data can only nest arrays two deep due to limitations in ini files, item not written', E_USER_NOTICE);
                        }
                        $string .= preg_replace('/[' . preg_quote('{}|&~![()"') . ']/', '', $key) . '[] = "' . str_replace('"', '', $var) . '"' . PHP_EOL;
                    }
                }
            }
        }

        file_put_contents($this->filename, $string);
    }
}