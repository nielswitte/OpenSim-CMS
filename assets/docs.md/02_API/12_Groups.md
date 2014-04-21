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