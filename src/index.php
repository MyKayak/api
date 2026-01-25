<?php

$path = explode('/', explode('/api/src/', $_SERVER['REQUEST_URI'])[1]);

header('Content-Type: application/json; charset=utf-8');


switch ($path[0]) {
    case 'api':
        switch ($path[1]) {
            case 'reset':
                require_once 'reset.php';
                echo json_encode(['status' => 'success']);
                exit;
        }
        exit;
}