<?php
class Sqlite3Iter {
	public $index;
}

class Sqlite3Store extends PhpGtkCustomTreeModel {

	protected $iter;
	protected $filename = 'test.db';
	protected $table = 'languages';

	protected $columns = array(
		'used' => Gobject::TYPE_BOOLEAN,
		'name' => Gobject::TYPE_STRING,
		'year' => Gobject::TYPE_LONG,
	);

	private $column_map;
	private $column_count;
	private $rowidcache;
	private $result;

	public function __construct($dir) {
		parent::__construct();
		$this->sqlite = new Sqlite3($dir . '/' . $this->filename);
		$this->rowidcache = array();
		$this->iter = new Sqlite3Iter;
		$this->column_map = array_values($this->columns);
		$this->column_count = count($this->columns);
	}

	public function on_get_flags() {
		return Gtk::TREE_MODEL_LIST_ONLY;
	}

	public function on_get_n_columns() {
		return $this->column_count;
	}

	public function on_get_column_type($index) {
		return $this->column_map[$index];
	}

	public function on_get_iter($path) {
		if (isset($path[0])) {
			$this->iter->index = $this->get_rowid($path[0]);
			return $this->iter;
		} else {
			return null;
		}
	}

	public function on_get_path($rowid) {
		return $this->sqlite->querySingle("SELECT COUNT(ROWID) FROM $this->table WHERE ROWID < $rowid");
	}

	public function on_get_value($rowid, $column) {
		if ($rowid instanceof Sqlite3Iter) {
			$rowid = $rowid->index;
		}
		if($column > count($this->columns) - 1) {
			return null;
		} elseif ($column == 0) {
			return $rowid;
		} else {
			$result = $this->sqlite->query("SELECT * FROM $this->table WHERE ROWID = $rowid");
			$values = $result->fetchArray(SQLITE3_NUM);
			return $values[$column];
		}
	}

	public function on_iter_next($rowid) {
		var_dump($this->rowidcache);
		return $this->get_next_rowid($rowid);
	}

	public function on_iter_children($rowid) {
		if (is_null($rowid)) {
			return $this->get_next_rowid(-1);
		}
		return null;
	}

	public function on_iter_has_child($rowid) {
		return false;
	}

	public function on_iter_n_children($rowid) {
		if (is_null($rowid)) {
			return $this->get_n_rows();
		}
		return 0;
	}

	public function on_iter_nth_child($rowid, $n) {
		if (is_null($rowid)) {
			return $this->get_rowid($n);
		}
		return null;
	}

	public function on_iter_parent($child) {
		return null;
	}

	protected function get_n_rows() {
		return $this->sqlite->querySingle("SELECT COUNT(ROWID) FROM $this->table");
	}

	protected function get_rowid($offset) {
		return $this->sqlite->querySingle("SELECT ROWID FROM $this->table LIMIT 1 OFFSET $offset");
	}

	protected function get_next_rowid($rowid) {
		if ($rowid instanceof Sqlite3Iter)
		$rowid = $rowid->index;
		if (isset($this->rowidcache[$rowid + 1])) {
			return $this->rowidcache[$rowid + 1];
		} else {
			$result = $this->sqlite->query("SELECT ROWID FROM $this->table WHERE ROWID > $rowid LIMIT 1024");
			if ($result->numColumns() > 0) {
				$rowid = $result->fetchArray(SQLITE3_NUM);
				$rowid = $this->rowidcache[] = $rowid[0];
				while($row = $result->fetchArray(SQLITE3_NUM)) {
					$this->rowidcache[] = $row[0];
				}
			} else {
				return null;
			}
		}
		return $rowid;
	}
}
?>