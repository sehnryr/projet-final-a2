## Setup database and fill with sample data

First, make sure the database `matchmaking` is created.
```sql
CREATE DATABASE "matchmaking";
```

Add the `model.sql` to that database. This will add the structure of the tables.
```sh
psql -U postgres -d matchmaking -a -f sql/model.sql
```

Next, we need to add the sample data.
```sh
python3 sql/populate.py --sql-only
psql -U postgres -d matchmaking -a -f sql/data.sql
```

You might not have the python dependencies.
```
pip3 install unidecode faker
```


## API

*NOTE: Are noted between brackets [] parameters that are optional.*

| Method | Endpoint | Parameters | Headers | Description |
| :---: | :---: | :---: | :---: | :---: |
| ![POST][POST] | /login | `email`, `password` | [Authorization: Bearer <access_token>] | Login the user with their email and password, or skip if Authorization header is set and valid. |
| ![POST][POST] | /logout | [`email`, `password`] | Authorization: Bearer <access_token> | Logout the user via the Authorization header, or email and password if set and if the Authorization header is invalid. |
| ![POST][POST] | /register | `first_name`, `last_name`, `email`, `password`, `postal_code`, `birthdate`, [`phone_number`] || Register a new user. |
| ![DELETE][DELETE] | /delete | [`email`, `password`] | [Authorization: Bearer <access_token>] | Delete user with either the email and password or Authorization header. |
| ![GET][GET] | /user | `user_id` | [Authorization: Bearer <access_token>] | Get the user infos whose id is `user_id` and optionally get personal infos if Authorization header is set and valid. |
| ![GET][GET] | /cities ||| Get the list of the cities stored in the database (by default all the French cities). |
| ![GET][GET] | /sports ||| Get the list of the sports stored in the database. |
| ![GET][GET] | /user_level | `user_id`, `sport_id` | Authorization: Bearer <access_token> | Get the user' level whose id is `user_id` if Authorization header is valid. |
| ![PUT][PUT] | /user_level | `sport_id` | Authorization: Bearer <access_token> | Edit one' level in one sport which id is `sport_id` if Authorization header is valid. |
| ![GET][GET] | /match | `match_id` | [Authorization: Bearer <access_token>] | Get the match whose id is `match_id` and further infos if Authorization header is set and valid. |
| ![POST][POST] | /match | `sport_id`, `latitude`, `longitude`, `max_players`, `min_players`, `duration`, `datetime`, `description`, `recommended_level`, [`price`] | Authorization: Bearer <access_token> | Create a match with the specified parameters if Authorization header is set. |
| ![PUT][PUT] | /match | `match_id`, [`sport_id`, `latitude`, `longitude`, `max_players`, `min_players`, `duration`, `datetime`, `description`, `recommended_level`, `price`] | Authorization: Bearer <access_token> | Edit a match with the specified parameters if Authorization header matches the organizer of the match. |
| ![DELETE][DELETE] | /match | `match_id` | Authorization: Bearer <access_token> | Delete a match if Authorization header matches the organizer of the match. |
| ![GET][GET] | /participations | [`match_id`, `user_id`] | Authorization: Bearer <access_token> | Get the participations of a match if the current user is the organizer or get the participations of the user if authenticated. |
| ![POST][POST] | /participate | `match_id` | Authorization: Bearer <access_token> | Place a participation to a match if available for the current authenticated user. |
| ![DELETE][DELETE] | /participate | `match_id` | Authorization: Bearer <access_token> | Resign to participate to a match for the current authenticated user. |
| ![PUT][PUT] | /validate | `participation_id`, `value` | Authorization: Bearer <access_token> | Validate or invalidate a participation as the organizer of the match associated to the participation. |
| ![PUT][PUT] | /score | `participation_id`, `value` | Authorization: Bearer <access_token> | Change the score of a user as the organizer. |
| ![GET][GET] | /teams | `match_id` || Get the teams associated to a match. |
| ![POST][POST] | /team | `match_id`, [`name`] | Authorization: Bearer <access_token> | Create a team for a match. |
| ![PUT][PUT] | /team | `team_id`, `participation_id` | Authorization: Bearer <access_token> | Add user to a team as organizer if the participation and valid. |
| ![DELETE][DELETE] | /team | `team_id` | Authorization: Bearer <access_token> | Delete a team and reset all participations `team_id`. |
| ![PUT][PUT] | /rename_team | `team_id` | Authorization: Bearer <access_token> | Rename a team with an unused name as organizer. |
| ![POST][POST] | /note | `score`, `comment` | Authorization: Bearer <access_token> | Create a review of the app and replace the old one if exists. |
| ![PUT][PUT] | /note | [`score`, `comment`] | Authorization: Bearer <access_token> | Edit a review for the current user if it exists. |
| ![POST][POST] | /notification | `message`, [`url`] | Authorization: Bearer <access_token> | Send a notification to a user. Is only applicable if the sender is an organizer and the user participates to one of his matches. |


[GET]: https://img.shields.io/badge/GET-brightgreen?style=flat
[POST]: https://img.shields.io/badge/POST-orange?style=flat
[PUT]: https://img.shields.io/badge/PUT-blue?style=flat
[DELETE]: https://img.shields.io/badge/DELETE-red?style=flat