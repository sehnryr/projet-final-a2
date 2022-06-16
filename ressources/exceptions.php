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
