# OpenSim-CMS API

The OpenSim-CMS communicates with OpenSim objects through an JSON-API, based on REST.
For valid requests the `HTTP/1.1 200 OK` is used, for failures an exception is thrown by
the system and displayed as output with a `HTTP/1.1 400 Bad Request` header. For most functions
the user needs to be authorized, if the user is not authorized but should be, a `HTTP/1.1 401 Unauthorized`
header is used.

## Request handling
@todo create index

### POST, PUT and DELETE

All `POST`, `PUT` and `DELETE` request will atleast return the following result when successfully processed:

```json
{
    "success": true
}
```

### Error messages
When the configuration value `SERVER_DEBUG` is set to `FALSE`, bad and unauthorized requests will provide,
beside the corresponding HTTP header, an exception message in JSON. For example the following message
is displayed when attempting to access a protected function without a valid API token.

```json
{
    "success": false,
    "error": "Unauthorized to access this API URL"
}
```

When `SERVER_DEBUG` is set to `TRUE`, additional information will be displayed. Including the
file, line and stack trace of the error. It is recommended to disable debugging for public API servers.

```json
{
    "success": false,
    "error": "Unauthorized to access this API URL"
    "Code": 0,
    "File": "/OpenSim-CMS/api/index.php",
    "Line": 62,
    "Trace": [

    ]
}
```


## Authorization
Before the API can be used, an user needs to authorize himself. This can be done by using the following API:

```http
POST /api/auth/username/ HTTP/1.1
```

| Parameter         | Type      | Description                                                   |
|-------------------|-----------|---------------------------------------------------------------|
| username          | String    | The username of the user in the CMS                           |
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
    "userId": 1,
    "lastLogin": "2014-02-16 15:32:14"
}
```

The validity of the token depends on the config settings and is extended everytime the token is used.
The user OpenSim with user ID -1 can only accessed from the IP/Hostname which is used by OpenSim according
to the grid list. In addition the `X-SecondLife-Shard` header needs to be set to access this user, this is
done by default for OpenSim.

### Permissions

For each API there are permissions for the user account. The user requires a specific level for accessing certain
functions. The levels are represented by integers that represent a binary permission.

| Level         | Binary    | Integer |
|---------------|-----------|---------|
| NONE          | 000       | 0       |
| READ          | 100       | 4       |
| EXECUTE       | 101       | 5       |
| WRITE         | 110       | 6       |
| ALL           | 111       | 7       |

These numbers can be used for the following parameters:

| Parameter         | Type      | Description                                                   |
|-------------------|-----------|---------------------------------------------------------------|
| auth              | Integer   | Permission level regarding Authorization API                  |
| chat              | Integer   | Permission level regarding Chats API                          |
| comment           | Integer   | Permission level regarding Comments API                       |
| document          | Integer   | Permission level regarding Documents API                      |
| file              | Integer   | Permission level regarding Files API                          |
| grid              | Integer   | Permission level regarding Grids API                          |
| meeting           | Integer   | Permission level regarding Meetings API                       |
| meetingroom       | Integer   | Permission level regarding Meeting rooms API                  |
| presentation      | Integer   | Permission level regarding Presentations API                  |
| user              | Integer   | Permission level regarding Users API                          |

## Users
To get a list of 50 users, use:

```http
GET /api/users/ HTTP/1.1
```

Or use the following API request with a offset to get more users. For example if the total user base contains 100 users,
use offset: 50 to get users 51 to 100;

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
        "picture": "http://localhost:80/OpenSim-CMS/api/user/4/picture/"
    },
    {
        "id": 3,
        "username": "johndoe",
        "firstName": "John",
        "lastName": "Doe",
        "email": "john@doe.com",
        "picture": false
    },
    (...)
]
```

