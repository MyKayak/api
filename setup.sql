USE mykayak;

DROP TABLE IF EXISTS api_keys;
DROP TABLE IF EXISTS admin_api_keys;

DROP TABLE IF EXISTS followed_teams;
DROP TABLE IF EXISTS followed_athletes;
DROP TABLE IF EXISTS tokens;

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
    athlete_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    surname VARCHAR(255) NOT NULL,
    birth_date DATE,
    UNIQUE KEY uq_athlete (name, surname, birth_date)
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
    race_id INT AUTO_INCREMENT PRIMARY KEY,
    race_code VARCHAR(255) NOT NULL,
    meet_id VARCHAR(255) NOT NULL,
    distance INT NOT NULL,
    division CHAR(3) NOT NULL,
    category CHAR(1) NOT NULL,
    boat CHAR(2) NOT NULL,
    level CHAR(2) NOT NULL,
    FOREIGN KEY (meet_id) REFERENCES meets(meet_id) ON DELETE CASCADE,
    UNIQUE KEY (race_code, meet_id)
);

CREATE TABLE heats (
    heat_id INT AUTO_INCREMENT,
    heat_index INT NOT NULL,
    race_id INT NOT NULL,
    start_time DATETIME,
    FOREIGN KEY (race_id) REFERENCES races(race_id) ON DELETE CASCADE,
    PRIMARY KEY (heat_id),
    UNIQUE KEY uq_heat (heat_index, race_id)
);

CREATE TABLE performances (
    performance_id INT PRIMARY KEY AUTO_INCREMENT,
    heat_id INT NOT NULL,
    team_id CHAR(5),
    lane INT,
    placement INT,
    time_ms INT NULL,
    status VARCHAR(3) NULL,
    points INT DEFAULT 0,
    FOREIGN KEY (heat_id) REFERENCES heats(heat_id) ON DELETE CASCADE,
    CONSTRAINT chk_time_or_status CHECK (
        (time_ms IS NULL AND status IS NOT NULL)
            OR
        (time_ms IS NOT NULL AND status IS NULL)
    )
);

CREATE TABLE outcomes (
    performance_id INT,
    outcome VARCHAR(255),
    FOREIGN KEY (performance_id) REFERENCES performances(performance_id) ON DELETE CASCADE,
    UNIQUE KEY (performance_id, outcome)
);

CREATE TABLE performances_athletes (
    performance_id INT NOT NULL,
    athlete_id INT NOT NULL,
    PRIMARY KEY (performance_id, athlete_id),
    FOREIGN KEY (performance_id) REFERENCES performances(performance_id) ON DELETE CASCADE,
    FOREIGN KEY (athlete_id) REFERENCES athletes(athlete_id) ON DELETE CASCADE
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

CREATE TABLE api_keys (
    api_key CHAR(64) PRIMARY KEY,
    description VARCHAR(255),
    is_active BOOL DEFAULT TRUE,
    uses INT DEFAULT 0
);

CREATE TABLE admin_api_keys (
    api_key CHAR(64) PRIMARY KEY,
    description VARCHAR(255),
    is_active BOOL DEFAULT TRUE,
    uses INT DEFAULT 0
);

