<?php
/**
 * actions.class.php - container for gtkactions & gtkactiongroups
 *
 * manages all actions and actiongroups
 *
 * This is released under the GPL, see docs/gpl.txt for details
 *
 * @author       Elizabeth Smith <emsmith@callicore.net>
 * @copyright    Elizabeth Smith (c)2006
 * @link         http://callicore.net/desktop
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: actions.class.php 150 2007-06-13 02:07:50Z emsmith $
 * @since        Php 5.1.0
 * @package      callicore
 * @subpackage   desktop
 * @category     lib
 * @filesource
 */

/**
 * Actions - actions/actiongroups map
 *
 * fixes the weirdness of tooltips, andd generally makes managing actions easier
 */
class CC_Actions
{

	/**
	 * singleton instance for this class
	 * @var $singleton instanceof Tooltips
	 */
	static protected $singleton;

	/**
	 * list of all current created action groups
	 * @var $groups array
	 */
	protected $groups = array();

	/**
	 * global accelerator container for actions
	 * @var $accel instanceof GtkAccelGroup
	 */
	protected $accel;

	/**
	 * public function __construct
	 *
	 * sets up the global accelgroup
	 *
	 * @return void
	 */
	public function __construct()
	{
		if (!is_null(self::$singleton))
		{
			throw new CC_Exception(
			'%1$s is a singleton class - use %1$s::instance() to retrieve the current object',
			'CC_Actions');
		}
		self::$singleton = $this;

		$this->accel = new GtkAccelGroup();
		return;
	}

	/**
	 * public function get_group
	 *
	 * returns action group
	 *
	 * @param string $group name of action group to get
	 * @return instanceof GtkActionGroup
	 */
	public function get_group($group)
	{
		if (!isset($this->groups[$group]))
		{
			throw new CC_Exception('The group %s could not be found', $group);
		}
		return $this->groups[$group];
	}

	/**
	 * public function list_groups
	 *
	 * returns an array of all current gtkactiongroups
	 *
	 * @return array all current groups stored
	 */
	public function list_groups()
	{
		return $this->groups;
	}

	/**
	 * public function get_action
	 *
	 * returns an action from a group (shortcut)
	 *
	 * @param string $group name of action group to get
	 * @param string $action name of action to get
	 * @return instanceof GtkAction
	 */
	public function get_action($group, $action)
	{
		$action = $this->get_group($group)->get_action($action);
		if ($action instanceof GtkAction)
		{
			return $action;
		}
		else
		{
			throw new CC_Exception('The action %s in group %s could not be found', $action, $group);
		}
	}

	/**
	 * public function get_accel
	 *
	 * returns GtkAccelGroup instance, use this to set it in the right window
	 *
	 * @return object instanceof GtkAccelGroup
	 */
	public function get_accel()
	{
		return $this->accel;
	}

	/**
	 * public function create_menu_item
	 *
	 * uses get_group to fetch the action group specified
	 * uses get_action to fetch the action specified
	 * creates a menu item and sets a tooltip for the action
	 *
	 * @param string $group name of action group to get
	 * @param string $action name of action to get
	 * @return object instanceof GtkMenuItem
	 */
	public function create_menu_item($group, $action)
	{
		$tips = CC_Tooltips::instance();
		$item = $this->get_action($group, $action);
		$widget = $item->create_menu_item();
		if ($widget instanceof GtkImageMenuItem)
		$widget->set_image($item->create_icon(CC::$MENU));
		$tips->set_tooltip($widget, $item->get_property('tooltip'));
		return $widget;
	}

	/**
	 * public function create_tool_item
	 *
	 * uses get_group to fetch the action group specified
	 * uses get_action to fetch the action specified
	 * creates a toolbar item and sets a tooltip for the action
	 *
	 * @param string $group name of action group to get
	 * @param string $action name of action to get
	 * @return object instanceof GtkToolItem
	 */
	public function create_tool_item($group, $action)
	{
		$tips = CC_Tooltips::instance();
		$item = $this->get_action($group, $action);
		$widget = $item->create_tool_item();
		$widget->set_use_underline(TRUE);
		$tips->set_tooltip($widget, $item->get_property('tooltip'));
		$widget->show_all();
		return $widget;
	}

	/**
	 * public function create_button_item
	 *
	 * creates a gtkbutton, gtkcheckbutton or gtkradiobutton
	 * and hooks it to the action you specify
	 *
	 * @param string $group name of action group to get
	 * @param string $action name of action to get
	 * @return object instanceof GtkButton
	 */
	public function create_button_item($group, $action)
	{
		$tips = CC_Tooltips::instance();
		$item = $this->get_action($group, $action);
		if ($item instanceof GtkRadioAction)
		{
			$button = new GtkRadioButton();
		}
		elseif ($item instanceof GtkToggleAction)
		{
			$button = new GtkCheckButton();
		}
		else
		{
			$button = new GtkButton();
		}
		$tips->set_tooltip($button, $item->get_property('tooltip'));
		$item->connect_proxy($button);
		return $button;
	}

	/**
	 * public function create_icon
	 *
	 * creates a icon and does tooltip
	 *
	 * @param string $group name of action group to get
	 * @param string $action name of action to get
	 * @return object instanceof GtkImage
	 */
	public function create_icon($group, $action, $size = CC::DND)
	{
		$tips = CC_Tooltips::instance();
		$item = $this->get_action($group, $action);
		$image = $item->create_icon($size);
		$tips->set_tooltip($image, $item->get_property('tooltip'));
		return $image;
	}