### Search for users by username
To search for a specific user by his or her username, the following API can be used.
At least 3 characters are required.

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
        "picture": "http://localhost:80/OpenSim-CMS/api/user/4/picture/"
    },
    { (...) },
    { (...) },
    (...)
}
```

### Individual user
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

When OpenSim uses a MySQL database and the CMS is configured correctly, the following additional information is available
about the avatars of the user. For each avatar the following information is shown below `gridName`:

```json
{
    "online": 1,
    "lastLogin": "2014-02-17 13:39:28",
    "lastPosition": "<123.6372, 124.9078, 26.18366>",
    "lastRegionUuid": "72efcc78-2b1a-4571-8704-fea352998c0c"
}
```

### Create user
To add a new user to the CMS user the following API URL with the parameters as described in the table below.
The emailaddress and username need to be unique.


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

### Update user
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

### Change profile picture
Attach a profile picture to the given user, which will be resized to 250x250 pixels and displayed in the CMS for example next to comments and on the user's profile page.
When you upload a new picture the previous picture will be overwritten.

```http
PUT /api/user/<USER-ID>/picture/ HTTP/1.1
```

| Parameter         | Type      | Description                                                                               |
|-------------------|-----------|-------------------------------------------------------------------------------------------|
| image             | file      | Base64 encoded file, needs to be jpeg, jpg, png or gif                                    |

### Change password
Use the following function to update the user's password. You can update passwords of other users when you at least have
`WRITE` permissions or when you know the `currentPassword`.

```http
PUT /api/user/<USER-ID>/password/ HTTP/1.1
```

| Parameter         | Type      | Description                                                                               |
|-------------------|-----------|-------------------------------------------------------------------------------------------|
| password          | String    | The user's new password                                                                   |
| password2         | String    | The user's new password again, to check if no typo has been made                          |
| currentPassword   | String    | [Optional] The user's current password, only required when no WRITE permissions for users |

### Delete user
This will remove an user from the CMS, however it is not recommended to use. All files, comments and meetings are attached to an user,
removing the user can cause items to disappear. The recommended solution is to set all permissions to `NONE` for an user.

```http
DELETE /api/user/<USER-ID>/ HTTP/1.1
```

## Avatars
The CMS user can not be used in the OpenSim environment. OpenSim works with Avatars, which are independent of the CMS.
However, Avatars can be linked to an OpenSim-CMS user, to access data from a specific user.

### Link avatar to user
To match an UUID of a user to the user in the CMS the following command can be used.
Some form of authentication will be added later on. By sending a PUT request to the server with the CMS
username as parameter and use the UUID of the user in OpenSim as URL parameter.

```http
POST /api/grid/<GRID-ID>/avatar/<UUID>/ HTTP/1.1
```

| Parameter         | Type      | Description                                                   |
|-------------------|-----------|---------------------------------------------------------------|
| username          | String    | The username of the user in the CMS                           |


### Confirm avatar
Once an avatar is linked to a user account, it needs to be confirmed by the user. This can only be done
by the user himself.

```http
PUT /api/grid/<GRID-ID>/avatar/<UUID>/ HTTP/1.1
```

Because the token which is used for this request is matched to the userId, this will provide the additional
required information. Therefore no parameters are needed for this request.

This request will also return `"success": false` when the avatar is already confirmed.

### Unlink an avatar
This unlinks an avatar from the user. Just like confirming, this can only be performed by the user account associated with
the link or when you have `WRITE` permissions for the `user` section. Please not that this will not remove the Avatar from OpenSim.

```http
DELETE /api/grid/<GRID-ID>/avatar/<UUID>/confirm/ HTTP/1.1
```

### Create a new avatar
To create a new avatar on the given Grid the following API url can be used with a POST request.

```http
POST /api/grid/<GRID-ID>/avatar/ HTTP/1.1
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

