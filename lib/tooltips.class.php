<?php
/**
 * tooltips.class.php - wrapper for gtktooltips
 *
 * fixes tooltips for toolbar and forces singleton
 *
 * This is released under the GPL, see docs/gpl.txt for details
 *
 * @author       Elizabeth M Smith <emsmith@callicore.net>
 * @copyright    Elizabeth M Smith (c)2006
 * @link         http://callicore.net/desktop
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: tooltips.class.php 120 2007-01-12 13:12:00Z emsmith $
 * @since        Php 5.1.0
 * @package      callicore
 * @subpackage   desktop
 * @category     lib
 * @filesource
 */

/**
 * CC_Tooltips - tooltips wrapper
 *
 * fixes the weirdness of toolbar tooltips and forces a singleton instance
 */
class CC_Tooltips extends GtkTooltips
{

	/**
	 * singleton instance for this class
	 * @var $singletons instanceof Tooltips
	 */
	static protected $singleton;

	/**
	 * make sure constructor can ONLY be called by instance
	 * @var $check bool
	 */
	static protected $check;

	/**
	 * fake tooltip window
	 * @var $tooltip instanceof GtkWindow
	 */
	protected $tooltip;

	/**
	 * public function __construct
	 *
	 * sets delay and enables tooltips
	 *
	 * @return void
	 */
	public function __construct()
	{
		// pretend this is a protected method - throw the same error php does
		if (self::$check == false)
		{
			trigger_error('Call to protected ' . __METHOD__ . ' from invalid context', E_USER_ERROR);
		}

		parent::__construct();
		$this->enable();
		return;
	}

	/**
	 * public function set_tooltip
	 *
	 * overrides original function for GtkToolItem fix
	 *
	 * @param object $wiget instanceof GtkObject
	 * @return void
	 */
	public function set_tooltip($widget, $tooltip, $path = null)
	{
		if ($widget instanceof GtkToolItem)
		{
			$widget->set_tooltip($this, $tooltip);
		}
		elseif($widget instanceof GtkTreeView || $widget instanceof GtkIconView)
		{
			if (is_null($this->tooltip))
			{
				$this->tooltip_window();
			}
			$widget->connect('motion-notify-event', array($this, 'on_motion_event'), $tooltip, $path);
			$widget->connect_simple('leave-notify-event', array($this, 'on_leave_event'));
		}
		else
		{
			// flags for menu items/buttons must be set incorrectly, because you can set tooltips on them
			if(!($widget instanceof GtkMenuItem) && !($widget instanceof GtkButton) &&
				$widget->flags() & Gtk::NO_WINDOW)
			{
				$parent = $widget->get_parent();
				$box = new GtkEventBox();
				if($parent instanceof GtkBox)
				{
					$pos = array_search($widget, $parent->get_children(), TRUE);
					$pack = $parent->query_child_packing($widget);
					$parent->pack_end($box);
					$parent->set_child_packing($box, $pack[0], $pack[1], $pack[2], $pack[3]);
					$parent->reorder_child($box, $pos);
					$widget->reparent($box);
				}
				else
				{
					$widget->reparent($box);
					$parent->add($box);
				}
				parent::set_tip($box, $tooltip);
				return;
			}
			parent::set_tip($widget, $tooltip);
		}
		return;
	}

	//----------------------------------------------------------------
	//             Faking Tooltips for non GtkWidgets
	//----------------------------------------------------------------

	/**
	 * protected function tooltip_window
	 *
	 * creates a fake tooltip window
	 *
	 * @return void
	 */
	protected function tooltip_window()
	{
		$this->tooltip = new GtkWindow(Gtk::WINDOW_POPUP);
		$this->tooltip->set_name('gtk-tooltips');
		$this->tooltip->set_resizable(false);
		$this->tooltip->set_border_width(4);
		$this->tooltip->set_app_paintable(true);
		$this->tooltip->connect('expose-event', array($this, 'on_expose_event'));

		$this->tooltip->label = $label = new GtkLabel('');
		$label->set_line_wrap(true);
		$label->set_alignment(0.5, 0.5);
		$label->set_use_markup(true);
		$this->tooltip->add($label);
		return;
	}

	/**
	 * public function on_expose_event
	 *
	 * makes the window look like a tooltip
	 *
	 * @return void
	 */
	public function on_expose_event($window, $event)
	{
		if(!$this->enabled)
		{
			return;
		}
		$size = $window->size_request();
		$window->style->paint_flat_box($window->window, Gtk::STATE_NORMAL,
			Gtk::SHADOW_OUT, null, $window, 'tooltip', 0, 0, $size->width,
			$size->height);
		return;
	}

	/**
	 * public function on_leave_event
	 *
	 * hides the tooltip window
	 *
	 * @return void
	 */
	public function on_leave_event()
	{
		if(!$this->enabled)
		{
			return;
		}
		$this->tooltip->hide();
		return;
	}

	/**
	 * public function on_motion_event
	 *
	 * actually does the work - checks for location of mouse
	 * and pops up tooltip appropriately
	 *
	 * @return void
	 */
	public function on_motion_event($view, $event, $tooltip, $path)
	{
		if(!$this->enabled)
		{
			return;
		}
		$current = $view->get_path_at_pos($event->x, $event->y);
		if (is_null($path) || is_null($current[0]))
		{
			return;
		}
		if($current[0] == $path)
		{
			$this->tooltip->label->set_markup($tooltip);
			$size = $this->tooltip->size_request();
			$this->tooltip->move($event->x_root - $size->width/2, $event->y_root + 12);
			$this->tooltip->show_all();
		}

		return;
	}


	/**
	 * static public function instance
	 *
	 * this is how items can access the tooltips
	 *
	 * @return object instanceof CC_Tooltips
	 */
	static public function instance()
	{
		if (!isset(self::$singleton))
		{
			self::$check = true;
			self::$singleton = new CC_Tooltips();
			self::$check = false;
		}
		return self::$singleton;
	}

	/**
	 * protected function __clone()
	 *
	 * disable cloning of a singleton
	 *
	 * @return void
	 */
	protected function __clone() {}
}
?>