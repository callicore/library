<?php

$uimanager = new GtkUIManager();
$accelgroup = $uimanager->get_accel_group();

$action_group = new GtkActionGroup('actions');
$uimanager->insert_action_group($action_group, 0);



$ui = '
<ui>
 <menubar name="MenuBar">
    <menu action="File">
      <menuitem action="Quit"/>
    </menu>
    <menu action="Sound">
      <menuitem action="Mute"/>
    </menu>
    <menu action="RadioBand">
      <menuitem action="AM"/>
      <menuitem action="FM"/>
      <menuitem action="SSB"/>
    </menu>
  </menubar>
  <toolbar name="Toolbar">
    <toolitem action="Quit"/>
    <separator/>
    <toolitem action="Mute"/>
    <separator name="sep1"/>
    <placeholder name="RadioBandItems">
      <toolitem action="AM"/>
      <toolitem action="FM"/>
      <toolitem action="SSB"/>
    </placeholder>
  </toolbar>
  </ui>';

$merge_id = $uimanager->add_ui_from_string($ui);

$window = new GtkWindow();
$window->add_accel_group($accelgroup);
$vbox = new GtkVBox();
$menubar = $uimanager->get_widget('/MenuBar');
$toolbar = $uimanager->get_widget('/Toolbar');
$vbox->pack_start($menubar, false);
$vbox->pack_start($toolbar, false);

$window->show_all();
Gtk::main();
?>