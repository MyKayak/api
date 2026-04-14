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
    UNIQUE KEY uq_athlete (name, surname, birth_date),
    INDEX idx_athletes_birth_date (birth_date),
    INDEX idx_athletes_name (name, surname)
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
    is_championship BOOLEAN DEFAULT FALSE,
    INDEX idx_meets_date (date),
    INDEX idx_meets_championship (is_championship)
);

-- UPDATE meets SET is_championship = TRUE WHERE name LIKE '%campionat%' AND (name LIKE '%italian%' OR name LIKE '%nazional%');

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
    UNIQUE KEY uq_races_code_meet (race_code, meet_id),
    INDEX idx_races_meet_id (meet_id)
);

CREATE TABLE heats (
    heat_id INT AUTO_INCREMENT,
    heat_index INT NOT NULL,
    race_id INT NOT NULL,
    start_time DATETIME,
    FOREIGN KEY (race_id) REFERENCES races(race_id) ON DELETE CASCADE,
    PRIMARY KEY (heat_id),
    UNIQUE KEY uq_heat (heat_index, race_id),
    INDEX idx_heats_race_id (race_id)
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
    INDEX idx_performances_heat_id (heat_id),
    INDEX idx_performances_team_id (team_id),
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

CREATE OR REPLACE VIEW medal_table_view AS
SELECT
    meets.meet_id,
    meets.date,
    meets.is_championship,
    team_id,
    teams.name AS team_name,
    SUM(CASE WHEN placement = 1 THEN 1 ELSE 0 END) AS gold,
    SUM(CASE WHEN placement = 2 THEN 1 ELSE 0 END) AS silver,
    SUM(CASE WHEN placement = 3 THEN 1 ELSE 0 END) AS bronze,
    COUNT(*) AS total_medals
FROM meets
JOIN races ON meets.meet_id = races.meet_id
JOIN heats ON races.race_id = heats.race_id
JOIN performances USING (heat_id)
JOIN teams USING (team_id)
WHERE races.level IN ("SR", "DF", "FA")
  AND placement BETWEEN 1 AND 3
  AND status IS NULL
GROUP BY meets.meet_id, team_id, teams.name;

CREATE OR REPLACE VIEW athlete_rankings_view AS
SELECT
    athletes.athlete_id,
    athletes.name,
    athletes.surname,
    athletes.birth_date,
    races.distance,
    races.category,
    races.division,
    performances.time_ms,
    meets.date
FROM athletes
INNER JOIN performances_athletes USING (athlete_id)
INNER JOIN performances USING (performance_id)
INNER JOIN heats USING (heat_id)
INNER JOIN races USING (race_id)
INNER JOIN meets USING (meet_id)
WHERE races.boat IN ('K1', 'C1')
  AND performances.time_ms IS NOT NULL
  AND performances.status IS NULL
  AND performances.time_ms >= 25000
ORDER BY athletes.athlete_id, races.distance, races.category, races.division, meets.date DESC;

CREATE OR REPLACE VIEW personal_records_view AS (
    SELECT athlete_id, boat, distance, category, MIN(time_ms) AS time 
    FROM athletes
    INNER JOIN performances_athletes USING (athlete_id)
    INNER JOIN performances USING (performance_id)
    INNER JOIN heats USING (heat_id)
    INNER JOIN races USING (race_id)
    GROUP BY boat, distance, category, athlete_id
);

CREATE OR REPLACE VIEW athlete_time_progression_view AS
SELECT 
    performances_athletes.athlete_id,
    races.distance,
    races.boat,
    races.category,
    performances.time_ms,
    meets.date
FROM performances_athletes
INNER JOIN performances USING (performance_id)
INNER JOIN heats USING (heat_id)
INNER JOIN races USING (race_id)
INNER JOIN meets USING (meet_id)
WHERE performances.time_ms IS NOT NULL
ORDER BY performances_athletes.athlete_id, races.distance, races.boat, races.category, meets.date;

DELIMITER //
DROP PROCEDURE IF EXISTS get_athlete_current_team //
CREATE PROCEDURE get_athlete_current_team(IN p_athlete_id INT)
BEGIN
    SELECT teams.team_id, teams.name AS team_name, teams.logo
    FROM performances_athletes
    JOIN performances USING (performance_id)
    JOIN heats USING (heat_id)
    JOIN races USING (race_id)
    JOIN meets USING (meet_id)
    JOIN teams USING (team_id)
    WHERE performances_athletes.athlete_id = p_athlete_id
    ORDER BY meets.date DESC, races.race_id DESC
    LIMIT 1;
END //
DELIMITER ;

CREATE OR REPLACE VIEW titles_view AS
SELECT athlete_id, performance_id, team_id, time_ms, athletes.name, surname, start_time, distance, division, category, boat, location FROM performances
INNER JOIN performances_athletes USING (performance_id)
INNER JOIN athletes USING (athlete_id)
INNER JOIN heats USING (heat_id)
INNER JOIN races USING (race_id)
INNER JOIN meets USING (meet_id)
WHERE is_championship = true
AND time_ms > 0
AND placement = 1
AND (level = 'DF' OR level = 'FA')
ORDER BY start_time DESC;
