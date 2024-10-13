<?php

namespace App\Controllers;

class HomepageController {
    private $views = __DIR__ . '/../../src/Views/';
    public function show(): void {
        $title = "Homepage";
        $content = "Welcome to the homepage!";
        
        echo "<h1>$content</h1>";
        include $this->views . 'homepage.php';
    }
}