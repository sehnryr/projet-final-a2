<?php
require_once 'resources/config.php';
require_once 'resources/database.php';
require_once 'resources/exceptions.php';

class HTTPRequestMethods
{
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
}

class HTTPResponseCodes
{
    const Success = 200;
    const BadRequest = 400;
    const Forbidden = 403;
    const NotFound = 404;
    const InternalServerError = 500;
    const NotImplemented = 501;
}

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

function sendResponse(int $responseCode, array $data = null): void
{
    $encodedJson = !empty($data) ? json_encode($data) : null;

    http_response_code($responseCode);
    die($encodedJson);
}

class APIErrors
{
    public static function invalidGrant()
    {
        sendResponse(
            HTTPResponseCodes::BadRequest,
            array(
                'error' => 'invalid_grant',
                'error_description' => 'The authorization code is invalid or expired.'
            )
        );
    }

    public static function invalidHeader()
    {
        sendResponse(
            HTTPResponseCodes::BadRequest,
            array(
                'error' => 'invalid_header',
                'error_description' => 'The request is missing the Authorization header or the Authorization header is invalid.'
            )
        );
    }

    public static function invalidRequest()
    {
        sendResponse(
            HTTPResponseCodes::BadRequest,
            array(
                'error' => 'invalid_request',
                'error_description' => 'The request is missing a parameter, uses an unsupported parameter, uses an invalid parameter or repeats a parameter.'
            )
        );
    }

    public static function internalError()
    {
        sendResponse(HTTPResponseCodes::InternalServerError);
    }
}

switch ($pathInfo[0] . $_SERVER['REQUEST_METHOD']) {

    case 'login' . HTTPRequestMethods::POST:
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

        // Send response code 200: success
        sendResponse(
            HTTPResponseCodes::Success,
            array(
                'access_token' => $access_token,
                'created_at' => time(),
                'token_type' => 'bearer'
            )
        );
        break;
    case 'logout' . HTTPRequestMethods::POST:
    case 'register' . HTTPRequestMethods::POST:
    case 'delete' . HTTPRequestMethods::DELETE:
    case 'user' . HTTPRequestMethods::GET:
    case 'cities' . HTTPRequestMethods::GET:
    case 'sports' . HTTPRequestMethods::GET:
    case 'user_level' . HTTPRequestMethods::GET:
    case 'user_level' . HTTPRequestMethods::PUT:
    case 'match' . HTTPRequestMethods::GET:
    case 'match' . HTTPRequestMethods::POST:
    case 'match' . HTTPRequestMethods::PUT:
    case 'participations' . HTTPRequestMethods::GET:
    case 'participate' . HTTPRequestMethods::POST:
    case 'participate' . HTTPRequestMethods::DELETE:
    case 'validate' . HTTPRequestMethods::PUT:
    case 'score' . HTTPRequestMethods::PUT:
    case 'teams' . HTTPRequestMethods::GET:
    case 'team' . HTTPRequestMethods::POST:
    case 'team' . HTTPRequestMethods::PUT:
    case 'team' . HTTPRequestMethods::DELETE:
    case 'rename_team' . HTTPRequestMethods::DELETE:
    case 'note' . HTTPRequestMethods::POST:
    case 'note' . HTTPRequestMethods::PUT:
    case 'notification' . HTTPRequestMethods::POST:
    default:
        sendResponse(HTTPResponseCodes::NotFound);
        break;
}
