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

function getMedalTable($meet_id, $after, $before, $championships){
    require "connect.php";
    $meet_id = str_replace("%20"," ", $meet_id);
    $conditions = [];
    $params = [];
    
    if(!empty($meet_id)){
        $conditions[] = "meet_id LIKE :meet_id";
        $params[":meet_id"] = "%$meet_id%";
    }
    if(!empty($after)){
        $conditions[] = "date > :after";
        $params[":after"] = $after;
    }
    if(!empty($before)){
        $conditions[] = "date < :before";
        $params[":before"] = $before;
    }
    if($championships){
        $conditions[] = "is_championship = true";
    }
    
    $where = empty($conditions) ? "" : "WHERE " . implode(" AND ", $conditions);
    
    // If filtering by meet_id only, return per-meet standings
    // Otherwise aggregate across all matching meets by team
    if(!empty($meet_id) && empty($after) && empty($before) && !$championships){
        $query = "SELECT meet_id, team_id, team_name, gold, silver, bronze, total_medals FROM medal_table_view $where ORDER BY gold DESC, silver DESC, bronze DESC";
    } else {
        $query = "SELECT 
            team_id, 
            team_name, 
            SUM(gold) AS gold, 
            SUM(silver) AS silver, 
            SUM(bronze) AS bronze, 
            SUM(total_medals) AS total_medals 
            FROM medal_table_view $where 
            GROUP BY team_id, team_name 
            ORDER BY gold DESC, silver DESC, bronze DESC";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAthleteRankings($category, $division, $distance, $after){
    require "connect.php";
    $conditions = [];
    $params = [];
    
    if(!empty($category)){
        $conditions[] = "category = :category";
        $params[":category"] = $category;
    }
    if(!empty($division)){
        $conditions[] = "division = :division";
        $params[":division"] = $division;
    }
    if(!empty($distance)){
        $conditions[] = "distance = :distance";
        $params[":distance"] = $distance;
    }
    if(!empty($after)){
        $conditions[] = "date >= :after";
        $params[":after"] = $after;
    }
    
    $where = empty($conditions) ? "" : "WHERE " . implode(" AND ", $conditions);

    $query = "SELECT 
        athlete_id,
        name,
        surname,
        birth_date,
        distance,
        category,
        division,
        time_ms
        FROM athlete_rankings_view ar
        $where
        ORDER BY athlete_id, distance, category, division, time_ms ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $athletes = [];
    foreach($results as $row){
        $key = $row["athlete_id"] . "_" . $row["distance"] . "_" . $row["category"] . "_" . $row["division"];
        if(!isset($athletes[$key])){
            $athletes[$key] = [
                "athlete_id" => $row["athlete_id"],
                "name" => $row["name"],
                "surname" => $row["surname"],
                "birth_date" => $row["birth_date"],
                "distance" => $row["distance"],
                "category" => $row["category"],
                "division" => $row["division"],
                "best_time" => $row["time_ms"],
                "times" => []
            ];
        }
        $athletes[$key]["times"][] = $row["time_ms"];
    }

    $rankings = [];
    foreach($athletes as $athlete){
        if(count($athlete["times"]) >= 3){
            $best3 = array_slice($athlete["times"], 0, 3);
            $avg = array_sum($best3) / count($best3);
            $rankings[] = [
                "athlete_id" => $athlete["athlete_id"],
                "name" => $athlete["name"],
                "surname" => $athlete["surname"],
                "birth_date" => $athlete["birth_date"],
                "distance" => $athlete["distance"],
                "category" => $athlete["category"],
                "division" => $athlete["division"],
                "best_time" => $athlete["best_time"],
                "avg_best_3" => $avg
            ];
        }
    }

    usort($rankings, function($a, $b){
        return $a["avg_best_3"] <=> $b["avg_best_3"];
    });
    
    return $rankings;
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
    $stmt = $conn->prepare("SELECT boat, distance, time, category FROM personal_records_view WHERE athlete_id = :athlete_id");
    $stmt->execute(["athlete_id" => $athlete_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAthleteTimeProgression($athlete_id){
    require "connect.php";
    $stmt = $conn->prepare("SELECT distance, boat, category, time_ms, date FROM athlete_time_progression_view WHERE athlete_id = :athlete_id");
    $stmt->execute(["athlete_id" => $athlete_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $progression = [];
    foreach($results as $row){
        $key = $row["distance"] . "_" . $row["boat"] . "_" . $row["category"];
        if(!isset($progression[$key])){
            $progression[$key] = [];
        }
        $progression[$key][] = [
            "time_ms" => (int)$row["time_ms"],
            "date" => $row["date"]
        ];
    }

    return $progression;
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
    $athlete["time_progression"] = getAthleteTimeProgression($athlete_id);

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
    $team = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
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
