OpenSim-CMS API
===============
The OpenSim-CMS communicates with OpenSim objects through an JSON-API, based on REST.
For valid requests the `HTTP/1.1 200 OK` is used, for failures an exception is thrown by
the system and displayed as output with a `HTTP/1.1 400 Bad Request` header.

## User

User information can be accessed by using, the UUID of the user is based on the user's UUID in OpenSim:

```http
GET /api/user/<UUID>/ HTTP/1.1
```

Example of output

```json
{
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

### Update a user's UUID

To match an UUID of a user to the user in the CMS the following command can be used.
Some form of authentication will be added later on. By sending a POST request to the server with the CMS
username as parameter and the UUID of the user in OpenSim.

```http
POST /api/user/<UUID>/ HTTP/1.1

userName=<USERNAME>
```

### Create a new avatar

To create a new avatar the following API url can be used with a PUT request.
The data is example data from a WebKit PUT request (without the headers)

```http
PUT /api/user/ HTTP/1.1

------WebKitFormBoundaryDLxtrbcYE4nDTSu1
Content-Disposition: form-data; name="firstName"

<FirstName>
------WebKitFormBoundaryDLxtrbcYE4nDTSu1
Content-Disposition: form-data; name="lastName"

<LastName>
------WebKitFormBoundaryDLxtrbcYE4nDTSu1
Content-Disposition: form-data; name="password"

<UserPassword>
------WebKitFormBoundaryDLxtrbcYE4nDTSu1
Content-Disposition: form-data; name="email"

<User@Email.Address>
------WebKitFormBoundaryDLxtrbcYE4nDTSu1
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
POST /api/user/<UUID>/teleport/ HTTP/1.1
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

## Presentation

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
    "ownerUuid": "3fedbbf8-465c-499c-9c2d-3fba9ed61701",
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
This is done by using the POST function for a single slide (see below).

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
POST /api/presentation/<ID>/slide/<SLIDE#>/ HTTP/1.1

uuid=<UUID>
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
    "image": "http:\/\/localhost:80\/OpenSim-CMS\/api\/72efcc78-2b1a-4571-8704-fea352998c0c\/image\/",
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