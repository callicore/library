<?php
namespace Callicore::Lib::Datastore;

class Iter {
	public $index = 0;
}

abstract class PdoSqlite3 extends PhpGtkCustomTreeModel {

	/**
	 * Extending classes need to define these
	 */
	protected $filename;
	protected $table;
	protected $columns;

	/**
	 * Internal data, don't touch
	 */
	private $column_map;
	private $column_count;
	private $pdo;
	private $iter;
	private $cache = array();

	public function __construct() {
		parent::__construct();
		$this->pdo = new Pdo('sqlite:' . $this->filename);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		$this->pdo->exec($this->create_table());
		foreach($this->columns as $key => $data) {
			$this->column_map[$key] = $data['name'];
		}
		$this->column_count = count($this->columns);
		$this->iter = new Iter;
	}

	/**
	 * insert a new row
	 */
	public function insert() {
		$data = func_get_args();
		$cols = array();
		$into = array();
		foreach ($data as $key => $value) {
			$cols[] = '"' . $this->column_map[$key] .'"';
			$into[] = '?';
		}
		$sql = 'INSERT INTO "' .$this->table. '" ('. implode(', ', $cols)
			. ') VALUES (' . implode(', ', $into) . ');';
		$result = $this->pdo->prepare($sql);
		$result->execute($data);
	}

	/**
	 * update an existing row
	 */
	public function update($rowid) {
		$data = func_get_args();
		array_shift($data);
		if ($rowid instanceof Iter) {
			$rowid = $rowid->index;
		} elseif (is_array($rowid) && isset($rowid[0])) {
			$rowid = $rowid[0];
		}
		$cols = array();
		foreach ($data as $key => $value) {
			$cols[] = '"' . $this->column_map[$key] .'" = ?';
		}
		$sql = 'UPDATE "' .$this->table. '" SET ' . implode(', ', $cols)
			. ' WHERE ROWID = ?';
		$result = $this->pdo->prepare($sql);
		array_push($data, $rowid);
		$result->execute($data);
		$this->on_unref_node($rowid);
	}

	/**
	 * delete an existing row
	 */
	public function delete($rowid) {
		if ($rowid instanceof Iter) {
			$rowid = $rowid->index;
		} elseif (is_array($rowid) && isset($rowid[0])) {
			$rowid = $rowid[0];
		}
		$this->pdo->exec("DELETE FROM $this->table WHERE ROWID = $rowid");
		$this->on_unref_node($rowid);
	}

	/**
	 * clear the db
	 */
	public function clear() {
		$this->pdo->exec("DELETE FROM $this->table");
		$this->cache = array();
	}

	public function on_ref_node($iter) {
		if ($iter instanceof Iter) {
			$iter = $iter->index;
		}
		if (!isset($this->cache[$iter])) {
			$result = $this->pdo->query("SELECT " . implode(', ', $this->column_map)
				. " FROM $this->table WHERE ROWID = $iter LIMIT 1", PDO::FETCH_NUM);
			$this->cache[$iter] = $result->fetch();
		}
	}

	public function on_unref_node($iter) {
		if ($iter instanceof Iter) {
			$iter = $iter->index;
		}
		if (isset($this->cache[$iter])) {
			unset($this->cache[$iter]);
		}
	}

	public function on_get_n_columns() {
		return $this->column_count;
	}

	public function on_get_flags() {
		return Gtk::TREE_MODEL_LIST_ONLY;
	}

	public function on_get_column_type($index) {
		return $this->columns[$index]['type'];
	}

	public function on_iter_next($iter) {
		$result = $this->pdo->query("SELECT ROWID FROM $this->table WHERE ROWID > $iter->index LIMIT 1",
									PDO::FETCH_COLUMN, 0);
		$next = $result->fetch();
		unset($result);
		if ($next == false || $this->iter->index == $next) {
			return null;
		} else {
			$this->iter->index = $next;
			return $this->iter;
		}
	}

	public function on_get_iter($path) {
		if (isset($path[0])) {
			$num = $this->get_rowid($path[0]);
			if ($num) {
				$this->iter->index = $num;
				return $this->iter;
			}
		}
		return null;
	}

	public function on_get_path($rowid) {
		$result = $this->pdo->query("SELECT COUNT(ROWID) FROM $this->table WHERE ROWID < $rowid",
									PDO::FETCH_COLUMN, 0);
		return $result->fetch();
	}

