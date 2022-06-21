<?php

/**
 * PHP version 7.4.28
 *
 * @author Paul-Adrien PENET <pauladrienpenet@gmail.com
 * @author Youn Mélois <youn@melois.dev>
 */

require_once 'config.php';
require_once 'exceptions.php';
require_once 'common.php';

class Database
{
    protected $PDO;

    /**
     * Connect to the PostgreSQL database.
     * 
     * @throws PDOException Error thrown if the connection to 
     *                      the database failed.
     */
    public function __construct()
    {
        $db_name = DB_NAME;
        $db_server = DB_SERVER;
        $db_port = DB_PORT;

        $dsn = "pgsql:dbname={$db_name};host={$db_server};port={$db_port}";

        $this->PDO = new PDO($dsn, DB_USER, DB_PASSWORD);
    }

    /**
     * Get a user id from its access_token.
     * 
     * @param string $access_token
     * 
     * @throws AuthenticationException
     */
    private function _getUserId(string $access_token): int
    {
        $user_id = $this->getUserPersonalInfos($access_token)['id'];

        return (int) $user_id;
    }

    /**
     * Gets the password hash of a user.
     * 
     * @param string $email
     * 
     * @return string The password hash.
     */
    private function _getUserPasswordHash(
        string $email
    ): ?string {
        $email = strtolower($email);

        $request = 'SELECT "password_hash" FROM "user" 
                      WHERE LOWER("email") = :email';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':email', $email);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_OBJ);

        if (!$result) {
            return NULL;
        }

        return $result->password_hash;
    }

    /** 
     * Verifies the user credentials.
     * 
     * @param string $email
     * @param string $password
     * 
     * @return bool
     */
    public function verifyUserCredentials(
        string $email,
        string $password
    ): bool {
        $password_hash = $this->_getUserPasswordHash($email);
        return !empty($password_hash) &&
            password_verify($password, $password_hash);
    }

    /**
     * Verifies the user access token.
     * 
     * @param string $access_token
     * 
     * @return bool
     */
    public function verifyUserAccessToken(string $access_token): bool
    {
        $request = 'SELECT * FROM "user"
                      WHERE "access_token" = :access_token';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':access_token', $access_token);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_OBJ);

        return !empty($result);
    }

    /**
     * Creates an access token if credentials are valid.
     * 
     * @param string $email
     * @param string $password
     * 
     * @throws AuthenticationException If the credentials are invalid.
     */
    public function getUserAccessToken(
        string $email,
        string $password
    ): ?string {
        if (!$this->verifyUserCredentials($email, $password)) {
            throw new AuthenticationException();
        }

        $email = strtolower($email);

        $access_token = hash('sha256', $email . $password . time());

        // Set access token on the user
        $request = 'UPDATE "user" SET "access_token" = :access_token
                      WHERE LOWER("email") = :email';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':email', $email);
        $statement->bindParam(':access_token', $access_token);
        $statement->execute();

        return $access_token;
    }

    /**
     * Removes the access token from the user.
     * 
     * @param string $access_token
     * 
     * @throws AuthenticationException If the credentials are invalid.
     */
    public function removeUserAccessToken(string $access_token): void
    {
        if (!$this->verifyUserAccessToken($access_token)) {
            throw new AuthenticationException();
        }

        // remove access token
        $request = 'UPDATE "user" SET "access_token" = NULL
                      WHERE "access_token" = :access_token';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':access_token', $access_token);
        $statement->execute();
    }

