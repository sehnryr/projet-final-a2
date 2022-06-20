<?php

/**
 * PHP version 7.4.28
 * 
 * @author Paul-Adrien PENET <pauladrienpenet@gmail.com
 * @author Youn Mélois <youn@melois.dev>
 */

/**
 * This exception is thrown when the authentication failed.
 */
class AuthenticationException extends Exception
{
}

/**
 * This will be thrown when trying to create a user with a duplicate email.
 */
class DuplicateEmailException extends Exception
{
}

/**
 * Exception when an id isn't associated to an entry in a table.
 */
class EntryDoesNotExists extends Exception
{
}

/**
 * Thrown when the authorization code is invalid or expired.
 */
class InvalidGrantException extends Exception
{
}

/**
 * Thrown when the request is missing the Authorization header or the 
 * Authorization header is invalid.
 */
class InvalidHeaderException extends Exception
{
}

/**
 * Thrown when the request is missing a parameter, uses an unsupported 
 * parameter, uses an invalid parameter or repeats a parameter.
 */
class InvalidRequestException extends Exception
{
}
