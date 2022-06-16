<?php

/**
 * PHP version 7.4.28
 *
 * @author Paul-Adrien PENET <pauladrienpenet@gmail.com
 * @author Youn Mélois <youn@melois.dev>
 */

require_once 'constants.php';
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

    private function getUserPasswordHash(
        string $email
    ): ?string {
        $email = strtolower($email);

        $request = 'SELECT password_hash FROM user 
                      WHERE email = :email';

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
        $password_hash = $this->getUserPasswordHash($email);
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
        $request = 'SELECT * FROM user
                      WHERE access_token = :access_token';

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
     * @return string The access_token.
     */
    public function getUserAccessToken(
        string $email,
        string $password
    ): ?string {
        if (!$this->verifyUserCredentials($email, $password)) {
            return NULL;
        }

        $email = strtolower($email);

        $access_token = hash('sha256', $email . $password . time());

        // Set access token on the user
        $request = 'UPDATE user SET access_token = :access_token
                      WHERE email = :email';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':email', $email);
        $statement->bindParam(':access_token', $access_token);
        $statement->execute();

        return $access_token;
    }

    /**
     * Connects the user by returning its unique id if the 
     * credentials are valid.
     * 
     * @param string $email
     * @param string $password
     * @param int $session_expire (optional) The lifetime of the session cookie in seconds.
     * 
     * @throws AuthenticationException If the authentication failed.
     */
    public function connectUser(
        string $email,
        string $password,
        int $session_expire = 0
    ): void {
        if (!$this->verifyUserCredentials($email, $password)) {
            throw new AuthenticationException('Authentication failed.');
        }

        $email = strtolower($email);

        $access_token = hash('sha256', $email . $password . microtime(true));

        // Set session hash on the user
        $request = 'UPDATE user SET access_token = :access_token
                      WHERE email = :email';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':email', $email);
        $statement->bindParam(':access_token', $access_token);
        $statement->execute();

        $access_token = $this->getUserAccessToken($email, $password);

        switch ($session_expire) {
            case 0:
                $cookie_expire = 0;
                break;
            default:
                $cookie_expire = time() + $session_expire;
                break;
        }

        setcookie('docto_session', $access_token, $cookie_expire);
    }

    /**
     * Tries to connect the user with its session cookie if valid.
     * 
     * @throws AuthenticationException If the authentication failed.
     */
    public function tryConnectUser(): void
    {
        if (!isset($_COOKIE['mm_session'])) {
            throw new AuthenticationException('Authentication failed.');
        }

        $access_token = $_COOKIE['mm_session'];

        $request = 'SELECT * FROM user
                      WHERE access_token = :access_token';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':access_token', $access_token);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_OBJ);

        if (empty($result)) {
            throw new AuthenticationException('Authentication failed.');
        }
    }

    /**
     * Removes the access token from the user.
     * 
     * @param string $access_token
     * 
     * @throws AccessTokenNotFound If the access token is invalid.
     */
    public function removeUserAccessToken(string $access_token): void
    {
        if (!$this->verifyUserAccessToken($access_token)) {
            throw new AuthenticationException();
        }

        // remove access token
        $request = 'UPDATE user SET access_token = NULL
                      WHERE access_token = :access_token';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':access_token', $access_token);
        $statement->execute();
    }

    /**
     * Disconnects the current user by resetting the session hash stored in the
     * database.
     */
    public function disconnectUser(): void
    {
        if (!isset($_COOKIE['mm_session'])) {
            return;
        }

        $access_token = $_COOKIE['mm_session'];
        $this->removeUserAccessToken($access_token);

        setcookie('mm_session', '', time() - 3600);
    }

    /**
     * Gets the general infos of a user
     * 
     * @param string $access_token
     * 
     * @return array Array of id, firstname, lastname, phone number and email.
     */
    public function getUserInfos(string $access_token): ?array
    {
        $request = 'SELECT id, firstname, lastname, phone_number, email, profile_picture_url, birthdate, FROM user
                      WHERE access_token = :access_token';

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
     * @param string $firstname first name
     * @param string $lastname last name
     * @param string $email 
     * @param string $phoneNumber phone number
     * @param string $password
     *
     */
    public function createUser(
        string $firstname,
        string $lastname,
        string $email,
        string $phoneNumber,
        string $password,
        string $birthdate,
        string $cityName
    ) {
        // test if user already exists
        $request = 'SELECT * FROM user
                      WHERE email = :email';

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
                      WHERE name = :name';
        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':name', $cityName);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_OBJ);
        $city_id = $result->id;


        $request = 'INSERT INTO user 
                      (city_id, first_name, last_name, email, phone_number, password_hash, birthdate)
                      VALUES (:city_id, :firstname, :lastname, :email, :phone_number, :password_hash, :birthdate)';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':city_id', $city_id);
        $statement->bindParam(':firstname', $firstname);
        $statement->bindParam(':lastname', $lastname);
        $statement->bindParam(':email', $email);
        $statement->bindParam(':password_hash', $password_hash);
        $statement->bindParam(':phone_number', $phoneNumber);
        $statement->bindParam(':birthdate', $birthdate);
        $statement->execute();
    }

    /**
     * Deletes a user.
     * 
     * @param string $email
     * @param string $password
     * 
     * @throws AuthenticationException
     */
    public function deleteUser(
        string $email,
        string $password
    ): void {
        if (!$this->verifyUserCredentials($email, $password)) {
            throw new AuthenticationException();
        }

<<<<<<< HEAD
        $request = 'DELETE FROM users
                      WHERE email = :email';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':email', $email);
        $statement->execute();
    }
}
=======
      $request = 'DELETE FROM user
                      WHERE email = :email';

      $statement = $this->PDO->prepare($request);
      $statement->bindParam(':email', $email);
      $statement->execute();
  }
  public function deleteUserWithToken(string $access_token): void
    {
        if (!$this->verifyUserAccessToken($access_token)) {
            throw new AuthenticationException();
        }

        $request = 'DELETE FROM user
                        WHERE access_token = :access_token';

        $statement = $this->PDO->prepare($request);
        $statement->bindParam(':access_token', $access_token);
        $statement->execute();
    }
  public function getUserInfos(string $access_token): ?array
  {
    $request = 'SELECT id, city_id, first_name, last_name, email, phone_number, birthdate FROM user
                        WHERE access_token = :access_token';

    $statement = $this->PDO->prepare($request);
    $statement->bindParam(':access_token', $access_token)        
    $statement->execute();

    $result = $statement->fetch(PDO::FETCH_OBJ);

    if (empty($result)) {
      throw new AuthentificationException();
    }

        return (array) $result;
  }
  public function getUserMatchs(
    
  ): ?array
  {
    $request 
  }
  }

>>>>>>> 4de1b45 (api getuser info addition, user.js user.html addition)
