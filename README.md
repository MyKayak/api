# MyKayak API
## Database
The MyKayak API uses a MySQL database to store data.
#### Tables:
- meets
- races
- heats
- performances
- athletes
- teams
- users
- followed_athletes
- followed_teams
### meets
A meet is an event that takes place at a specific location on a specific day.
#### Attributes:
- meet_id
    - Type: INT
- name
    - Type: VARCHAR(255)
- location
    - Type: VARCHAR(255)
- date
    - Type: DATE
- is_championship
    - Type: BOOLEAN
### races
A race is a a collection of heats that share the same distance, division and category.
#### Attributes:
- race_id
    - Type: INT
- meet_id
    - Type: INT
- distance
    - Type: INT
- division
    - Type: VARCHAR(3)
- category
    - Type: CHAR(1)
### heats
A heat is a single instance of a race.
#### Attributes:
- heat_id
    - Type: INT
- race_id
    - Type: INT
- start_time
    - Type: DATETIME
### performances
A performance is a single instance of an athlete within a heat.
#### Attributes:
- performance_id
    - Type: INT
- heat_id
    - Type: INT
- lane
    - Type: INT
- placement
    - Type: INT
- athlete_id
    - Type: INT
- time
    - Type: INT (milliseconds)
### athletes
An athlete is a person who participates in a race.
#### Attributes:
- athlete_id
    - Type: INT
- name
    - Type: VARCHAR(255)
- birth_date
    - Type: DATE
- team_id
    - Type: INT
### teams
A team is a group of athletes.
#### Attributes:
- team_id
    - Type: INT
- name
    - Type: VARCHAR(255)
- icon
    - Type: VARCHAR(255)
### users
A user is a person registered in the MyKayak system.
#### Attributes:
- user_id
    - Type: INT
- name
    - Type: VARCHAR(255)
- email
    - Type: VARCHAR(255)
- password
    - Type: VARCHAR(255)
### followed_athletes
A followed athlete is a relationship between a user and an athlete that is used to show notifications.
#### Attributes:
- user_id
    - Type: INT
- athlete_id
    - Type: INT
### followed_teams
A followed team is a relationship between a user and a team that is used to show notifications.
#### Attributes:
- user_id
    - Type: INT
- team_id
    - Type: INT