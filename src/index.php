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
                $params = explode("&", explode("?", $path[0])[1]);
                $meet_id = "";
                $after = "0000-01-01";
                $before = "9999-12-31";
                $championships = false;

                foreach($params as $param) {
                    $parts = explode("=", $param);
                    switch($parts[0]) {
                        case "meet_id":
                            $meet_id = $parts[1];
                            break;
                        case "before":
                            $before = $parts[1];
                            break;
                        case "after":
                            $after = $parts[1];
                            break;
                        case "only_championships":
                            $championships = $parts[1] == true;
                            break;
                    }
                }
                // TODO : require auth
                require "utils/queries.php";
                echo json_encode(getMedalTable($meet_id, $before, $after));
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
            case "athlete":
                if(empty($path[1])) {
                    header("HTTP/1.1 400 Bad request");
                    exit;
                }
                require "utils/queries.php";
                echo json_encode(getAthlete($path[1]));
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
            case "team":
                if(empty($path[1])) {
                    header("HTTP/1.1 400 Bad request");
                    exit;
                }
                require "utils/queries.php";
                echo json_encode(getTeam($path[1]));
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
