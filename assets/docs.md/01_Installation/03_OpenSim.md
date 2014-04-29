The assets use JSON stores to process JSON data returned by the server. JSON stores are supported in all versions newer than `0.7.6`. At this moment it requires you to run a development version of OpenSim.

The source code of a development version can be downloaded from [https://www.github.com/opensim/opensim](https://www.github.com/opensim/opensim). [Compile instructions](http://opensimulator.org/wiki/Build_Instructions) can be found in the [Wiki](http://opensimulator.org/wiki/Main_Page) of OpenSim.

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

The cache functions used by `presenterScreen.lsl` require the use of the `FlotsamCache.ini`. For more information about the FlotsamCache see:
[http://opensimulator.org/wiki/AssetCache](http://opensimulator.org/wiki/AssetCache).

In addition it is recommended to use MySQL as a database server for OpenSim. See [http://opensimulator.org/wiki/Database_Settings#MySQL_Walkthrough](http://opensimulator.org/wiki/Database_Settings#MySQL_Walkthrough) for instructions on how to set things up.