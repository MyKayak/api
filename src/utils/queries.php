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
            "distance" => $race["distance"],
            "division" => $race["division"],
            "category" => $race["category"],
            "boat" => $race["boat"],
            "level" => $race["level"],
        ];
    }
    return json_encode($races);
}