*WARNING* This way of avatar creation does not support special chars in the password. The following characters
can not be used in a password: ` ?{}<>;" '[]/\ `. This will result in not being able to login in OpenSim.

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
PUT /api/grid/<GRID-ID>/avatar/<UUID>/teleport/ HTTP/1.1
```

| Parameter         | Type      | Description                                                   |
|-------------------|-----------|---------------------------------------------------------------|
| regionName        | string    | [Optional] The name of the region (default from the region)   |
| firstName         | string    | [Optional] agent's first name                                 |
| lastName          | string    | [Optional] agent's last name                                  |
| posX              | float     | [Optional] X-coordinate to teleport to (default: 128)         |
| posY              | float     | [Optional] Y-coordinate to teleport to (default: 128)         |
| posX              | float     | [Optional] Z-coordinate to teleport to (default: 25)          |
| lookatX           | float     | [Optional] X-coordinate to look at (default: 0)               |
| lookatY           | float     | [Optional] Y-coordinate to look at (default: 0)               |
| lookatZ           | float     | [Optional] Z-coordinate to look at (default: 0)               |

This will return on success:

```json
{
    "success": true
}
```

And on failure it will provide an error message, for example when the agent's uuid is not found or the user is offline:

```json
{
    "success": false,
    "error": "No agent with agent_id 44172f17-b7a8-4b30-a42e-9698b563789b found in this simulator"
}
```

## Chats

Through the CMS it is possible to chat with users on the Grid. The following line will return the latest
50 chat messages on the given grid.

```http
GET /api/grid/<GRID-ID>/chats/ HTTP/1.1
```

Or get all messages since a given unix timestamp in seconds

```http
GET /api/grid/<GRID-ID>/chats/<UNIX-TIMESTAMP>
```

This will return a list with chat messages and their sender. The `fromCMS` value is used to indicate if the message
was sent from the chat in the CMS (1) or was captured in the OpenSim environment (0).

```json
[
    {
        "id": 1,
        "user": {
            "id": 3,
            "username": "johndoe",
            "firstName": "John",
            "lastName": "Doe",
            "email": "john@doe.com",
            "picture": false
        },
        "message": "Hi Jane",
        "timestamp": "2014-04-08 13:15:03",
        "fromCMS": 0
    },
    {
        "id": 2,
        "user": {
            "id": 4,
            "username": "janedoe",
            "firstName": "Jane",
            "lastName": "Doe",
            "email": "jane@doe.com",
            "picture": "http://localhost:80/OpenSim-CMS/api/user/4/picture/",
        },
        "message": "Hello John",
        "timestamp": "2014-04-08 13:15:04",
        "fromCMS": 1
    },
    (...)
]
```

### Add chat
To add a line to the chat you require `WRITE` permissions for the chat section. For OpenSim to add a line to
the chat, the server first needs to match the Avatar to a user. Or when no match can be found, the server could use
0 as ID, and append the message with the Avatar's name.

The chats can be in an array or a just sent as a single element.

```http
POST /api/grid/<GRID-ID/chats/ HTTP/1.1
```
| Parameter         | Type      | Description                                                               |
|-------------------|-----------|---------------------------------------------------------------------------|
| userId            | integer   | The CMS user ID                                                           |
| message           | string    | The message to be saved                                                   |
| timestamp         | string    | The timestamp of the message in the format YYYY-MM-DD HH:mm:ss            |
| fromCMS           | integer   | 1 (True) if the message is from the CMS, 0 (false) if from OpenSim Server |

## Meetings
The most important part of the CMS is the scheduling of meetings.

### List with meetings

The retrieve a list with 50 meetings, use the following function. The meetings are ordered by date in descending order.

```http
GET /api/meetings/ HTTP/1.1
```
To get more than the first 50 meetings, an offset parameter needs to be added to the URL.

```http
GET /api/meetings/<OFFSET>/ HTTP/1.1
```
Or if you only want meetings after a specific date, you can add a date as parameter.

```http
GET /api/meetings/<YYYY-MM-DD>/ HTTP/1.1
```
All above API functions return a similar result, as displayed below:

```json
[
    {
        "id": 41,
        "name": "Example meeting",
        "startDate": "2014-04-08 18:00:00",
        "endDate": "2014-04-08 19:00:00",
        "creator": {
            "id": 3,
            "username": "johndoe",
            "firstName": "John",
            "lastName": "Doe",
            "email": "john@doe.com",
            "picture": false
        },
        "roomId": 4,
        "url": "http://localhost:80/OpenSim-CMS/api/meeting/42/"
    },
    {
        "id": 43,
        "name": "Next day meeting",
        "startDate": "2014-04-09 15:00:00",
        "endDate": "2014-04-09 17:30:00",
        "creator": {
            "id": 4,
            "username": "janedoe",
            "firstName": "jane",
            "lastName": "Doe",
            "email": "jane@doe.com",
            "picture": "http://localhost:80/OpenSim-CMS/api/user/4/picture/"
        },
        "roomId": 4,
        "url": "http://localhost:80/OpenSim-CMS/api/meeting/43/"
    },
    (...)
]

