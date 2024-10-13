<?php
    declare(strict_types=1);

    namespace App\Controllers;

    define('ROOT', dirname(__DIR__) . '/src/');

    require ROOT.'/../vendor/autoload.php';
    require ROOT.'/../src/Controllers/HomepageController.php';
    //require ROOT.'/../src/Controllers/NewsController.php';
    //require ROOT.'/../src/Controllers/ArticleController.php';



    $request = $_SERVER['REQUEST_URI'];

    $controller = null;
    switch ($request) {
        case '/' :
            $controller = new HomepageController();
            break;
        default:
            http_response_code(404);
            echo "<h1>Page not found! <a href='/'>Go back</a></h1>";
            break;
    }

    // Show page content
    $controller->render();
