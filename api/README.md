OpenSim-CMS API
===============

## Presentations

To retrieve a specific presentation use the following command and replace the id with the number of the
presentation you want to get.

`/api/presentation/<ID>`

Example of output:
`{
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
        {
            "number": "2",
            "uuid": "4279a3f1-11fd-4d0d-bae3-e16bcaf0f4d5",
            "uuidUpdated": "2014-02-13 14:49:24",
            "uuidExpired": "0",
            "url": "http:\/\/localhost:80\/OpenSim-CMS\/api\/presentation\/1\/slide\/2\/"
        },
        {
            "number": "3",
            "uuid": "9b481636-a7ae-4533-a6c6-68bd9e0a4d39",
            "uuidUpdated": "2014-02-13 14:52:39",
            "uuidExpired": "0",
            "url": "http:\/\/localhost:80\/OpenSim-CMS\/api\/presentation\/1\/slide\/3\/"
        },
        {
            "number": "4",
            "uuid": "4d96b99a-5dca-4a79-8f8a-580fe6d939f8",
            "uuidUpdated": "2014-02-13 15:05:12",
            "uuidExpired": "0",
            "url": "http:\/\/localhost:80\/OpenSim-CMS\/api\/presentation\/1\/slide\/4\/"
        }
    ],
    "openSim": [
        "1be74003-2d7c-4dbd-87c2-a1c95e0864e6",
        "4279a3f1-11fd-4d0d-bae3-e16bcaf0f4d5",
        "http:\/\/localhost:80\/OpenSim-CMS\/api\/presentation\/1\/slide\/3\/",
        "4d96b99a-5dca-4a79-8f8a-580fe6d939f8"
    ],
    "slidesCount": "4",
    "creationDate": "2014-02-13 14:21:47",
    "modificationDate": "2014-02-13 14:22:09"
}`