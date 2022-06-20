<?php

/**
 * PHP version 7.4.28
 *
 * @author Paul-Adrien PENET <pauladrienpenet@gmail.com
 * @author Youn Mélois <youn@melois.dev>
 */

require_once 'config.php';
require_once 'exceptions.php';

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
     * @return array Array of id, first_name, last_name, phone number and email.
     */
    public function getUserPersonalInfos(string $access_token): ?array
    {
        $request = 'SELECT "id", "first_name", "last_name", "email", "phone_number", "profile_picture_url", "birthdate" 
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
     * Get the participations of a match if the current user is the organizer or
     * get the participations of the user if authenticated.
     * 
     * @param string $access_token
     * @param int [$match_id]
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
}
