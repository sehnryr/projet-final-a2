## API

*NOTE: Are noted between brackets [] parameters that are optional.*

| Method | Endpoint | Parameters | Headers | Description |
| :---: | :---: | :---: | :---: | :---: |
| ![POST][POST] | /login | `email`, `password` | [Authorization: Bearer <access_token>] | Login the user with their email and password, or skip if Authorization header is set and valid. |
| ![POST][POST] | /logout | [`email`, `password`] | Authorization: Bearer <access_token> | Logout the user via the Authorization header, or email and password if set and if the Authorization header is invalid. |
| ![POST][POST] | /register | `first_name`, `last_name`, `email`, `phone_number`, `password`, `city_id`, |birthdate | Register a new user. |
| ![DELETE][DELETE] | /delete | [`email`, `password`] | [Authorization: Bearer <access_token>] | Delete user with either the email and password or Authorization header. |
| ![GET][GET] | /user | `user_id` | [Authorization: Bearer <access_token>] | Get the user infos whose id is `user_id` and optionally get personal infos if Authorization header is set and valid. |
| ![GET][GET] | /cities ||| Get the list of the cities stored in the database (by default all the French cities). |
| ![GET][GET] | /sports ||| Get the list of the sports stored in the database. |
| ![GET][GET] | /user_level | `user_id`, `sport_id` | Authorization: Bearer <access_token> | Get the user' level whose id is `user_id` if Authorization header is valid. |
| ![PUT][PUT] | /user_level | `sport_id` | Authorization: Bearer <access_token> | Edit one' level in one sport which id is `sport_id` if Authorization header is valid. |
| ![GET][GET] | /match | `match_id` | [Authorization: Bearer <access_token>] | Get the match whose id is `match_id` and further infos if Authorization header is set and valid. |
| ![POST][POST] | /match | `sport_id`, `latitude`, `longitude`, `max_players`, `min_players`, `duration`, `datetime`, `description`, `recommended_level`, [`price`] | Authorization: Bearer <access_token> | Create a match with the specified parameters if Authorization header is set. |
| ![PUT][PUT] | /match | [`sport_id`, `latitude`, `longitude`, `max_players`, `min_players`, `duration`, `datetime`, `description`, `recommended_level`, `price`] | Authorization: Bearer <access_token> | Edit a match with the specified parameters if Authorization header matches the organizer of the match. |

[GET]: https://img.shields.io/badge/GET-brightgreen?style=flat
[POST]: https://img.shields.io/badge/POST-orange?style=flat
[PUT]: https://img.shields.io/badge/PUT-blue?style=flat
[DELETE]: https://img.shields.io/badge/DELETE-red?style=flat