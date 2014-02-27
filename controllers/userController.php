<?php
namespace Controllers;

if(EXEC != 1) {
	die('Invalid request');
}

/**
 * This class is the user controller
 *
 * @author Niels Witte
 * @version 0.1
 * @date February 12th, 2014
 */
class UserController {
    private $user;

    /**
     * Constructs a new controller for the given user
     *
     * @param \Models\User $user
     */
    public function __construct(\Models\User $user = NULL) {
        $this->user = $user;
    }

    /**
     * Sets the avatar UUID and matches it to the username
     *
     * @param string $username - User name to match
     * @param integer $gridId - The grid where the Avatar is on
     * @param string $uuid - UUID to use
     * @return boolean
     * @throws \Exception
     */
    public function setUuid($username, $gridId, $uuid) {
        $results = FALSE;
        if(\Helper::isValidUuid($uuid)) {
            $db = \Helper::getDB();
            // Check if UUID not in use
            $db->where('uuid', $db->escape($uuid));
            $db->where('gridId', $db->escape($gridId));
            $avatars = $db->get('avatars');

            // Not used?
            if(!isset($avatars[0])) {
                // Get user's ID
                $db->where("username", $db->escape($username));
                $user = $db->get("users", 1);

                $avatarData = array(
                    'userId'        => $db->escape($user[0]['id']),
                    'gridId'        => $db->escape($gridId),
                    'uuid'          => $db->escape($uuid)
                );
                $results = $db->insert('avatars', $avatarData);
            } else {
                $db->where("id", $db->escape($avatars[0]['userId']));
                $user = $db->get("users", 1);

                throw new \Exception("UUID already in use on this Grid, used by: ". $user[0]['username'], 3);
            }
        } else {
            throw new \Exception("Invalid UUID provided", 2);
        }

        // Something when wrong?
        if($results === FALSE) {
            throw new \Exception("Updating UUID failed, check Username and Grid ID", 1);
        }
        return $results !== FALSE;
    }

    /**
     * Check to see if the given username is unique
     *
     * @param string $username
     * @return boolean - TRUE when available
     */
    public function checkUsername($username) {
        $db = \Helper::getDB();
        $db->where('username', $db->escape($username));
        $result = $db->get('users', 1);

        return !isset($result[0]);
    }

    /**
     * Checks to see if the password matches the stored hash for this user
     *
     * @param string $password - The unhashed password
     * @return boolean
     */
    public function checkPassword($password) {
        $db = \Helper::getDB();
        $db->where('id', $db->escape($this->user->getId()));
        $result = $db->get('users', 1);

        // Got a result?
        if(isset($result[0]['password'])) {
            $hash = $result[0]['password'];
        } else {
            $hash = '';
        }

        return password_verify($password, $hash);
    }

    /**
     * Updates the password and hash it for this user
     *
     * @param string $hash - The unhashed password
     * @return boolean
     */
    public function setPassword($password) {
        $hash   = \Helper::Hash($password);
        $db     = \Helper::getDB();
        $db->where('id', $db->escape($this->user->getId()));
        $result = $db->update('users', array('password' => $hash));
        return $result;
    }

    /**
     * Creates a new user with the given parameters
     *
     * @param array $parameters - Array with parameters to create the user
     *              * string username - The user's username
     *              * string firstName - The user's first name
     *              * string lastName - The user's last name
     *              * string email - The user's email address
     *              * string password - The unhashed password for the user
     *              * string password2 - Unhashed retyped password to check if the user did not made any typo's
     * @return integer - The userId when creation succeded, or boolean FALSE when failed.
     */
    public function createUser($parameters) {
        $result = FALSE;
        $data   = array(
            'username'      => $db->escape($parameters['username']),
            'firstName'     => $db->escape($parameters['firstName']),
            'lastName'      => $db->escape($parameters['lastName']),
            'email'         => $db->escape($parameters['email']),
            'password'      => $db->escape(\Helper::Hash($parameters['password']))
        );
        $db     = \Helper::getDB();
        $userId = $db->insert('users', $data);
        // User created successful?
        if($userId !== FALSE) {
            $result = $userId;
        }
        return $result;
    }

    /**
     * Checks if the given parameters are valid for creating a new user
     *
     * @param array $parameters - See createUser()
     * @return boolean
     * @throws \Exception
     */
    public  function validateParametersCreate($parameters) {
        $result = FALSE;
        if(count($parameters) != 6) {
            throw new \Exception('Expected 6 parameters, '. count($parameters) .' given', 1);
        } elseif(!isset($parameters['username']) || strlen($parameters['username']) < SERVER_MIN_USERNAME_LENGTH) {
            throw new \Exception('Missing parameter (string) "username" with a minimum length of '. SERVER_MIN_USERNAME_LENGTH, 2);
        } elseif(isset($parameters['username']) && !$this->checkUsername($parameters['username'])) {
            throw new \Exception("Username is already being used", 9);
        } elseif(!isset($parameters['password']) || strlen($parameters['password']) < SERVER_MIN_PASSWORD_LENGTH) {
            throw new \Exception('Missing parameter (string) "password" with a minimum length of '. SERVER_MIN_PASSWORD_LENGTH, 3);
        } elseif(!isset($parameters['password2']) || $parameters['password'] != $parameters['password2']) {
            throw new \Exception('Missing parameter (string) "password2" which should match parameter (string) "password" with a minimum length of '. SERVER_MIN_PASSWORD_LENGTH, 4);
        } elseif(!isset($parameters['firstName'])) {
            throw new \Exception('Missing parameter (string) "firstName"', 6);
        } elseif(!isset($parameters['lastName'])) {
            throw new \Exception('Missing parameter (string) "lastName"', 7);
        } elseif (!isset($parameters['email']) || !filter_var($parameters['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Missing parameter (string) "email" with a valid email address', 8);
        } else {
            $result = TRUE;
        }
        return $result;
    }

    /**
     * Validates the parameters required for updating the user's password
     *
     * @param array $parameters
     * @return boolean
     * @throws \Exception
     */
    public function validateParameterPassword($parameters) {
        $result = FALSE;
        if(count($parameters) != 3) {
            throw new \Exception('Expected 3 parameters, '. count($parameters) .' given', 1);
        } elseif(!isset($parameters['password']) || strlen($parameters['password']) < SERVER_MIN_PASSWORD_LENGTH) {
            throw new \Exception('Missing parameter (string) "password" with a minimum length of '. SERVER_MIN_PASSWORD_LENGTH, 2);
        } elseif(!isset($parameters['password2']) || $parameters['password'] != $parameters['password2']) {
            throw new \Exception('Missing parameter (string) "password2" which should match parameter (string) "password" with a minimum length of '. SERVER_MIN_PASSWORD_LENGTH, 3);
        } elseif(!isset($parameters['currentPassword']) || !$this->checkPassword($parameters['currentPassword'])) {
            throw new \Exception('Missing parameter (string) "currentPassword" which should match the current user\'s password', 4);
        } else {
            $result = TRUE;
        }

        return $result;
    }
}
