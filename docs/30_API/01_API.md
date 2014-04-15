# OpenSim-CMS API

The OpenSim-CMS communicates with OpenSim objects through an JSON-API, based on REST.
For valid requests the `HTTP/1.1 200 OK` is used, for failures an exception is thrown by
the system and displayed as output with a `HTTP/1.1 400 Bad Request` header. For most functions
the user needs to be authorized, if the user is not authorized but should be, a `HTTP/1.1 401 Unauthorized`
header is used.

## POST, PUT and DELETE

All `POST`, `PUT` and `DELETE` request will atleast return the following result when successfully processed:

```json
{
    "success": true
}
```

## Error messages
When the configuration value `SERVER_DEBUG` is set to `FALSE`, bad and unauthorized requests will provide,
beside the corresponding HTTP header, an exception message in JSON. For example the following message
is displayed when attempting to access a protected function without a valid API token.

```json
{
    "success": false,
    "error": "Unauthorized to access this API URL"
}
```

When `SERVER_DEBUG` is set to `TRUE`, additional information will be displayed. Including the
file, line and stack trace of the error. It is recommended to disable debugging for public API servers.

```json
{
    "success": false,
    "error": "Unauthorized to access this API URL"
    "Code": 0,
    "File": "/OpenSim-CMS/api/index.php",
    "Line": 62,
    "Trace": [

    ]
}
```
