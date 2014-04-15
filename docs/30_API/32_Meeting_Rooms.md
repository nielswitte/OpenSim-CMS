To retrieve a list with rooms available on a specific grid use the following API function:

```http
GET /api/grid/<GRID-ID>/rooms/ HTTP/1.1
```

This will return an array with rooms.
```json
[
    {
        "id": 1,
        "name": "Board Room",
        "gridId": 1,
        "region": {
            "uuid": "72efcc78-2b1a-4571-8704-fea352998c0c",
            "name": "Manhattan"
        },
        "description": "A luxurious space with all the facilities for a good meeting",
        "coordinates": {
            "x": 69,
            "y": 27,
            "z": 23
        }
    },
    {
        "id": 2,
        "name": "Open air",
        "gridId": 1,
        "region": {
            "uuid": "72efcc78-2b1a-4571-8704-fea352998c0c",
            "name": "Oceania"
        },
        "description": "For a meeting in the open air",
        "coordinates": {
            "x": 129,
            "y": 128,
            "z": 25
        }
    },
    {
        "id": 3,
        "name": "Iglo",
        "gridId": 1,
        "region": {
            "uuid": "72efcc78-2b1a-4571-8704-fea352998c2c",
            "name": "North Pole"
        },
        "description": "A meeting room in arctic style",
        "coordinates": {
            "x": 145,
            "y": 83,
            "z": 11
        }
    }
]
```

## Get rooms by region
This will return a similar list to the above function only filtered by a region UUID.

```http
GET /api/grid/<GRID-ID>/region/<REGION-UUID>/rooms/ HTTP/1.1
```

## Get a specific room
For retrieving the full information about a specific room the following function can be used:
```http
GET /api/grid/<GRID-ID>/room/<ROOM-ID/ HTTP/1.1
```

This will also return the grid information. 

```json
{
    "id": 1,
    "name": "Board Room",
    "grid": {
        "id": 1,
        "name": "New York",
        "openSim": {
            "protocol": "http",
            "ip": "localhost",
            "port": 9000
        }
    },
    "region": {
        "uuid": "72efcc78-2b1a-4571-8704-fea352998c0c",
        "name": "Manhattan"
    },
    "description": "A luxurious space with all the facilities for a good meeting",
    "coordinates": {
        "x": 69,
        "y": 27,
        "z": 23
    }
}
```