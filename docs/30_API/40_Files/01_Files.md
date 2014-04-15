
## Files

```http
GET /api/files/ HTTP/1.1
```

```http
GET /api/files/<OFFSET>/ HTTP/1.1
```

Search for a files by title, at least 3 characters.
```http
GET /api/files/<SEARCH>/ HTTP/1.1
```

### Add a new file

```http
POST /api/file/ HTTP/1.1
```
| Parameter         | Type      | Description                                                 |
|-------------------|-----------|-------------------------------------------------------------|
| title             | string    | The title of the document                                   |
| type              | string    | The document type                                           |
| file              | file      | Base64 encoded file                                         |

### Remove a file

```http
DELETE /api/file/<FILE-ID>/ HTTP/1.1
```

### Images
The image of file with type `image` can be retrieved by using the following API function

```http
GET /api/file/<FILE-ID>/image/ HTTP/1.1
```

### Source file
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


### Owned by a specific user
It is possible to retrieve a list with documents owned by a specific user. This can be done by
using the user's ID:

```http
GET /api/user/<USER-ID>/files/ HTTP/1.1
```

Or by using an UUID of an avatar linked to the user.

```http
GET /api/grid/<GRID-ID>/avatar/<AVATAR-UUID>/files/ HTTP/1.1
```

### Clear the cache
Removes all expired items from the cache. The functions returns the number of removed
cache items.

```http
DELETE /api/files/cache/ HTTP/1.1
```