```

### Calendar format
```http
GET /api/meetings/<YYYY-MM-DD>/calendar/ HTTP/1.1
```

```json
[
    {
        "id": 42,
        "start": 1397109600000,
        "end": 1397113200000,
        "url": "http://localhost:80/OpenSim-CMS/api/meeting/42/"
        "class": "event-default",
        "title": "Example meeting (Room: Test room)",
        "description": "Reservation made by: johndoe"
    },
    {
        "id": 43,
        "start": 1397109600000,
        "end": 1397113200000,
        "url": "http://localhost:80/OpenSim-CMS/api/meeting/43/"
        "class": "event-default",
        "title": "Next day meeting (Room: New room)",
        "description": "Reservation made by: janedoe"
    },
    (...)
]

```

### Get a specific meeting
When retrieving a specific meeting you get a lot more details. Information such as the participants, documents and the agenda are included.

```http
GET /api/meeting/<MEETING-ID>/ HTTP/1.1
```

The output will be something like this:
```json
{
    "id": "43",
    "name": "Next day meeting",
    "startDate": "2014-04-09 15:00:00",
    "endDate": "2014-04-09 17:30:00",
    "creator": {
        "id": 4,
        "username": "janedoe",
        "firstName": "Jane",
        "lastName": "Doe",
        "email": "jane@doe.com",
        "picture": "http://localhost:80/OpenSim-CMS/api/user/4/picture/"
    },
    "room": {
        "id": 2,
        "name": "Board room",
        "grid": {
            "id": 1,
            "name": "Niels' test grid",
            "openSim": {
                "protocol": "http",
                "ip": "valentina.no-ip.info",
                "port": 9000
            }
        },
        "region": {
            "name": "Region Name",
            "uuid": "72efcc78-2b1a-4571-8704-fea352998c0c"
        },
        "description": "New room, Just some new room with a description",
        "coordinates": {
            "x": 128,
            "y": 128,
            "z": 25
        }
    },
    "participants": [
        {
            "id": 4,
            "username": "janedoe",
            "firstName": "Jane",
            "lastName": "Doe",
            "email": "jane@doe.com",
            "picture": "http://localhost:80/OpenSim-CMS/api/user/4/picture/"
        },
        {
            "id": 3,
            "username": "johndoe",
            "firstName": "John",
            "lastName": "Doe",
            "email": "john@doe.com",
            "picture": false
        },
        (...)
    ],
    "agenda": "1. Opening\n2. Second Agenda Item\n  2.1. Sub agenda item\n3. Closing",
    "documents": [
        {
            "id": 104,
            "type": "presentation",
            "user": {
                "id": 4,
                "username": "janedoe",
                "firstName": "Jane",
                "lastName": "Doe",
                "email": "jane@doe.com",
                "picture": "http://localhost:80/OpenSim-CMS/api/user/4/picture/"
            },
            "title": "Jane's presentation",
            "creationDate": "2014-04-02 09:04:59",
            "modificationDate": "2014-04-02 09:49:59",
            "sourceFile": "",
            "url": "http://localhost:80/OpenSim-CMS/api/presentation/104/"
        },
        (...)
    ]
}
```

### Create a meeting
For scheduling a new meeting a lot of parameters are required. Only the list with documents is optional.
Please ensure you use the correct parameters or else you will be losing a lot of time debugging.

| Parameter         | Type             | Description                                                      |
|-------------------|------------------|------------------------------------------------------------------|
| name              | string           | The title of the meeting                                         |
| startDate         | string           | The start date time of the meeting (format: YYYY-MM-DD HH:mm:ss) |
| endDate           | string           | The end date time of the meeting (format: YYYY-MM-DD HH:mm:ss)   |
| room              | integer or array | Room ID, can be submitted as an integer or as a json array `[{id: 1, (...)}, {id: 2, (...)}, (...) }]` |
| agenda            | string           | A string with each agenda item on a new line and starting with a number and separated by a space, the topic. For example `1. Opening\n2. Minutes\n2.1 Questions\n3. (...)`   |
| participants      | array            | A array with user IDs. Can be an array `{1, 2, 3, 4, (...)}` or a json array with users that contain an id field  `[{id: 1, (...)}, {id: 2, (...)}, (...) }]`         |
| documents         | array            | [Optional] A array with document IDs. Can be an array `{1, 2, 3, 4, (...)}` or a json array with documents that contain an id field  `[{id: 1, (...)}, {id: 2, (...)}, (...) }]` |

When everything went well, the following information is returned:

```json
{
    "success": true,
    "meetingId": <MEETING-ID>
}
```

### Update a meeting

For updating a meeting you can use the following `PUT` request.

```http
PUT /api/meeting/<MEETING-ID>/ HTTP/1.1
```
The parameters are the same as for creating a meeting, see creating a meeting for more details.

### Get the meeting agenda
To return only the agenda for a specific meeting, the following API can be used. This will return the agenda as a multi dimensional array
instead of the flat string in the meeting details response.

```http
GET /api/meeting/<MEETING-ID>/agenda HTTP/1.1
```

This will output something similar to:

```json
[
    {
        "id": 1,
        "value": "Opening",
        "sort": 1,
        "parentId": null
    },
    {
        "id": 2,
        "value": "Testing",
        "sort": 2,
        "parentId": null,
        "items": [
            {
                "id": 3,
                "value": "Sub test",
                "sort": 1,
                "parentId": 2,
                "items": [
                    {
                        "id": 4,
                        "value": "small sub sub test",
                        "sort": 1,
                        "parentId": 3
                    }
                ]
            },
            {
                "id": 5,
                "value": "Additional sub test",
                "sort": 2,
                "parentId": 2
            }
        ]
    },
    {
        "id": 6,
        "value": "Finish testing",
        "sort": 3,
        "parentId": null
    }
]
```
### Get meetings by participant
This will return a list with meetings where the given user is a participant for. The output is similar to the `/api/meetings/` request.

```http
GET /api/user/<USER-ID>/meetings/ HTTP/1.1
```

### Minutes
The `meetingLogger.lsl` script has the ability to log the chat during a meeting. These minutes can be retrieved by using the following API functionality:

```http
GET /api/meeting/<MEETING-ID>/minutes/ HTTP/1.1
```

Just like the chat this can return a really long response. The response includes some basic information about the meeting which can be used to parse the
minutes with more details.

```json
{
    "id": "43",
    "name": "Next day meeting",
    "startDate": "2014-04-09 15:00:00",
    "endDate": "2014-04-09 17:30:00",
    "creator": {
        "id": 4,
        "username": "janedoe",
        "firstName": "Jane",
        "lastName": "Doe",
        "email": "jane@doe.com",
        "picture": "http://localhost:80/OpenSim-CMS/api/user/4/picture/"
    },
    "roomId": 2,
    "url": "http://localhost:80/OpenSim-CMS/api/meeting/43/",
    "agenda": "1. Opening\n2. Second Agenda Item\n  2.1. Sub agenda item\n3. Closing",
    "minutes": [
        {
            "id": 221,
            "timestamp": "2014-04-10 12:49:18",
            "agenda": {
                "id": 1,
                "parentId": null,
                "sort": 1,
                "value": "Opening"
            },
            "uuid": "",
            "name": "Server",
            "message": "At 11:49 starting with agenda item: 1. Opening",
            "user": ""
        },
        {
            "id": 222,
            "timestamp": "2014-04-10 12:49:18",
            "agenda": {
                "id": 1,
                "parentId": null,
                "sort": 1,
                "value": "Opening"
            },
            "uuid": "",
            "name": "Server",
            "message": "Avatar entered the meeting: Jane Doe (6c7d2c8f-b9b9-43e9-9ce3-8d56232b51c9)",
            "user": ""
        },
        (...)
    ]
}

