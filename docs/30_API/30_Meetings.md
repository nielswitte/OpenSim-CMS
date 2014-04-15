# Meetings

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

## Calendar format
The following will return the meetings past the provided date in the format used by [Bootstrap-calendar](https://github.com/Serhioromano/bootstrap-calendar)


```http
GET /api/meetings/<YYYY-MM-DD>/calendar/ HTTP/1.1
```
The output will be as the example below. The `start` and `end` fields are an Unix timestamp in milliseconds.

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

## Get a specific meeting
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

## Create a meeting
For scheduling a new meeting a lot of parameters are required. Only the list with documents is optional.
Please ensure you use the correct parameters or else you will be losing a lot of time debugging.

| Parameter         | Type             | Description                                                      |
|-------------------|------------------|------------------------------------------------------------------|
| name              | string           | The title of the meeting                                         |
| startDate         | string           | The start date time of the meeting (format: YYYY-MM-DD HH:mm:ss) |
| endDate           | string           | The end date time of the meeting (format: YYYY-MM-DD HH:mm:ss)   |
| room              | integer or array | Room ID, can be submitted as an integer or as a json array `[{id: 1, name: (...), (...)}]` |
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

## Update a meeting

For updating a meeting you can use the following `PUT` request.

```http
PUT /api/meeting/<MEETING-ID>/ HTTP/1.1
```
The parameters are the same as for creating a meeting, see creating a meeting for more details.

## Get the meeting agenda
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
## Get meetings by participant
This will return a list with meetings where the given user is a participant for. The output is similar to the `/api/meetings/` request.

```http
GET /api/user/<USER-ID>/meetings/ HTTP/1.1
```