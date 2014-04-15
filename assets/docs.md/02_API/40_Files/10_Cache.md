The cache section of document pages and presentation slides is used to prevent the OpenSim server from downloading and converting the same image over and over again.
When OpenSim has successfully converted a slide or a page, it can provide the CMS with feedback to speed up future access of the same slide or page on the Grid.

The cache section will look something like this:
```json
    "cache": {
        "1": {
            "uuid": "45e1c4bb-31cd-4b9b-b0a2-720f3f013a6d",
            "expires": "2014-04-16 15:59:43",
            "isExpired": 0
        },
        "3": {
            "uuid": "ef2e0867-9429-4c1b-ad32-66a8ffe71b4d",
            "expires": "2014-04-10 06:23:18",
            "isExpired": 1
        }
    }
```
The number in front of the cache details, in this example `1` and `3` indicates the Grid ID of the cached asset. So, on the grid which is known in the CMS
with ID 3, the page or slide is known under the `uuid: ef2e0867-9429-4c1b-ad32-66a8ffe71b4d`. However, as you can see the cache has already expired on that grid
so the server has probably removed the UUID from its temporary assets. The `presenterScreen.lsl` asset implemented the cache function and checks to see if the
image can be loaded from the assets cache or needs to be loaded through the CMS API.

## Update UUID
These functions depend on the type of document. For pages the update UUID function can be found in the [Documents API](02_Documents.md) and for slides in the [Presentation API](03_Presentations.md)

## Clear the cache
Removes all expired items from the cache. The functions returns the number of removed
cache items.

```http
DELETE /api/files/cache/ HTTP/1.1
```