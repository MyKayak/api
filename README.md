# MyKayak API
## Database
The MyKayak API uses a MySQL database to store data.
#### Tables:
- meets
- races
- heats
- performances
- athletes
- performances_athletes
- teams
- users
- followed_athletes
- followed_teams
- tokens
- outcomes
- points
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
    - Type: INT (200, 500, 1000, 2000, 5000 ...)
- division
    - Type: VARCHAR(3) (SEN, U23, JUN, RAG, CDB, CDA, ALB, ALA, DRA, DRB)
- category
    - Type: CHAR(1) (M, F, X)
- boat
    - Type: CHAR(2) (C1, C2, C4, K1, K2, K4)
- level
    - Type: CHAR(2) (HT, SF, FA, FB, FC)

### heats
A heat is a single instance of a race.
#### Attributes:
- heat_id
    - Type: INT
- race_id
    - Type: INT
- heat_index
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
- athlete_team_id
    - Type: INT
- lane
    - Type: INT
- placement
    - Type: INT
- time
    - Type: INT (milliseconds)
- status
    - Type: VARCHAR(4) (OK, DNS, DNF, DSQ ...)
### athletes
A athlete is a person that participates in a race.
#### Attributes:
- athlete_id
    - Type: INT
- name
    - Type: VARCHAR(255)
- surname
    - Type: VARCHAR(255)
- birth_date
    - Type: DATE
### teams
A team is a group of athletes that typically race in the same crew and train together.
#### Attributes:
- team_id
    - Type: INT
- name
    - Type: VARCHAR(255)
- logo
    - Type: TEXT (url to the team logo)
### users
A user is a person registered in the MyKayak system.
#### Attributes:
- user_id
    - Type: INT
- username
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
### tokens
A token is used by the client for authentication.
#### Attributes:
- token_id
    - Type: INT
- user_id
    - Type: INT
- token
    - Type: CHAR(64)
- expiration_date
    - Type: DATE
### performances_athletes
This table is used because a given performance may involve either a single athlete (K1/C1) or a crew (K2/C2/K4/C4)
- performance_id
    - Type: INT
- athlete_id
    - Type: INT
### outcomes
An outcome is a status that can be assigned to a non-final performance. It indicates whether an athlete will advance to the next round or not.
#### Attributes:
- performance_id
    - Type: INT
- outcome_code
    - Type: VARCHAR(4)
### points
A point is a score that is assigned to a performance.
#### Attributes:
- performance_id
    - Type: INT
- points
    - Type: INT

## Known issues (should fix)
- All finals are stored the same, this means people are awarded medals in B finals too even though they're only supposed to get them for A finals.