	public function on_get_value($iter, $column) {
		if(!isset($this->column_map[$column])) {
			return null;
		} elseif (isset($this->cache[$iter->index]) && isset($this->cache[$iter->index][$column])) {
			return $this->cache[$iter->index][$column];
		} else {
			$result = $this->pdo->query("SELECT {$this->column_map[$column]} FROM $this->table WHERE ROWID = $iter->index",
									PDO::FETCH_COLUMN, 0);
			return $result->fetch();
		}
	}

	protected function get_rowid($offset) {
		$result = $this->pdo->query("SELECT ROWID FROM $this->table LIMIT 1 OFFSET $offset",
									PDO::FETCH_COLUMN, 0);
		return $result->fetch();
	}

	public function on_iter_nth_child($iter, $n) {
		if (is_null($iter)) {
			return $this->get_rowid($n);
		}
		return null;
	}

	public function on_iter_n_children($iter) {
		if (is_null($iter)) {
			$result = $this->pdo->query("SELECT COUNT(ROWID) FROM $this->table",
									PDO::FETCH_COLUMN, 0);
			return $result->fetch();
		}
		return 0;
	}

	public function on_iter_children($iter) {
		return null;
	}

	/**
	 * DND support
	 */
	public function row_draggable($path) {
		// TODO: check to see if path exists
		return true;
	}

	public function drag_data_delete($path) {
		return $this->delete($path);
	}

	public function drag_data_get($path, GtkSelectionData $selection_data) {
		
	}

	public function drag_data_received($dest, GtkSelectionData $selection_data) {
		
	}

	public function row_drop_possible($dest_path, GtkSelectionData $selection_data) {
		
	}

	/**
	 * Sort support
	 */
	public function get_sort_column_id() {
		echo "we did try to call this";
	}

	public function has_default_sort_func() {
		
	}

	public function set_default_sort_func($callback) {
	}

	public function set_sort_column_id($sort_column_id , $order) {
	}

	public function set_sort_func($column, $callback) {
	}

	public function sort_column_changed() {
	}

	/**
	 * Internal methods for creating table sql and quoting values
	 */
	protected function create_table() {
		$columns = array();
		foreach ($this->columns as $key => $data) {
			// name and type are required
			if (!isset($data['name']) || !isset($data['type'])) {
				unset($this->columns[$key]);
				trigger_error('Missing name or type for column at index ' . $key, E_USER_WARNING);
				continue;
			}
			// switch on column type
			switch ($data['type']) {
				case Gobject::TYPE_CHAR:
				case Gobject::TYPE_STRING:
				case Gobject::TYPE_PHP_VALUE:
					$string = '"' . $data['name'] . '" TEXT';
					break;
				case Gobject::TYPE_DOUBLE:
					$string = '"' . $data['name'] . '" REAL';
					break;
				case Gobject::TYPE_BOOLEAN:
					$string = '"' . $data['name'] . '" INTEGER';
					if (!isset($data['default'])) {
						$data['default'] = false;
					}
					break;
				case GObject::TYPE_LONG:
					$string = '"' . $data['name'] . '" INTEGER';
					break;
				case Gobject::TYPE_PHP_VALUE:
					$string = '"' . $data['name'] . '" NUMERIC';
					break;
				case Gdkpixbuf::gtype:
					$string = '"' . $data['name'] . '" BLOB';
					break;
				default:
					// default is not a type we support so unset it
					unset($this->columns[$key]);
					trigger_error('Unsupported type for column at index ' . $key, E_USER_WARNING);
			}
			// add not null
			if (isset($data['not_null']) && $data['not_null'] == true) {
				$string .= ' NOT NULL';
			}
			// add default
			if (isset($data['default'])) {
				$string .= ' DEFAULT ' . $this->quote($data['default'], $data['type'] == GObject::TYPE_BOOLEAN);
			}
			// primary key
			if (isset($data['pk']) && $data['pk'] == true) {
				$string .= ' PRIMARY KEY';
			}
			$columns[] = $string;
		}
		return 'CREATE TABLE IF NOT EXISTS "' . $this->table . '" ('
				. implode(', ', $columns) . ');';
	}

	private function quote($value, $bool = false) {
		if (is_null($value)) {
			return 'NULL';
		} elseif ($bool == true) {
			// f and false strings should be false
			if (strcasecmp($value, 'f') === 0 || strcasecmp($value, 'false') === 0) {
				$value = 0;
			}
			if ($value) {
				return 1;
			} else {
				return 0;
			}
		} elseif (is_numeric($value)) {
			return $value;
		} else {
			return "'" . str_replace("'", "''", $value) ."'";
		}
	}
}
//GObject::register_type(__NAMESPACE__ . '::PdoSqlite3');
?>