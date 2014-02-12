OpenSim-CMS
===========

A Content Management System (CMS) for OpenSim. Uses the Sim-on-a-Stick package as platform. Uses JSON to serve data from and to OpenSim.
Also includes a couple of example scripts to be used in OpenSim in the assets folder. Such as the `presenter.lsl` which enables you to access
presentations created in the CMS.

## Installation
clone this repository in your Sim-on-a-Stick www directory. For example: `/SoaS/www/`.
Copy `config.php.example` and rename it to `config.php`. Change the values in `config.php` to the values for your installation and setup your database
by importing the `database.sql` file in the assets directory.