	/**
	 * public function connect_proxy
	 *
	 * shortcut for connect proxy
	 *
	 * @param string $group name of action group to get
	 * @param string $action name of action to get
	 * @return object instanceof GtkWidget
	 */
	public function connect_proxy($widget, $group, $action)
	{
		$tips = CC_Tooltips::instance();
		$item = $this->get_action($group, $action);
		$tips->set_tooltip($widget, $item->get_property('tooltip'));
		return $item->connect_proxy($widget);
	}

	/**
	 * public function add_group
	 *
	 * adds a new group with the specific name
	 *
	 * @param string $group group to create
	 * @return void
	 */
	public function add_group($group)
	{
		$this->groups[$group] = new GtkActionGroup($group);
		return;
	}

	/**
	 * public function add_action
	 *
	 * adds and action to a specific group (shortcut)
	 * definition is array(
	 * 'type' => action|toggle|radio,
	 * 'callback' => php callback,
	 * 'accel' => accelerator string to use,
	 * 'name' => internal name for action,
	 * 'label' => label,
	 * 'short-label' => shorter label (for toolbar),
	 * 'tooltip' => tooltip for the action,
	 * 'image' => stock id or named image),
	 * 'value' => value for radio action,
	 * 'radio' => for radio items, the name of the action to group with
	 * );
	 *
	 * @param string $group group to add to
	 * @param array $def action definition array
	 * @return void
	 */
	public function add_action($group, $def)
	{
		if (!isset($this->groups[$group]))
		{
			$this->add_group($group);
		}
		$group = $this->get_group($group);

		if (!isset($def['image']))
		{
			$def['image'] = NULL;
		}
		switch ($def['type'])
		{
			case 'radio':
				$action = new GtkRadioAction($def['name'], CC::i18n($def['label']), CC::i18n($def['tooltip']),$def['image'],$def['value']);
				$signal = 'toggled';
				break;
			case 'toggle':
				$action = new GtkToggleAction($def['name'], CC::i18n($def['label']), CC::i18n($def['tooltip']),$def['image']);
				$signal = 'toggled';
				break;
			default:
				$action = new GtkAction($def['name'], CC::i18n($def['label']), CC::i18n($def['tooltip']),$def['image']);
				$signal = 'activate';
		}
		$action->set_property('short-label', isset($def['short-label']) ? CC::i18n($def['short-label']) : NULL);
		if (isset($def['active']))
		{
			$action->set_active($def['active']);
		}
		if (isset($def['callback']))
		{
			$action->connect($signal, $def['callback']);
		}
		if (isset($def['accel']))
		{
			$action->set_accel_group($this->accel);
			$group->add_action_with_accel($action, $def['accel']);
		}
		else
		{
			$group->add_action($action);
		}
		if (isset($def['radio']))
		{
			$action->set_group($group->get_action($def['radio']));
		}
		return;
	}

	/**
	 * public function add_actions
	 *
	 * adds an array of actions - shortcut for add_action
	 *
	 * @param string $group group to add to
	 * @param array $definitions array of action definitions
	 * @return void
	 */
	public function add_actions($group, $definitions)
	{
		foreach ($definitions as $def)
		{
			$this->add_action($group, $def);
		}
		return;
	}

	/**
	 * public function connect_instance
	 *
	 * connects ALL ACTIONS in a specific group automatically to a
	 *
	 * @param string $group group to add to
	 * @param array $definitions array of action definitions
	 * @return void
	 */
	public function connect_instance($group, $object)
	{
		$list = $this->get_group($group)->list_actions();
		foreach($list as $action)
		{
			$name = $action->get_name();
			$methods = array(
				'on_' . $name . '_activate',
				'on_' . $name . '_toggled',
				'on_action_' . $name . '_activate',
				'on_action_' . $name . '_toggled'
			);

			foreach($methods as $method)
			{
				if(method_exists($object,  $method))
				{
					$action->connect('activate', array($object, $method));
				}
			}
		}
		return;
	}

	/**
	 * public function connect
	 *
	 * connects ALL ACTIONS in a specific group to a callback
	 *
	 * @param string $group group to add to
	 * @param array $definitions array of action definitions
	 * @return void
	 */
	public function connect_all($group, $signal, $callback)
	{
		$args = func_get_args();
		array_shift($args);
		$list = $this->get_group($group)->list_actions();
		foreach($list as $action)
		foreach ($group as $action)
		{
			call_user_func_array(array($action, 'connect'), $args);
		}
		return;
	}

	/**
	 * public function connect_simple
	 *
	 * connects ALL ACTIONS in a specific group to a callback using connect_simple
	 *
	 * @param string $group group to add to
	 * @param array $definitions array of action definitions
	 * @return void
	 */
	public function connect_all_simple($group, $signal, $callback)
	{
		// need list groups and list actions
		$args = func_get_args();
		array_shift($args);
		$list = $this->get_group($group)->list_actions();
		foreach($list as $action)
		{
			call_user_func_array(array($action, 'connect_simple'), $args);
		}
		return;
	}

	/**
	 * static public function instance
	 *
	 * this is how items can access the actions
	 *
	 * @return object instanceof CC_Actions
	 */
	static public function instance()
	{
		if (is_null(self::$singleton))
		{
			self::$singleton = new CC_Actions();
		}
		return self::$singleton;
	}

	/**
	 * public function __clone()
	 *
	 * disable cloning of a singleton
	 *
	 * @return void
	 */
	public function __clone()
	{
		throw new CC_Exception('Cannot clone singleton object %s', 'CC_Actions');
		return;
	}
}
?>