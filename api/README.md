OpenSim-CMS API
===============
The OpenSim-CMS communicates with OpenSim objects through an JSON-API, based on REST.
For valid requests the `HTTP/1.1 200 OK` is used, for failures an exception is thrown by
the system and displayed as output with a `HTTP/1.1 400 Bad Request` header.

## Authorization
Before the API can be used, an user needs to authorize himself. This can be done by using the following API:

```http
POST /api/auth/user/ HTTP/1.1
```

| Parameter         | Type      | Description                                                   |
|-------------------|-----------|---------------------------------------------------------------|
| UserName          | String    | The username of the user in the CMS                           |
| password          | String    | The corresponding password of the user in the CMS             |
| ip                | String    | [Optional] The IP address to assign this token to             |

The optional parameter ip, can be used to assign a token to a machine that can not perform the auth request
by itself, for example if the CMS is running on localhost, the token is for the user of the CMS, not the CMS.

This request will return, on succes the following JSON:

```json
{
    "token": "53048c5375b1d2.66536292",
    "ip": "192.168.1.102",
    "expires": "2014-02-19 12:19:55",
    "userId": 1
}
```

The validity of the token depends on the config settings. The user OpenSim with user ID -1 can only accessed from the
IP set in the config which is used by OpenSim. In addition the `HTTP_X_SECONDLIFE_SHARD` header needs to be set, which
is done by default by OpenSim.

## User
User information can be accessed by using, the UUID of the user is based on the user's UUID in OpenSim:

```http
GET /api/user/<UUID>/ HTTP/1.1
```
Or request an user by its ID:

```http
GET /api/user/<ID>/ HTTP/1.1
```

Example of output

```json
{
    "id": 1,
    "uuid": "0a1811f4-7174-4e42-8bb5-26ef78335407",
    "userName": "testuser",
    "firstName": "Test",
    "lastName": "User",
    "email": "testuser@email.com",
    "presentationIds": [
        "1",
        "5",
        "8"
    ]
}
```

When OpenSim uses a MySQL database and the CMS is configered correctly, the following additional information is available
when requesting a user. This is only shown when `OS_DB_ENABLED = TRUE`.

```json
{
    "online": 1,
    "lastLogin": "2014-02-17 13:39:28",
    "lastPosition": "<123.6372, 124.9078, 26.18366>",
    "lastRegionUuid": "72efcc78-2b1a-4571-8704-fea352998c0c"
}
```

### Search for users by userName
To search for a specific user by his or her username, the following API can be used.
Atleast 3 chars are required.

```http
GET /api/user/<SEARCH>/ HTTP/1.1
```

The result is similar to the request by UUID, but displayed as a list ordered by username.

```json
{
    "1": {
        "id": 1,
        "uuid": "0a1811f4-7174-4e42-8bb5-26ef78335407",
        "userName": "testuser",
        "firstName": "Test",
        "lastName": "User",
        "email": "testuser@email.com",
        "presentationIds": [
            "1",
            "5",
            "8"
        ],
        "online": 1,
        "lastLogin": "2014-02-17 13:39:28",
        "lastPosition": "<123.6372, 124.9078, 26.18366>",
        "lastRegionUuid": "72efcc78-2b1a-4571-8704-fea352998c0c"
    },
    "2": { (...) },
    "3": { (...) },
    (...)
}

```

### Update a user's UUID
To match an UUID of a user to the user in the CMS the following command can be used.
Some form of authentication will be added later on. By sending a PUT request to the server with the CMS
username as parameter and use the UUID of the user in OpenSim as URL parameter.

```http
PUT /api/user/<UUID>/ HTTP/1.1
```

| Parameter         | Type      | Description                                                   |
|-------------------|-----------|---------------------------------------------------------------|
| UserName          | String    | The username of the user in the CMS                           |

### Create a new avatar
To create a new avatar the following API url can be used with a POST request.

```http
POST /api/user/avatar/ HTTP/1.1
```

The parameters that can be used are the following:

| Parameter         | Type      | Description                                                   |
|-------------------|-----------|---------------------------------------------------------------|
| firstName         | string    | agent's first name                                            |
| lastName          | string    | agent's last name                                             |
| email             | string    | agent's email address                                         |
| password          | string    | agent's password (plain text)                                 |
| startRegionX      | integer   | [Optional] X-coordinate of the start region (default: 0)      |
| startRegionY      | integer   | [Optional] Y-coordinate of the start region (default: 0)      |

This request will return a JSON message with the result. It contains two or three elements.
1) success, a boolean wheter or not the request was processed successful. 2) Optional, only used when
the request is not successful. 3) the UUID of the newly created user, which is filled with zeros on
failure. Two examples of output are listed below, first a successful request,
second a failure because the user's first and lastname were already used.

```json
{
    "success": true,
    "avatar_uuid": "44172f17-b7a8-4b30-a42e-9699b563789b"
}
```

```json
{
    "success": false,
    "error": "failed to create new user <FirstName> <LastName>",
    "avatar_uuid": "00000000-0000-0000-0000-000000000000"
}
```

### Teleport a user to a location
To teleport a user you need at least the UUID of the user.
All other parameters are optional and listed in the table below.

