OpenSim-CMS
===========

A Content Management System (CMS) for OpenSim. Uses the 0.8 development versions of OpenSim to ensure JSON support. Uses JSON to serve data from and to OpenSim.
Also includes a couple of example scripts to be used in OpenSim in the assets folder. Such as the `presenter.lsl` which enables you to access
presentations created in the CMS.

The CMS is based on the MVC pattern and uses Apache web server with PHP5.4+ and a MySQL database. The API is based on the REST principle.

## Installation
clone this repository in your www directory. For example: `/var/www/`.
Copy `config.php.example` and rename it to `config.php`. Change the values in `config.php` to the values for your installation and setup your database
by importing the `database.sql` file in the assets directory.

@todo will be extended later

## Credits
Kudos to those who created the packages and classes which are used by this program.
 * Ajillion for PHP-MySQLi-Database-Class (https://github.com/ajillion/PHP-MySQLi-Database-Class)
 * Sprain for class.Images.php (https://github.com/sprain/class.Images.php)