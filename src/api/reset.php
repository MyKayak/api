<?php

set_time_limit(40 * 60);

require_once '../connect.php';

function fix_ficr_string($str) // courtesy of Gemini
{
    // FICR API sends UTF-8 strings that were doubly-encoded as Windows-1252.
    // We reverse this by converting "From UTF-8 (interpreted)" -> "To Windows-1252 (bytes)"
    return mb_convert_encoding($str, "Windows-1252", "UTF-8");
}

function getOrInsertAthlete($conn, $name, $surname, $dob)
{
    $stmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE name = :name AND surname = :surname AND birth_date = :birth_date");
    $stmt->execute(["name" => $name, "surname" => $surname, "birth_date" => $dob]);
    $athlete_from_db = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$athlete_from_db) {
        $stmt = $conn->prepare("INSERT INTO athletes (name, surname, birth_date) values (:name, :surname, :dob)");
        $stmt->execute(["name" => $name, "surname" => $surname, "dob" => $dob]);
        return $conn->lastInsertId();
    } else {
        return $athlete_from_db["athlete_id"];
    }
}

//$division_map = [
//    "SE" => "SEN", "U2" => "U23", "JU" => "JUN", "RA" => "RAG",
//    "CB" => "CDB", "CA" => "CDA", "AB" => "ALB", "AA" => "ALA",
//    "DA" => "DRA", "DB" => "DRB", "R1" => "RA1", "MA" => "MAA",
//    "MB" => "MAB", "MC" => "MAC", "MD" => "MAD", "ME" => "MAE",
//    "MF" => "MAF", "MG" => "MAG", "MH" => "MAH",
//];
//
//$distance_map = [
//    '01' => 200, '02' => 500, '03' => 1000, '04' => 2000, '05' => 5000,
//    '08' => 15000, '14' => 15000, '17' => 15000, '18' => 15000,
//    '09' => 17500, '10' => 12500, '11' => 12500, '39' => 12500,
//    '15' => 20000, '16' => 10000, '50' => 10000, '23' => 18000,
//    '28' => 21000, '29' => 24000, '30' => 38000, '36' => 5000, '38' => 17500,
//];
//
//$boat_map = [
//    '00' => "K2", '10' => "K2", '01' => "C1", '07' => "C1", '21' => "C1",
//    '02' => "C2", '08' => "C2", '03' => "C4", '04' => "K1", '09' => "K1",
//    '18' => "K1", '20' => "K1", '90' => "K1", '94' => "K1", '05' => "K2",
//    '06' => "K4", '11' => "K4", '12' => "K4", '13' => "V2", '15' => "V2",
//    '14' => "V1", '19' => "MX", '24' => "S1", '25' => "S2", '26' => "O1",
//    '27' => "O2", '99' => "DB",
//];
//
//$level_map = [
//    "001" => "HT", "003" => "SF", "005" => "FA",
//    "006" => "DF", "007" => "SR",
//];
//
//$years = range(2022, getdate()['year']);
//
//foreach ($years as $year) {
//    $meets_url = "https://apimanvarie.ficr.it/VAR/mpcache-30/get/schedule/$year/*/19";
//    $meets_json = @file_get_contents($meets_url);
//    if ($meets_json === false) {
//        error_log("Failed to fetch meets from URL: " . $meets_url);
//        continue;
//    }
//    $meets = json_decode($meets_json)->data;
//
//    foreach ($meets as $meet) {
//        $stmt = $conn->prepare("INSERT IGNORE INTO meets (meet_id, location, name, date) VALUES (:meet_id, :location, :name, :date)");
//        $stmt->execute([
//            "meet_id" => $meet->CodicePub,
//            "location" => fix_ficr_string($meet->Place),
//            "name" => fix_ficr_string($meet->Description),
//            "date" => DateTime::createFromFormat('d/m/Y', $meet->Data)->format('Y-m-d')
//        ]);
//    }
//}
//
//$meetIDs = $conn->query("SELECT meet_id FROM meets")->fetchAll(PDO::FETCH_COLUMN);
//
//foreach ($meetIDs as $meetID) {
//    $program_url = "https://apicanoavelocita.ficr.it/CAV/mpcache-30/get/programdate/$meetID";
//    $program_json = @file_get_contents($program_url);
//    if ($program_json === false) {
//        error_log("Failed to fetch program from URL: " . $program_url);
//        continue;
//    }
//    $raceDays = json_decode($program_json)->data;
//
//    foreach ($raceDays as $raceDay) {
//        if (!isset($raceDay->e)) continue;
//
//        foreach ($raceDay->e as $race) {
//            $division = $division_map[substr($race->c0, 0, 2)] ?? null;
//            $category = in_array(substr($race->c0, 2), ["M", "F"]) ? substr($race->c0, 2) : "X";
//            $distance = $distance_map[substr($race->c1, 2, 4)] ?? null;
//            $boat = $boat_map[substr($race->c1, 0, 2)] ?? null;
//            $level = $level_map[$race->c2] ?? null;
//
//            if (!$division || !$distance || !$boat || !$level) {
//                error_log("Could not decode race details for race code: $race->c0-$race->c1-$race->c2 in meet $meetID");
//                continue;
//            }
//
//            $stmt = $conn->prepare("INSERT IGNORE INTO races (race_id, meet_id, distance, division, category, boat, level) VALUES (:race_id, :meet_id, :distance, :division, :category, :boat, :level)");
//            $stmt->execute([
//                "meet_id" => $meetID,
//                "race_id" => "$race->c0-$race->c1-" . substr($race->c2, 1) . "-$race->c3",
//                "distance" => $distance,
//                "division" => $division,
//                "category" => $category,
//                "boat" => $boat,
//                "level" => $level
//            ]);
//        }
//    }
//}

