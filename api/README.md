OpenSim-CMS API
===============
The OpenSim-CMS communicates with OpenSim objects through an JSON-API, based on REST.

## Users

User information can be accessed by using, the UUID of the user is based on the user's UUID in OpenSim:
```
GET /api/user/<UUID>/
```

To match an UUID of a user to the user in the CMS the following command can be used. 
Some form of authentication will be added later on. By sending a POST request to the server with the CMS username as parameter and the UUID of the user in OpenSim.  

```
POST /api/user/<UUID>/

userName=<USERNAME>
````

## Presentations

To retrieve a specific presentation use the following command and replace the id with the number of the
presentation you want to get. The trailing / is optional.
```
GET /api/presentation/<ID>/
````
Example of output when request is succesful:
```json
{
    "type": "presentation",
    "title": "Test Presentatie",
    "presentationId": "1",
    "ownerUuid": "3fedbbf8-465c-499c-9c2d-3fba9ed61701",
    "slides": [
        {
            "number": "1",
            "uuid": "1be74003-2d7c-4dbd-87c2-a1c95e0864e6",
            "uuidUpdated": "2014-02-13 14:55:27",
            "uuidExpired": "0",
            "url": "http:\/\/localhost:80\/OpenSim-CMS\/api\/presentation\/1\/slide\/1\/"
        },
        
        (...)
        
    ],
    "openSim": [
        "1be74003-2d7c-4dbd-87c2-a1c95e0864e6",
        "4279a3f1-11fd-4d0d-bae3-e16bcaf0f4d5",
        "http://localhost:80/OpenSim-CMS/api/presentation/1/slide/3/",
        "4d96b99a-5dca-4a79-8f8a-580fe6d939f8",
        (...)
    ],
    "slidesCount": "14",
    "creationDate": "2014-02-13 14:21:47",
    "modificationDate": "2014-02-13 14:22:09"
}
```
The included openSim section will give an URL when the slide isn't matched to a UUID or the UUID is expired.
The given URL will provide a jpg of the slide resized and centered at 1024x1024 with a black background.

```
GET /api/presentation/<ID>/slide/<SLIDE #>/
````

When an slide has been processed by OpenSim an UUID is generated for the texture, this UUID can be stored with the slide to speed up future use. The cache periode is set in the `OpenSim.ini` configuration and needs to be matched by the `OS_ASSET_CACHE_EXPIRES` value in `config.php`.

```
POST /api/presentation/<ID>/slide/<SLIDE #>/

uuid=<UUID>
````
