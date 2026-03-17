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
    return json_encode($meets);
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
    return json_encode($races);
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
    $stmt = $conn->prepare("SELECT * FROM performances WHERE heat_id = :heat_id");
    $stmt->bindParam(":heat_id", $heat_id);
    $stmt->execute();
    foreach($stmt->fetchAll() as $performance){
        $performances[] = [
            "id" => $performance["performance_id"],
            "team_id" => $performance["team_id"],
            "lane" => $performance["lane"],
            "placement" => $performance["placement"],
            "time_ms" => $performance["time_ms"],
            "status" => $performance["status"],
            "points" => $performance["points"],
            "athletes" => getAthletes($performance["performance_id"]),
        ];
    }
    return $performances;
}

function getAthletes($performance_id){
    require "connect.php";
    $athletes = [];

    $stmt = $conn->prepare("SELECT name, surname, birth_date FROM athletes INNER JOIN performances_athletes USING (athlete_id) INNER JOIN performances USING (performance_id) WHERE performance_id = :performance_id");
    $stmt->bindParam(":performance_id", $performance_id);
    $stmt->execute();
    foreach($stmt->fetchAll() as $athlete){
        $athletes[] = [
            "name" => $athlete["name"],
            "surname" => $athlete["surname"],
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
    return json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
