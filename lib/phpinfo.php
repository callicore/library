<?php

class PhpInfoWidget extends GtkNotebook{
	public function __construct(){
		parent::__construct();
		$this->build();
	}

	protected function build(){
		$this->append($label='general',       INFO_GENERAL);
		$this->append($label='credits',       INFO_CREDITS);
		$this->append($label='configuration', INFO_CONFIGURATION);
		$this->append($label='modules',       INFO_MODULES);
		$this->append($label='environnement', INFO_ENVIRONMENT);
		$this->append($label='variables',     INFO_VARIABLES);
		$this->append($label='license',       INFO_LICENSE);
	}

	public function append($title, $what){

		ob_start();
		phpinfo($what);
		$text=ob_get_contents();
		ob_end_clean();

		$text = $this->text_to_scrolled_text(strip_tags($text));
		$title = new GtkLabel($title);
		$this->append_page($text, $title);
	}

	protected function text_to_scrolled_text($text){

		$view = new GtkTextView();
		$view->set_accepts_tab(true);
		$view->set_left_margin(5);
		$view->set_pixels_below_lines(2);
		
		$view->set_editable(false);
		$view->modify_font(new PangoFontDescription("Arial 10"));
		$view->set_wrap_mode(Gtk::WRAP_WORD);

		$buffer=$view->get_buffer();
		$buffer->set_text($text);
		
		$scrolled = new GtkScrolledWindow();
		$scrolled->add_with_viewport($view);

		return $scrolled;
	}
}

class PhpInfoWindow extends GtkWindow{

	protected $notebook;

	public function __construct(){
		parent::__construct();

		$this->buildUI();
		$this->set_title('PhpGtkInfo widget');
		$this->set_position(Gtk::WIN_POS_CENTER);
		$this->set_size_request(600, 400);
		$this->connect_simple('destroy', array('Gtk','main_quit'));
	}

	public function buildUI(){
		$this->notebook = new PhpInfoWidget();
		$this->add($this->notebook);
	}
}

$info = new PhpInfoWindow();
$info->show_all();
Gtk::main();

?>