To retrieve information about a region the following API can be used.

```http
GET /api/grid/<GRID-ID>/region/<REGION-UUID>/ HTTP/1.1
```

This will return some basic information about the region, such as the name and a thumbnail. Most of the information is only available if OpenSim and the webserver run on the same device or if the MySQL database of OpenSim accepts remote connections.

```json
{
    "uuid": "72efcc78-2b1a-4571-8704-fea352998c0c",
    "name": "The Grid",
    "image": "http://localhost:80/OpenSim-CMS/api/grid/1/region/72efcc78-2b1a-4571-8704-fea352998c0c/image/",
    "serverStatus": 1
}
```

When the grid is configured to use MySQL or when Remote Admin is available, the following additional information is shown:

```json
{
    "totalUsers": 2,
    "activeUsers": 1
}
```

## Automatically retrieve region information
When the grid is online, uses MySQL and configured correctly, you can retrieve the regions automatically by using the following URL:

```http
POST /api/grid/<GRID-ID>/regions/ HTTP/1.1
```

This will return the number of updated regions and a boolean indicating if the update succeeded. However, this only adds and updates regions, it does not remove regions that are listed in the CMS but do not exist anymore.

```json
{
    "success": true,
    "regionsUpdated": 3
}
```

## Region image
A small map preview can be opened by using the following API request

```http
GET /api/region/<REGION-UUID>/image/ HTTP/1.1
```
This will return a 256x256 JPEG preview of the region map.
