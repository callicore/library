<?php
error_reporting(E_ALL | E_STRICT);
include 'datastore/pdosqlite3.php';
use Callicore::Lib::Datastore::PdoSqlite3;

class MyStore extends PdoSqlite3 {
	protected $filename = 'test.db';
	protected $table = 'languages';

	protected $columns = array(
		array('name' => 'used',
			  'type' => Gobject::TYPE_BOOLEAN,
			  'not_null' => true,
			  'default' => false),
		array('name' => 'name',
			  'type' => Gobject::TYPE_STRING),
		array('name' => 'year',
			  'type' => Gobject::TYPE_LONG,
			  'not_null' => true)
	);

	public function __construct() {
		$this->filename = __DIR__ . '/' . $this->filename;
		parent::__construct();
	}
}

// the model and data for it
$store = new MyStore();
$store->clear();
$store->insert(true, 'PHP', 1994);
$store->insert(true, 'C'  , 1970);
$store->insert(true, 'C++', 1983);
$store->insert(false, 'Ruby'   , 1995);
$store->insert(false, 'Python' , 1990);
$store->insert(true , 'Java'   , 1994);
$store->insert(false, 'Fortran', 1950);
$store->insert(false, 'List'   , 1958);
$store->insert(false, 'Haskell', 1987);
$store->insert(false, 'Eiffel' , 1985);
 
//We want to display our data in a GtkTreeView
$treeview = new GtkTreeView($store);
$treeview->set_reorderable(true);
 
//the text renderer is used to display text
$cell_renderer = new GtkCellRendererText();
 
//Create the first column, make it resizable and sortable
$colLanguage = new GtkTreeViewColumn('Language', $cell_renderer, 'text', 1);
//make the column resizable in width
$colLanguage->set_resizable(true);
//make it sortable and let it sort after model column 1
$colLanguage->set_sort_column_id(1);
//add the column to the view
$treeview->append_column($colLanguage);
 
//second column, also sortable
$colYear = new GtkTreeViewColumn('Year', $cell_renderer, 'text', 2);
$colYear->set_sort_column_id(2);
$treeview->append_column($colYear);

//we want to display a boolean value, so we can use a check box for display
$bool_cell_renderer = new GtkCellRendererToggle();
$colUsed = new GtkTreeViewColumn('Used', $bool_cell_renderer, 'active', 0);
$colUsed->set_sort_column_id(0);
$treeview->append_column($colUsed);
 
 
//A window where we can put our tree view
$wnd = new GtkWindow();
$wnd->set_title('Programming languages');
$wnd->connect_simple('destroy', array('gtk', 'main_quit'));
 
//to make the view scrollable, we need a scrolled window
$scrwnd = new GtkScrolledWindow();
$scrwnd->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_AUTOMATIC);
$scrwnd->add($treeview);
 
$wnd->add($scrwnd);
$wnd->set_default_size(250, 200);
$wnd->show_all();
Gtk::main();