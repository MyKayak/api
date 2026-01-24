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
- followedAthletes
- followedTeams
### Meet
A meet is an event that takes place at a specific location on a specific day.
#### Attributes:
- MeetID
- Name
- Location
- Date
- IsChampionship
### Race
A race is a a collection of heats that share the same distance, division and category.
#### Attributes:
- RaceID
- MeetID
- Distance
- Division
- Category
### Heat
A heat is a single instance of a race.
#### Attributes:
- HeatID
- RaceID
- DateTime
### Performance
A performance is a single instance of an athlete within a heat.
#### Attributes:
- PerformanceID
- HeatID
- Lane
- Placement
- AthleteID
- Time
### Athlete
An athlete is a person who participates in a race.
#### Attributes:
- AthleteID
- Name
- Category
- DOB
- TeamID
### Team
A team is a group of athletes.
#### Attributes:
- TeamID
- Name
- Icon
### User
A user is a person registered in the MyKayak system.
#### Attributes:
- UserID
- Name
- Email
- Password
### FollowedAthlete
A followed athlete is a relationship between a user and an athlete that is used to show notifications.
#### Attributes:
- FollowedAthleteID
- UserID
- AthleteID
### FollowedTeam
A followed team is a relationship between a user and a team that is used to show notifications.
#### Attributes:
- FollowedTeamID
- UserID
- TeamID