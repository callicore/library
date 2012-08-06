<?php

error_reporting(E_ALL);

class MyIter {
         public $idx = null;
}

class SqliteModel extends PhpGtkCustomTreeModel //implements GtkTreeDragSource, GtkTreeDragDest, GtkTreeSortable
{
	/**
	 * @const INTEGER column holds integer data
	 */
	const INTEGER = 1;

	/**
	 * @const NUMERIC column is a numeric (real) type
	 */
	const NUMERIC = 2;

	/**
	 * @const TEXT column is text
	 */
	const TEXT = 3;

	/**
	 * @const BLOB column is binary data
	 */
	const BLOB = 4;

	/**
	 * @const IMAGE column is binary data that will be autopixbufed
	 */
	const IMAGE = 5;

	/**
	 * @const IMAGEFILE column is a string path to a file that will be autopixbufed
	 */
	const IMAGEFILE = 6;

	/**
	 * @const NONE column can hold any data (no affinity)
	 */
	const NONE = 7;

	/**
	 * database handle for class
	 * @var $dbh object instanceof Pdo
	 */
	static protected $dbh;

	/**
	 * absolute path to db file
	 * @var $dbfile string
	 */
	static protected $dbfile;

	/**
	 * table name or table join string
	 * @var $table string
	 */
	protected $table;

	/**
	 * public function __construct
	 *
	 * give a join string or table name and a list of table name/data type pairs
	 * this will open/create a table based on the information sent
	 *
	 * @return void
	 */
	public function __construct($table, array $columns = array())
	{
		parent::__construct();

		$this->table = $table;

		$map = $this->parse_columns($columns);
		$this->update_tables($map);
		return;
	}

	/**
	 * protected update_tables
	 *
	 * Updates table to match column information sent - WARNING: every table will
	 * automatically be given a tablename_id and tablename_order columns to use for
	 * default order and primary key
	 * WARNING - this will ONLY add additional columns, use drop_table or drop_column
	 * to remove data
	 *
	 * @param $map array map of table names to columns
	 * @see __construct
	 * @return void 
	 */
	protected function update_tables($map)
	{
		
	}

	/**
	 * protected parse_columns
	 *
	 * parses out a list of column names and a join clause into a table -> column
	 * map
	 *
	 * @param $columns array of column name (in table.column format with AS option) to data type
	 * @see __construct
	 * @return array map of table names to columns
	 */
	protected function parse_columns($columns)
	{
		// get table and alias names
		$joins = preg_split('/join/i', $this->table);
		$tables = array();
		$main = null;
		foreach($joins as $string)
		{
			preg_match('/(?:(RIGHT|LEFT|INNER|NATURAL) JOIN )*([\w]+)( AS ([\w]+))*/i', $string, $matches);
			$tables[$matches[1]] = isset($matches[3]) ? $matches[3] : $matches[1];
			if (is_null($main))
			{
				$main = $matches[1];
			}
		}

		// sort columns by table
		$map = array();
		foreach($tables as $table => $alias)
		{
			$map[$table] = array();
		}
		foreach ($columns as $name => $type)
		{
			preg_match('/(([\w]+)\.)*([\w]+)( AS ([\w]+))*/i', $name, $matches);
			if (isset($matches[2]))
			{
				if (array_key_exists($matches[2], $tables))
				{
					$table = $matches[2];
				}
				else
				{
					$table = array_search($matches[2], $tables);
					if ($table === false)
					$table = $main;
				}
			}
			else
			{
				$table = $main;
			}
			$map[$table][$matches[3]] = $type;
		}
		return $map;
	}

	/**
	 * static public get_connection
	 *
	 * attempts to return a current PDO instance or attempts to create a new one
	 *
	 * @param $file string name of sqlite3 file to open
	 * @see set_file
	 * @return instanceof PDO
	 */
	static public function get_connection($file = null)
	{
		if (is_null(self::$dbh))
		{
			if (!is_null($file))
			{
				self::set_file($file);
			}
			if (empty(self::$dbfile))
			{
				throw new CC_Exception('No database filename given', self);
			}
			try {
				$this->dbh = new PDO('sqlite:' . self::$dbfile);
			}
			catch(PdoException $e) {
				throw new CC_Exception('Could not open database file %s', self::$dbfile);
			}
		}
		return self::$dbh;
	}

	/**
	 * static public set_file
	 *
	 * assigns a new filename - return fail if the file does not exist or
	 * the connection is already established
	 *
	 * @param $file string name of sqlite3 file to open
	 * @see set_connection
	 * @return bool
	 */
	static public function set_file($file)
	{
		if (!is_null(self::$dbh) || !file_exists($file))
		{
			return false;
		}
		else
		{
			self::$db = $file;
			return true;
		}
	}
/*
function drag_data_delete($path)
{
}
function drag_data_get($path, GtkSelectionData $data)
{
}
function row_draggable($path)
{
}
function drag_data_received($dest, GtkSelectionData $data)
{
}
function row_drop_possible($path, GtkSelectionData $data)
{
}
function get_sort_column_id()
{
}
function has_default_sort_function()
{
}
function set_default_sort_function($callback)
{
}
function set_sort_column_id($id, $order)
{
}
function set_sort_function($column, $callback)
{
}
function sort_column_changed()
{
}*/
/*
         private $text = array();
         private $iter = null;

         function __construct($filename)
         {
                 parent::__construct();
                 if (is_file($filename)) {
                         $this->text = file($filename);
                         $this->iter = new MyIter;
                 } else {
                         throw new Exception("Could not open  
$filename");
                 }
         }

         function on_get_flags()
         {
                 print "on_get_flags\n";
                 return Gtk::TREE_MODEL_LIST_ONLY;
         }

         function on_get_n_columns()
         {
                 print "on_get_n_columns\n";
                 return 1;
         }

         function on_get_column_type($index)
         {
                 print "on_get_column_type\n";
                 return Gtk::TYPE_STRING;
         }

         function on_get_path($node)
         {
                 exit;
                 print "on_get_path\n";
                 return $node;
         }

         function on_get_iter($path)
         {
                 print "on_get_iter\n";
                 assert(count($path) == 1);
                 $this->iter->idx = $path[0];
                 return $this->iter;
         }

         function on_get_value($iter, $n)
         {
                 print "on_get_value\n";
                 assert($n == 0);
                 return $this->text[$this->iter->idx];
         }

         function on_iter_next($iter)
         {
                 print "on_iter_next\n";
                 $num = count($this->text);
                 if ($this->iter->idx == $num-1) {
                         return null;
                 } else {
                         $this->iter->idx++;
                         return $this->iter;
                 }
         }

         function on_iter_children($iter)
         {
                 return null;
         }

         function on_iter_has_child($iter)
         {
                 return false;
         }

         function on_iter_n_children($iter)
         {
                 return 0;
         }

         function on_iter_nth_child($iter, $n)
         {
                 return null;
         }

         function on_iter_parent($iter)
         {
                 return null;
         } */
}


$w = new GtkWindow();
$w->set_default_size(500, 400);
$w->connect_simple('destroy', array('gtk', 'main_quit'));
$sc = new GtkScrolledWindow();
$sc->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_AUTOMATIC);
$w->add($sc);

$m = new SqliteModel(dirname(__FILE__) . '/test.db');
$tree_view = new GtkTreeView($m);
$cell = new GtkCellRendererText();
$col = new GtkTreeViewColumn("example", $cell, 'text', 0);
$tree_view->append_column($col);

$sc->add($tree_view);
$w->show_all();

gtk::main();