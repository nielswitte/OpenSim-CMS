/**
 * This screen shows the current agenda of the active meeting
 *
 * Do not forget to enable the following settings in your OpenSim configuration
 * For loading dynamic textures and enable JSON support:
 * [XEngine]
 *      AllowOSFunctions = true
 *      AllowMODFunctions = true
 * [JsonStore]
 *      Enabled = true
 *
 * @author Niels Witte
 * @date March 19th, 2014
 * @version 0.1
 */
string title;                       // Meeting title
string agenda;                      // Meeting agenda
integer currentAgendaItem = 0;      // Active agenda item

integer lineHeight = 25;            // Text line height
integer offsetX = 25;               // Offset from the side
integer titleHeight = 50;           // Additional offset for the title
integer offsetY = 125;              // Offset from the top

/**
 * Erase all text on screen
 */
clearText() {
    llSetText("", <0,0,0>, 0);
    // Set color to white and remove textures
    llSetTexture(TEXTURE_BLANK, ALL_SIDES);
    llSetColor(<1.0, 1.0, 1.0>, ALL_SIDES);
}

/**
 * Write the agenda to the screen
 */
writeAgenda() {
    // Clear previous agenda
    llSetTexture(TEXTURE_BLANK, ALL_SIDES);
    llSetColor(<1.0, 1.0, 1.0>, ALL_SIDES);

    string agendaList = ""; // Storage for our drawing commands
    list agendaItems  = llParseString2List(agenda, "\n", "");

    // Set title
    agendaList = osSetFontSize(agendaList, 30);
    agendaList = osMovePen(agendaList, offsetX, offsetY);
    agendaList += "FontProp B;";
    agendaList = osDrawText(agendaList, title);

    agendaList = osSetFontSize(agendaList, 14);
    agendaList += "FontProp R;";
    integer x;
    // Create agenda
    for(x = 0; x <= llGetListLength(agendaItems); x++) {
        // Highlight active item
        if(currentAgendaItem == x) {
            agendaList += "FontProp B;";
            agendaList += "PenColour Red;";
        }
        agendaList = osMovePen(agendaList, offsetX, offsetY + titleHeight + lineHeight * x);
        agendaList = osDrawText(agendaList, llList2String(""+ agendaItems, x));
        // Back to black
        if(currentAgendaItem == x) {
            agendaList += "FontProp R;";
            agendaList += "PenColour Black;";
        }
    }

    // Now draw the image
    osSetDynamicTextureData( "", "vector", agendaList, "width:1024,height:1024", 0 );
}

/**
 * Default state
 */
default {
    /**
     * On entry reset everything
     */
    state_entry() {
        title               = "";
        agenda              = "";
        currentAgendaItem   = 0;
        clearText();
    }

    /**
     * Handle messages from linked scripts
     *
     * @param integer sender_num
     * @param integer num
     * @param string msg
     * @param integer id
     */
    link_message(integer sender_num, integer num, string msg, key id) {
        // Update the agenda
        if(num == 1) {
            agenda = msg;
        // Update the current selected item
        } else if(num == 2) {
            currentAgendaItem = (integer) msg;
            currentAgendaItem++;
            writeAgenda();
        // Update the title
        } else if(num == 3) {
            title = msg;
        // Clear the screen
        } else if(num == 10) {
            clearText();
        }
    }
}