    /**
     * Get all the public information on a user.
     * 
     * @param int $user_id
     * 
     * @return array Array of id, first_name, last_name, profile_picture_url.
     * 
     * @throws EntryDoesNotExists If the id is outbound.
     */
    public function getUserInfos(string $user_id): array
    {
        $request = 'SELECT "id", "first_name", "last_name", "profile_picture_url"
                        FROM "user"
                        WHERE "id" = :id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':id', $user_id);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_OBJ);

        if (empty($result)) {
            throw new EntryDoesNotExists("Id " . $user_id . " does not exist.");
        }

        return (array) $result;
    }

    /**
     * Gets all the information on the user who as access_token.
     * 
     * @param string $access_token
     * 
     * @throws AuthenticationException
     */
    public function getUserPersonalInfos(string $access_token): ?array
    {
        $request = 'SELECT "id", "city_id", "first_name", "last_name", "email", "phone_number", "profile_picture_url", "birthdate" 
                        FROM "user"
                        WHERE "access_token" = :access_token';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':access_token', $access_token);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_OBJ);

        if (empty($result)) {
            throw new AuthenticationException();
        }

        return (array) $result;
    }

    /**
     * Create an user in the database and return a bool to result
     *
     * @param string $first_name first name
     * @param string $last_name last name
     * @param string $email 
     * @param string $phone_number phone number
     * @param string $password
     *
     */
    public function createUser(
        string $first_name,
        string $last_name,
        string $email,
        string $password,
        string $birthdate,
        string $postal_code,
        ?string $phone_number = NULL
    ): void {

        $email = strtolower($email);

        // test if user already exists
        $request = 'SELECT * FROM "user"
                      WHERE LOWER("email") = :email';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':email', $email);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_OBJ);

        if ($result) {
            throw new DuplicateEmailException('Email already exists.');
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);


        // return the city_id of a city
        $request = 'SELECT id FROM city
                      WHERE "postal_code" = :postal_code';
        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':postal_code', $postal_code);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_OBJ);
        $city_id = $result->id;


        $request = 'INSERT INTO "user" 
                      ("city_id", "first_name", "last_name", "email", "phone_number", "password_hash", "birthdate")
                      VALUES (:city_id, :first_name, :last_name, :email, :phone_number, :password_hash, :birthdate)';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':city_id', $city_id);
        $statement->bindParam(':first_name', $first_name);
        $statement->bindParam(':last_name', $last_name);
        $statement->bindParam(':email', $email);
        $statement->bindParam(':phone_number', $phone_number);
        $statement->bindParam(':password_hash', $password_hash);
        $statement->bindParam(':birthdate', $birthdate);
        $statement->execute();
    }

    /**
     * Delete a user.
     * 
     * @param string $access_token
     * 
     * @throws AuthenticationException
     */
    public function deleteUser(string $access_token): void
    {
        if (!$this->verifyUserAccessToken($access_token)) {
            throw new AuthenticationException();
        }

        $request = 'DELETE FROM "user"
                        WHERE "access_token" = :access_token';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':access_token', $access_token);
        $statement->execute();
    }

    /**
     * Update user information.
     * 
     * @param string $access_token
     * @param ?int $city_id
     * @param ?string $first_name
     * @param ?string $last_name
     * @param ?string $email
     * @param ?string $phone_number
     * @param ?string $password
     * @param ?string $profile_picture_url
     * @param ?DateTime $birthdate
     * 
     * @throws AuthenticationException
     * @throws DuplicateEmailException
     * @throws PatternException
     * @throws PDOException
     */
    public function updateUser(
        string $access_token,
        ?int $city_id = null,
        ?string $first_name = null,
        ?string $last_name = null,
        ?string $email = null,
        ?string $phone_number = null,
        ?string $password = null,
        ?string $profile_picture_url = null,
        ?DateTime $birthdate = null
    ): array {
        if (!$this->verifyUserAccessToken($access_token)) {
            throw new AuthenticationException();
        }

        // Get all the user info including its password hash.
        $request = 'SELECT * FROM "user"
                        WHERE "access_token" = :access_token';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':access_token', $access_token);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_OBJ);

        $data = (array) $result;

        // replace by their old values if not set.
        $access_token = $access_token ?? $data['access_token'];
        $city_id = $city_id ?? $data['city_id'];
        $first_name = $first_name ?? $data['first_name'];
        $last_name = $last_name ?? $data['last_name'];
        $email = strtolower($email ?? $data['email']);
        $phone_number = $phone_number ?? $data['phone_number'];
        $profile_picture_url = $profile_picture_url ?? $data['profile_picture_url'];
        $birthdate = $birthdate ?? $data['birthdate'];

        if (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            (isset($password) && strlen($password) < 4) ||
            strlen($first_name) < 2 ||
            strlen($last_name) < 2
        ) {
            throw new PatternException();
        }

        // test if user already exists
        $request = 'SELECT * FROM "user"
                      WHERE LOWER("email") = :email
                      AND "access_token" != :access_token';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':email', $email);
        $statement->bindParam(':access_token', $access_token);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_OBJ);

        if ($result) {
            throw new DuplicateEmailException('Email already exists.');
        }

        // create new password_hash if password is set
        $password_hash = $password
            ? password_hash($password, PASSWORD_BCRYPT)
            : $data['password_hash'];

        $request = 'UPDATE "user"
                        SET "city_id" = :city_id,
                            "first_name" = :first_name,
                            "last_name" = :last_name,
                            "email" = :email,
                            "phone_number" = :phone_number,
                            "password_hash" = :password_hash,
                            "profile_picture_url" = :profile_picture_url,
                            "birthdate" = :birthdate
                        WHERE "access_token" = :access_token';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':access_token', $access_token);
        $statement->bindParam(':city_id', $city_id);
        $statement->bindParam(':first_name', $first_name);
        $statement->bindParam(':last_name', $last_name);
        $statement->bindParam(':email', $email);
        $statement->bindParam(':phone_number', $phone_number);
        $statement->bindParam(':password_hash', $password_hash);
        $statement->bindParam(':profile_picture_url', $profile_picture_url);
        $statement->bindParam(':birthdate', $birthdate);
        $statement->execute();

        return $this->getUserPersonalInfos($access_token);
    }

    /**
     * Get a list of the cities stored in the database.
     */
    public function getCities(): array
    {
        $request = 'SELECT * FROM "city"';

        $statement = $this->PDO->prepare($request);
        $statement->execute();

        $result = $statement->fetchAll(PDO::FETCH_OBJ);

        return (array) $result;
    }

    /**
     * Get a sport
     * 
     * @param int $sport_id
     */
    public function getSport(int $sport_id): ?array
    {
        $request = 'SELECT * FROM "sport"
                        WHERE "id" = :id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':id', $sport_id);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_OBJ);

        return empty($array) ? (array) $result : null;
    }

    /**
     * Get a list of the sports in the database.
     */
    public function getSports(): array
    {
        $request = 'SELECT * FROM "sport"';

        $statement = $this->PDO->prepare($request);
        $statement->execute();

        $result = $statement->fetchAll(PDO::FETCH_OBJ);

        return (array) $result;
    }

    /**
     * Get the user_level of a user in a specific sport.
     * 
     * @param int $user_id
     * @param int $sport_id
     */
    public function getUserLevel(
        int $user_id,
        int $sport_id
    ): array {
        $request = 'SELECT * FROM "user_level"
                        WHERE "user_id" = :user_id
                        AND "sport_id" = :sport_id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':user_id', $user_id);
        $statement->bindParam(':sport_id', $sport_id);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_OBJ);

        return (array) $result;
    }

    /**
     * Set the user_level of a user in a specific sport according to the
     * access token is valid.
     * 
     * @param string $access_token
     * @param int $sport_id
     * @param int $level Value between 0 and 5
     * @param string $description
     * 
     * @throws AuthenticationException
     * @throws PatternException
     */
    public function setUserLevel(
        string $access_token,
        int $sport_id,
        int $level,
        string $description
    ): void {
        if (!$this->verifyUserAccessToken($access_token)) {
            throw new AuthenticationException();
        }

        if ($level < 0 || $level > 5) {
            throw new PatternException();
        }

        $user_id = $this->getUserPersonalInfos($access_token)['id'];

        // Delete previous record if exists
        $request = 'DELETE FROM "user_level"
                        WHERE "user_id" = :user_id
                        AND "sport_id" = :sport_id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':user_id', $user_id);
        $statement->bindParam(':sport_id', $sport_id);
        $statement->execute();

        // Insert
        $request = 'INSERT INTO "user_level"
                        ("user_id", "sport_id", "level", "description")
                        VALUES (:user_id, :sport_id, :level, :description)';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':user_id', $user_id);
        $statement->bindParam(':sport_id', $sport_id);
        $statement->bindParam(':level', $level);
        $statement->bindParam(':description', $description);
        $statement->execute();
    }

    /**
     * Get the participations of a match.
     * 
     * @param int $match_id
     */
    public function getMatchParticipations(int $match_id): array
    {
        $request = 'SELECT * FROM "participation"
                        WHERE p."match_id" = :match_id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':match_id', $match_id);
        $statement->execute();

        $result = $statement->fetchAll(PDO::FETCH_OBJ);

        return (array)$result;
    }

    /**
     * Get a user participations.
     * 
     * @param string $access_token
     * 
     * @throws AuthenticationException
     */
    public function getUserParticipations(string $access_token): array
    {
        if (!$this->verifyUserAccessToken($access_token)) {
            throw new AuthenticationException();
        }

        $user_id = $this->_getUserId($access_token);

        $request = 'SELECT * FROM "participation"
                        WHERE "user_id" = :user_id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':user_id', $user_id);
        $statement->execute();

        $result = $statement->fetchAll(PDO::FETCH_OBJ);

        return (array)$result;
    }

    /**
     * Get the participations of a match if the current user is the organizer or
     * get the participations of the user if authenticated.
     * 
     * @param string $access_token
     * @param ?int $match_id
     * 
     * @throws AuthenticationException
     */
    public function getParticipations(
        string $access_token,
        int $match_id = null
    ): array {
        if (!$this->verifyUserAccessToken($access_token)) {
            throw new AuthenticationException();
        }

        if ($match_id != null) {
            $request = 'SELECT p."id", p."user_id", p."match_id", p."team_id", p."validation", p."score"
                            FROM "participation" p 
                            LEFT JOIN "match" m ON p."match_id" = m."id"
                            LEFT JOIN "user" u ON m."organizer_id" = u."id"
                            WHERE m."match_id" = :match_id
                            AND u."access_token" = :access_token';
        } else {
            $request = 'SELECT p."id", p."user_id", p."match_id", p."team_id", p."validation", p."score"
                            FROM "participation" p 
                            LEFT JOIN "user" u ON p."user_id" = u."id"
                            WHERE u."access_token" = :access_token';
        }

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':access_token', $access_token);
        $statement->bindParam(':match_id', $match_id);
        $statement->execute();

        $result = $statement->fetchAll(PDO::FETCH_OBJ);

        if (empty($result)) {
            throw new AuthenticationException();
        }

        return (array) $result;
    }

    /**
     * Get a match info.
     * 
     * @param int $match_id
     */
    public function getMatch(int $match_id): array
    {
        $request = 'SELECT * FROM "match"
                        WHERE "id" = :match_id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':match_id', $match_id);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_OBJ);

        $data = (array)$result;

        $request = 'SELECT * FROM "participation"
                        WHERE "match_id" = :match_id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':match_id', $match_id);
        $statement->execute();

        $result = $statement->fetchAll(PDO::FETCH_OBJ);

        $data['participation'] = (array) $result;

        return (array) $data;
    }

    /**
     * Create a match with the specified parameters if Authorization header 
     * is set.
     * 
     * @param string $access_token
     * @param int $sport_id
     * @param float $latitude
     * @param float $longitude
     * @param int $duration in minutes
     * @param DateTime $datetime date_parse
     * @param string $description
     * @param int $recommended_level
     * @param ?int $max_players
     * @param ?int $min_players
     * @param ?float $price
     * 
     * @throws AuthenticationException
     */
    public function setMatch(
        string $access_token,
        int $sport_id,
        float $latitude,
        float $longitude,
        int $duration,
        DateTime $datetime,
        string $description,
        int $recommended_level,
        ?int $max_players = null,
        ?int $min_players = null,
        ?float $price = null
    ): array {
        if (!$this->verifyUserAccessToken($access_token)) {
            throw new AuthenticationException();
        }

        if ($recommended_level < 0 || $recommended_level > 5) {
            throw new PatternException();
        }

        $organizer_id = $this->_getUserId($access_token);
        $sport_data = $this->getSport($sport_id);

        $request = 'INSERT INTO "match" 
                        ("organizer_id", "sport_id", "latitude", "longitude", "max_players", "min_players", "price", "duration", "datetime", "description", "recommended_level")
                        VALUES (:organizer_id, :sport_id, :latitude, :longitude, :max_players, :min_players, :price, :duration, :datetime, :description, :recommended_level)
                        RETURNING "id"';

        $duration = convertToHoursMins($duration);
        $datetime = $datetime->format('Y-m-d H:i:s');
        var_dump($max_players);

        $max_players = $max_players ?? $sport_data['default_max_players'];
        $min_players = $min_players ?? $sport_data['default_min_players'];


        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':organizer_id', $organizer_id);
        $statement->bindParam(':sport_id', $sport_id);
        $statement->bindParam(':latitude', $latitude);
        $statement->bindParam(':longitude', $longitude);
        $statement->bindParam(':duration', $duration);
        $statement->bindParam(':datetime', $datetime);
        $statement->bindParam(':description', $description);
        $statement->bindParam(':recommended_level', $recommended_level);
        $statement->bindParam(':max_players', $max_players);
        $statement->bindParam(':min_players', $min_players);
        $statement->bindParam(':price', $price);
        $statement->execute();

        $response = (array) $statement->fetch(PDO::FETCH_OBJ);

        $id = $response['id'];

        return $this->getMatch((int) $id);
    }

    /**
     * Update a match columns.
     * 
     * @param string $access_token
     * @param int $match_id
     * @param ?int $sport_id
     * @param ?float $latitude
     * @param ?float $longitude
     * @param ?int $duration in minutes
     * @param ?DateTime $datetime date_parse
     * @param ?string $description
     * @param ?int $recommended_level
     * @param ?int $max_players
     * @param ?int $min_players
     * @param ?float $price
     * 
     * @throws AuthenticationException
     * @throws EntryDoesNotExists
     */
    public function updateMatch(
        string $access_token,
        int $match_id,
        ?int $sport_id = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $duration = null,
        ?DateTime $datetime = null,
        ?string $description = null,
        int $recommended_level = null,
        ?int $max_players = null,
        ?int $min_players = null,
        ?float $price = null
    ): array {
        $organizer_id = $this->_getUserId($access_token);
        $data = $this->getMatch($match_id);

        if (empty($data)) {
            throw new EntryDoesNotExists();
        }

        $sport_id = $sport_id ?? $data['sport_id'];
        $latitude = $latitude ?? $data['latitude'];
        $longitude = $longitude ?? $data['longitude'];
        $duration = $duration ?? $data['duration'];
        $datetime = $datetime ?? $data['datetime'];
        $description = $description ?? $data['description'];
        $recommended_level = $recommended_level ?? $data['recommended_level'];
        $max_players = $max_players ?? $data['max_players'];
        $min_players = $min_players ?? $data['min_players'];
        $price = $price ?? $data['price'];

        $request = 'UPDATE "match"
                        SET "sport_id" = :sport_id,
                            "latitude" = :latitude,
                            "longitude" = :longitude,
                            "max_players" = :max_players,
                            "min_players" = :min_players,
                            "price" = :price,
                            "duration" = :duration,
                            "datetime" = :datetime,
                            "description" = :description,
                            "recommended_level" = :recommended_level
                        WHERE "organizer_id" = :organizer_id
                        AND "id" = :id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':organizer_id', $organizer_id);
        $statement->bindParam(':sport_id', $sport_id);
        $statement->bindParam(':latitude', $latitude);
        $statement->bindParam(':longitude', $longitude);
        $statement->bindParam(':duration', $duration);
        $statement->bindParam(':datetime', $datetime);
        $statement->bindParam(':description', $description);
        $statement->bindParam(':recommended_level', $recommended_level);
        $statement->bindParam(':max_players', $max_players);
        $statement->bindParam(':min_players', $min_players);
        $statement->bindParam(':price', $price);
        $statement->execute();

        return $this->getMatch($match_id);
    }

    /**
     * Delete a match.
     * 
     * @param string $access_token
     * @param int $match_id
     * 
     * @throws AuthenticationException
     */
    public function deleteMatch(
        string $access_token,
        int $match_id
    ): void {
        $organizer_id = $this->_getUserId($access_token);

        $request = 'DELETE FROM "match"
                        WHERE "organizer_id" = :organizer_id
                        AND "id" = :id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':organizer_id', $organizer_id);
        $statement->bindParam(':id', $match_id);
        $statement->execute();
    }

    /**
     * Create a participation.
     * 
     * @param string $access_token
     * @param int $match_id
     * 
     * @throws AuthenticationException
     * @throws DuplicateEntryException
     * @throws MatchFullException
     * @throws PDOException
     */
    public function joinMatch(
        string $access_token,
        int $match_id
    ): void {
        $user_id = $this->_getUserId($access_token);

        // check if participation not exist
        $request = 'SELECT * FROM "participation"
                        WHERE "user_id" = :user_id
                        AND "match_id" = :match_id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':user_id', $user_id);
        $statement->bindParam(':match_id', $match_id);
        $statement->execute();

        $response = (array) $statement->fetch(PDO::FETCH_OBJ);

        if (!empty($response)) {
            throw new DuplicateEntryException();
        }

        // check if match is full
        $match_data = $this->getMatch($match_id);

        if (
            count($match_data) > 1 &&
            $match_data['max_players'] <= count($match_data['participation'])
        ) {
            throw new MatchFullException();
        }

        $request = 'INSERT INTO "participation"
                        ("user_id", "match_id", "validation", "score")
                        VALUES (:user_id, :match_id, false, 0)';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':user_id', $user_id);
        $statement->bindParam(':match_id', $match_id);
        $statement->execute();
    }

    /**
     * Leave a match.
     * 
     * @param string $access_token
     * @param int $match_id
     * 
     * @throws AuthenticationException
     * @throws EntryDoesNotExists
     */
    public function leaveMatch(
        string $access_token,
        int $match_id
    ): void {
        $user_id = $this->_getUserId($access_token);

        // check if participation exists
        $request = 'SELECT * FROM "participation"
                        WHERE "user_id" = :user_id
                        AND "match_id" = :match_id
                        AND "validation" = false';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':user_id', $user_id);
        $statement->bindParam(':match_id', $match_id);
        $statement->execute();

        $response = (array) $statement->fetch(PDO::FETCH_OBJ);

        if (empty($response)) {
            throw new EntryDoesNotExists();
        }

        $request = 'DELETE FROM "participation"
                        WHERE "user_id" = :user_id
                        AND "match_id" = :match_id
                        AND "validation" = false';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':user_id', $user_id);
        $statement->bindParam(':match_id', $match_id);
        $statement->execute();
    }

    /**
     * Get the infos on a participation.
     * 
     * @param int $participation_id
     * 
     * @throws EntryDoesNotExists
     */
    public function getParticipation(int $participation_id): array
    {
        $request = 'SELECT * FROM "participation"
                        WHERE "id" = :id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':id', $participation_id);
        $statement->execute();

        $response = (array) $statement->fetch(PDO::FETCH_OBJ);

        if (empty($response)) {
            throw new EntryDoesNotExists();
        }

        return $response;
    }

    /**
     * Set statuses on a participation. E.g. the validation and the score.
     * Can only be done by the organizer of the match.
     * 
     * @param string $access_token
     * @param int $participation_id
     * @param ?bool $validation
     * @param ?int $score
     * 
     * @throws AuthenticationException
     * @throws EntryDoesNotExists
     */
    public function updateParticipation(
        string $access_token,
        int $participation_id,
        ?bool $validation = null,
        ?int $score = null
    ): array {
        if (!$this->verifyUserAccessToken($access_token)) {
            throw new AuthenticationException();
        }

        $organizer_id = $this->_getUserId($access_token);

        // check if participation exists and if access token is from an 
        // organizer
        $request = 'SELECT * FROM "participation" p
                        LEFT JOIN "match" m ON p."match_id" = m."id"
                        WHERE p."id" = :id
                        AND m."organizer_id" = :organizer_id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':id', $participation_id);
        $statement->bindParam(':organizer_id', $organizer_id);
        $statement->execute();

        $response = (array) $statement->fetch(PDO::FETCH_OBJ);

        if (empty($response)) {
            throw new EntryDoesNotExists();
        }

        $data = $response;

        $validation = $validation ?? $data['validation'];
        $score = $score ?? $data['score'];

        $request = 'UPDATE "participation"
                        SET "validation" = :validation,
                            "score" = :score
                        WHERE "id" = :id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':id', $participation_id);
        $statement->bindParam(':validation', $validation);
        $statement->bindParam(':score', $score);
        $statement->execute();

        return $this->getParticipation($participation_id);
    }

    /**
     * Get the teams associated to a match.
     * 
     * @param int $match_id
     * 
     * @throws EntryDoesNotExists
     */
    public function getTeams(
        int $match_id
    ): array {
        $request = 'SELECT * FROM "team"
                        WHERE "match_id" = :match_id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':match_id', $match_id);
        $statement->execute();

        $response = (array) $statement->fetchAll(PDO::FETCH_OBJ);

        if (empty($response)) {
            throw new EntryDoesNotExists();
        }

        return $response;
    }

    /**
     * Get a team's infos.
     * 
     * @param int $team_id
     * 
     * @throws EntryDoesNotExists
     */
    public function getTeam(
        int $team_id
    ): array {
        $request = 'SELECT * FROM "team"
                        WHERE "id" = :id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':id', $team_id);
        $statement->execute();

        $response = (array) $statement->fetch(PDO::FETCH_OBJ);

        if (empty($response)) {
            throw new EntryDoesNotExists();
        }

        return $response;
    }

    /**
     * Create a team for a match.
     * 
     * @param string $access_token
     * @param int $match_id
     * @param ?string $name
     * 
     * @throws AuthenticationException
     * @throws EntryDoesNotExists
     */
    public function setTeam(
        string $access_token,
        int $match_id,
        ?string $name = null
    ): array {
        $organizer_id = $this->_getUserId($access_token);

        // check if user is organizer
        $request = 'SELECT * FROM "match"
                        WHERE "organizer_id" = :organizer_id
                        AND "id" = :id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':organizer_id', $organizer_id);
        $statement->bindParam(':id', $match_id);
        $statement->execute();

        $response = (array) $statement->fetch(PDO::FETCH_OBJ);

        if (empty($response)) {
            throw new EntryDoesNotExists();
        }

        $request = 'INSERT INTO "team"
                        ("name", "match_id")
                        VALUES (:name, :match_id)
                        RETURNING "id"';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':match_id', $match_id);
        $statement->bindParam(':name', $name);
        $statement->execute();

        $response = (array) $statement->fetch(PDO::FETCH_OBJ);

        $id = $response['id'];

        return $this->getTeam($id);
    }

    /**
     * Rename a team.
     * 
     * @param string $access_token
     * @param int $team_id
     * @param ?string $name
     * 
     * @throws AuthenticationException
     * @throws DuplicateEntryException
     * @throws EntryDoesNotExists
     */
    public function renameTeam(
        string $access_token,
        int $team_id,
        ?string $name = null
    ): array {
        $organizer_id = $this->_getUserId($access_token);

        // check if user is organizer
        $request = 'SELECT * FROM "team" t
                        LEFT JOIN "match" m ON t."match_id" = m."id"
                        WHERE m."organizer_id" = :organizer_id
                        AND t."id" = :id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':organizer_id', $organizer_id);
        $statement->bindParam(':id', $team_id);
        $statement->execute();

        $response = (array) $statement->fetch(PDO::FETCH_OBJ);

        if (empty($response)) {
            throw new EntryDoesNotExists();
        }

        // check if name exists
        $request = 'SELECT * FROM "team" t
                        LEFT JOIN "match" m ON t."match_id" = m."id"
                        WHERE m."organizer_id" = :organizer_id
                        AND t."name" = :name
                        AND t."name" IS NOT NULL';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':organizer_id', $organizer_id);
        $statement->bindParam(':name', $name);
        $statement->execute();

        $response = (array) $statement->fetch(PDO::FETCH_OBJ);

        if (!empty($response)) {
            throw new DuplicateEntryException();
        }

        $request = 'UPDATE "team"
                        SET "name" = :name
                        WHERE "id" = :team_id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':id', $team_id);
        $statement->bindParam(':name', $name);
        $statement->execute();

        return $this->getTeam($team_id);
    }

    /**
     * Delete a team.
     * 
     * @param string $access_token
     * @param int $team_id
     * 
     * @throws AuthenticationException
     * @throws EntryDoesNotExists
     */
    public function deleteTeam(
        string $access_token,
        int $team_id
    ): void {
        $organizer_id = $this->_getUserId($access_token);

        // check if user is organizer
        $request = 'SELECT * FROM "team" t
                        LEFT JOIN "match" m ON t."match_id" = m."id"
                        WHERE m."organizer_id" = :organizer_id
                        AND t."id" = :id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':organizer_id', $organizer_id);
        $statement->bindParam(':id', $team_id);
        $statement->execute();

        $response = (array) $statement->fetch(PDO::FETCH_OBJ);

        if (empty($response)) {
            throw new EntryDoesNotExists();
        }

        $request = 'DELETE FROM "team"
                        WHERE "id" = :id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':id', $team_id);
        $statement->execute();
    }

    /**
     * Make a participation join a team.
     * 
     * @param string $access_token
     * @param int $team_id
     * @param int $participation_id
     * 
     * @throws AuthenticationException
     * @throws EntryDoesNotExists
     */
    public function joinTeam(
        string $access_token,
        int $team_id,
        int $participation_id
    ): array {
        // check if participation exists
        $this->getParticipation($participation_id);

        // check if user is organizer
        $organizer_id = $this->_getUserId($access_token);

        $request = 'SELECT * FROM "participation" p
                        LEFT JOIN "match" m ON p."match_id" = m."id"
                        WHERE m."organizer_id" = :organizer_id
                        AND p."id" = :id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':organizer_id', $organizer_id);
        $statement->bindParam(':id', $participation_id);
        $statement->execute();

        $response = (array) $statement->fetch(PDO::FETCH_OBJ);

        if (empty($response)) {
            throw new EntryDoesNotExists();
        }

        // check if team exists
        $this->getTeam($team_id);

        // update the participation
        $request = 'UPDATE "participation"
                        SET "team_id" = :team_id
                        WHERE "id" = :id';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':team_id', $team_id);
        $statement->bindParam(':id', $participation_id);
        $statement->execute();

        return $this->getParticipation($participation_id);
    }
}
