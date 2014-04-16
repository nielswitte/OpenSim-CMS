Before you start, you require a web server with [Apache](https://httpd.apache.org/), [PHP 5.4+](http://www.php.net) and [MySQL 5.6+](http://www.mysql.com). On windows you can use for example [XAMPP](https://www.apachefriends.org/index.html). On Linux the software can be installed by your package manager.

PHP requires the following extensions to be enabled:

 * php5-curl
 * php5-gd
 * php5-json
 * php5-mysqli
 * php5-xmlrpc
 * php5-xsl

Apache requires the following modules:

 * mod_rewrite
 * mod_expires

## Source code

Clone this repository in your `www` directory. For example: `/var/www/`.

```cmd
git clone https://github.com/nielswitte/OpenSim-CMS.git
```

Go to the `OpenSim-CMS` directory.

Copy `config.php.example` and rename it to `config.php` in `OpenSim-CMS/api` and `OpenSim-CMS/cms` directories . Change the values in `config.php` to the values for your installation and create a new MySQL database and importing the structure from the `database.sql` file in the `assets` directory. And add default users to enable access to the API by importing `users.sql` into the newly created database.

### Configuration
The API configuration contains the following settings:

#### General settings
| Setting                       | Possible values   | Description                                                                                           |
|-------------------------------|-------------------|-------------------------------------------------------------------------------------------------------|
| EXEC                          | `1`               | This value is used to check if the configuration file is loaded correctly before executing any script |
| CMS_ADMIN_EMAIL               | e-mail address    | The sender's address of the e-mail messages the API sends                                             |
| CMS_ADMIN_NAME                | name              | The sender's name corresponding to the e-mail messages the API sends                                  |

#### Server settings
| Setting                       | Possible values   | Description                                                                                           |
|-------------------------------|-------------------|-------------------------------------------------------------------------------------------------------|
| SERVER_DEBUG                  | `TRUE` or `FALSE` | Show debugging output when an API request gone wrong                                                  |
| SERVER_PHP_ERRORS             | `TRUE` or `FALSE` | Lets the API show PHP errors, on production servers this should be `FALSE`                            |
| SERVER_TIMEOUT                | seconds           | Time in seconds before a script times out                                                             |
| SERVER_PROTOCOL               | `http` or `https` | The protocol used to access the API. Please note that `https` has not been tested yet                 |
| SERVER_ADDRESS                | URL               | The URL to the API. Can be an IP address but also web address                                         |
| SERVER_PORT                   | Port number       | The port used by the webserver. Default for `http` is `80` and `https` uses `443`                     |
| SERVER_ROOT                   | location          | The path from the root of the webserver to the API. Default is `/OpenSim-CMS`                         |
| SERVER_API_TOKEN_EXPIRES      | time description  | In English the time a normal API token is valid since its last use. The string needs to be valid for [strtotime](http://www.php.net/strtotime) |
| SERVER_API_TOKEN_EXPIRES2     | time description  | In English the time an API token is valid since its last use for an OpenSim Grid. The string needs to be valid for [strtotime](http://www.php.net/strtotime) |
| SERVER_MIN_USERNAME_LENGTH    | number            | The minimal length of an username                                                                     |
| SERVER_MIN_PASSWORD_LENGTH    | number            | The minimal length of a password                                                                      |

#### Database settings
| Setting                       | Possible values   | Description                                                                                           |
|-------------------------------|-------------------|-------------------------------------------------------------------------------------------------------|
| $DB_USERNAME                  | MySQL username    | The username to access the MySQL database used by the API.                                            |
| $DB_PASSWORD                  | MySQL password    | The password corresponding to the user to access the API MySQL database                               |
| $DB_NAME                      | MySQL database name| The name of the database where the API data is stored                                                |
| $DB_ADDRESS                   | MySQL address     | The address to access the MySQL database on. If the MySQL database is on the same machine as the API, the address is most likely `localhost` |
| $DB_PORT                      | MySQL port        | The port used by MySQL to access the database. Default is `3306`                                      |

#### File settings
**WARNING:** Changing these values while the API is already in use can corrupt existing files.

| Setting                       | Possible values     | Description                                                                                             |
|-------------------------------|---------------------|---------------------------------------------------------------------------------------------------------|
| post_max_size                 | File size in bytes  | Maximum amount of data that can be sent to the server in a POST request                                 |
| upload_max_filesize           | File size in bytes  | Maximum file size that can be uploaded to the server                                                    |
| IMAGE_THUMBNAIL_WIDTH         | Pixels              | The width in pixels of thumbnails generated by the server                                               |
| IMAGE_THUMBNAIL_HEIGHT        | Pixels              | The height in pixels of thumbnails generated by the server                                              |
| IMAGE_WIDTH                   | Pixels              | The width in pixels of images (including documents and presentations) which will be used by the API to output to OpenSim  |
| IMAGE_HEIGHT                  | Pixels              | The height in pixels of images (including documents and presentations) which will be used by the API to output to OpenSim |
| IMAGE_TYPE                    | `jpg`, `png`, `gif` | The file type to save images in. Use one of the three options. OpenSim works best with `jpg`, which is the system's default and recommend option |

#### Additional settings
| Setting                       | Possible values     | Description                                                                                             |
|-------------------------------|---------------------|---------------------------------------------------------------------------------------------------------|
| date_default_timezone_set     | time zone           | The time zone used to display dates in. See [http://www.php.net/manual/en/timezones.php](http://www.php.net/manual/en/timezones.php) for a list with available time zones. |

## Users
The default users which are added by importing `assets/users.sql` are listed in the table below.
You can login into the CMS with the `admin` user. The passwords are lower case.

| Username          | Password              |
|-------------------|-----------------------|
| OpenSim           | opensim               |
| admin             | password              |

**WARNING:** Both users have maximum permissions for every part of the API and therefore it is highly recommended to change the passwords as quick as possible.

# Linux
Some of the functions for the processing of documents and presentations rely on `pdftoppm`. This program, located in `poppler-utils`, needs to be installed on your Linux server for conversion of PDF to images to work.

On Ubuntu this can be done by using: `apt-get install poppler-utils`.

# Apache
Apache requires `mod_rewrite` and optionally `mod_expires`. In addition PHP 5.4 or higher is required with CURL, JSON, GD and XML support.