<?php

$path = array_slice(explode('/', $_SERVER['REQUEST_URI']), 1);

header('Content-Type: application/json; charset=utf-8');

switch ($_SERVER["REQUEST_METHOD"]) {
    case "GET":
        switch (explode("?", $path[0])[0]) {
            case "meets":
                // TODO : require auth
                require "utils/queries.php";
                echo json_encode(getMeets());
                exit;
            case "races":
                if(empty($path[1])) {
                    header("HTTP/1.1 400 Bad request");
                    exit;
                }
                // TODO : require auth
                require "utils/queries.php";
                echo json_encode(getRaces($path[1]));
                exit;
            case "heats":
                if(empty($path[1])){
                    header("HTTP/1.1 400 Bad request");
                    exit;
                }
                // TODO : require auth
                require "utils/queries.php";
                $heats = getHeats($path[1]);
                echo json_encode($heats);
                exit;
            case "medal_table":
                $params = explode("&", explode("?", $path[0])[1] ?? "");
                $meet_id = "";
                $after = "";
                $before = "";
                $championships = false;

                foreach($params as $param) {
                    if(empty($param)) continue;
                    $parts = explode("=", $param);
                    switch($parts[0]) {
                        case "meet_id":
                            $meet_id = $parts[1] ?? "";
                            break;
                        case "before":
                            $before = $parts[1] ?? "";
                            break;
                        case "after":
                            $after = $parts[1] ?? "";
                            break;
                        case "only_championships":
                            $championships = isset($parts[1]) && $parts[1] === "true";
                            break;
                    }
                }
                // TODO : require auth
                require "utils/queries.php";
                echo json_encode(getMedalTable($meet_id, $after, $before, $championships));
                exit;
            case "rankings":
                $params = explode("&", explode("?", $path[0])[1] ?? "");
                $category = "";
                $division = "";
                $distance = "";
                $after = "";

                foreach($params as $param) {
                    if(empty($param)) continue;
                    $parts = explode("=", $param);
                    switch($parts[0]) {
                        case "category":
                            $category = $parts[1] ?? "";
                            break;
                        case "division":
                            $division = $parts[1] ?? "";
                            break;
                        case "distance":
                            $distance = $parts[1] ?? "";
                            break;
                        case "after":
                            $after = $parts[1] ?? "";
                            break;
                    }
                }
                // TODO : require auth
                require "utils/queries.php";
                echo json_encode(getAthleteRankings($category, $division, $distance, $after));
                exit;
            case "athletes":
                require "utils/queries.php";
                $params = explode("&", explode("?", $path[0])[1]);
                $name_hint = "";
                $dob_before = "9999-12-31";
                $dob_after = "0000-01-01";

                foreach($params as $param) {
                    $parts = explode("=", $param);
                    switch($parts[0]) {
                        case "name_hint":
                            $name_hint = $parts[1];
                            break;
                        case "birth_before":
                            $dob_before = $parts[1];
                            break;
                        case "birth_after":
                            $dob_after = $parts[1];
                            break;
                    }
                }

                echo json_encode(getAthletes($name_hint, $dob_before, $dob_after));
                exit;
            case "athlete":
                if(empty($path[1])) {
                    header("HTTP/1.1 400 Bad request");
                    exit;
                }
                require "utils/queries.php";
                echo json_encode(getAthlete($path[1]));
                exit;
            case "teams":
                $params = explode("&", explode("?", $path[0])[1]);
                $hint = "";

                foreach($params as $param) {
                    $parts = explode("=", $param);
                    switch($parts[0]) {
                        case "hint":
                            $hint = $parts[1];
                            break;
                    }
                }
                require "utils/queries.php";
                echo json_encode(getTeams($hint));
                exit;
            case "team":
                if(empty($path[1])) {
                    header("HTTP/1.1 400 Bad request");
                    exit;
                }
                require "utils/queries.php";
                echo json_encode(getTeam($path[1]));
                exit;
        }
        break;
    case "POST":
        switch ($path[0]) {
            case 'create_key':
                require_once 'utils/auth.php';
                $adminKey = $_SERVER["HTTP_X_ADMIN_API_KEY"] ?? "";
                if (empty($adminKey) || !verifyAdminApiKey($adminKey)) {
                    header("HTTP/1.1 401 Unauthorized");
                    exit;
                }
                require_once 'utils/create_api_key.php';
                echo json_encode(["key" => create_api_key($_POST["description"])]);
                exit;
            case 'register':
                require_once 'utils/auth.php';
                if (empty($_POST["username"]) || empty($_POST["email"]) || empty($_POST["password"])) {
                    header("HTTP/1.1 400 Bad request");
                    exit;
                }
                try {
                    $success = registerUser($_POST["username"], $_POST["email"], $_POST["password"]);
                    if ($success) {
                        header("HTTP/1.1 201 Created");
                        echo json_encode(["success" => true]);
                    } else {
                        header("HTTP/1.1 409 Conflict");
                        echo json_encode(["error" => "Email already in use"]);
                    }
                } catch (\PDOException $e) {
                    if ($e->getCode() == 23000) {
                        header("HTTP/1.1 409 Conflict");
                        echo json_encode(["error" => "Username or email already in use"]);
                    } else {
                        header("HTTP/1.1 500 Internal Server Error");
                        echo json_encode(["error" => "Internal Server Error"]);
                    }
                }
                exit;
            case 'login':
                require_once 'utils/auth.php';
                if (empty($_POST["email"]) || empty($_POST["password"])) {
                    header("HTTP/1.1 400 Bad request");
                    exit;
                }
                $token = verifyUserCredentials($_POST["email"], $_POST["password"]);
                if ($token) {
                    echo json_encode(["token" => $token]);
                } else {
                    header("HTTP/1.1 401 Unauthorized");
                    echo json_encode(["error" => "Invalid credentials"]);
                }
                exit;
        }
}

header("HTTP/1.1 404 Not Found");