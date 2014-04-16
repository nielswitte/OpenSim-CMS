The `meetingLogger.lsl` script has the ability to log the chat during a meeting. These minutes can be retrieved by using the following API functionality:

```http
GET /api/meeting/<MEETING-ID>/minutes/ HTTP/1.1
```

Just like the chat this can return a really long response. The response includes some basic information about the meeting which can be used to parse the minutes with more details.

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
        "picture": "http://localhost:80/OpenSim-CMS/api/user/4/picture/",
        "lastLogin": "2014-04-15 16:00:01"
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

## Add minutes to meeting
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
