<?php

$path = explode('/', explode('/api/', $_SERVER['REQUEST_URI'])[1]);

header('Content-Type: application/json; charset=utf-8');

switch ($_SERVER["REQUEST_METHOD"]) {
    case "GET":
        switch ($path[0]) {
            case 'reset':
                require_once 'utils/reset.php';
                exit;
        }
        break;
    case "POST":
        switch ($path[0]) {
            case 'create_key':
                require_once 'utils/create_api_key.php';
                echo json_encode(["key" => create_api_key($_POST["description"])]);
                exit;
        }
}
