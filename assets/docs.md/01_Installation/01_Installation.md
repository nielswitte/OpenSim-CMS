Clone this repository in your `www` directory. For example: `/var/www/`.

```cmd
git clone https://github.com/nielswitte/OpenSim-CMS.git
```

Go to the `OpenSim-CMS` directory.

Copy `config.php.example` and rename it to `config.php` in `api` and `cms` directories . Change the values in `config.php` to the values for your installation and create a new
MySQL database and importing the structure from the `database.sql` file in the `assets` directory.

## Users
@todo add sql file with default users and data to access CMS.


# Linux
Some of the functions for the processing of documents and presentations rely on `pdftoppm`. This program, located in `poppler-utils`, needs to be installed on your Linux server
for conversion of PDF to images to work.

On Ubuntu this can be done by using: `apt-get install poppler-utils`.

# Apache
Apache requires `mod_rewrite` and optionally `mod_expires`. In addition PHP 5.4 or higher is required with XML and GD support.