```

#### Add minutes to meeting
Can be a single element or an array of elements

```http
POST /api/meeting/<MEETING-ID>/minutes/ HTTP/1.1
```
| Parameter         | Type             | Description                                                          |
|-------------------|------------------|----------------------------------------------------------------------|
| timestamp         | string           | Timestamp (format: YYYY-MM-DD HH:mm:ss) or Unix timestamp in seconds |
| uuid              | string           | UUID of the avatar that wrote the message                            |
| name              | string           | Name of the avatar or object that wrote the message                  |
| agendaId          | integer          | Integer that corresponds to the current agenda ID of the meeting     |
| message           | string           | The actual message to be stored                                      |

## Meeting Rooms

```http
GET /api/grid/<GRID-ID>/rooms/ HTTP/1.1
```

```http
GET /api/grid/<GRID-ID>/room/<ROOM-ID/ HTTP/1.1
```

```http
GET /api/grid/<GRID-ID>/region/<REGION-UUID>/rooms/ HTTP/1.1
```

## Files

```http
GET /api/files/ HTTP/1.1
```

```http
GET /api/files/<OFFSET>/ HTTP/1.1
```

Search for a files by title, at least 3 characters.
```http
GET /api/files/<SEARCH>/ HTTP/1.1
```

### Add a new file

```http
POST /api/file/ HTTP/1.1
```
| Parameter         | Type      | Description                                                 |
|-------------------|-----------|-------------------------------------------------------------|
| title             | string    | The title of the document                                   |
| type              | string    | The document type                                           |
| file              | file      | Base64 encoded file                                         |

### Remove a file

```http
DELETE /api/file/<FILE-ID>/ HTTP/1.1
```

### Images
The image of file with type `image` can be retrieved by using the following API function

```http
GET /api/file/<FILE-ID>/image/ HTTP/1.1
```

### Source file
Retrieve the original file uploaded before being processed by the API.

```http
GET /api/file/<FILE-ID>/source/ HTTP/1.1
```

Or for presentations this alias could be used:

```http
GET /api/presentation/<PRESENTATION-ID>/source/ HTTP/1.1
```

Or a document

```http
GET /api/document/<DOCUMENT-ID>/source/ HTTP/1.1
```


### Owned by a specific user
It is possible to retrieve a list with documents owned by a specific user. This can be done by
using the user's ID:

```http
GET /api/user/<USER-ID>/files/ HTTP/1.1
```

Or by using an UUID of an avatar linked to the user.

```http
GET /api/grid/<GRID-ID>/avatar/<AVATAR-UUID>/files/ HTTP/1.1
```

### Clear the cache
Removes all expired items from the cache. The functions returns the number of removed
cache items.

```http
DELETE /api/files/cache/ HTTP/1.1
```
## Documents

```http
GET /api/documents/ HTTP/1.1
```

```http
GET /api/documents/<OFFSET>/ HTTP/1.1
```

Search for a document by title, at least 3 characters.
```http
GET /api/documents/<SEARCH>/ HTTP/1.1
```
### Get a single document
```http
GET /api/document/<DOCUMENT-ID>/ HTTP/1.1
```

### Add a new document

```http
POST /api/document/ HTTP/1.1
```
| Parameter         | Type      | Description                                                 |
|-------------------|-----------|-------------------------------------------------------------|
| title             | string    | The title of the document                                   |
| type              | string    | The document type                                           |
| file              | file      | Base64 encoded file                                         |

### Specific document page
The page details for just one specific page can be accessed through its ID:

```http
GET /api/document/<ID>/page/<PAGE-ID>/ HTTP/1.1
```

However, it is often easier to navigate based on page number:

```http
GET /api/document/<ID>/page/number/<PAGE#>/ HTTP/1.1
```

The given image url will provide an IMAGE_TYPE of the page resized and centered at IMAGE_WIDTH x IMAGE_HEIGHT with a black or white background.

```http
GET /api/document/<ID>/page/number/<PAGE#>/image/ HTTP/1.1
```

When an page has been processed by OpenSim an UUID is generated for the texture, this UUID can be stored with
the page to speed up future use. The cache period (`FileCacheTimeout`) is set in the `FlotsamCache.ini` configuration and needs to be
matched by the configuration value for the grid in the CMS.

```http
PUT /api/document/<ID>/page/number/<PAGE#>/ HTTP/1.1
```

| Parameter         | Type      | Description                                     |
|-------------------|-----------|-------------------------------------------------|
| uuid              | string    | UUID of the page to be saved                    |
| gridId            | integer   | The ID of the grid, as used in the CMS database |

## Presentations
A list with presentations can be requested by using the following GET request.

```http
GET /api/presentations/ HTTP/1.1
```

This will return the first 50 presentations. To request the next 50, add the offset as a parameter.
When using an ofset of 50, the following example will return the presentations from 51 to 100.

```http
GET /api/presentations/<OFFSET>/ HTTP/1.1
```

Example of the output will be similar to the request of a single presentation, only in a list form.
Cache information is left out in the list view.

```json
{
    {
        "type": "presentation",
        "title": "Test presentation title",
        "presentationId": "1",
        "ownerId": 1,
        "slides": [
            {
                "id": 1,
                "number": 1,
                "hasComments": false,
                "image": "http://localhost:80/OpenSim-CMS/api/presentation/1/slide/1/image/"
            },
            {
                (...)
            },
            (...)
        ],
        "slidesCount": 14,
        "creationDate": "2014-02-13 14:21:47",
        "modificationDate": "2014-02-13 14:22:09"
    },
    { (...) },
    { (...) },
    (...)
}
```
### Add a new presentation

```http
POST /api/presentation/ HTTP/1.1
```
| Parameter         | Type      | Description                                                 |
|-------------------|-----------|-------------------------------------------------------------|
| title             | string    | The title of the presentation                               |
| type              | string    | The document type, in this case it should be "presentation" |
| file              | file      | Base64 encoded file (PDF)                                   |


#### Specific presentation
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
    "slides": [
        {
            "id": 1,
            "number": 1,
            "hasComments": false,
            "image": "http:\/\/localhost:80\/OpenSim-CMS\/api\/presentation\/1\/slide\/1\/image\/",
            "cache": {
                "1": {
                    "uuid": "90591103-6982-4eed-9b31-291f7077194a",
                    "expires": "2014-02-23 14:29:25",
                    "isExpired": 0
                },
                "2": { (...) },
                (...)
        },
        {
            (...)
        },
        (...)
    ],
    "slidesCount": 14,
    "creationDate": "2014-02-13 14:21:47",
    "modificationDate": "2014-02-13 14:22:09"
}
```
The cache section of a slide uses the UUID which is matched to the UUID generated by OpenSim when the slide is accessed.
By saving the UUID caching of textures is enabled and OpenSim does not neet to request the image from the server everytime it is used.
The cache is done on a grid base, the index of the case refers to the Grid ID.
This is done by using the PUT function for a single slide (see below).

