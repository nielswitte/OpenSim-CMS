# Files

```http
GET /api/files/ HTTP/1.1
```

```http
GET /api/files/<OFFSET>/ HTTP/1.1
```
## Search

You can also search for files. This can be done based on Title, User or Avatar.

### Title

Search for a files by title, at least 3 characters.
```http
GET /api/files/<SEARCH>/ HTTP/1.1
```

### User
It is possible to retrieve a list with documents owned by a specific user. This can be done by
using the user's ID:

```http
GET /api/user/<USER-ID>/files/ HTTP/1.1
```

### Avatar
Or by using an UUID of an avatar linked to the user.

```http
GET /api/grid/<GRID-ID>/avatar/<AVATAR-UUID>/files/ HTTP/1.1
```

All the above functions will return a similar output, such as the example given below:

```json
[
    {
        "id": 134,
        "type": "presentation",
        "user": {
            "id": 4,
            "username": "janedoe",
            "firstName": "Jane",
            "lastName": "Doe",
            "email": "jane@doe.com",
            "picture": "http://localhost:80/OpenSim-CMS/api/user/4/picture/"
        },
        "title": "OpenSim-CMS in a nutshell",
        "creationDate": "2014-04-15 09:13:30",
        "modificationDate": "2014-04-15 09:13:30",
        "sourceFile": "source.pdf",
        "url": "http://localhost:80/OpenSim-CMS/api/presentation/134/"
    },
    {
        "id": 133,
        "type": "document",
        "user": {
            "id": 3,
            "username": "johndoe",
            "firstName": "John",
            "lastName": "Doe",
            "email": "john@doe.com",
            "picture": false
        },
        "title": "OpenSim-CMS Documentation",
        "creationDate": "2014-04-14 15:55:31",
        "modificationDate": "2014-04-14 15:55:31",
        "sourceFile": "source.pdf",
        "url": "http://localhost:80/OpenSim-CMS/api/document/133/"
    },
    { (...) },
    (...)
]
```

## Get a specific file
When retrieving a specific file, the output depends on the file type.

```http
GET /api/file/<FILE-ID/ HTTP/1.1
```

For example if the type is a document it will return the same output as `/api/document/<DOCUMENT-ID/`, where `<FILE-ID>` equals `<DOCUMENT-ID>`.


## Add a new file

```http
POST /api/file/ HTTP/1.1
```
| Parameter         | Type      | Description                                                 |
|-------------------|-----------|-------------------------------------------------------------|
| title             | string    | The title of the document                                   |
| type              | string    | The document type                                           |
| file              | file      | Base64 encoded file                                         |

## Remove a file

```http
DELETE /api/file/<FILE-ID>/ HTTP/1.1
```

## Images
The image of file with type `image` can be retrieved by using the following API function

```http
GET /api/file/<FILE-ID>/image/ HTTP/1.1
```

## Source file
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