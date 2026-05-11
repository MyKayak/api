<?php

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = explode('/', trim($request_uri, '/'));

header('Content-Type: application/json; charset=utf-8');

switch ($_SERVER["REQUEST_METHOD"]) {
    case "GET":
        switch ($path[0]) {
            case "meets":
                require "utils/queries.php";
                echo json_encode(getMeets());
                exit;
            case "races":
                if(empty($path[1])) {
                    header("HTTP/1.1 400 Bad request");
                    exit;
                }
                require "utils/queries.php";
                echo json_encode(getRaces($path[1]));
                exit;
            case "heats":
                if(empty($path[1])){
                    header("HTTP/1.1 400 Bad request");
                    exit;
                }
                $as_startlist = ($_GET["startlist"] ?? "") === "true";
                require "utils/queries.php";
                $heats = getHeats($path[1], $as_startlist);
                echo json_encode($heats);
                exit;
            case "medal_table":
                $meet_id = $_GET["meet_id"] ?? "";
                $before = $_GET["before"] ?? "";
                $after = $_GET["after"] ?? "";
                $championships = ($_GET["only_championships"] ?? "") === "true";

                require "utils/queries.php";
                echo json_encode(getMedalTable($meet_id, $after, $before, $championships));
                exit;
            case "rankings":
                $category = $_GET["category"] ?? "";
                $division = $_GET["division"] ?? "";
                $distance = $_GET["distance"] ?? "";
                $after = $_GET["after"] ?? "";
                $before = $_GET["before"] ?? "";
                $boat = $_GET["boat"] ?? "";

                require "utils/queries.php";
                echo json_encode(getAthleteRankings($category, $division, $distance, $after, $before, $boat));
                exit;
            case "athletes":
                require "utils/queries.php";
                $name_hint = $_GET["name_hint"] ?? "";
                $dob_before = $_GET["birth_before"] ?? "9999-12-31";
                $dob_after = $_GET["birth_after"] ?? "0000-01-01";
                $limit = $_GET["limit"] ?? 100;
                $offset = $_GET["offset"] ?? 0;

                echo json_encode(getAthletes($name_hint, $dob_before, $dob_after, $limit, $offset));
                exit;
            case "athlete":
                if(empty($path[1])) {
                    header("HTTP/1.1 400 Bad request");
                    exit;
                }
                require "utils/queries.php";
                echo json_encode(getAthlete($path[1]));
                exit;
            case "stats":
                require "utils/queries.php";
                echo json_encode(getStats());
                exit;
            case "teams":
                $hint = $_GET["hint"] ?? "";
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
            case 'register':
                require_once 'utils/auth.php';
                requireAdmin();
                if (empty($_POST["username"]) || empty($_POST["password"])) {
                    header("HTTP/1.1 400 Bad request");
                    exit;
                }
                try {
                    $success = registerAdmin($_POST["username"], $_POST["password"]);
                    if ($success) {
                        header("HTTP/1.1 201 Created");
                        echo json_encode(["success" => true]);
                    } else {
                        header("HTTP/1.1 409 Conflict");
                        echo json_encode(["error" => "Username already in use"]);
                    }
                } catch (\PDOException $e) {
                    header("HTTP/1.1 500 Internal Server Error");
                    echo json_encode(["error" => "Internal Server Error"]);
                }
                exit;
            case 'login':
                require_once 'utils/auth.php';
                if (empty($_POST["username"]) || empty($_POST["password"])) {
                    header("HTTP/1.1 400 Bad request");
                    exit;
                }
                $token = verifyAdminCredentials($_POST["username"], $_POST["password"]);
                if ($token) {
                    echo json_encode(["token" => $token]);
                } else {
                    header("HTTP/1.1 401 Unauthorized");
                    echo json_encode(["error" => "Invalid credentials"]);
                }
                exit;
        }
        break;
    case "PATCH":
        if ($path[0] === 'meets' && !empty($path[1])) {
            require_once 'utils/auth.php';
            requireAdmin();
            require_once 'utils/queries.php';
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                header("HTTP/1.1 400 Bad request");
                exit;
            }
            
            if (updateMeet($path[1], $input)) {
                echo json_encode(["success" => true]);
            } else {
                header("HTTP/1.1 500 Internal Server Error");
                echo json_encode(["error" => "Failed to update meet"]);
            }
            exit;
        }
        break;
    case "PUT":
        if ($path[0] === 'teams' && !empty($path[1]) && ($path[2] ?? '') === 'logo') {
            require_once 'utils/auth.php';
            requireAdmin();
            require_once 'utils/queries.php';
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!isset($input['logo'])) {
                header("HTTP/1.1 400 Bad request");
                exit;
            }
            
            if (updateTeamLogo($path[1], $input['logo'])) {
                echo json_encode(["success" => true]);
            } else {
                header("HTTP/1.1 500 Internal Server Error");
                echo json_encode(["error" => "Failed to update team logo"]);
            }
            exit;
        }
        break;
}

header("HTTP/1.1 404 Not Found");