$races = $conn->query("SELECT * FROM races")->fetchAll(PDO::FETCH_ASSOC);

function to_milliseconds($time)
{
    $parts = preg_split("/[:'.]/", $time);
    $millis = 0;
    $count = count($parts);

    if ($count > 0) $millis += intval(array_pop($parts)) * 10;
    if ($count > 1) $millis += intval(array_pop($parts)) * 1000;
    if ($count > 2) $millis += intval(array_pop($parts)) * 60 * 1000;
    if ($count > 3) $millis += intval(array_pop($parts)) * 60 * 60 * 1000;

    return $millis;
}

foreach ($races as $race) {
    $result_url = "https://apicanoavelocita.ficr.it/CAV/mpcache-10/get/result/" . str_replace(" ", "", $race["meet_id"]) . "/KY/" . str_replace("-", "/", str_replace(" ", "", $race["race_id"]));
    $race_json = @file_get_contents($result_url);
    if ($race_json === false) {
        error_log("Failed to fetch result from URL: " . $result_url);
        continue;
    }
    $raceData = json_decode($race_json);

    if (!isset($raceData->data->data) || count($raceData->data->data) === 0) {
        continue;
    }
    foreach ($raceData->data->data as $performance) {
        try {
            $team_id = $performance->PlaTeamCod;

            $stmt = $conn->prepare("INSERT IGNORE INTO heats (meet_id, race_id, heat_index, start_time) values (:meet_id, :race_id, :heat_index, :start_time)");
            $stmt->execute([
                "meet_id" => $race["meet_id"],
                "race_id" => $race["race_id"],
                "heat_index" => $performance->b,
                "start_time" => DateTime::createFromFormat('d/m/Y', $raceData->data->Event->Date)->format('Y-m-d') . " " . $raceData->data->Event->Time . ":00"
            ]);
            $heat_id = $conn->lastInsertId();

            $team_id_padded = str_pad($team_id, 5, "0", STR_PAD_LEFT);
            $stmt = $conn->prepare("INSERT IGNORE INTO teams (team_id, name) values (:team_id, :team_name)");
            $stmt->execute([
                "team_id" => $team_id_padded,
                "team_name" => fix_ficr_string(mb_convert_case($performance->TeamDescrIta, MB_CASE_TITLE, 'UTF-8'))
            ]);

            $time_ms = null;
            $status = null;
            $qual_info = null;

            $mem_prest_val = $performance->MemPrest;
            $status_codes = ['NP', 'NA', 'SQ', 'RIT'];
            $codes_equiv = ['NP' => 'DNS', 'NA' => 'DNF', 'SQ' => 'DSQ', 'RIT' => 'RET'];

            if (in_array($mem_prest_val, $status_codes)) {
                $status = $codes_equiv[$mem_prest_val];
            } elseif (!empty($mem_prest_val) && $mem_prest_val !== " ") {
                $time_ms = to_milliseconds($mem_prest_val);
            }

            $race_id_parts = explode('-', $race['race_id']);
            $c3 = end($race_id_parts);

            $stmt = $conn->prepare(
                "INSERT IGNORE INTO performances (heat_id, team_id, lane, placement, time_ms, status) 
                 VALUES (:heat_id, :team_id, :lane, :placement, :time_ms, :status)"
            );
            $stmt->execute([
                "heat_id" => $heat_id,
                "team_id" => $team_id_padded,
                "lane" => $performance->PlaLane,
                "placement" => $performance->PlaCls,
                "time_ms" => $time_ms,
                "status" => $status,
            ]);
            $performance_id = $conn->lastInsertId();

            if ($performance_id == 0) {
                continue;
            }

            if (!empty($performance->MemQual)){
                $stmt = $conn->prepare(
                    "INSERT IGNORE INTO outcomes (performance_id, outcome) 
                 VALUES (:performance_id, :outcome)"
                );
                $stmt->execute([
                    "performance_id" => $performance_id,
                    "outcome" => $performance->MemQual
                ]);
            }

            $athletes_to_insert = [];
            if (isset($performance->Players) && count($performance->Players) > 0) {
                $athletes_to_insert = $performance->Players;
            } else {
                $athletes_to_insert[] = $performance;
            }

            foreach ($athletes_to_insert as $athlete) {
                if (empty($athlete->PlaName) && empty($athlete->PlaSurname)) continue;

                $dob = (isset($athlete->PlaBirth) && strlen($athlete->PlaBirth) > 7 && $athlete->PlaBirth != "00/00/0000") ? DateTime::createFromFormat('d/m/Y', $athlete->PlaBirth)->format('Y-m-d') : "1970-01-01";
                $athlete_name = fix_ficr_string($athlete->PlaName);
                $athlete_surname = fix_ficr_string(mb_convert_case($athlete->PlaSurname, MB_CASE_TITLE, 'UTF-8'));
                $athlete_id = getOrInsertAthlete($conn, $athlete_name, $athlete_surname, $dob);

                $stmt = $conn->prepare("INSERT IGNORE INTO performances_athletes (performance_id, athlete_id) values (:performance_id, :athlete_id)");
                $stmt->execute(["performance_id" => $performance_id, "athlete_id" => $athlete_id]);
            }
        } catch (PDOException $e) {
            error_log("Error inserting performance data for race " . $race['race_id'] . ": " . $e->getMessage());
        }
    }
}