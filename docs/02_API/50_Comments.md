# Comments
On almost everything users can leave comments (when the user has sufficient permissions).
The comments can be saved, retrieved and removed through the API by using the following functions:

## Get comments
Getting the latest comments by using this URL. The comments are returned as a threaded array.

```http
GET /api/comments/<TYPE>/<ID>/ HTTP/1.1
```
| Types         | Description                                                      |
|---------------|------------------------------------------------------------------|
| document      | Get comments for the document with id = `<ID>`.                  |
| meeting       | Get comments on the meeting with the id = `<ID>`.                |
| page          | Get comments for the page with id = `<ID>`.                      |
| slide         | Get comments for the slide with id = `<ID>`.                     |

## Get comments after given timestamp

Returns a flat list with 50 comments.
```http
GET /api/comments/<UNIX-TIMESTAMP>/ HTTP/1.1
```

Or with an offset:

```http
GET /api/comments/<UNIX-TIMESTAMP>/<OFFSET>/ HTTP/1.1
```

## Post a comment

```http
POST /api/comment/<TYPE>/<ID>/ HTTP/1.1
```
| Parameter         | Type              | Description                                                                       |
|-------------------|-------------------|-----------------------------------------------------------------------------------|
| user              | array or integer  | An user object containing atleast the user ID, or only the userID as integer      |
| parentId          | integer           | The ID of the comment for which this is a reply, 0 when no reply                  |
| message           | string            | The message (the CMS uses Markdown)                                               |
| timestamp         | string            | [Optional] The timestamp when the message is posted (format: YYYY-MM-DD HH:mm:ss) |

## Update a comment
The only thing that can be updated of a comment is the message. The poster, parentId, type, itemId and the original posted timestamp remain the same.

The editTimestamp will be automatically set by the server.

```http
PUT /api/comment/<COMMENT-ID>/ HTTP/1.1
```

| Parameter         | Type              | Description                                                                       |
|-------------------|-------------------|-----------------------------------------------------------------------------------|
| message           | string            | The message (the CMS uses Markdown)                                               |

## Remove comment

```http
DELETE /api/comment/<COMMENT-ID>/ HTTP/1.1
```
