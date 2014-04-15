For each API there are permissions for the user account. The user requires a specific level for accessing certain
functions. The levels are represented by integers that represent a binary permission.

| Level         | Binary    | Integer |
|---------------|-----------|---------|
| NONE          | 000       | 0       |
| READ          | 100       | 4       |
| EXECUTE       | 101       | 5       |
| WRITE         | 110       | 6       |
| ALL           | 111       | 7       |

These numbers can be used for the following parameters:

| Parameter         | Type      | Description                                                   |
|-------------------|-----------|---------------------------------------------------------------|
| auth              | Integer   | Permission level regarding Authorization API                  |
| chat              | Integer   | Permission level regarding Chats API                          |
| comment           | Integer   | Permission level regarding Comments API                       |
| document          | Integer   | Permission level regarding Documents API                      |
| file              | Integer   | Permission level regarding Files API                          |
| grid              | Integer   | Permission level regarding Grids API                          |
| meeting           | Integer   | Permission level regarding Meetings API                       |
| meetingroom       | Integer   | Permission level regarding Meeting rooms API                  |
| presentation      | Integer   | Permission level regarding Presentations API                  |
| user              | Integer   | Permission level regarding Users API                          |
