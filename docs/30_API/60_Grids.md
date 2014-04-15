## Grids
For an overview of all grids and their information, the following request can be used:

```http
GET /api/grids/ HTTP/1.1
```

This will return quite a large list with data. I do not recommend using this query often
when a lot of Grids are in the database. Use the get Grid by ID function instead for a
specific grid when possible.

```json
[
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
    "regions": [
        {
            "uuid": "72efcc78-2b1a-4571-8704-fea352998c0c",
            "name": "The Grid",
            "image": "http://localhost:80/OpenSim-CMS/api/grid/1/region/72efcc78-2b1a-4571-8704-fea352998c0c/image/"
        },
        { (...) },
        { (...) }
    ]
}
```