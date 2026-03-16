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

function getHeats($meet_id, $race_id){
    require "connect.php";
    $heats = [];
    $stmt = $conn->prepare("SELECT * FROM heats WHERE meet_id = :meet_id AND race_id = :race_id");
    $meet_id = str_replace("%20"," ", $meet_id);
    $stmt->bindParam(":meet_id", $meet_id);
    $stmt->bindParam(":race_id", $race_id);
    $stmt->execute();
    foreach($stmt->fetchAll() as $heat){
        $heats[] = [
            "id" => $heat["heat_id"],
            "index" => $heat["heat_index"],
            "start_time" => $heat["start_time"],
        ];
    }
    return json_encode($heats);
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
            "points" => $performance["points"]
        ];
    }
    return json_encode($performances);
}

function getMedalTable($meet_id){
    require "connect.php";
    $stmt = $conn->prepare("SELECT * FROM meets WHERE meets.meet_id = :meet_id INNER JOIN races ON meets.meet_id = ");
}
/*
SELECT * FROM meets
INNER JOIN races ON meets.meet_id = races.meet_id
INNER JOIN heats ON races.meet_id = heats.meet_id AND races.race_id = heats.race_id
INNER JOIN performances USING (heat_id)
WHERE meets.meet_id = "CanoaCastelGandolfoRM04082024_92"
AND (level = "SR"
    OR level = "DF"
    OR level = "FA");*/