```http
PUT /api/user/<UUID>/teleport/ HTTP/1.1
```

| Parameter         | Type      | Description                                                   |
|-------------------|-----------|---------------------------------------------------------------|
| regionName        | string    | [Optional] The name of the region (default from config.php)   |
| firstName         | string    | [Optional] agent's first name                                 |
| lastName          | string    | [Optional] agent's last name                                  |
| posX              | float     | [Optional] X-coordinate to teleport to (default: 128)         |
| posY              | float     | [Optional] Y-coordinate to teleport to (default: 128)         |
| posX              | float     | [Optional] Z-coordinate to teleport to (default: 25)          |
| lookatX           | float     | [Optional] X-coordinate to look at (default: 0)               |
| lookatY           | float     | [Optional] Y-coordinate to look at (default: 0)               |
| lookatZ           | float     | [Optional] Z-coordinate to look at (default: 0)               |

this will return on success:

```json
{
    "success": true
}
```

and on failure it will provide an error message, for example when the agent's uuid is not found or the user is offline:

```json
{
    "success": false,
    "error": "No agent with agent_id 44172f17-b7a8-4b30-a42e-9698b563789b found in this simulator"
}
```

## Presentations
A list with presentations can be requested by using the following GET request.

```http
GET /api/presentations/ HTTP/1.1
```

This will return the first 50 presentations. To request the next 50, add the offset as a parameter.
The following example will return the presentations from 51 to 100.

```http
GET /api/presentations/50/ HTTP/1.1
```

Example of the output will be similar to the request of a single presentation, only in a list form.

```json
{
    "1": {
        "type": "presentation",
        "title": "Test presentation title",
        "presentationId": "1",
        "ownerId": 1,
        "slides": {
            "1": {
                "number": 1,
                "image": "http://localhost:80/OpenSim-CMS/api/presentation/1/slide/1/image/",
                "uuid": "1be74003-2d7c-4dbd-87c2-a1c95e0864e6",
                "uuidUpdated": "2014-02-13 14:55:27",
                "uuidExpired": 0
            },
            "2": {
                (...)
            },
            (...)
        },
        "slidesCount": 14,
        "creationDate": "2014-02-13 14:21:47",
        "modificationDate": "2014-02-13 14:22:09"
    },
    "2": { (...) },
    "3": { (...) },
    (...)
}
```

### Specific presentation
To retrieve a specific presentation use the following command and replace the id with the number of the
presentation you want to get. The trailing / is optional.

```http
GET /api/presentation/<ID>/ HTTP/1.1
```

Example of output when request is successful:

```json
{
    "type": "presentation",
    "title": "Test presentation title",
    "presentationId": "1",
    "ownerId": 1,
    "slides": {
        "1": {
            "number": 1,
            "image": "http://localhost:80/OpenSim-CMS/api/presentation/1/slide/1/image/",
            "uuid": "1be74003-2d7c-4dbd-87c2-a1c95e0864e6",
            "uuidUpdated": "2014-02-13 14:55:27",
            "uuidExpired": 0
        },
        "2": {
            (...)
        },
        (...)
    },
    "slidesCount": 14,
    "creationDate": "2014-02-13 14:21:47",
    "modificationDate": "2014-02-13 14:22:09"
}
```
The UUID is matched to the UUID generated by OpenSim when the slide is accessed, to enable caching of textures.
This is done by using the PUT function for a single slide (see below).

The slide details for just one specific slide can be accessed through:

```http
GET /api/presentation/<ID>/slide/<SLIDE#>/ HTTP/1.1
```

The given image url will provide a jpg of the slide resized and centered at 1024x1024 with a black background.

```http
GET /api/presentation/<ID>/slide/<SLIDE#>/image/ HTTP/1.1
```

When an slide has been processed by OpenSim an UUID is generated for the texture, this UUID can be stored with
the slide to speed up future use. The cache periode is set in the `OpenSim.ini` configuration and needs to be
matched by the `OS_ASSET_CACHE_EXPIRES` value in `config.php`.

```http
PUT /api/presentation/<ID>/slide/<SLIDE#>/ HTTP/1.1
```

| Parameter         | Type      | Description                   |
|-------------------|-----------|-------------------------------|
| uuid              | string    | UUID of the slide to be saved |

## Region
To retrieve information about a region the following API can be used.

```http
GET /api/region/<REGION-UUID>/ HTTP/1.1
```

This will return some basic information about the region, such as the name and a thumbnail.
Most of the information is only available if OpenSim and the webserver run on the same device
or if the MySQL database of OpenSim accepts remote connections.

```json
{
    "uuid": "72efcc78-2b1a-4571-8704-fea352998c0c",
    "name": "The Grid",
    "image": "http://localhost:80/OpenSim-CMS/api/72efcc78-2b1a-4571-8704-fea352998c0c/image/",
    "serverStatus": 1
}
```

When `OS_DB_ENABLED` is `TRUE`, the following additional information is shown:

```json
{
    "totalUsers": 2,
    "activeUsers": 1
}
```

### Region image
A small map preview can be opened by using the following API request

```http
GET /api/region/<REGION-UUID>/image/ HTTP/1.1
```
This will return a 256x256 JPEG preview of the region map.