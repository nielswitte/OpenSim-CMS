To get a list of 50 users, use:

```http
GET /api/users/ HTTP/1.1
```

Or use the following API request with a offset to get more users. For example if the total user base contains 100 users, use offset: 50 to get users 51 to 100;

```http
GET /api/users/<OFFSET>/ HTTP/1.1
```

Both will return a list similar to this:

```json
[
    {
        "id": 4,
        "username": "janedoe",
        "firstName": "Jane",
        "lastName": "Doe",
        "email": "jane@doe.com",
        "picture": "http://localhost:80/OpenSim-CMS/api/user/4/picture/",
        "lastLogin": "2014-04-15 16:00:01"
    },
    {
        "id": 3,
        "username": "johndoe",
        "firstName": "John",
        "lastName": "Doe",
        "email": "john@doe.com",
        "picture": false,
        "lastLogin": "2014-04-16 11:17:04"
    },
    (...)
]
```

## Search for users by username
To search for a specific user by his or her username, the following API can be used. At least 3 characters are required.

```http
GET /api/user/<SEARCH>/ HTTP/1.1
```

The output is similar to the user list request, but displayed as a list ordered by username.

```json
{
    {
        "id": 4,
        "username": "janedoe",
        "firstName": "Jane",
        "lastName": "Doe",
        "email": "jane@doe.com",
        "picture": "http://localhost:80/OpenSim-CMS/api/user/4/picture/",
        "lastLogin": "2014-04-15 16:00:01"
    },
    { (...) },
    { (...) },
    (...)
}
```

## Individual user
User information can be accessed by using, the UUID of the user's avatar in OpenSim on the given Grid:

```http
GET /api/grid/<GRID-ID>/avatar/<UUID>/ HTTP/1.1
```

Or request an user by its ID in the CMS:

```http
GET /api/user/<ID>/ HTTP/1.1
```

Example of output

```json
{
    "id": 4,
    "username": "janedoe",
    "firstName": "Jane",
    "lastName": "Doe",
    "email": "jane@doe.com",
    "picture": "http://localhost:80/OpenSim-CMS/api/user/4/picture/",
    "lastLogin": "2014-04-10 07:34:44",
    "permissions": {
        "auth": 4,
        "chat": 4,
        "comment": 5,
        "document": 5,
        "grid": 4,
        "file": 5,
        "meeting": 5,
        "meetingroom": 4,
        "presentation": 5,
        "user": 5
    },
    "avatars": [
        {
            "uuid": "6c7d2c8f-b9b9-43e9-9ce3-8d56232b51c9",
            "firstName": "Jane",
            "lastName": "Doe",
            "email": "jane@doe.com",
            "gridId": 1,
            "gridName": "OpenSim's test grid",
            "confirmed": 1
        }
    ]
}
```

When OpenSim uses a MySQL database and the CMS is configured correctly, the following additional information is available about the avatars of the user. For each avatar the following information is shown below `gridName`:

```json
{
    "online": 1,
    "lastLogin": "2014-02-17 13:39:28",
    "lastPosition": "<123.6372, 124.9078, 26.18366>",
    "lastRegionUuid": "72efcc78-2b1a-4571-8704-fea352998c0c"
}
```

## Create user
To add a new user to the CMS user the following API URL with the parameters as described in the table below. The emailaddress and username need to be unique.


```http
POST /api/user/ HTTP/1.1
```

| Parameter         | Type      | Description                                                       |
|-------------------|-----------|-------------------------------------------------------------------|
| username          | String    | The username of the new user                                      |
| email             | String    | The email address for the new user                                |
| firstName         | String    | The first name of the new user                                    |
| lastName          | String    | The last name of the new user                                     |
| password          | String    | The new user's password                                           |
| password2         | String    | The new user's password again, to check if no typo has been made  |

The result of a successful request to create an user will be as follows:

```json
{
    "success": true,
    "userId": <NEW-USER-ID>
}
```

## Update user
Change some information about the user. The username and id are locked. The password is a separate function.

```http
PUT /api/user/<USER-ID>/ HTTP/1.1
```
| Parameter         | Type      | Description                                                       |
|-------------------|-----------|-------------------------------------------------------------------|
| email             | String    | The email address for the new user                                |
| firstName         | String    | The first name of the new user                                    |
| lastName          | String    | The last name of the new user                                     |
| permissions       | Array     | The permission levels for the user (see permissions)              |

## Change profile picture
Attach a profile picture to the given user, which will be resized to 250x250 pixels and displayed in the CMS for example next to comments and on the user's profile page. When you upload a new picture the previous picture will be overwritten.

```http
PUT /api/user/<USER-ID>/picture/ HTTP/1.1
```

| Parameter         | Type      | Description                                                                               |
|-------------------|-----------|-------------------------------------------------------------------------------------------|
| image             | file      | Base64 encoded file, needs to be jpeg, jpg, png or gif                                    |

## Change password
Use the following function to update the user's password. You can update passwords of other users when you at least have `WRITE` permissions or when you know the `currentPassword`.

```http
PUT /api/user/<USER-ID>/password/ HTTP/1.1
```

| Parameter         | Type      | Description                                                                               |
|-------------------|-----------|-------------------------------------------------------------------------------------------|
| password          | String    | The user's new password                                                                   |
| password2         | String    | The user's new password again, to check if no typo has been made                          |
| currentPassword   | String    | [Optional] The user's current password, only required when no WRITE permissions for users |

## Delete user
This will remove an user from the CMS, however it is not recommended to use. All files, comments and meetings are attached to an user, removing the user can cause items to disappear. The recommended solution is to set all permissions to `NONE` for an user.

```http
DELETE /api/user/<USER-ID>/ HTTP/1.1
```
