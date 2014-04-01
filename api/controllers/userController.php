<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the user controller
 *
 * @author Niels Witte
 * @version 0.3
 * @date April 1st, 2014
 * @since February 12th, 2014
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
     * @param integer $gridId - The grid where the Avatar is on
     * @param string $uuid - UUID to use
     * @return boolean
     * @throws \Exception
     */
    public function linkAvatar($gridId, $uuid) {
        $results = FALSE;
        if(\Helper::isValidUuid($uuid)) {
            $db = \Helper::getDB();
            // Check if UUID not in use
            $db->where('uuid', $db->escape($uuid));
            $db->where('gridId', $db->escape($gridId));
            $avatars = $db->getOne('avatars');

            // Not used?
            if(!$avatars) {
                $avatarData = array(
                    'userId'        => $db->escape($this->user->getId()),
                    'gridId'        => $db->escape($gridId),
                    'uuid'          => $db->escape($uuid)
                );
                $results = $db->insert('avatars', $avatarData);
            } else {
                $db->where('id', $db->escape($avatars['userId']));
                $user = $db->getOne('users');

                throw new \Exception('UUID already in use on this Grid, used by: '. $user['username'], 3);
            }
        } else {
            throw new \Exception('Invalid UUID provided', 2);
        }

        // Something when wrong?
        if($results === FALSE) {
            throw new \Exception('Updating UUID failed, check Username and Grid ID', 1);
        }
        return $results !== FALSE;
    }

    /**
     * Unlinks the given avatar from the user
     *
     * @param \Models\Avatar $avatar
     * @return boolean
     * @throws \Exception
     */
    public function unlinkAvatar(\Models\Avatar $avatar) {
        $db         = \Helper::getDB();
        $db->where('userId', $db->escape($this->user->getId()));
        $db->where('uuid', $db->escape($avatar->getUuid()));
        $db->where('gridId', $db->escape($avatar->getGrid()->getId()));
        $result     = $db->delete('avatars');

        if($result === FALSE) {
            throw new \Exception('Given Avatar not found on the given Grid for the given User', 1);
        }

        return $result;
    }

    /**
     * Confirms that the given avatar uuid matches this user
     *
     * @param \Models\Avatar $avatar
     * @return boolean
     * @throws \Exception
     */
    public function confirmAvatar(\Models\Avatar $avatar) {
        $db         = \Helper::getDB();
        $db->where('userId', $db->escape($this->user->getId()));
        $db->where('uuid', $db->escape($avatar->getUuid()));
        $db->where('gridId', $db->escape($avatar->getGrid()->getId()));

        $data       = array('confirmed' => 1);
        $result     = $db->update('avatars', $data);

        if($result === FALSE) {
            throw new \Exception('Given unconfirmed Avatar not found on the given Grid for the currently logged in user', 1);
        }

        return $result;
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
        $result = $db->getOne('users');

        return !$result;
    }

    /**
     * Check to see if the given e-mail address in unused
     *
     * @param string $email
     * @return boolean - TRUE when available
     */
    public function checkEmail($email) {
        $db = \Helper::getDB();
        $db->where('email', $db->escape($email));
        $result = $db->getOne('users');

        return !$result;
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
        $result = $db->getOne('users');

        // Got a result?
        if($result) {
            $hash = $result['password'];
        } else {
            $hash = '';
        }

        return password_verify($password, $hash);
    }

    /**
     * Updates the password and hash it for this user
     *
     * @param string $password - The unhashed password
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
        $db     = \Helper::getDB();
        $data   = array(
            'username'      => $db->escape($parameters['username']),
            'firstName'     => $db->escape($parameters['firstName']),
            'lastName'      => $db->escape($parameters['lastName']),
            'email'         => $db->escape($parameters['email']),
            'password'      => $db->escape(\Helper::Hash($parameters['password']))
        );
        $userId = $db->insert('users', $data);
        // User creation successful?
        if($userId !== FALSE) {
            $result = $userId;

            // Add default permissions
            $permissions = array(
                'userId'        => $db->escape($userId),
                'chat'          => $db->escape(\Auth::READ),
                'comment'       => $db->escape(\Auth::READ),
                'auth'          => $db->escape(\Auth::READ),
                'document'      => $db->escape(\Auth::READ),
                'grid'          => $db->escape(\Auth::READ),
                'meeting'       => $db->escape(\Auth::READ),
                'meetingroom'   => $db->escape(\Auth::READ),
                'presentation'  => $db->escape(\Auth::READ),
                'user'          => $db->escape(\Auth::READ)
            );
            $db->insert('user_permissions', $permissions);
        }
        return $result;
    }

    /**
     * Updates part of the user's information
     *
     * @param array $parameters - Array with parameters to update the user
     *              * string firstName - The user's first name
     *              * string lastName - The user's last name
     *              * string email - The user's email address
     * @return boolean
     */
    public function updateUser($parameters) {
        $db     = \Helper::getDB();
        $data   = array(
            'firstName'     => $db->escape($parameters['firstName']),
            'lastName'      => $db->escape($parameters['lastName']),
            'email'         => $db->escape($parameters['email'])
        );
        $db->where('id', $db->escape($this->user->getId()));
        $result = $db->update('users', $data);

        return $result;
    }

    /**
     *
     * @param array $parameters - Array with parameters to set permissions to
     *              * integer auth - permission level
     *              * integer chat - permission level
     *              * integer comment - permission level
     *              * integer document - permission level
     *              * integer grid - permission level
     *              * integer meeting - permission level
     *              * integer meetingroom - permission level
     *              * integer presentation - permission level
     *              * integer user - permission level
     * @return boolean
     */
    public function updateUserPermissions($parameters) {
        $db     = \Helper::getDB();
        $data   = array(
            'auth'          => $db->escape($parameters['auth']),
            'chat'          => $db->escape($parameters['chat']),
            'comment'       => $db->escape($parameters['comment']),
            'document'      => $db->escape($parameters['document']),
            'grid'          => $db->escape($parameters['grid']),
            'meeting'       => $db->escape($parameters['meeting']),
            'meetingroom'   => $db->escape($parameters['meetingroom']),
            'presentation'  => $db->escape($parameters['presentation']),
            'user'          => $db->escape($parameters['user'])
        );
        $db->where('userId', $db->escape($this->user->getId()));
        $result = $db->update('user_permissions', $data);

        return $result;
    }

    /**
     * Removes this user from the CMS
     *
     * @return boolean
     * @throws \Exception
     */
    public function removeUser() {
        $db         = \Helper::getDB();
        $db->where('id', $db->escape($this->user->getId()));
        $result     = $db->delete('users');

        if($result === FALSE) {
            throw new \Exception('Given User could not be removed from the CMS', 1);
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
            throw new \Exception('Username is already being used', 9);
        } elseif(isset($parameters['email']) && !$this->checkEmail($parameters['email'])) {
            throw new \Exception('This is email is already being used', 10);
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
     * Checks the parameters given for updating user information
     *
     * @param array $parameters
     * @return boolean
     * @throws \Exception
     */
    public function validateParameterUpdateUser($parameters) {
        $result = FALSE;
        if(count($parameters) < 3) {
            throw new \Exception('Expected 3 parameters, '. count($parameters) .' given', 1);
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
     * Checks to see if the list with rights is correct
     *
     * @param array $parameters
     * @return boolean
     * @throws \Exception
     */
    public function validatePermissions($parameters) {
        $result = FALSE;
        $permissions = array(
            \Auth::NONE,
            \Auth::READ,
            \Auth::EXECUTE,
            \Auth::WRITE,
            \Auth::ALL
        );
        // Permissions to check
        $permissionTypes = array(
            'auth',
            'chat',
            'comment',
            'document',
            'grid',
            'meeting',
            'meetingroom',
            'presentation',
            'user'
        );

        if(count($parameters) < count($permissionTypes)) {
            throw new \Exception('Expected '. count($permissionTypes) .' parameters, '. count($parameters) .' given', 1);
        } elseif(count($parameters) >= count($permissionTypes)) {
            $result = TRUE;
            foreach($permissionTypes as $type) {
                if(!isset($parameters[$type]) || !in_array($parameters[$type], $permissions)) {
                    $result = FALSE;
                    throw new \Exception('Missing parameter (integer) "'. $type .'", with value in ('. implode(', ', $permissions) .')', 2);
                }
            }
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
        if(count($parameters) < 2) {
            throw new \Exception('Expected atleast 2 parameters, '. count($parameters) .' given', 1);
        } elseif(!isset($parameters['password']) || strlen($parameters['password']) < SERVER_MIN_PASSWORD_LENGTH) {
            throw new \Exception('Missing parameter (string) "password" with a minimum length of '. SERVER_MIN_PASSWORD_LENGTH, 2);
        } elseif(!isset($parameters['password2']) || $parameters['password'] != $parameters['password2']) {
            throw new \Exception('Missing parameter (string) "password2" which should match parameter (string) "password" with a minimum length of '. SERVER_MIN_PASSWORD_LENGTH, 3);
        } elseif(!\Auth::checkRights('user', \AUTH::WRITE) && (!isset($parameters['currentPassword']) || !$this->checkPassword($parameters['currentPassword']))) {
            throw new \Exception('Missing parameter (string) "currentPassword" which should match the current user\'s password', 4);
        } else {
            $result = TRUE;
        }

        return $result;
    }
}