The slide details for just one specific slide can be accessed through its ID:

```http
GET /api/presentation/<ID>/slide/<SLIDE-ID>/ HTTP/1.1
```

However, it is often easier to navigate based on page/slide number:

```http
GET /api/presentation/<ID>/slide/number/<SLIDE#>/ HTTP/1.1
```

The given image url will provide an IMAGE_TYPE of the slide resized and centered at IMAGE_WIDTH x IMAGE_HEIGHT with a black background.

```http
GET /api/presentation/<ID>/slide/number/<SLIDE#>/image/ HTTP/1.1
```

When an slide has been processed by OpenSim an UUID is generated for the texture, this UUID can be stored with
the slide to speed up future use. The cache period (`FileCacheTimeout`) is set in the `FlotsamCache.ini`  configuration and needs to be
matched by the configuration value for the grid in the CMS.

```http
PUT /api/presentation/<ID>/slide/number/<SLIDE#>/ HTTP/1.1
```

| Parameter         | Type      | Description                                     |
|-------------------|-----------|-------------------------------------------------|
| uuid              | string    | UUID of the slide to be saved                   |
| gridId            | integer   | The ID of the grid, as used in the CMS database |

## Comments
On almost everything users can leave comments (when the user has sufficient permissions).
The comments can be saved, retrieved and removed through the API by using the following functions:

