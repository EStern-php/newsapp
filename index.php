
<?php
// Used to hide deprecated warning from andreskrey\Readability
// Temporary since php 8.1 is strict on return. 
// If more time, could patch the library.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', 1);

spl_autoload_register(function($class) {
    $candidates = [
        __DIR__ . '/controllers/' . $class . '.php',
        __DIR__ . '/models/' . $class . '.php',
    ];
    foreach ($candidates as $file) {
        if (file_exists($file)) { require_once $file; return; }
    }
});

$controller = new newsApiController();
$controller->execute();
