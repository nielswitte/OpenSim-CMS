# OpenSim-CMS

A Content Management System (CMS) for OpenSim. Uses the 0.8 development versions of OpenSim to ensure JSON support. Uses JSON to serve data from and to OpenSim.
Also includes a couple of example scripts to be used in OpenSim in the assets folder. Such as the `presenterScreen.lsl` which enables you to access
presentations created in the CMS.

This project is based on the MVC pattern and uses Apache web server with PHP5.4+ and a MySQL database. The API is RESTful and provides the basic GET, POST, PUT and DELETE functionalities.

The CMS itself is build with AngularJS and RestAngular allowing it to remain separated from the Model and Controller and access only the API.

## Documentation
The [docs](docs) folder contains the documentation of the [installation](docs/Installation/Installation.html) and usage of the [API](docs/API/API.html).

## Credits
Kudos to those who created the packages and classes which are used by this program. If I forgot someone, please create a new issue and I will update the list.

### Used in the Models/Controllers and API:
 * Ajillion and Avbdr for PHP-MySQLi-Database-Class ([https://github.com/avbdr/PHP-MySQLi-Database-Class/](https://github.com/avbdr/PHP-MySQLi-Database-Class/))
 * Sprain for class.Images.php ([https://github.com/sprain/class.Images.php](https://github.com/sprain/class.Images.php))
 * PHPMailer ([https://github.com/PHPMailer/PHPMailer/](https://github.com/PHPMailer/PHPMailer/))

### Used in the CMS:
 * Mgonto for Restangular ([https://github.com/mgonto/restangular](https://github.com/mgonto/restangular))
 * Mgcrea for Angular-Strap ([https://github.com/mgcrea/angular-strap](https://github.com/mgcrea/angular-strap))
 * Serhioromano for Bootstrap-calendar ([https://github.com/Serhioromano/bootstrap-calendar](https://github.com/Serhioromano/bootstrap-calendar))
 * Tobiasahlin for SpinKit ([https://github.com/tobiasahlin/SpinKit](https://github.com/tobiasahlin/SpinKit))
 * Ivaynberg for Select2 ([https://github.com/ivaynberg/select2](https://github.com/ivaynberg/select2))
 * Moment ([https://github.com/moment/moment](https://github.com/moment/moment))
 * Jashkenas for Underscore ([https://github.com/jashkenas/underscore](https://github.com/jashkenas/underscore))
 * Evilstreak for Markdown-js ([https://github.com/evilstreak/markdown-js](https://github.com/evilstreak/markdown-js))

### To generate the documentation
 * Justinwalsh for Daux.io ([https://github.com/justinwalsh/daux.io/](https://github.com/justinwalsh/daux.io/))