The users overview shows all the users in the CMS. Everyone with `READ` permissions can see all users and their details. However, you can only edit your own basic user information. To edit user information of other users or create new users requires `WRITE` permission.

## User details
When viewing the user details you can see the basic information, permissions, groups the user is in and the avatars associated to this user account.

To be able to add a new avatar requires `EXECUTE` permissions. When creating a new avatar be careful with special characters in the first name, last name or password of the avatar. It is still unclear what is going wrong exactly, but it has something to do with escaping the data when sending XML to the OpenSim grid and unescaping the XML contents by OpenSim. See the [Avatar section](../API/Avatars.html) of the [User API](../API/Users.html) for more information.

## Edit user
You can edit your own basic user information. The `username` can not be changed and, just like all other objects in the CMS, the `ID` is also fixed. Permissions can only be modified by users who have `WRITE` permissions to the User API. Even when you have `WRITE` permissions you can not set every permission you want. It is only possible to set permissions to `ALL` for APIs you have `ALL` access to. For example if your user account has `ALL` access to the Files API and `WRITE` permissions to the User API, you can give other users `ALL` permission to the File API, but not to other APIs. The other APIs are limited to grant maximal `WRITE` permissions.

### Profile picture
From the user details you can select a profile picture for the user. This picture is displayed next to comments to make it easier to see who said what.

### Password
Users can change their password or the password of others by using the `Change password` button. When you have `WRITE` permissions you can change the passwords of others, if you do not have `WRITE` permission, you can change your own password and for changing the password of others you need to enter the current password of the user as well.

## New user
With `WRITE` permission to the User API you have the option to create new users. The new user form includes a password field. This field can be left empty, which will trigger the server to generate a random password. The newly created user will receive an e-mail message with the username and password.

After creating a new user, be sure to check the user's permissions to see if they are all set correctly to allow the user to perform his/her tasks.

# Groups
Users can be added to groups to share documents with the other group members.

## Manage groups
Every user with `WRITE` permissions on the User API can create new groups and change existing groups. A new group only requires a name. Adding users and documents to a group can be done after the group is created by editing the group, at the document details by sharing a document with a group, or when editing a user and updating the user's groups.
