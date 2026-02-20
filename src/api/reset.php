<?php

require_once '../connect.php';





function fix_ficr_string($str) // courtesy of Gemini
{
    // FICR API sends UTF-8 strings that were doubly-encoded as Windows-1252.
    // We reverse this by converting "From UTF-8 (interpreted)" -> "To Windows-1252 (bytes)"
    return mb_convert_encoding($str, "Windows-1252", "UTF-8");
}

$years = range(2022, getdate()['year']);

foreach ($years as $year) {
    $meets = json_decode(file_get_contents("https://apimanvarie.ficr.it/VAR/mpcache-30/get/schedule/$year/*/19"))->data;
    foreach ($meets as $meet) {
        $stmt = $conn->prepare("INSERT INTO meets (meet_id, location, name, date) VALUES (:meet_id, :location, :name, :date)");
        try {
            $stmt->execute([
                "meet_id" => $meet->CodicePub,
                "location" => fix_ficr_string($meet->Place),
                "name" => fix_ficr_string($meet->Description),
                "date" => DateTime::createFromFormat('d/m/Y', $meet->Data)->format('Y-m-d')
            ]);
        } catch (PDOException $e) {
        }
    }
}

$meetIDs = $conn->query("SELECT meet_id FROM meets")->fetchAll(PDO::FETCH_COLUMN);

foreach ($meetIDs as $meetID) {
    $raceDays = json_decode(file_get_contents("https://apicanoavelocita.ficr.it/CAV/mpcache-30/get/programdate/$meetID"))->data;
    foreach ($raceDays as $raceDay) {
        if (!isset($raceDay->e))
            continue;

        foreach ($raceDay->e as $race) {
            $division;
            $category;

            switch (substr($race->c0, 0, 2)) {
                case "SE":
                    $division = "SEN";
                    break;
                case "U2":
                    $division = "U23";
                    break;
                case "JU":
                    $division = "JUN";
                    break;
                case "RA":
                    $division = "RAG";
                    break;
                case "CB":
                    $division = "CDB";
                    break;
                case "CA":
                    $division = "CDA";
                    break;
                case "AB":
                    $division = "ALB";
                    break;
                case "AA":
                    $division = "ALA";
                    break;
                case "DA":
                    $division = "DRA";
                    break;
                case "DB":
                    $division = "DRB";
                    break;
            }

            $catChar = substr($race->c0, 2);
            $category = is_int(array_search($catChar, ["M", "F"])) ? $catChar : "X";

            $distance;

            switch (substr($race->c1, 2, 4)) {
                case '01':
                    $distance = 200;
                    break;
                case '02':
                    $distance = 500;
                    break;
                case '03':
                    $distance = 1000;
                    break;
                case '04':
                    $distance = 2000;
                    break;
                case '05':
                    $distance = 5000;
                    break;
                case '08':
                case '14':
                case '17':
                case '18':
                    $distance = 15000;
                    break;
                case '09':
                    $distance = 17500;
                    break;
                case '10':
                case '11':
                case '39':
                    $distance = 12500;
                    break;
                case '15':
                    $distance = 20000;
                    break;
                case '16':
                case '50':
                    $distance = 10000;
                    break;
                case '23':
                    $distance = 18000;
                    break;
                case '28':
                    $distance = 21000;
                    break;
                case '29':
                    $distance = 24000;
                    break;
                case '30':
                    $distance = 38000;
                    break;
                case '36':
                    $distance = 5000;
                    break;
                case '38':
                    $distance = 17500;
                    break;
            }

            $boat;

            switch (substr($race->c1, 0, 2)) {
                case '00':
                case '10':
                    $boat = "K2";
                    break;
                case '01':
                case '07':
                case '21':
                    $boat = "C1";
                    break;
                case '02':
                case '08':
                    $boat = "C2";
                    break;
                case '03':
                    $boat = "C4";
                    break;
                case '04':
                case '09':
                case '18':
                case '20':
                case '90':
                case '94':
                    $boat = "K1";
                    break;
                case '05':
                    $boat = "K2";
                    break;
                case '06':
                case '11':
                case '12':
                    $boat = "K4";
                    break;
                case '13':
                case '15':
                    $boat = "V2";
                    break;
                case '14':
                    $boat = "V1";
                    break;
                case '19':
                    $boat = "MX";
                    break;
                case '24':
                    $boat = "S1";
                    break;
                case '25':
                    $boat = "S2";
                    break;
                case '26':
                    $boat = "O1";
                    break;
                case '27':
                    $boat = "O2";
                    break;
                case '99':
                    $boat = "DB";
                    break;
            }

            $level;

            switch ($race->c2) {
                case "001":
                    $level = "HT";
                    break;
                case "003":
                    $level = "SF";
                    break;
                case "005":
                    $level = "FA";
                    break;
                case "006":
                    $level = "DF";
                    break;
                case "007":
                    $level = "SR"; // Similar to a final but the winner can't be declared a champion
                    break;
            }

            $stmt = $conn->prepare("INSERT INTO races (race_id, meet_id, distance, division, category, boat, level) VALUES (:race_id, :meet_id, :distance, :division, :category, :boat, :level)");
            try {
                $stmt->execute([
                    "meet_id" => $meetID,
                    "race_id" => "$race->c0-$race->c1-" . substr($race->c2, 1) . "-$race->c3",
                    "distance" => $distance,
                    "division" => $division,
                    "category" => $category,
                    "boat" => $boat,
                    "level" => $level
                ]);
            } catch (PDOException $e) {
            }
        }
    }
}

