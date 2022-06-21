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

function tryGetAuthorizationToken(): ?string
{
    $authorization = $_SERVER['HTTP_AUTHORIZATION']
        ?? apache_request_headers()['Authorization'];

    if (!isset($authorization)) {
        throw new InvalidHeaderException();
    }

    $authorization = explode(' ', trim($authorization), 2)[1];

    if (empty($authorization)) {
        throw new InvalidGrantException();
    }

    return $authorization;
}

function getAuthorizationToken(): string
{
    try {
        $authorization = tryGetAuthorizationToken();
    } catch (InvalidHeaderException $_) {
        APIErrors::invalidHeader();
    } catch (InvalidGrantException $_) {
        APIErrors::invalidGrant();
    }

    return $authorization;
}

function sendResponse(int $responseCode, array $data = null): void
{
    $encodedJson = !empty($data) ? json_encode($data) : json_encode(array());

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
    case 'logout' . HTTPRequestMethods::POST:
        $access_token = getAuthorizationToken();

        // Try to remove the access token
        try {
            $db->removeUserAccessToken($access_token);
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        }

        sendResponse(
            HTTPResponseCodes::Success,
            array('message' => 'Authorization code delete successfully.')
        );
    case 'register' . HTTPRequestMethods::POST:
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $birthdate = $_POST['birthdate'];
        $postal_code = $_POST['postal_code'];
        $phone_number = $_POST['phone_number'];

        if (
            !isset($first_name) ||
            !isset($last_name) ||
            !isset($email) ||
            !isset($password) ||
            !isset($birthdate) ||
            !isset($postal_code)
        ) {
            APIErrors::invalidRequest();
        }

        $db->createUser(
            $first_name,
            $last_name,
            $email,
            $password,
            $birthdate,
            $postal_code,
            $phone_number ?? NULL
        );

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
    case 'delete' . HTTPRequestMethods::DELETE:
        $access_token = getAuthorizationToken();

        try {
            $db->deleteUser($access_token);
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        }

        sendResponse(
            HTTPResponseCodes::Success,
            array('message' => 'User deleted successfully.')
        );
    case 'profile' . HTTPRequestMethods::PUT:
        parse_str(file_get_contents('php://input'), $_PUT);

        $city_id = $_PUT['city_id'];
        $first_name = $_PUT['first_name'];
        $last_name = $_PUT['last_name'];
        $email = $_PUT['email'];
        $phone_number = $_PUT['phone_number'];
        $password = $_PUT['password'];
        $profile_picture_url = $_PUT['profile_picture_url'];
        $birthdate = $_PUT['birthdate'];

        $access_token = getAuthorizationToken();

        try {
            $data = $db->updateUser(
                $access_token,
                isset($city_id) ? (int) $city_id : null,
                isset($first_name) ? $first_name : null,
                isset($last_name) ? $last_name : null,
                isset($email) ? $email : null,
                isset($phone_number) ? $phone_number : null,
                isset($password) ? $password : null,
                isset($profile_picture_url) ? $profile_picture_url : null,
                isset($birthdate) ? new DateTime($birthdate) : null
            );
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        } catch (DuplicateEmailException $_) {
            APIErrors::invalidRequest();
        } catch (PatternException $_) {
            APIErrors::invalidRequest();
        } catch (PDOException $_) {
            APIErrors::invalidHeader();
        }

        sendResponse(
            HTTPResponseCodes::Success,
            $data
        );
    case 'user' . HTTPRequestMethods::GET:
        $user_id = $_GET['user_id'];

        if (!isset($user_id)) {
            try {
                $access_token = tryGetAuthorizationToken();
            } catch (Exception $_) {
                APIErrors::invalidRequest();
            }
        }

        if (isset($access_token)) {
            try {
                $user_info = $db->getUserPersonalInfos($access_token);
            } catch (AuthenticationException $_) {
                APIErrors::invalidGrant();
            }
        } else {
            $user_info = $db->getUserInfos($user_id);
        }

        sendResponse(HTTPResponseCodes::Success, $user_info);
    case 'cities' . HTTPRequestMethods::GET:
        sendResponse(HTTPResponseCodes::Success, $db->getCities());
    case 'sport' . HTTPRequestMethods::GET:
        $sport_id = $_GET['sport_id'];
        if (!isset($sport_id)) {
            APIErrors::invalidRequest();
        }
        sendResponse(HTTPResponseCodes::Success, $db->getSport((int) $sport_id));
    case 'sports' . HTTPRequestMethods::GET:
        sendResponse(HTTPResponseCodes::Success, $db->getSports());
    case 'user_level' . HTTPRequestMethods::GET:
        $user_id = $_GET['user_id'];
        $sport_id = $_GET['sport_id'];

        if (!isset($user_id) || !isset($sport_id)) {
            APIErrors::invalidRequest();
        }

        $data = $db->getUserLevel((int)$user_id, (int)$sport_id);

        if (empty($data)) {
            APIErrors::invalidRequest();
        }

        sendResponse(HTTPResponseCodes::Success, $data);
    case 'user_level' . HTTPRequestMethods::POST:
        $sport_id = $_POST['sport_id'];
        $level = $_POST['level'];
        $description = $_POST['description'];

        $access_token = getAuthorizationToken();

        if (
            !isset($sport_id) ||
            !isset($level) ||
            !isset($description)
        ) {
            APIErrors::invalidRequest();
        }

        try {
            $db->setUserLevel($access_token, (int)$sport_id, (int)$level, $description);
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        } catch (PatternException $_) {
            APIErrors::invalidRequest();
        }

        sendResponse(
            HTTPResponseCodes::Success,
            $db->getUserLevel(
                $db->getUserPersonalInfos($access_token)['id'],
                (int)$sport_id
            )
        );
    case 'user_level' . HTTPRequestMethods::PUT:
        parse_str(file_get_contents('php://input'), $_PUT);

        $sport_id = $_PUT['sport_id'];
        $level = $_PUT['level'];
        $description = $_PUT['description'];

        $access_token = getAuthorizationToken();

        if (
            !isset($sport_id)
        ) {
            APIErrors::invalidRequest();
        }

        $user_id = $db->getUserPersonalInfos($access_token)['id'];
        $data = $db->getUserLevel($user_id, (int)$sport_id);

        if (isset($level)) {
            $data['level'] = (int)$level;
        }
        if (isset($description)) {
            $data['description'] = $description;
        }

        try {
            $db->setUserLevel(
                $access_token,
                (int)$sport_id,
                $data['level'],
                $data['description']
            );
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        } catch (PatternException $_) {
            APIErrors::invalidRequest();
        }

        sendResponse(
            HTTPResponseCodes::Success,
            $db->getUserLevel(
                $db->getUserPersonalInfos($access_token)['id'],
                (int)$sport_id
            )
        );
    case 'match' . HTTPRequestMethods::GET:
        $match_id = $_GET['match_id'];

        if (!isset($match_id)) {
            APIErrors::invalidRequest();
        }

        sendResponse(
            HTTPResponseCodes::Success,
            $db->getMatch((int)$match_id)
        );
    case 'match' . HTTPRequestMethods::POST:
        $sport_id = $_POST['sport_id'];
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];
        $duration = $_POST['duration'];
        $datetime = $_POST['datetime'];
        $description = $_POST['description'];
        $recommended_level = $_POST['recommended_level'];
        $max_players = $_POST['max_players'];
        $min_players = $_POST['min_players'];
        $price = $_POST['price'];

        $access_token = getAuthorizationToken();

        if (
            !isset($sport_id) ||
            !isset($latitude) ||
            !isset($longitude) ||
            !isset($duration) ||
            !isset($datetime) ||
            !isset($description) ||
            !isset($recommended_level)
        ) {
            APIErrors::invalidRequest();
        }

        try {
            $data = $db->setMatch(
                $access_token,
                (int) $sport_id,
                (float) $latitude,
                (float) $longitude,
                $duration,
                new DateTime($datetime),
                $description,
                (int) $recommended_level,
                isset($max_players) ? (int) $max_players : null,
                isset($min_players) ? (int) $min_players : null,
                isset($price) ? (float) $price : null
            );
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        }

        if (empty($data)) {
            APIErrors::invalidRequest();
        }

        sendResponse(HTTPResponseCodes::Success, $data);
    case 'match' . HTTPRequestMethods::PUT:
        parse_str(file_get_contents('php://input'), $_PUT);

        $match_id = $_PUT['match_id'];
        $sport_id = $_PUT['sport_id'];
        $latitude = $_PUT['latitude'];
        $longitude = $_PUT['longitude'];
        $duration = $_PUT['duration'];
        $datetime = $_PUT['datetime'];
        $description = $_PUT['description'];
        $recommended_level = $_PUT['recommended_level'];
        $max_players = $_POST['max_players'];
        $min_players = $_PUT['min_players'];
        $price = $_PUT['price'];

        $access_token = getAuthorizationToken();

        if (
            !isset($match_id)
        ) {
            APIErrors::invalidRequest();
        }

        try {
            $data = $db->updateMatch(
                $access_token,
                isset($match_id) ? (int) $match_id : null,
                isset($sport_id) ? (int) $sport_id : null,
                isset($latitude) ? (float) $latitude : null,
                isset($longitude) ? (float) $longitude : null,
                isset($duration) ? $duration : null,
                isset($datetime) ? new DateTime($datetime) : null,
                isset($description) ? $description : null,
                isset($recommended_level) ? (int) $recommended_level : null,
                isset($max_players) ? (int) $max_players : null,
                isset($min_players) ? (int) $min_players : null,
                isset($price) ? (float) $price : null
            );
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        } catch (PatternException $_) {
            APIErrors::invalidRequest();
        }

        sendResponse(HTTPResponseCodes::Success, $data);
    case 'match' . HTTPRequestMethods::DELETE:
        parse_str(file_get_contents('php://input'), $_DELETE);
        $match_id = $_DELETE['match_id'];

        $access_token = getAuthorizationToken();

        if (!isset($match_id)) {
            APIErrors::invalidRequest();
        }

        try {
            $db->deleteMatch($access_token, (int) $match_id);
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        }

        sendResponse(
            HTTPResponseCodes::Success,
            array('message' => 'Match deleted successfully.')
        );
    case 'participations' . HTTPRequestMethods::GET:
        $match_id = $_POST['match_id'];

        if (!isset($match_id)) {
            try {
                $access_token = tryGetAuthorizationToken();
                $data = $db->getUserParticipations($access_token);
            } catch (InvalidHeaderException | InvalidGrantException $_) {
                APIErrors::invalidGrant();
            }
        } else {
            $data = $db->getMatchParticipations((int) $match_id);
        }

        sendResponse(HTTPResponseCodes::Success, $data);
    case 'participation' . HTTPRequestMethods::GET:
        $participation_id = $_GET['participation_id'];

        if (!isset($participation_id)) {
            APIErrors::invalidRequest();
        }

        try {
            $data = $db->getParticipation((int) $participation_id);
        } catch (EntryDoesNotExists $_) {
            APIErrors::invalidRequest();
        }

        sendResponse(HTTPResponseCodes::Success, $data);
    case 'participate' . HTTPRequestMethods::POST:
        $match_id = $_POST['match_id'];

        if (!isset($match_id)) {
            APIErrors::invalidRequest();
        }

        $access_token = getAuthorizationToken();

        try {
            $db->joinMatch($access_token, (int) $match_id);
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        } catch (PDOException $_) {
            APIErrors::invalidRequest();
        } catch (DuplicateEntryException $_) {
            APIErrors::invalidRequest();
        } catch (MatchFullException $_) {
            sendResponse(
                HTTPResponseCodes::BadRequest,
                array(
                    'message' => 'Match is full. Cannot join.'
                )
            );
        }

        sendResponse(
            HTTPResponseCodes::Success,
            array('message' => 'Match successfully joined.')
        );
    case 'participate' . HTTPRequestMethods::DELETE:
        parse_str(file_get_contents('php://input'), $_DELETE);
        $match_id = $_DELETE['match_id'];

        if (!isset($match_id)) {
            APIErrors::invalidRequest();
        }

        $access_token = getAuthorizationToken();

        try {
            $db->leaveMatch($access_token, (int) $match_id);
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        } catch (EntryDoesNotExists $_) {
            APIErrors::invalidRequest();
        }

        sendResponse(
            HTTPResponseCodes::Success,
            array('message' => 'Match leaved successfully.')
        );
    case 'validate' . HTTPRequestMethods::PUT:
        parse_str(file_get_contents('php://input'), $_PUT);
        $participation_id = $_PUT['participation_id'];
        $value = $_PUT['value'];

        if (!isset($participation_id) || !isset($value)) {
            APIErrors::invalidRequest();
        }

        $access_token = getAuthorizationToken();

        try {
            $data = $db->updateParticipation(
                $access_token,
                (int) $participation_id,
                validation: (bool) $value,
            );
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        } catch (EntryDoesNotExists $_) {
            APIErrors::invalidRequest();
        }

        sendResponse(HTTPResponseCodes::Success, $data);
    case 'score' . HTTPRequestMethods::PUT:
        parse_str(file_get_contents('php://input'), $_PUT);
        $participation_id = $_PUT['participation_id'];
        $value = $_PUT['value'];

        if (!isset($participation_id) || !isset($value)) {
            APIErrors::invalidRequest();
        }

        $access_token = getAuthorizationToken();
    case 'teams' . HTTPRequestMethods::GET:
        $match_id = $_GET['match_id'];

        if (!isset($match_id)) {
            APIErrors::invalidRequest();
        }

        try {
            $data = $db->getTeams((int) $match_id);
        } catch (EntryDoesNotExists $_) {
            APIErrors::invalidRequest();
        }

        sendResponse(HTTPResponseCodes::Success, $data);
    case 'team' . HTTPRequestMethods::POST:
        $match_id = $_POST['match_id'];
        $name = $_POST['name'];

        if (!isset($match_id)) {
            APIErrors::invalidRequest();
        }

        $access_token = getAuthorizationToken();

        try {
            $data = $db->setTeam(
                $access_token,
                (int) $match_id,
                isset($name) ? $name : null
            );
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        } catch (EntryDoesNotExists $_) {
            APIErrors::invalidRequest();
        }

        sendResponse(HTTPResponseCodes::Success, $data);
    case 'team' . HTTPRequestMethods::PUT:
        parse_str(file_get_contents('php://input'), $_PUT);
        $team_id = $_PUT['team_id'];
        $participation_id = $_PUT['participation_id'];

        if (!isset($team_id) || !isset($participation_id)) {
            APIErrors::invalidRequest();
        }

        $access_token = getAuthorizationToken();

        try {
            $data = $db->joinTeam(
                $access_token,
                (int) $team_id,
                (int) $participation_id
            );
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        } catch (EntryDoesNotExists $_) {
            APIErrors::invalidRequest();
        }

        sendResponse(HTTPResponseCodes::Success, $data);
    case 'team' . HTTPRequestMethods::DELETE:
        parse_str(file_get_contents('php://input'), $_DELETE);
        $team_id = $_DELETE['team_id'];

        if (!isset($team_id)) {
            APIErrors::invalidRequest();
        }

        $access_token = getAuthorizationToken();

        try {
            $db->deleteTeam(
                $access_token,
                (int) $team_id
            );
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        } catch (EntryDoesNotExists $_) {
            APIErrors::invalidRequest();
        }

        sendResponse(
            HTTPResponseCodes::Success,
            array('message' => 'Team deleted successfully.')
        );
    case 'rename_team' . HTTPRequestMethods::PUT:
        parse_str(file_get_contents('php://input'), $_PUT);
        $team_id = $_PUT['team_id'];
        $name = $_PUT['name'];

        if (!isset($team_id)) {
            APIErrors::invalidRequest();
        }

        $access_token = getAuthorizationToken();

        try {
            $data = $db->renameTeam(
                $access_token,
                (int) $team_id,
                isset($name) ? $name : null
            );
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        } catch (DuplicateEntryException $_) {
            APIErrors::invalidRequest();
        } catch (EntryDoesNotExists $_) {
            APIErrors::invalidRequest();
        }

        sendResponse(HTTPResponseCodes::Success, $data);
    case 'note' . HTTPRequestMethods::POST:
        $score = $_POST['score'];
        $comment = $_POST['comment'];

        if (!isset($score) || !isset($comment)) {
            APIErrors::invalidRequest();
        }

        $access_token = getAuthorizationToken();

        try {
            $data = $db->setNote(
                $access_token,
                (int) $score,
                (int) $comment
            );
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        }

        sendResponse(HTTPResponseCodes::Success, $data);
    case 'note' . HTTPRequestMethods::PUT:
        parse_str(file_get_contents('php://input'), $_PUT);
        $score = $_PUT['score'];
        $comment = $_PUT['comment'];

        $access_token = getAuthorizationToken();

        try {
            $data = $db->updateNote(
                $access_token,
                isset($score) ? (int) $score : null,
                isset($comment) ? (int) $comment : null
            );
        } catch (AuthenticationException $_) {
            APIErrors::invalidGrant();
        } catch (EntryDoesNotExists $_) {
            APIErrors::invalidRequest();
        }

        sendResponse(HTTPResponseCodes::Success, $data);
    case 'notification' . HTTPRequestMethods::POST:
        $message = $_POST['message'];
        $url = $_POST['url'];

        if (!isset($message)) {
            APIErrors::invalidRequest();
        }

        $access_token = getAuthorizationToken();
    case 'notification' . HTTPRequestMethods::DELETE:
        parse_str(file_get_contents('php://input'), $_DELETE);
        $notification_id = $_DELETE['notification_id'];

        if (!isset($notification_id)) {
            APIErrors::invalidRequest();
        }
    default:
        sendResponse(HTTPResponseCodes::NotFound);
        break;
}
