The grids page is currently only there to provide information. Changes can, at the moment, only be made by editing the database. In later versions the ability to manage grids will be added. The information is retrieved from the server's MySQL database. When no database is configured, Remote Admin is used. When even Remote Admin fails or is not configured, some XML requests will be performed to acquire information about the grid. Only when MySQL is enabled, the information about the Grid such as `Total users` is accurate. 

When viewing the details of a grid, it is possible to update the information about the regions and the grid's name. When updating the regions or the name a request is send to the OpenSim server. Regions will only be added, old regions will not be removed.

## Work in progress
Basically this area is still under development. Because of the anticipated small number of changes that will occur in this area, advanced users (administrators) can modify the data directly in the database.

Besides the grid's address, Remote Admin settings and MySQL settings, grids also contain the meeting rooms. These will also have to be added manually into the database.