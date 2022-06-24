# Matchmaking

Connect with others with sport.

## Test accounts

Since every user ban be either a player or an organizer, there is no need to specify the status of the test account.

*Note: every user in the [dataset](sql/datasets/users.csv) has their password set to 1234.*

```
email: paulinejacquot7685@example.net
password: 1234
```
```
email: patrickgermain5953@example.com
password: 1234
```

## Connect to the VM

```
IP: 10.10.51.66
user: user1
password: BonnesVacances
```

## Languages & Frameworks

![HTML5](https://img.shields.io/badge/html5-%23E34F26.svg?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/css3-%231572B6.svg?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/javascript-%23323330.svg?style=for-the-badge&logo=javascript&logoColor=%23F7DF1E)

![jQuery](https://img.shields.io/badge/jquery-%230769AD.svg?style=for-the-badge&logo=jquery&logoColor=white)

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

*NOTE: Parameters have the priority on the authorization header.*

<details>
<summary>List of endpoints:</summary>

| Method | Endpoint | Parameters | Headers | Description |
| :---: | :---: | :---: | :---: | :---: |
| ![GET][GET] | /check_email | `email` || Check if email is either valid or does not exist in the database. |
| ![POST][POST] | /login | `email`, `password` | [Authorization: Bearer <access_token>] | Login the user with their email and password, or skip if Authorization header is set and valid. |
| ![POST][POST] | /logout | [`email`, `password`] | Authorization: Bearer <access_token> | Logout the user via the Authorization header, or email and password if set and if the Authorization header is invalid. |
| ![POST][POST] | /register | `first_name`, `last_name`, `email`, `password`, `postal_code`, `birthdate`, [`phone_number`] || Register a new user. |
| ![DELETE][DELETE] | /delete || Authorization: Bearer <access_token> | Delete user with the Authorization header. |
| ![PUT][PUT] | /profile | [`city_id`, `first_name`, `last_name`, `email`, `phone_number`, `password`, `profile_picture_url`, `birthdate`] | Authorization: Bearer <access_token> | Update ones information. |
| ![GET][GET] | /user | `user_id` | [Authorization: Bearer <access_token>] | Get the user infos whose id is `user_id` and optionally get personal infos if Authorization header is set and valid. |
| ![GET][GET] | /cities ||| Get the list of the cities stored in the database (by default all the French cities). |
| ![GET][GET] | /sport | `sport_id` || Get the infos on a sport. |
| ![GET][GET] | /sports ||| Get the list of the sports stored in the database. |
| ![GET][GET] | /user_level | `user_id`, `sport_id` || Get the user' level whose id is `user_id` if Authorization header is valid. |
| ![POST][POST] | /user_level | `sport_id`, `level`, `description` | Authorization: Bearer <access_token> | Create one' level in one sport which id is `sport_id` if Authorization header is valid. |
| ![PUT][PUT] | /user_level | `sport_id`, [`level`, `description`] | Authorization: Bearer <access_token> | Edit one' level in one sport which id is `sport_id` if Authorization header is valid. |
| ![GET][GET] | /matches | [`organizer_id`, `range`, `city_id`, `date`, `sport_id`] || Get all the matches with the ability to filter them. |
| ![GET][GET] | /match | `match_id` || Get the match whose id is `match_id`. |
| ![POST][POST] | /match | `sport_id`, `latitude`, `longitude`, `max_players`, `min_players`, `duration`, `datetime`, `description`, `recommended_level`, [`price`] | Authorization: Bearer <access_token> | Create a match with the specified parameters if Authorization header is set. |
| ![PUT][PUT] | /match | `match_id`, [`sport_id`, `latitude`, `longitude`, `max_players`, `min_players`, `duration`, `datetime`, `description`, `recommended_level`, `price`] | Authorization: Bearer <access_token> | Edit a match with the specified parameters if Authorization header matches the organizer of the match. |
| ![DELETE][DELETE] | /match | `match_id` | Authorization: Bearer <access_token> | Delete a match if Authorization header matches the organizer of the match. |
| ![GET][GET] | /participations | [`match_id`, `user_id`] | Authorization: Bearer <access_token> | Get the participations of a match if the current user is the organizer or get the participations of the user if authenticated. |
| ![GET][GET] | /participation | `participation_id` || Get the infos on a participation. |
| ![POST][POST] | /participate | `match_id` | Authorization: Bearer <access_token> | Place a participation to a match if available for the current authenticated user. |
| ![DELETE][DELETE] | /participate | `match_id` | Authorization: Bearer <access_token> | Resign to participate to a match for the current authenticated user. |
| ![PUT][PUT] | /validate | `participation_id`, `value` | Authorization: Bearer <access_token> | Validate or invalidate a participation as the organizer of the match associated to the participation. |
| ![PUT][PUT] | /score | `participation_id`, `value` | Authorization: Bearer <access_token> | Change the score of a user as the organizer. |
| ![GET][GET] | /teams | `match_id` || Get the teams associated to a match. |
| ![POST][POST] | /team | `match_id`, [`name`] | Authorization: Bearer <access_token> | Create a team for a match. |
| ![PUT][PUT] | /team | `team_id`, `participation_id` | Authorization: Bearer <access_token> | Add user to a team as organizer if the participation and valid. |
| ![DELETE][DELETE] | /team | `team_id` | Authorization: Bearer <access_token> | Delete a team and reset all participations `team_id`. |
| ![PUT][PUT] | /rename_team | `team_id`, `name` | Authorization: Bearer <access_token> | Rename a team with an unused name as organizer. |
| ![POST][POST] | /note | `score`, `comment` | Authorization: Bearer <access_token> | Create a review of the app and replace the old one if exists. |
| ![PUT][PUT] | /note | [`score`, `comment`] | Authorization: Bearer <access_token> | Edit a review for the current user if it exists. |
| ![GET][GET] | /notifications || Authorization: Bearer <access_token> | Get the notifications of the authenticated user. |
| ![POST][POST] | /notification | `user_id`, `message`, [`url`] | Authorization: Bearer <access_token> | Send a notification to a user. Is only applicable if the sender is an organizer and the user participates to one of his matches. |
| ![DELETE][DELETE] | /notification | `notification_id`| Authorization: Bearer <access_token> | Delete a notification if the user is authenticated. |


</details>

[GET]: https://img.shields.io/badge/GET-brightgreen?style=flat
[POST]: https://img.shields.io/badge/POST-orange?style=flat
[PUT]: https://img.shields.io/badge/PUT-blue?style=flat
[DELETE]: https://img.shields.io/badge/DELETE-red?style=flat
