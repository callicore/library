<?php
error_reporting(E_ALL | E_STRICT);
$new = new boohoo;
class foobar extends GObject implements GtkTreeModel {

    public function for_each () {
    }

    public function get () {
    }

    public function get_column_type($index){}

    public function get_flags () {
    }

    public function get_iter ($treepath){}

    public function get_iter_first () {
    }

    public function get_iter_from_string($path){}

    public function get_iter_root() {
    }

    public function get_n_columns() {
    }

    public function get_path(GtkTreeIter $iter= null) {}

    public function get_string_from_iter(GtkTreeIter $iter=null){}

    public function get_value(GtkTreeIter $iter=null, $column){}

    public function iter_children(GtkTreeIter $parent_iter=null){}

    public function iter_has_child(GtkTreeIter $iter=null){}

    public function iter_n_children(GtkTreeIter $iter=null) {}

    public function iter_next(GtkTreeIter $iter=null){}

    public function iter_nth_child(GtkTreeIter $parent_iter=null, $n) {}

    public function iter_parent(GtkTreeIter $iter=null){}
    public function ref_node(GtkTreeIter $iter=null){}

    public function row_changed($path, GtkTreeIter $iter=null){}

    public function row_deleted ($path) {}

    public function row_has_child_toggled($path, GtkTreeIter $iter=null) {}

    public function row_inserted ($path, GtkTreeIter $iter=null) {}

    public function rows_reordered() {
    }

    public function unref_node(GtkTreeIter $iter=null) {}
}
Gobject::register_type('foobar');
