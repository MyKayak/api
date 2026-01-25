<?php

require_once 'connect.php';

$conn->query("USE mykayak");

$conn->query("DROP TABLE IF EXISTS followed_teams");
$conn->query("DROP TABLE IF EXISTS followed_athletes");
$conn->query("DROP TABLE IF EXISTS tokens");
$conn->query("DROP TABLE IF EXISTS points");
$conn->query("DROP TABLE IF EXISTS outcomes");
$conn->query("DROP TABLE IF EXISTS performances_athletes");
$conn->query("DROP TABLE IF EXISTS performances");
$conn->query("DROP TABLE IF EXISTS heats");
$conn->query("DROP TABLE IF EXISTS races");
$conn->query("DROP TABLE IF EXISTS meets");
$conn->query("DROP TABLE IF EXISTS users");
$conn->query("DROP TABLE IF EXISTS athletes");
$conn->query("DROP TABLE IF EXISTS teams");

$conn->query("CREATE TABLE teams (
    team_id CHAR(5) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    logo TEXT
)");

$conn->query("CREATE TABLE athletes (
    athlete_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    surname VARCHAR(255) NOT NULL,
    birth_date DATE
)");

$conn->query("CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)");

$conn->query("CREATE TABLE meets (
    meet_id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    date DATE,
    is_championship BOOLEAN DEFAULT FALSE
)");

$conn->query("CREATE TABLE races (
    race_id VARCHAR(255) PRIMARY KEY,
    meet_id VARCHAR(255) NOT NULL,
    distance INT NOT NULL,
    division CHAR(3) NOT NULL,
    category CHAR(1) NOT NULL,
    boat CHAR(2) NOT NULL,
    level CHAR(2) NOT NULL,
    FOREIGN KEY (meet_id) REFERENCES meets(meet_id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE heats (
    heat_id INT PRIMARY KEY AUTO_INCREMENT,
    race_id VARCHAR(255) NOT NULL,
    heat_index INT NOT NULL,
    start_time DATETIME,
    FOREIGN KEY (race_id) REFERENCES races(race_id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE performances (
    performance_id INT PRIMARY KEY AUTO_INCREMENT,
    heat_id INT NOT NULL,
    athlete_team_id INT,
    lane INT,
    placement INT,
    time INT,
    status VARCHAR(4) DEFAULT 'OK',
    FOREIGN KEY (heat_id) REFERENCES heats(heat_id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE performances_athletes (
    performance_id INT NOT NULL,
    athlete_id INT NOT NULL,
    PRIMARY KEY (performance_id, athlete_id),
    FOREIGN KEY (performance_id) REFERENCES performances(performance_id) ON DELETE CASCADE,
    FOREIGN KEY (athlete_id) REFERENCES athletes(athlete_id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE outcomes (
    performance_id INT PRIMARY KEY,
    outcome_code VARCHAR(4) NOT NULL,
    FOREIGN KEY (performance_id) REFERENCES performances(performance_id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE points (
    performance_id INT PRIMARY KEY,
    points INT NOT NULL,
    FOREIGN KEY (performance_id) REFERENCES performances(performance_id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token CHAR(64) NOT NULL,
    expiration_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE followed_athletes (
    user_id INT NOT NULL,
    athlete_id INT NOT NULL,
    PRIMARY KEY (user_id, athlete_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (athlete_id) REFERENCES athletes(athlete_id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE followed_teams (
    user_id INT NOT NULL,
    team_id CHAR(5) NOT NULL,
    PRIMARY KEY (user_id, team_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE
)");



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
                    "race_id" => "$race->c0/$race->c1/" . substr($race->c2, 1) . "/$race->c3",
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
