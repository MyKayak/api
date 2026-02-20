USE mykayak;

DROP TABLE IF EXISTS followed_teams;
DROP TABLE IF EXISTS followed_athletes;
DROP TABLE IF EXISTS tokens;
DROP TABLE IF EXISTS points;
DROP TABLE IF EXISTS outcomes;
DROP TABLE IF EXISTS performances_athletes;
DROP TABLE IF EXISTS performances;
DROP TABLE IF EXISTS heats;
DROP TABLE IF EXISTS races;
DROP TABLE IF EXISTS meets;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS athletes;
DROP TABLE IF EXISTS teams;

CREATE TABLE teams (
    team_id CHAR(5) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    logo TEXT
);

CREATE TABLE athletes (
    athlete_id INT,
    name VARCHAR(255) NOT NULL,
    surname VARCHAR(255) NOT NULL,
    birth_date DATE,
    PRIMARY KEY (athlete_id, name, surname)
);

CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE meets (
    meet_id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    date DATE,
    is_championship BOOLEAN DEFAULT FALSE
);

CREATE TABLE races (
    race_id VARCHAR(255) NOT NULL,
    meet_id VARCHAR(255) NOT NULL,
    distance INT NOT NULL,
    division CHAR(3) NOT NULL,
    category CHAR(1) NOT NULL,
    boat CHAR(2) NOT NULL,
    level CHAR(2) NOT NULL,
    FOREIGN KEY (meet_id) REFERENCES meets(meet_id) ON DELETE CASCADE,
    PRIMARY KEY (race_id, meet_id)
);

CREATE TABLE heats (
    heat_id INT NOT NULL,
    race_id VARCHAR(255) NOT NULL,
    meet_id VARCHAR(255) NOT NULL,
    heat_index INT NOT NULL,
    start_time DATETIME,
    FOREIGN KEY (race_id) REFERENCES races(race_id) ON DELETE CASCADE,
    FOREIGN KEY (meet_id) REFERENCES races(meet_id) ON DELETE CASCADE,
    PRIMARY KEY (heat_id, race_id, meet_id)
);

CREATE TABLE performances (
    performance_id INT PRIMARY KEY AUTO_INCREMENT,
    heat_id INT NOT NULL,
    team_id INT,
    lane INT,
    placement INT,
    time INT,
    status VARCHAR(4) DEFAULT 'OK',
    FOREIGN KEY (heat_id) REFERENCES heats(heat_id) ON DELETE CASCADE
);

CREATE TABLE performances_athletes (
    performance_id INT NOT NULL,
    athlete_id INT NOT NULL,
    PRIMARY KEY (performance_id, athlete_id),
    FOREIGN KEY (performance_id) REFERENCES performances(performance_id) ON DELETE CASCADE,
    FOREIGN KEY (athlete_id) REFERENCES athletes(athlete_id) ON DELETE CASCADE
);

CREATE TABLE outcomes (
    performance_id INT PRIMARY KEY,
    outcome_code VARCHAR(4) NOT NULL,
    FOREIGN KEY (performance_id) REFERENCES performances(performance_id) ON DELETE CASCADE
);

CREATE TABLE points (
    performance_id INT PRIMARY KEY,
    points INT NOT NULL,
    FOREIGN KEY (performance_id) REFERENCES performances(performance_id) ON DELETE CASCADE
);

CREATE TABLE tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token CHAR(64) NOT NULL,
    expiration_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE followed_athletes (
    user_id INT NOT NULL,
    athlete_id INT NOT NULL,
    PRIMARY KEY (user_id, athlete_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (athlete_id) REFERENCES athletes(athlete_id) ON DELETE CASCADE
);

CREATE TABLE followed_teams (
    user_id INT NOT NULL,
    team_id CHAR(5) NOT NULL,
    PRIMARY KEY (user_id, team_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE
);