$races = $conn->query("SELECT * FROM races")->fetchAll(PDO::FETCH_ASSOC);

function to_milliseconds($time){
    $millis = 0;
    $temp = "";
    for ($i = strlen($time) - 1; $i >= 0; $i--){
        switch($time[$i]){
            case '.':
                $millis += intval($temp) * 10;
                $temp = "";
                break;
            case '\'':
                $millis += intval($temp) * 1000;
                $temp = "";
                break;
            case ':':
                $millis += intval($temp) * 1000 * 60;
                $temp = "";
                break;
        }
        $temp .= $time[$i];
    }
    $millis += intval($temp) * 1000 * 60 * 60;

    return $millis;
}

foreach ($races as $race) {
    $raceData = json_decode(file_get_contents("https://apicanoavelocita.ficr.it/CAV/mpcache-10/get/result/" . str_replace(" ", "", $race["meet_id"]) . "/KY/" . str_replace("-", "/", str_replace(" ", "", $race["race_id"]))))->data;
    print_r($raceData);
    foreach ($raceData->data as $performance) {
        $heat_index = $performance->b;
        $heat_number = $performance->PlaCod;
        $team_id = $performance->PlaTeamCod;

        $conn->prepare("INSERT INTO heats (meet_id, race_id, heat_index, heat_id, start_time) values (:meet_id, :race_id, :heat_index, :heat_id, :start_time)")
            ->execute(["meet_id" => $race["meet_id"], "race_id" => $race["race_id"], "heat_index" => $heat_index, "heat_id" => $heat_number, "start_time" => DateTime::createFromFormat('d/m/Y', $raceData->Event->Date)->format('Y-m-d') . " " . $raceData->Event->Time . ":00"]);

        $conn->prepare("INSERT INTO teams (team_id, name) values (:team_id, :team_name)")
            ->execute(["team_id" => str_pad($team_id, 5, STR_PAD_LEFT), "team_name" => mb_convert_case($performance->TeamDescrIta, MB_CASE_TITLE, 'UTF-8')]);

        $stmt = $conn->prepare("SELECT heat_id FROM heats WHERE race_id = :race_id AND heat_index = :heat_index");
        $stmt->execute(["race_id" => $race["race_id"], "heat_index" => $heat_index]);
        $heat_id = $stmt->fetch(PDO::FETCH_ASSOC)["heat_id"];


        $time = 0;
        $status;
        switch ($performance->MemPrest) {
            case "NP":
                $status = "DNS";
                break;
            case "NA":
                $status = "DNF";
                break;
            case "SQ":
                $status = "DSQ";
                break;
            case "RIT":
                $status = "RET";
                break;
            default:
                $status = "OK";
                $time = to_milliseconds($performance->MemPrest);
                break;
        }
        $conn->prepare("INSERT INTO performances (heat_id, lane, placement, athlete_team_id, time, status) values (:heat_id, :lane, :placement, :team_id, :time, :status)")->execute([
            "heat_id" => $heat_id,
            "lane" => $performance->PlaLane,
            "placement" => $performance->PlaCls,
            "team_id" => str_pad($team_id, 5, STR_PAD_LEFT),
            "time" => $time,
            "status" => $status
        ]);

        if (defined('performance::Players') && count($performance->Players) > 0) {
            foreach ($performance->Players as $athlete) {
                $dob = strlen($athlete->PlaBirth) > 7 ? DateTime::createFromFormat('d/m/Y', $athlete->PlaBirth)->format('Y-m-d') : "1970-01-01";
                $conn->prepare("INSERT INTO athletes (athlete_name, athlete_surname, birth_date) values (:name, :surname, :dob)")->execute(["name" => $athlete->PlaName, "surname" => mb_convert_case($athlete->PlaSurname, MB_CASE_TITLE, 'UTF-8'), "dob" => $dob]);
            }
        } else {
            $dob = strlen($performance->PlaBirth) > 7 ? DateTime::createFromFormat('d/m/Y', $performance->PlaBirth)->format('Y-m-d') : "1970-01-01";
            $conn->prepare("INSERT INTO athletes (name, surname, birth_date) values (:name, :surname, :dob)")->execute(["name" => $performance->PlaName, "surname" => mb_convert_case($performance->PlaSurname, MB_CASE_TITLE, 'UTF-8'), "dob" => $dob]);
        }
    }
}