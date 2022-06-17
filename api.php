<?php
require_once 'resources/config.php';
require_once 'resources/database.php';
require_once 'resources/exceptions.php';

const GET = 'GET';
const POST = 'POST';
const PUT = 'PUT';
const DELETE = 'DELETE';

$pathInfo = explode('/', trim($_SERVER['PATH_INFO'], '/\\'));

header('content-type: application/json; charset=utf-8');

$db = new Database();

function getAuthorizationToken(): ?string
{
    $authorization = $_SERVER['HTTP_AUTHORIZATION'];

    if (!isset($authorization)) {
        APIErrors::invalidHeader();
    }

    $authorization = explode(' ', trim($authorization), 2)[1];

    if (empty($authorization)) {
        APIErrors::invalidGrant();
    }

    return $authorization;
}

class APIErrors
{
    public static function invalidGrant()
    {
        http_response_code(400);
        die(json_encode(array(
            'error' => 'invalid_grant',
            'error_description' => 'The authorization code is invalid or expired.'
        )));
    }

    public static function invalidHeader()
    {
        http_response_code(400);
        die(json_encode(array(
            'error' => 'invalid_header',
            'error_description' => 'The request is missing the Authorization header or the Authorization header is invalid.'
        )));
    }

    public static function invalidRequest()
    {
        http_response_code(400);
        die(json_encode(array(
            'error' => 'invalid_request',
            'error_description' => 'The request is missing a parameter, uses an unsupported parameter, uses an invalid parameter or repeats a parameter.'
        )));
    }

    public static function internalError()
    {
        http_response_code(500);
        die();
    }
}

switch ($pathInfo[0] . $_SERVER['REQUEST_METHOD']) {

    case 'login' . POST:
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Throw error if the parameters does not exist.
        if (!isset($email) || !isset($password)) {
            APIErrors::invalidRequest();
        }

        // Try to get the access token
        try {
            $access_token = $db->getUserAccessToken($email, $password);
        } catch (AuthenticationException $_) {
            APIErrors::invalidRequest();
        }

        http_response_code(200);
        die(json_encode(array(
            'access_token' => $access_token,
            'created_at' => time(),
            'token_type' => 'bearer'
        )));
        break;
    case 'logout' . POST:
    case 'register' . POST:
    case 'delete' . DELETE:
    case 'user' . GET:
    case 'cities' . GET:
    case 'sports' . GET:
    case 'user_level' . GET:
    case 'user_level' . PUT:
    case 'match' . GET:
    case 'match' . POST:
    case 'match' . PUT:
    case 'participations' . GET:
    case 'participate' . POST:
    case 'participate' . DELETE:
    case 'validate' . PUT:
    case 'score' . PUT:
    case 'teams' . GET:
    case 'team' . POST:
    case 'team' . PUT:
    case 'team' . DELETE:
    case 'rename_team' . DELETE:
    case 'note' . POST:
    case 'note' . PUT:
    case 'notification' . POST:
    default:
        http_response_code(404);
        die();
        break;
}
