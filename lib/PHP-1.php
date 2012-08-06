<?php

// (I may not be explaning it very well but I can visulise it perfectly

$wnd = new GtkWindow();
$wnd->set_title("Panel test Script");
//$wnd->set_size_request(500,500);

$vbox = new GtkVBox();
$hbox = new GtkHBox();

$panel_top = new GtkHbox();
$panel_bottom = new GtkHbox();
$panel_right = new GtkVBox();
$panel_left = new GtkVBox();

$button_left = new GtkButton(">");
$button_left->set_size_request(25,25);
$button_left_label = $button_left->get_child();
$button_left_label->set_angle(90);

$button_right = new GtkButton(">");
$button_right_label = $button_right->get_child();
$button_right_label->set_angle(90);
$button_right->set_size_request(25,25);

$button_bottom = new GtkButton(">");
$button_bottom_label = $button_bottom->get_child();
$button_bottom->set_size_request(25,25);

$panel_right->pack_start($button_left, false, false);
$panel_left->pack_start($button_right,false,false);
$panel_bottom->pack_start(new Gtklabel(''));
$panel_bottom->pack_start($button_bottom,false,false);
$panel_bottom->pack_start(new Gtklabel(''));

$vbox->pack_end($panel_bottom, false,false);
$vbox->pack_start($hbox);
$hbox->pack_start($panel_right, false,false);
$hbox->pack_end($panel_left,false,false);

$wnd->add($vbox);

$main = new GtkNoteBook();
$page_1 = new GtkTextView();
$page_2 = new GtkTextView();
$main->append_page($page_1);
$main->append_page($page_2);

$hpaned1 = new GtkHPaned();
$hpaned2 = new GtkHPaned();
$vpaned1 = new GtkVPaned();
$vpaned1->pack1($hpaned1);
$vpaned1->pack2($bottom = new GtkNoteBook());
$hpaned1->pack1($left = new GtkNoteBook());
$hpaned1->pack2($hpaned2);
$hpaned2->pack1($main);
$hpaned2->pack2($right = new GtkNoteBook());

$hbox->pack_start($vpaned1);

$wnd->connect_simple('destroy',array('gtk','main_quit'));
$wnd->show_all();

$left->hide();
$left->append_page(new Gtklabel(''));
$left->append_page(new Gtklabel(''));
$right->hide();
$right->append_page(new Gtklabel(''));
$right->append_page(new Gtklabel(''));
$bottom->hide();
$bottom->append_page(new Gtklabel(''));
$bottom->append_page(new Gtklabel(''));

$button_left->connect_simple('clicked','on_button_left_clicked',$left,$button_left_label);
$button_right->connect_simple('clicked','on_button_right_clicked',$right,$button_right_label);
$button_bottom->connect_simple('clicked','on_button_bottom_clicked',$bottom,$button_bottom_label);

Gtk::main();

function on_button_left_clicked($pane,$label){
	if ($pane->is_visible()) {
		$pane->hide();
		$label->set_label(">");
		$label->set_angle(90);
	}else{
		$pane->show_all();
		$label->set_label(">");
		$label->set_angle(0);
	}
}

function on_button_right_clicked($pane,$label){
	if ($pane->is_visible()) {
		$pane->hide();
		$label->set_label(">");
		$label->set_angle(90);
	}else{
		$pane->show_all();
		$label->set_label("<");
		$label->set_angle(0);
	}
}

function on_button_bottom_clicked($pane,$label){
	if ($pane->is_visible()) {
		$pane->hide();
		$label->set_label(">");
		$label->set_angle(0);
	}else{
		$pane->show_all();
		$label->set_label(">");
		$label->set_angle(90);
	}
}

?>