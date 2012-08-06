<?php
include 'Application.php';
use Callicore\Lib\Application;

class Test extends Application {
    public function __do_startup() {
        echo "we got called - kind of like a constructor\n";
    }
    public function __do_shutdown() {
        echo "we got called - kind of like a destructor\n";
    }
}
$test = new Test();
?>