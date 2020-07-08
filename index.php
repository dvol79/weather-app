<?php

use App\App;
use App\Request;

require 'vendor/autoload.php';

// Show Errors
error_reporting(E_ALL);
ini_set("display_errors", 1);

// DataBase Encoding
mb_internal_encoding("UTF-8");

// offsets from UTC.
date_default_timezone_set( 'UTC' );

// Start SESSION
session_start();

// Config
$config = parse_ini_file('config/config.ini');

// Run application
$app = new App($config);
$data = $app->getDbForecastData();

if(isset($_POST['date'])){
    $request = new Request();
    $upd_data = $request->post;
    $result = $app->updatePostRow($upd_data);
    if ($app->updatePostRow($upd_data)) {
        $_SESSION['message'] = 'Данные успешно обновлены!';
    } else {
        $_SESSION['message'] = 'Ошибка обновления данных!';
    }
    header("Refresh: 0");
}

// Include header:
require_once 'template/header.php';
// Include main content
require_once 'template/content.php';
// Include footer:
require_once 'template/footer.php';