### Get comments
Getting the latest comments by using this URL. The comments are returned as a threaded array.

```http
GET /api/comments/<TYPE>/<ID>/ HTTP/1.1
```
| Types         | Description                                                      |
|---------------|------------------------------------------------------------------|
| document      | Get comments for the document with id = <ID>.                    |
| meeting       | Get comments on the meeting with the given id.                   |
| page          | Get comments for the page with id = <ID>.                        |
| slide         | Get comments for the slide with id = <ID>.                       |

### Get comments after given timestamp

Returns a flat list with 50 comments.
```http
GET /api/comments/<UNIX-TIMESTAMP>/ HTTP/1.1
```

Or with an offset:

```http
GET /api/comments/<UNIX-TIMESTAMP>/<OFFSET>/ HTTP/1.1
```

### Post a comment

```http
POST /api/comment/<TYPE>/<ID>/ HTTP/1.1
```
| Parameter         | Type              | Description                                                                       |
|-------------------|-------------------|-----------------------------------------------------------------------------------|
| user              | array or integer  | An user object containing atleast the user ID, or only the userID as integer      |
| parentId          | integer           | The ID of the comment for which this is a reply, 0 when no reply                  |
| message           | string            | The message (the CMS uses Markdown)                                               |
| timestamp         | string            | [Optional] The timestamp when the message is posted (format: YYYY-MM-DD HH:mm:ss) |

