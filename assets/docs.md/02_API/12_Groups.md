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