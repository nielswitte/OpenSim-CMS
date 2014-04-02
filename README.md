OpenSim-CMS
===========

A Content Management System (CMS) for OpenSim. Uses the 0.8 development versions of OpenSim to ensure JSON support. Uses JSON to serve data from and to OpenSim.
Also includes a couple of example scripts to be used in OpenSim in the assets folder. Such as the `presenterScreen.lsl` which enables you to access
presentations created in the CMS.

This project is based on the MVC pattern and uses Apache web server with PHP5.4+ and a MySQL database. The API is RESTful and provides the basic GET, POST, PUT and DELETE functionalities.

The CMS itself is build with AngularJS and RestAngular allowing it to remain separated from the Model and Controller and access only the API.

## Installation
clone this repository in your `www` directory. For example: `/var/www/`. Go to the `OpenSim-CMS` directory.
Copy `config.php.example` and rename it to `config.php` in `api` and `cms` directories . Change the values in `config.php` to the values for your installation and setup your database
by importing the `database.sql` file in the `assets` directory.

### Apache
Apache requires `mod_rewrite` and optional is `mod_expires`.

### OpenSim
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
The assets folder contains multiple examples of how to use the API. Some of them are explained below. The `xml` files are implementations of the script, and can be imported
in your OpenSim environment as examples.

### Presenter screen
`OpenSim/presenterScreen.lsl` allows you to show presentations in the virtual environment, it uses the presentations API to show the presentations that are
created by the user which is linked to your avatar.

### Avatar linker
`OpenSim/avatarLinker.lsl` allows you to link an avatar to a CMS user.

### Chatter
`OpenSim/chatter.lsl` enables the chat function from the CMS to the OpenSim Grid and back. It allows users within a 20m radius of the primitive that hosts the script
to chat with users using the CMS chat.

### Meeting logger and Agenda viewer
`OpenSim/meetingLogger.lsl` and `OpenSim/agendaViewer.lsl` need to be linked. The Meeting logger script enables a user to log a meeting and navigate through the agenda.
The agenda viewer script enables the agenda to be displayed on a prim and highlights the current active topic.

### OpenSim URLs
`osurl.reg` registers the `opensim://` protocol to match the Singularity Viewer. Edit the path to the viewer (the last line in the `reg` file) to match the location of
your Singularity installation.
`osurl.bat` needs to be placed in the same directory as the Singularity Viewer to pass the parameters of the URL to the viewer.

This allows you to open URLs that start with `opensim://`. What these URLs do is open the viewer when no viewer is open. Or when a viewer is already running and logged in,
the URL allows you to quickly teleport to a specific location. URLs need to be formatted as follows:

`opensim://[IP][:PORT]/[REGION NAME]/[X]/[Y]/[Z]` the IP, PORT and X,Y,Z-coordinates are optional.

For example if you have a server running on `192.168.1.2` on port `9000`, with a region called `My Region` and you want to travel to the coordinates `<100, 80, 20>` use:

`opensim://192.168.1.2:9000/My%20Region/100/80/20`, The URL is case sensitive and spaces need to be converted to `%20` or `+`, use functions like `urlencode()` to ensure a valid URL.

## Credits
Kudos to those who created the packages and classes which are used by this program.

Used in the Models/Controllers and API:
 * Ajillion and Avbdr for PHP-MySQLi-Database-Class (https://github.com/avbdr/PHP-MySQLi-Database-Class/)
 * Sprain for class.Images.php (https://github.com/sprain/class.Images.php)
 * PHPMailer (https://github.com/PHPMailer/PHPMailer/)

Used in the CMS:
 * Mgonto for Restangular (https://github.com/mgonto/restangular)
 * Mgcrea for Angular-Strap (https://github.com/mgcrea/angular-strap)
 * Serhioromano for Bootstrap-calendar (https://github.com/Serhioromano/bootstrap-calendar)
 * Tobiasahlin for SpinKit (https://github.com/tobiasahlin/SpinKit)
 * Ivaynberg for Select2 (https://github.com/ivaynberg/select2)
 * Moment (https://github.com/moment/moment)
 * Jashkenas for Underscore (https://github.com/jashkenas/underscore)
 * evilstreak for markdown-js (https://github.com/evilstreak/markdown-js)