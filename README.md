OpenSim-CMS
===========

A Content Management System (CMS) for OpenSim. Uses the 0.8 development versions of OpenSim to ensure JSON support. Uses JSON to serve data from and to OpenSim.
Also includes a couple of example scripts to be used in OpenSim in the assets folder. Such as the `presenter.lsl` which enables you to access
presentations created in the CMS.

This project is based on the MVC pattern and uses Apache web server with PHP5.4+ and a MySQL database. The API is RESTful and provides the basic GET, POST, PUT and DELETE functionalities.

The CMS itself is build with jQuery.rest allowing it to remain separated from the Model and Controller and access only the API. The only thing the CMS shares with the other parts
is the `config.php` file. However, this could become obsolute in the future.

## Installation
clone this repository in your www directory. For example: `/var/www/`.
Copy `config.php.example` and rename it to `config.php`. Change the values in `config.php` to the values for your installation and setup your database
by importing the `database.sql` file in the assets directory.

OpenSim needs to be configured with the following settings:

For loading dynamic textures:
```ini
[XEngine]
    AllowOSFunctions = true
```

Enable JSON support:

```ini
[XEngine]
    AllowMODFunctions = true
[JsonStore]
    Enabled = true
```

For RemoteAdmin functions:

```ini
[RemoteAdmin]
    enabled = true
    port = 9000
    access_password = "<ACCESS PASSWORD HERE>"
    access_ip_addresses = 127.0.0.1
    enabled_methods = all
```

In addition it is recommended to use MySQL as a database server for OpenSim. See http://opensimulator.org/wiki/Database_Settings#MySQL_Walkthrough for instructions
on how to set things up.

## Assets
`Presenter.lsl` allows you to show presentations in the virtual environment
`osurl.reg` registers the `opensim://` protocol to match the Singularity Viewer. Edit the path to the viewer to match the location of your installation.
`osurl.bat` needs to be placed in the same directory as the Singularity Viewer to pass the parameters of the URL to the viewer.

@todo will be extended later

## Credits
Kudos to those who created the packages and classes which are used by this program.
 * Ajillion and Avbdr for PHP-MySQLi-Database-Class (https://github.com/avbdr/PHP-MySQLi-Database-Class/)
 * Sprain for class.Images.php (https://github.com/sprain/class.Images.php)
 * Jpillora for jQuery.rest (https://github.com/jpillora/jquery.rest)
 * Arshaw for Fullcalendar (https://github.com/arshaw/fullcalendar)
 * Tobiasahlin for SpinKit (https://github.com/tobiasahlin/SpinKit)