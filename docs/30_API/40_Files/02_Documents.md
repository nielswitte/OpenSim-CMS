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
