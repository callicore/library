<?php
$timeout_id = null;
function update($lcd)
{
	list($hours, $minutes, $seconds) = explode(':', $lcd->get_text());
	$seconds++;
	if ($seconds == 60)
	{
		$seconds = '00';
		$minutes++;
	}
	if ($minutes == 60)
	{
		$minutes = '00';
		$hours++;
	}
	if (strlen($seconds) < 2)
	{
		$seconds = '0' . $seconds;
	}
	if (strlen($minutes) < 2)
	{
		$minutes = '0' . $minutes;
	}
	if (strlen($hours) < 2)
	{
		$hours = '0' . $hours;
	}
	$lcd->set_markup(sprintf('<span size="xx-large" weight="bold"><tt>%s:%s:%s</tt></span>', $hours, $minutes, $seconds));
	while(Gtk::events_pending()) { Gtk::main_iteration(); }
	return TRUE;
}
function toggle_timer()
{
	global $timeout_id, $lcd, $button;
	if(is_null($timeout_id))
	{
		$timeout_id = Gtk::timeout_add(1000,'update', $lcd);
		$button->set_label('Stop');
	}
	else
	{
		Gtk::timeout_remove($timeout_id);
		$timeout_id = null;
		$button->set_label('Start');
	}
}
function timer_reset()
{
	global $timeout_id, $lcd;
	if (!is_null($timeout_id))
	{
		toggle_timer();
	}
	$lcd->set_markup('<span size="xx-large" weight="bold"><tt>00:00:00</tt></span>');
}
$window = new GtkWindow();
$window->set_title('Botag PHP Time');
$window->connect_simple('destroy', array('Gtk', 'main_quit'));
$window->add($vbox = new GtkVBox());
$button = new GtkButton('Start');
$reset = new GtkButton('Reset');
$lcd = new GtkLabel();
$lcd->set_markup('<span size="xx-large" weight="bold"><tt>00:00:00</tt></span>');
$vbox->add($lcd);
$vbox->add($hbox = new GtkHButtonBox());
$hbox->add($button);
$hbox->add($reset);
$button->connect('clicked', 'toggle_timer');
$reset->connect('clicked', 'timer_reset');
$window->show_all();
Gtk::main();
?>