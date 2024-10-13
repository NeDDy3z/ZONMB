<?php

namespace App\Controllers;

class HomepageController {
    private $views = ROOT . 'Views/';
    public function render(): void {
        $title = "ZONMB";
        $content = "Welcome to the homepage!";

        include $this->views . 'Templates/header.php'; // Import start of HTML, HEAD and Nav bar
        include $this->views . 'homepage.php'; // Import page content
        include $this->views . 'Templates/footer.php'; // Import footer
    }
}