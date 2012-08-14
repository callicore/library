<?php
/**
 * Translate.php - \Callicore\Lib\Translate
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
 * Namespace for all the baseline library functionality
 */
namespace Callicore\Lib;
use Callicore\Lib\Application;

/**
 * The Translate class wraps some of the gettext and iconv functionality
 * to make dealing with locales, charsets, and translation easier
 */
class Translate {

    /**
     * current charset for program
     * @var string
     */
    protected $charset;

    /**
     * current locale for program
     * @var string
     */
    protected $locale;

    /**
     * current gettext domain, typically the name of the application running
     * @var string
     */
    protected $domain;

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
    public function __construct() {

        // get defaults
        $this->charset = ini_get('php-gtk.codepage');
        $this->locale = setlocale(LC_ALL, null); // gets current setting
        $this->domain = textdomain(null); // gets current setting
        $this->locale_dir = null;

        // override from configure
        $config = Application::config();
        if (isset($config['locale']) && $config['locale'] !== $this->locale) {
            $this->locale = $config['locale'];
        }
        if (isset($config['charset']) && $config['charset'] !== $this->charset) {
            $this->charset = $config['charset'];
        }
        if (isset($config['locale_dir']) && $config['locale_dir'] !== $this->locale_dir) {
            $this->locale_dir = $config['locale_dir'];
        }
        if (isset($config['domain']) && $config['domain'] !== $this->domain) {
            $this->domain = $config['domain'];
        }

        // setup gettext
        setlocale(LC_ALL, $this->locale);
        ini_set('php-gtk.codepage', $this->charset);
        bindtextdomain($this->domain, $this->locale_dir);
        textdomain($this->domain);
        bind_textdomain_codeset($this->domain, $this->charset);
    }

    /* Gettext helper functions */

    /**
     * Wrapper to make locale, domain, locale_dir and charset read-only properties
     *
     * @param string $string string to translate
     * @return string value of private property
     */
    public function __get($string) {
        if ($string == 'locale') {
            return $this->locale;
        } elseif ($string == 'charset') {
            return $this->charset;
        } elseif ($string == 'domain') {
            return $this->domain;
        } elseif ($string == 'locale_dir') {
            return $this->locale_dir;
        } else {
            return null;
        }
    }

    /**
     * Change locale for application, calls setlocale
     * and sets to configuration class properly
     *
     * @param string $locale new locale to set
     * @return boolean
     */
    public function set_locale($locale) {
        $locale = setlocale(LC_ALL, $locale);
        // the locale change was unsuccessful
        if ($locale === false) {
            return false;
        }
        $this->locale = $locale;
        Application::config()->locale = $this->locale;
        return true;
    }

    /**
     * Change location of the files for the current domain
     *
     * @param string $locale_dir new location for locale files
     * @return boolean
     */
    public function set_locale_dir($locale_dir) {
        $this->locale_dir = $locale_dir;
        bindtextdomain($this->domain, $this->locale_dir);
        Application::config()->locale_dir = $this->locale_dir;
        return true;
    }

    /**
     * Change charset for application, calls underlying gettext methods
     * and sets to configuration class properly
     *
     * @param string $charset new charset to set
     * @return boolean
     */
    public function set_charset($charset) {
        $this->charset = $charset;
        ini_set('php-gtk.codepage', $this->charset);
        bind_textdomain_codeset($this->domain, $this->charset);
        Application::config()->charset = $this->charset;
        return true;
    }

    /**
     * Change gettext domain lookup value
     *
     * @param string $domain new domain for lookups
     * @return boolean
     */
    public function set_domain($domain) {
        $this->domain = $domain;
        textdomain($this->domain);
        Application::config()->domain = $this->domain;
        return true;
    }

    /**
     * wrapper for gettext + vsprintf
     *
     * @param string $string string to translate
     * @return string translated string
     */
    public function _($string /* ... */) {
        $args = func_get_args();
        array_shift($args);
        if (!empty($args) && count($args) == 1 && is_array($args[0])) {
            $args = $args[0];
        }
        return vsprintf(gettext($string), $args);
    }

    /**
     * wrapper for ngettext + vsprintf
     *
     * @param string $string string to translate
     * @param string $plural_string plural version of string to translate
     * @param int $number number of items
     * @return string translated string
     */
    public function __($string, $plural_string, $number /* ... */) {
        $args = func_get_args();
        unset($args[0], $args[1]);
        if (isset($args[3]) && count($args) == 2 && is_array($args[3])) {
            $args = $args[3];
        }
        return vsprintf(ngettext($string, $plural_string, $number), $args);
    }

    /* Iconv helper functions */

    /**
     * uses iconv to convert a string from a different charset
     * to the application charset
     *
     * @param string $string string to translate
     * @return string translated string
     */
    public function convert($string, $charset) {
        return iconv($charset, $this->charset . '//TRANSLIT', $string);
    }

    /**
     * wrapper for iconv_strlen
     * returns length in characters
     *
     * @param string $string string to find character length of
     * @return int length of string
     */
    public function strlen($string) {
        return iconv_strlen($string, $this->charset);
    }

    /**
     * wrapper for iconv_strpos
     * returns offset in characters of first occurence
     *
     * @param string $haystack string we're looking in
     * @param string $needle string we're looking for
     * @param int $offset position where search should start
     * @return int length of string
     */
    public function strpos($haystack, $needle, $offset = 0) {
        return iconv_strpos($haystack, $needle, $offset, $this->charset);
    }

    /**
     * wrapper for iconv_strrpos
     * returns offset in characters of last occurence
     *
     * @param string $haystack string we're looking in
     * @param string $needle string we're looking for
     * @param int $offset position where search should start
     * @return int length of string
     */
    public function strrpos($haystack, $needle, $offset = 0) {
        return iconv_strrpos($haystack, $needle, $offset, $this->charset);
    }

    /**
     * wrapper for iconv_substr
     * Cuts a portion of str specified by the offset and length  parameters.
     * offset and length are in characters NOT bytes
     *
     * @param string $string string we're chopping
     * @param int $offset place to start the chop (neg or positive)
     * @param int $length place to end the chop (neg or positive)
     * @return int length of string
     */
    public function substr($string, $offset, $length = 0) {
        return iconv_substr($string, $offset, $length, $this->charset);
    }
}