### Update a comment
The only thing that can be updated of a comment is the message. The poster, parentId, type, itemId and the original posted timestamp remain the same.

The editTimestamp will be automatically set by the server.

```http
PUT /api/comment/<COMMENT-ID>/ HTTP/1.1
```

| Parameter         | Type              | Description                                                                       |
|-------------------|-------------------|-----------------------------------------------------------------------------------|
| message           | string            | The message (the CMS uses Markdown)                                               |

### Remove comment

```http
DELETE /api/comment/<COMMENT-ID>/ HTTP/1.1
```

## Grids
For an overview of all grids and their information, the following request can be used:

```http
GET /api/grids/ HTTP/1.1
```

This will return quite a large list with data. I do not recommend using this query often
when a lot of Grids are in the database. Use the get Grid by ID function instead for a
specific grid when possible.

```json
{
    {
        "isOnline": 1,
        "id": 1,
        "name": "Test Grid",
        "totalUsers": 2,
        "activeUsers": 1,
        "openSim": {
            "protocol": "http",
            "ip": "127.0.0.1",
            "port": 9000
        },
        "remoteAdmin": {
            "url": "http://127.0.0.1",
            "port": 9001
        },
        "cacheTime": "48 hours",
        "defaultRegionUuid": "72efcc78-2b1a-4571-8704-fea352998c0c",
        "regionCount": 3,
        "regions": {
            {
                "uuid": "72efcc78-2b1a-4571-8704-fea352998c0c",
                "name": "The Grid",
                "image": "http://localhost:80/OpenSim-CMS/api/grid/1/region/72efcc78-2b1a-4571-8704-fea352998c0c/image/",
                "serverStatus": 1,
                "totalUsers": 2,
                "activeUsers": 1
            },
            { (...) },
            { (...) }
        }
    },
    { (...) },
    { (...) }
]
```

### Get Grid by ID

Information about a Grid can be retrieved by using:
```http
GET /api/grid/<GRID-ID>/ HTTP/1.1
```

This will return a summary of the grid and regions, excluding the passwords.

```json
{
    "isOnline": 1,
    "id": 1,
    "name": "Test Grid",
    "totalUsers": 2,
    "activeUsers": 1,
    "openSim": {
        "protocol": "http",
        "ip": "127.0.0.1",
        "port": 9000
    },
    "remoteAdmin": {
        "url": "http://127.0.0.1",
        "port": 9001
    },
    "cacheTime": "48 hours",
    "defaultRegionUuid": "72efcc78-2b1a-4571-8704-fea352998c0c",
    "regionCount": 3,
    "regions": {
        {
            "uuid": "72efcc78-2b1a-4571-8704-fea352998c0c",
            "name": "The Grid",
            "image": "http://localhost:80/OpenSim-CMS/api/grid/1/region/72efcc78-2b1a-4571-8704-fea352998c0c/image/"
        },
        { (...) },
        { (...) }
    }
}

```

### Regions
To retrieve information about a region the following API can be used.

```http
GET /api/grid/<GRID-ID>/region/<REGION-UUID>/ HTTP/1.1
```

This will return some basic information about the region, such as the name and a thumbnail.
Most of the information is only available if OpenSim and the webserver run on the same device
or if the MySQL database of OpenSim accepts remote connections.

```json
{
    "uuid": "72efcc78-2b1a-4571-8704-fea352998c0c",
    "name": "The Grid",
    "image": "http://localhost:80/OpenSim-CMS/api/grid/1/region/72efcc78-2b1a-4571-8704-fea352998c0c/image/",
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
