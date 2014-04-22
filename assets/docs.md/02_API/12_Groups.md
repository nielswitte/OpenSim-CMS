Users can be added to groups to share access to specific files.

## Get groups
To retrieve a list of 50 available groups the following request can be made to the API:

```http
GET /api/groups/ HTTP/1.1
```

Or starting at a given offset:

```http
GET /api/groups/<OFFSET>/ HTTP/1.1
```

This will return an array with all groups, containing their ID and name.

```json
[
    {
        "id": 1,
        "name": "The Doe's"
    },
    { (...) },
    (...)
]
```

## Search by group name
To search for a specific group, it is possible to search by using a minimum of 3 characters of the group name.

```http
GET /api/groups/<SEARCH>/ HTTP/1.1
```

## Specific group
A specific group can be retrieved with its full details by using the following API URL:

```http
GET /api/group/<GROUP-ID>/ HTTP/1.1
```

This will return the following JSON result:

```json
{
    "id": "1",
    "name": "The Does",
    "files": [
        {
            "id": 133,
            "type": "presentation",
            "user": {
                "id": 3,
                "username": "johndoe",
                "firstName": "John",
                "lastName": "Doe",
                "email": "john@doe.com",
                "picture": false,
                "lastLogin": "2014-04-18 12:42:32"
            },
            "title": "Example Presentation",
            "creationDate": "2014-04-14 15:55:31",
            "modificationDate": "2014-04-14 15:55:31",
            "sourceFile": "source.pdf",
            "url": "http://localhost:80/OpenSim-CMS/api/presentation/133/"
        }
    ],
    "users": [
        {
            "id": 3,
            "username": "johndoe",
            "firstName": "John",
            "lastName": "Doe",
            "email": "john@doe.com",
            "picture": false,
            "lastLogin": "2014-04-18 12:42:32"
        },
        {
            "id": 4,
            "username": "janedoe",
            "firstName": "Jane",
            "lastName": "Doe",
            "email": "jane@doe.com",
            "picture": "http://localhost:80/OpenSim-CMS/api/user/4/picture/",
            "lastLogin": "2014-04-17 11:21:21"
        }
    ]
}
```

## Create group
Updating, creating and deleting groups requires at least `WRITE` permission to the User API.
Adding files to a group can be done with less permissions by going to the file you want to add and share it with the group.

```http
POST /api/group/ HTTP/1.1
```

| Parameter         | Type      | Description                                                       |
|-------------------|-----------|-------------------------------------------------------------------|
| name              | string    | The group's name                                                  |

This will return, when all went right, the following JSON:

```json
{
    "success": true,
    "groupId": 2
}
```

## Update group
Changing information about the group can be done by using the following API function:

```http
PUT /api/group/<GROUP-ID>/ HTTP/1.1
```

| Parameter         | Type      | Description                                                       |
|-------------------|-----------|-------------------------------------------------------------------|
| name              | string    | The group's name                                                  |
| files             | array     | [Optional] List with file IDs or file objects                     |
| users             | array     | [Optional] List with user IDs or User objects                     |

## Delete group
Removing a group can cause users to lose access to certain files. Please be careful with this.

```http
DELETE /api/group/<GROUP-ID>/ HTTP/1.1
```