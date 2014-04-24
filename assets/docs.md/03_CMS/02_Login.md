The login page can be accessed by using the `Login` button in the top right corner of the page.

## Login options
You can login with your `username` or the `e-mail address` associated with the user account and the correct `password`.

Logging in will update your last logged in timestamp  and provide you with an access token. This token is associated to your account and the currently used IP address. Your login token will have a limited validity time, depending on the configuration settings of the API, by default this is after last use 30 minutes.

So if you make a new API request, for example showing the details on a document, the API will reset the expiration time of the token and the token will be valid for another 30 minutes.