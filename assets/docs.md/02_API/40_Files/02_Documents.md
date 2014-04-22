**NOTICE:** You can only retrieve files if you meet at least one of the following requirements.

 * You have `ALL` permissions
 * You created the file
 * You are a member of a group to which the file is attached

The documents API is quite similar to the Files API since it is a subset. The main difference is that it will only return files with the type `document` and that documents have children in the form of `pages`.

```http
GET /api/documents/ HTTP/1.1
```

```http
GET /api/documents/<OFFSET>/ HTTP/1.1
```

## Search by title
```http
GET /api/documents/<SEARCH>/ HTTP/1.1
```

All the above functions will output a list like this:

```json
[
    {
        "id": 133,
        "type": "document",
        "user": {
            "id": 3,
            "username": "johndoe",
            "firstName": "John",
            "lastName": "Doe",
            "email": "john@doe.com",
            "picture": false,
            "lastLogin": "2014-04-16 11:17:04"
        },
        "title": "OpenSim-CMS Documentation",
        "creationDate": "2014-04-14 15:55:31",
        "modificationDate": "2014-04-14 15:55:31",
        "sourceFile": "source.pdf",
        "url": "http://localhost:80/OpenSim-CMS/api/document/133/",
        "pagesCount": 22
    },
    { (...) },
    (...)
]
```

## Get a single document

When retrieving a sinlg document you will get a lot of information, including a list with all pages and their cache information.

```http
GET /api/document/<DOCUMENT-ID>/ HTTP/1.1
```

For example when retrieving the document with ID 133 you can expect something like this:

```json
{
    "id": 133,
    "type": "document",
    "user": {
        "id": 3,
        "username": "johndoe",
        "firstName": "John",
        "lastName": "Doe",
        "email": "john@doe.com",
        "picture": false,
        "lastLogin": "2014-04-16 11:17:04"
    },
    "title": "OpenSim-CMS Documentation",
    "creationDate": "2014-04-14 15:55:31",
    "modificationDate": "2014-04-14 15:55:31",
    "sourceFile": "source.pdf",
    "url": "http://localhost:80/OpenSim-CMS/api/document/133/",
    "pages": [
        {
            "id": 80,
            "number": 1,
            "total": 22,
            "hasComments": false,
            "image": "http://localhost:80/OpenSim-CMS/api/document/133/page/number/1/image/",
            "thumbnail": "http://localhost:80/OpenSim-CMS/api/document/133/page/number/1/thumbnail/",
            "documentTitle": "OpenSim-CMS Documentation",
            "cache": {
                "1": {
                    "uuid": "45e1c4bb-31cd-4b9b-b0a2-720f3f013a6d",
                    "expires": "2014-04-16 15:59:43",
                    "isExpired": 0
                }
            }
        },
        {
            "id": 81,
            "number": 2,
            "total": 22,
            "hasComments": false,
            "image": "http://localhost:80/OpenSim-CMS/api/document/133/page/number/2/image/",
            "thumbnail": "http://localhost:80/OpenSim-CMS/api/document/133/page/number/2/thumbnail/",
            "documentTitle": "OpenSim-CMS Documentation",
            "cache": [

            ]
        },
        { (...) }.
        (...)
    ],
    "pagesCount": 22
}
```

## Add a new document

```http
POST /api/document/ HTTP/1.1
```
| Parameter         | Type      | Description                                                 |
|-------------------|-----------|-------------------------------------------------------------|
| title             | string    | The title of the document                                   |
| type              | string    | The document type                                           |
| file              | file      | Base64 encoded file                                         |

## Specific document page
The page details for just one specific page can be accessed through its ID:

```http
GET /api/document/<ID>/page/<PAGE-ID>/ HTTP/1.1
```

However, it is often easier to navigate based on page number:

```http
GET /api/document/<ID>/page/number/<PAGE#>/ HTTP/1.1
```

The given image URL will provide an IMAGE_TYPE of the page resized and centered at IMAGE_WIDTH x IMAGE_HEIGHT with a black or white background.

```http
GET /api/document/<ID>/page/number/<PAGE#>/image/ HTTP/1.1
```

## Cache
When an page has been processed by OpenSim an UUID is generated for the texture, this UUID can be stored with the page to speed up future use. The cache period (`FileCacheTimeout`) is set in the `FlotsamCache.ini` configuration and needs to be matched by the configuration value for the grid in the CMS.

```http
PUT /api/document/<ID>/page/number/<PAGE#>/ HTTP/1.1
```

| Parameter         | Type      | Description                                     |
|-------------------|-----------|-------------------------------------------------|
| uuid              | string    | UUID of the page to be saved                    |
| gridId            | integer   | The ID of the grid, as used in the CMS database |
