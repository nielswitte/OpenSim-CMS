Meetings are the heart of the CMS, this is the place where all the data becomes connected. The initial page provides an overview of the meetings scheduled. You can toggle the visibility from all meetings to only your own meetings (meetings in which you are a participant). The overview with all meetings shows all meetings from the past two weeks and in the future, the overview of your meetings will return all meetings you participate in.

## Schedule meeting
If your user account has `EXECUTE` permission for the Meetings API, you can schedule new meetings. Without this permission, the button `Schedule meeting` will not be visible.

Scheduling a meeting will require you to fill in the form. Enter a name, start date and time, end date and time. Each meeting requires the reservation of a room, this can be done by selecting a grid, a region on that grid and finally a room in that region.

**Notice:** Selecting a grid will trigger the CMS to request the regions from the API, it could take a few seconds before the regions and rooms become visible in the list.

### Agenda
The next field will be the agenda field. The agenda specifies the structure and an outline of the topics that will be discussed in the meeting. Use a simple numbered list with each topic starting with a number and on a new line. For example:

```
1. Opening
2. Notices
    2.1. Notices of task force 1
    2.2. Notices of other participants
3. Minutes previous meeting
4. Topic A
    4.1. Subtopic
5. Closing
```

**Notice:** The dot (`.`) after the number is optional, the dot between the numbers to indicate hierarchy is mandatory and there should always be a space between the number and the start of the topic. Indentations are also optional.

### Documents and Participants
Both documents and participants can be added and removed the same way. Using the input field with auto completion you can add documents and participants to the meeting and with the red cross behind each item you can remove the item from the meeting.

After completing this form you can schedule the meeting. This will perform a number of checks to see if the room is not booked double or that the times and dates are valid.

When all checks passed all the participants will receive an e-mail with the meeting details and an calendar invite (`ics`). The invite enables participants to add the meeting directly to their online agenda.

## Meeting details
When clicking on a meeting in the calendar you will go to the meeting details. Or you will receive an error message when you do not have the required permissions (`ALL`) to see the meeting and are not a participant.

The meeting details will provide an overview of all the data filled in during the creation of the meeting.


### Teleport
You can directly teleport your avatar to the meeting location in the virtual environment of OpenSim. This requires your avatar to be online on the grid, and linked to your account before pressing the `Teleport avatar to meeting` button.

Another option is to copy the URL of the `Go to meeting` button or the URL displayed in the `Location` section, both are the same, and paste it in the address bar of your viewer.

### Comments
Users can also place comments below the meeting, for example start a discussion about the agenda. Commends are threaded and ordered with the oldest first. New comments placed after your previous login are highlighted with a green border on the left side. Comments are formatted in [Markdown](http://daringfireball.net/projects/markdown/syntax) and a preview is available at the right side of the comment field.

## Editing a meeting
When you meet one of these requirements, it is possible to make changes to the meeting.

 * Viewing your own meeting.
 * Are participant of the meeting and have `WRITE` permissions to the Meetings API.
 * Have `ALL` permissions to the Meeting API.

**Notice:** Meetings can be rescheduled, however be sure to inform all participants since their calendar invite will not automatically update.

Besides rescheduling, almost all aspects of the meeting can be changed. When changing the time and location, be sure to move the meeting to a free slot for the given room. Not doing this will result in error messages appearing.

## Minutes
During a meeting, the meeting logger (`meetingLogger.lsl`) can be activated and used to record the progress of the meeting. This script will save all chat messages, entering of avatars to the meeting and keeps track of the progress based on the agenda. It also enables users to vote.

The minutes will contain all text chat happening in a radius of 20M around the recorder, this is a constraint by OpenSim. However, because it will also record the text of objects and or avatars it might become a bit chaotic. Therefore, at the top of the minutes overview you can toggle a filter `Only show invited participants`. Which will hide all entries from objects other than the Meeting Log script itself and avatars of invited participants.

### Voting
During a meeting you can issue a vote. During a vote all participants can vote `Approve`, `Reject`, `Blank` or `None` completely anonymous. After all participants have voted or when someone ends the voting, the results will become visible. These results will be displayed as progress bars in the minutes and as numbers in the OpenSim environment.

