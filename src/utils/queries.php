<?php

function getMeets(){
    require "connect.php";
    $meets = [];
    foreach($conn->query("SELECT * FROM meets ORDER BY date DESC")->fetchAll() as $meet){
        $meets[] = [
            "id" => trim($meet["meet_id"]),
            "name" => $meet["name"],
            "location" => $meet["location"],
            "date" => $meet["date"],
        ];
    }
    return $meets;
}

function getRaces($meet_id){
    require "connect.php";
    $races = [];
    $stmt = $conn->prepare("SELECT * FROM races WHERE meet_id = :meet_id");
    $meet_id = str_replace("%20"," ", $meet_id);
    $stmt->bindParam(":meet_id", $meet_id);
    $stmt->execute();
    foreach($stmt->fetchAll() as $race){
        $races[] = [
            "id" => $race["race_id"],
            "code" => $race["race_code"],
            "distance" => $race["distance"],
            "division" => $race["division"],
            "category" => $race["category"],
            "boat" => $race["boat"],
            "level" => $race["level"],
        ];
    }
    return $races;
}

function getHeats($race_id){
    require "connect.php";
    $heats = [];
    $stmt = $conn->prepare("SELECT * FROM heats WHERE  race_id = :race_id");
    $stmt->bindParam(":race_id", $race_id);
    $stmt->execute();
    foreach($stmt->fetchAll() as $heat){
        $heats[] = [
            "id" => $heat["heat_id"],
            "index" => $heat["heat_index"],
            "start_time" => $heat["start_time"],
            "performances" => getPerformances($heat["heat_id"]),
        ];
    }
    return $heats;
}

function getPerformances($heat_id){
    require "connect.php";
    $performances = [];
    $stmt = $conn->prepare("SELECT * FROM performances INNER JOIN teams USING (team_id) WHERE heat_id = :heat_id");
    $stmt->bindParam(":heat_id", $heat_id);
    $stmt->execute();
    foreach($stmt->fetchAll() as $performance){
        $performances[] = [
            "id" => $performance["performance_id"],
            "team_id" => $performance["team_id"],
            "team_name" => $performance["name"],
            "lane" => $performance["lane"],
            "placement" => $performance["placement"],
            "time_ms" => $performance["time_ms"],
            "status" => $performance["status"],
            "points" => $performance["points"],
            "athletes" => getPerformanceAthletes($performance["performance_id"]),
        ];
    }
    return $performances;
}

function getPerformanceAthletes($performance_id){
    require "connect.php";
    $athletes = [];

    $stmt = $conn->prepare("SELECT name, surname, birth_date FROM athletes INNER JOIN performances_athletes USING (athlete_id) INNER JOIN performances USING (performance_id) WHERE performance_id = :performance_id");
    $stmt->bindParam(":performance_id", $performance_id);
    $stmt->execute();
    foreach($stmt->fetchAll() as $athlete){
        $athletes[] = [
            "name" => $athlete["name"], "surname" => $athlete["surname"],
            "birth_date" => $athlete["birth_date"],
        ];
    }
    return $athletes;
}

function getMedalTable($meet_id){
    require "connect.php";
    $meet_id = str_replace("%20"," ", $meet_id);
    $stmt = $conn->prepare("SELECT * FROM medal_table_view WHERE meet_id = :meet_id ORDER BY gold DESC, silver DESC, bronze DESC");
    $stmt->bindParam(":meet_id", $meet_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAthletes($name_hint, $dob_before, $dob_after){
    require "connect.php";
    $stmt = $conn->prepare("SELECT * FROM athletes WHERE birth_date >= :dob_after AND birth_date <= :dob_before AND (name LIKE :name_hint OR surname LIKE :surname_hint)");
    $stmt->execute([
        "dob_after" => $dob_after,
        "dob_before" => $dob_before,
        "name_hint" => "%{$name_hint}%",
        "surname_hint" => "%{$name_hint}%"
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPersonalRecords($athlete_id){
    require "connect.php";
    $stmt = $conn->prepare("SELECT boat, distance, time FROM personal_records_view WHERE athlete_id = :athlete_id");
    $stmt->execute(["athlete_id" => $athlete_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAthlete($athlete_id){
    $athlete = [];
    require "connect.php";

    $stmt = $conn->prepare("SELECT * FROM athletes WHERE athlete_id = :athlete_id");
    $stmt->execute(["athlete_id" => $athlete_id]);
    $athlete_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $athlete["id"] = $athlete_id;
    $athlete["name"] = $athlete_data["name"];
    $athlete["surname"] = $athlete_data["surname"];
    $athlete["birth_date"] = $athlete_data["birth_date"];

    $stmt = $conn->prepare("CALL get_athlete_current_team(:athlete_id)");
    $stmt->execute(["athlete_id" => $athlete_id]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    $athlete["team"] = [
        "id" => $team["team_id"],
        "name" => $team["team_name"],
        "logo" => $team["logo"],
    ];

    $athlete["personal_records"] = getPersonalRecords($athlete_id);

    // TODO : add progession over time

    return $athlete;
}

function getTeams($hint){
    require "connect.php";
    $stmt = $conn->prepare("SELECT * FROM teams WHERE name LIKE :hint");
    $stmt->execute(["hint" => "%" . $hint . "%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTeam($team_id){
    require "connect.php";
    $stmt = $conn->prepare("SELECT * FROM teams WHERE team_id = :id");
    $stmt->execute(["id" => $team_id]);
    $team = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $conn->prepare("SELECT * FROM titles_view WHERE team_id = :id GROUP BY performance_id");
    $stmt->execute(["id" => $team_id]);
    $team_titles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($team_titles as $title){
        $team["titles"][] = [
            "time" => $title["time_ms"],
            "date" => $title["start_time"],
            "distance" => $title["distance"],
            "category" => $title["category"],
            "division" => $title["division"],
            "boat" => $title["boat"],
            "location" => $title["location"]
        ];
    }

    return $team;
}
