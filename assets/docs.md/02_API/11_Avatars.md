The CMS user can not be used in the OpenSim environment. OpenSim works with Avatars, which are independent of the CMS. However, Avatars can be linked to an OpenSim-CMS user, to access data from a specific user.

## Link avatar to user
To match an UUID of a user to the user in the CMS the following command can be used. Some form of authentication will be added later on. By sending a PUT request to the server with the CMS username as parameter and use the UUID of the user in OpenSim as URL parameter.

```http
POST /api/grid/<GRID-ID>/avatar/<UUID>/ HTTP/1.1
```

| Parameter         | Type      | Description                                                   |
|-------------------|-----------|---------------------------------------------------------------|
| username          | String    | The username of the user in the CMS                           |


## Confirm avatar
Once an avatar is linked to a user account, it needs to be confirmed by the user. This can only be done by the user himself.

```http
PUT /api/grid/<GRID-ID>/avatar/<UUID>/ HTTP/1.1
```

Because the token which is used for this request is matched to the userId, this will provide the additional required information. Therefore no parameters are needed for this request.

This request will also return `"success": false` when the avatar is already confirmed.

## Unlink an avatar
This unlinks an avatar from the user. Just like confirming, this can only be performed by the user account associated with the link or when you have `WRITE` permissions for the `user` section. Please not that this will not remove the Avatar from OpenSim.

```http
DELETE /api/grid/<GRID-ID>/avatar/<UUID>/confirm/ HTTP/1.1
```

## Create a new avatar
To create a new avatar on the given Grid the following API url can be used with a POST request.

```http
POST /api/grid/<GRID-ID>/avatar/ HTTP/1.1
```

The parameters that can be used are the following:

| Parameter         | Type      | Description                                                   |
|-------------------|-----------|---------------------------------------------------------------|
| firstName         | string    | agent's first name                                            |
| lastName          | string    | agent's last name                                             |
| email             | string    | agent's email address                                         |
| password          | string    | agent's password (plain text)                                 |
| startRegionX      | integer   | [Optional] X-coordinate of the start region (default: 0)      |
| startRegionY      | integer   | [Optional] Y-coordinate of the start region (default: 0)      |

This request will return a JSON message with the result. It contains two or three elements.
* 1) success, a boolean wheter or not the request was processed successful.
* 2) Optional, only used when the request is not successful.
* 3) the UUID of the newly created user, which is filled with zeros on failure.

Two examples of output are listed below, first a successful request, second a failure because the user's first and lastname were already used.

*WARNING* This way of avatar creation does not support special chars in the password. The following characters
can not be used in a password: ` ?{}<>;" '[]/\ `. Somehow the characters are escaped by the XML parser but not unescaped when generating a password hash. This will result in not being able to login in OpenSim.

```json
{
    "success": true,
    "avatar_uuid": "44172f17-b7a8-4b30-a42e-9699b563789b"
}
```

```json
{
    "success": false,
    "error": "failed to create new user <FirstName> <LastName>",
    "avatar_uuid": "00000000-0000-0000-0000-000000000000"
}
```

## Teleport an avatar to a location
To teleport a user you need at least the UUID of the user. All other parameters are optional and listed in the table below.

```http
PUT /api/grid/<GRID-ID>/avatar/<UUID>/teleport/ HTTP/1.1
```

| Parameter         | Type      | Description                                                   |
|-------------------|-----------|---------------------------------------------------------------|
| regionName        | string    | [Optional] The name of the region (default from the region)   |
| firstName         | string    | [Optional] agent's first name                                 |
| lastName          | string    | [Optional] agent's last name                                  |
| posX              | float     | [Optional] X-coordinate to teleport to (default: 128)         |
| posY              | float     | [Optional] Y-coordinate to teleport to (default: 128)         |
| posX              | float     | [Optional] Z-coordinate to teleport to (default: 25)          |
| lookatX           | float     | [Optional] X-coordinate to look at (default: 0)               |
| lookatY           | float     | [Optional] Y-coordinate to look at (default: 0)               |
| lookatZ           | float     | [Optional] Z-coordinate to look at (default: 0)               |

This will return on success:

```json
{
    "success": true
}
```

And on failure it will provide an error message, for example when the agent's uuid is not found or the user is offline:

```json
{
    "success": false,
    "error": "No agent with agent_id 44172f17-b7a8-4b30-a42e-9698b563789b found in this simulator"
}
```
