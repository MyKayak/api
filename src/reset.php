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
    team_id INT PRIMARY KEY AUTO_INCREMENT,
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
    meet_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    date DATE,
    is_championship BOOLEAN DEFAULT FALSE
)");

$conn->query("CREATE TABLE races (
    race_id INT PRIMARY KEY AUTO_INCREMENT,
    meet_id INT NOT NULL,
    distance INT NOT NULL,
    division VARCHAR(3) NOT NULL,
    category CHAR(1) NOT NULL,
    FOREIGN KEY (meet_id) REFERENCES meets(meet_id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE heats (
    heat_id INT PRIMARY KEY AUTO_INCREMENT,
    race_id INT NOT NULL,
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
    team_id INT NOT NULL,
    PRIMARY KEY (user_id, team_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE
)");
