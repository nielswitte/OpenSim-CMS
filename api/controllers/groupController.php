<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the group controller
 *
 * @author Niels Witte
 * @version 0.3
 * @date May 12, 2014
 * @since April 22, 2014
 */
class GroupController {
    /**
     * The group to control
     * @var \Models\Group
     */
    private $group;

    /**
     * Constructs a new controller for the given group
     *
     * @param \Models\Group $group
     */
    public function __construct(\Models\Group $group = NULL) {
        $this->group = $group;
    }

    /**
     * Insert the given group name to the groups database
     *
     * @param array $parameters
     *          * string name - The group name
     * @return integer with the GroupID or boolean FALSE when failed
     */
    public function createGroup($parameters) {
        $db = \Helper::getDB();
        $data = array('name' => $db->escape(\Helper::filterString($parameters['name'], TRUE)));
        return $db->insert('groups', $data);
    }

    /**
     * Validates the create parameters for creating a group
     *
     * @param array $parameters
     * @return boolean
     * @throws \Exception
     */
    public function validateParametersCreate($parameters) {
        $result = FALSE;
        if(!isset($parameters['name']) || strlen($parameters['name']) < 1) {
            throw new \Exception('Missing parameter (string) "name" with at least one character content', 1);
        } else {
            $result = TRUE;
        }
        return $result;
    }

    /**
     * Updates the group, by updating the name, the users and the files
     *
     * @param array $parameters
     * @return boolean
     * @throws \Exception
     */
    public function updateGroup($parameters) {
        // Update group users
        $users = FALSE;
        if(isset($parameters['users'])) {
            $users = $this->updateGroupUsers($parameters['users']);
        }
        // Update group files
        $files = FALSE;
        if(isset($parameters['files'])) {
            $files = $this->updateGroupFiles($parameters['files']);
        }

        // Group name
        $db     = \Helper::getDB();
        $data   = array(
            'name'  => $db->escape(\Helper::filterString($parameters['name'], TRUE))
        );
        $db->where('id', $db->escape($this->group->getId()));
        $name = $db->update('groups', $data);

        // Were any updates made?
        if($name || $users || $files) {
            return TRUE;
        } else {
            throw new \Exception('No changes were made to this group', 2);
        }
    }

    /**
     * Processes the array with users and removes and
     * adds users to the group
     *
     * @param array $users
     * @return boolean - TRUE if something updated
     */
    public function updateGroupUsers($users) {
        $userIds = array();
        // Get new group Ids
        foreach($users as $user) {
            if(is_array($user) && isset($user['id'])) {
                $userIds[] = $user['id'];
            } else {
                $userIds[] = $user;
            }
        }
        // Get the old group Ids
        $this->group->getGroupUsersFromDatabase();
        $oldUsers      = $this->group->getUsers();
        $oldUserIds    = array();
        foreach($oldUsers as $user) {
            $oldUserIds[] = $user->getId();
        }

        // Ids to remove
        $removeIds  = array_diff($oldUserIds, $userIds);
        $remove     = $this->removeUserIds($removeIds);
        $addIds     = array_diff($userIds, $oldUserIds);
        $add        = $this->addUserIds($addIds);

        // Something updated?
        return $remove || $add;
    }

    /**
     * Adds the user with an ID that is in the array to the group
     *
     * @param array $ids - List with user IDs
     * @return boolean
     */
    public function addUserIds($ids) {
        $db     = \Helper::getDB();
        $result = FALSE;
        foreach($ids as $id) {
            $data = array(
                'userId'    => $db->escape($id),
                'groupId'   => $db->escape($this->group->getId())
            );
            $result = $db->insert('group_users', $data);
        }

        return $result;
    }

    /**
     * Removes the user in the array with IDs from the group
     *
     * @param array $ids - List with user IDs
     * @return boolean
     */
    public function removeUserIds($ids) {
        $db     = \Helper::getDB();
        $result = FALSE;
        foreach($ids as $id) {
            $db->where('userId', $db->escape($id));
            $db->where('groupId', $db->escape($this->group->getId()));
            $result = $db->delete('group_users');
        }
        return $result;
    }


    /**
     * Processes the array with files and removes and
     * adds files to the group
     *
     * @param array $files
     * @return boolean - TRUE if something updated
     */
    public function updateGroupFiles($files) {
        $fileIds = array();
        // Get new group Ids
        foreach($files as $file) {
            if(is_array($file) && isset($file['id'])) {
                $fileIds[] = $file['id'];
            } else {
                $fileIds[] = $file;
            }
        }
        // Get the old group Ids
        $this->group->getGroupFilesFromDatabase();
        $oldFiles      = $this->group->getFiles();
        $oldFileIds    = array();
        foreach($oldFiles as $file) {
            $oldFileIds[] = $file->getId();
        }

        // Ids to remove
        $removeIds  = array_diff($oldFileIds, $fileIds);
        $remove     = $this->removeFileIds($removeIds);
        $addIds     = array_diff($fileIds, $oldFileIds);
        $add        = $this->addFileIds($addIds);

        // Something updated?
        return $remove || $add;
    }

    /**
     * Adds the file with an ID that is in the array to the group
     * You can only add files to which you have access!
     *
     * @param array $ids - List with file IDs
     * @return boolean
     */
    public function addFileIds($ids) {
        $db     = \Helper::getDB();
        $result = FALSE;

        // Process all files the user wants to add
        foreach($ids as $id) {
            // Only allow adding files to which the user has access to
            if(\Auth::checkUserFiles($id) || \Auth::checkGroupFile($id)) {
                $data = array(
                    'documentId'    => $db->escape($id),
                    'groupId'       => $db->escape($this->group->getId())
                );
                $result = $db->insert('group_documents', $data);
            }
        }

        return $result;
    }

    /**
     * Removes the file in the array with IDs from the group
     *
     * @param array $ids - List with file IDs
     * @return boolean
     */
    public function removeFileIds($ids) {
        $db     = \Helper::getDB();
        $result = FALSE;
        foreach($ids as $id) {
            $db->where('documentId', $db->escape($id));
            $db->where('groupId', $db->escape($this->group->getId()));
            $result = $db->delete('group_documents');
        }
        return $result;
    }

    /**
     * Validates the parameters for updating a group
     *
     * @param array $parameters
     * @return boolean
     * @throws \Exception
     */
    public function validateParametersUpdate($parameters) {
        $result = FALSE;
        if(!isset($parameters['name']) || strlen($parameters['name']) < 3) {
            throw new \Exception('Missing parameter (string) "name", with a minimum length of 3 character', 1);
        } elseif(isset($parameters['users']) && !is_array($parameters['users']) && (!empty($parameters['users']) || !isset($parameters['users'][0]))) {
            throw new \Exception('Optional parameter "users" should be an array');
        } elseif(isset($parameters['groups']) && !is_array($parameters['groups']) && (!empty($parameters['groups']) || !isset($parameters['groups'][0]))) {
            throw new \Exception('Optional parameter "groups" should be an array');
        } else {
            $result = TRUE;
        }
        return $result;
    }

    /**
     * Remove this group
     *
     * @return boolean
     */
    public function deleteGroup() {
        $db = \Helper::getDB();
        $db->where('id', $db->escape($this->group->getId()));
        return $db->delete('groups');
    }
}