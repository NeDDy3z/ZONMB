<?php
    declare(strict_types=1);
    define('ROOT', dirname(__DIR__) . '/src/');

    use App\Controllers\HomepageController;
    require __DIR__ . '/../src/Controllers/HomepageController.php';



    $request = $_SERVER['REQUEST_URI'];

    $controller = null;
    switch ($request) {
        case '/' :
            $controller = new HomepageController();

            break;
        default:
            http_response_code(404);
            echo "Page not found!";
            break;
    }


    // Import start of HTML, HEAD and Nav bar
    include ROOT . 'Views/Templates/header.php';

    // Show page content
    $controller->show();

    // Import footer
    include ROOT . 'Views/Templates/footer.php';
