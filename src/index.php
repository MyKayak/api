<?php

$path = array_slice(explode('/', $_SERVER['REQUEST_URI']), 1);

header('Content-Type: application/json; charset=utf-8');

switch ($_SERVER["REQUEST_METHOD"]) {
    case "GET":
        switch ($path[0]) {
            case "races":
                if(!isset($path[0])){
                    header("HTTP/1.1 400 Bad request");
                    exit;
                }
                require "utils/queries.php";
                echo getRaces($path[1]);
                exit;

            case "meets":
                // TODO : require auth
                require "utils/queries.php";
                echo getMeets();
                exit;
        }
        break;
    case "POST":
        switch ($path[0]) {
            case 'create_key':
                require_once 'utils/auth.php';
                if(!verifyAdminApiKey($_SERVER["HTTP_X_ADMIN_API_KEY"])){
                    header("HTTP/1.1 401 Unauthorized");
                    exit;
                }
                require_once 'utils/create_api_key.php';
                echo json_encode(["key" => create_api_key($_POST["description"])]);
                exit;
        }
}
