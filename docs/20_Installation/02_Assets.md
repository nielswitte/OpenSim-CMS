# Assets
The `assets` folder contains multiple examples of how to use the API. Some of them are explained below. The `xml` files are implementations of the script, and can be imported
in your OpenSim environment as examples.

## Presenter screen
`OpenSim/presenterScreen.lsl` allows you to show presentations in the virtual environment, it uses the presentations API to show the presentations that are
created by the user which is linked to your avatar.

## Avatar linker
`OpenSim/avatarLinker.lsl` allows you to link an avatar to a CMS user.

## Chatter
`OpenSim/chatter.lsl` enables the chat function from the CMS to the OpenSim Grid and back. It allows users within a 20m radius of the primitive that hosts the script
to chat with users using the CMS chat.

## Meeting logger and Agenda viewer
`OpenSim/meetingLogger.lsl` and `OpenSim/agendaViewer.lsl` need to be linked. The Meeting logger script enables a user to log a meeting and navigate through the agenda.
The agenda viewer script enables the agenda to be displayed on a prim and highlights the current active topic.

## OpenSim URLs
`osurl.reg` registers the `opensim://` protocol to match the Singularity Viewer. Edit the path to the viewer (the last line in the `reg` file) to match the location of
your Singularity installation.
`osurl.bat` needs to be placed in the same directory as the Singularity Viewer to pass the parameters of the URL to the viewer.

This allows you to open URLs that start with `opensim://`. What these URLs do is open the viewer when no viewer is open. Or when a viewer is already running and logged in,
the URL allows you to quickly teleport to a specific location. URLs need to be formatted as follows:

`opensim://[IP][:PORT]/[REGION NAME]/[X]/[Y]/[Z]` the IP, PORT and X,Y,Z-coordinates are optional.

For example if you have a server running on `192.168.1.2` on port `9000`, with a region called `My Region` and you want to travel to the coordinates `<100, 80, 20>` use:

`opensim://192.168.1.2:9000/My%20Region/100/80/20`, The URL is case sensitive and spaces need to be converted to `%20` or `+`, use functions like `urlencode()` to ensure a valid URL.
