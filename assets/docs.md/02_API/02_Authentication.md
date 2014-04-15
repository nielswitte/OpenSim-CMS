Before the API can be used, an user needs to authorize himself. This can be done by using the following API:

```http
POST /api/auth/username/ HTTP/1.1
```

| Parameter         | Type      | Description                                                   |
|-------------------|-----------|---------------------------------------------------------------|
| username          | String    | The username of the user in the CMS                           |
| password          | String    | The corresponding password of the user in the CMS             |
| ip                | String    | [Optional] The IP address to assign this token to             |

The optional parameter ip, can be used to assign a token to a machine that can not perform the auth request
by itself, for example if the CMS is running on localhost, the token is for the user of the CMS, not the CMS.

This request will return, on success the following JSON:

```json
{
    "token": "53048c5375b1d2.66536292",
    "ip": "192.168.1.102",
    "expires": "2014-02-19 12:19:55",
    "userId": 1,
    "lastLogin": "2014-02-16 15:32:14"
}
```

The validity of the token depends on the config settings and is extended everytime the token is used.
The user OpenSim with user ID -1 can only accessed from the IP/Hostname which is used by OpenSim according
to the grid list. In addition the `X-SecondLife-Shard` header needs to be set to access this user, this is
done by default for OpenSim.