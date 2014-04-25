Through the CMS it is possible to chat with users on the Grid. The following line will return the latest 50 chat messages on the given grid.

```http
GET /api/grid/<GRID-ID>/chats/ HTTP/1.1
```

Or get all messages since a given unix timestamp in seconds

```http
GET /api/grid/<GRID-ID>/chats/<UNIX-TIMESTAMP>/ HTTP/1.1
```

This will return a list with chat messages and their sender. The `fromCMS` value is used to indicate if the message was sent from the chat in the CMS (`1`) or was captured in the OpenSim environment (`0`).

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
            "picture": false,
            "lastLogin": "2014-04-16 11:17:04"
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
            "lastLogin": "2014-04-15 16:00:01"
        },
        "message": "Hello John",
        "timestamp": "2014-04-08 13:15:04",
        "fromCMS": 1
    },
    (...)
]
```

## Add chat
To add a line to the chat you require `WRITE` permissions for the chat section. For OpenSim to add a line to the chat, the server first needs to match the Avatar to a user. Or when no match can be found, the server could use `0` as ID, and append the message with the Avatar's name.

The chats can be in an array or a just sent as a single element.

```http
POST /api/grid/<GRID-ID/chats/ HTTP/1.1
```
| Parameter         | Type      | Description                                                               |
|-------------------|-----------|---------------------------------------------------------------------------|
| userId            | integer   | The CMS user ID                                                           |
| message           | string    | The message to be saved                                                   |
| timestamp         | string    | The timestamp of the message in the format YYYY-MM-DD HH:mm:ss            |
| fromCMS           | integer   | `1` (True) if the message is from the CMS, `0` (false) if from OpenSim Server |
