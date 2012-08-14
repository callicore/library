<?php
/**
 * Builder.php - \Callicore\Lib\Widget\Builder
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
namespace Callicore\Lib\Widget;
use GtkBuilder;

/**
 * GtkBuilder is a trait you can plugin to implement the layout
 * for almost any widget provided by callicore
 *
 * Use this in your window class to give it gtkbuilder functionality
 */
trait Builder {

    /**
     * GtkBuilder object
     *
     * @var object instanceof GtkBuilder
     */
    protected $builder_object;

    /**
     * public function layout
     *
     * implementation of layout that uses gtkbuilder
     *
     * @param object $app instanceof Callicore\Lib\Application
     * @param string $id name/id of object to use for layout
     * @return void
     */
    public function layout($app, $id = null)
    {
        if (is_null($this->builder_object)) {
            $this->init($app);
        }
        if (file_exists($id)) {
            $this->builder->add_from_file($id);
        }
    }

    /**
     * private function init
     *
     * sets up the gtkbuilder object, basically a constructor
     * for the trait
     *
     * @param string $id name/id of object to use for layout
     * @return void
     */
    private function init($app)
    {
        $builder = $this->builder_object = new GtkBuilder();
        $config = $app->config;
        if (isset($config['domain'])) {
            $builder->set_translation_domain($config['domain']);
        } else {
            $builder->set_translation_domain(textdomain(null)); // gets current gettext setting
        }
    }
}