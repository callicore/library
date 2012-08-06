<?php

error_reporting(E_ALL);

class MyIter {
         public $idx = null;
}

class FileModel extends PhpGtkCustomTreeModel {

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
                 return GObject::TYPE_STRING;
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
         }
}


$w = new GtkWindow();
$w->set_default_size(500, 400);
$w->connect_simple('destroy', array('gtk', 'main_quit'));
$sc = new GtkScrolledWindow();
$sc->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_AUTOMATIC);
$w->add($sc);

$m = new FileModel(__FILE__);
$tree_view = new GtkTreeView($m);
$cell = new GtkCellRendererText();
$col = new GtkTreeViewColumn("example", $cell, 'text', 0);
$tree_view->append_column($col);

$sc->add($tree_view);
$w->show_all();

gtk::main();
