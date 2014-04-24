For each API there are permissions for the user account. The user requires a specific level for accessing certain functions. The levels are represented by integers that represent a binary permission.

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

Most `GET` requests only require `READ` permissions. To make changes to your own items `READ` is also often enough. To add new files, meetings and avatars `EXECUTE` permissions are required to the corresponding API. To make changes to items of other users you require `WRITE` permissions. However, `WRITE` permissions do not give you access to other user's documents. Besides, you can only give someone the `ALL` permissions when you have the `ALL` permissions on the User API.

## Explanation
When you have `READ` permission you can access most basic functions but you cannot make any changes, except to Files or Users you own. With `EXECUTE` permissions you can perform actions, such as schedule a meeting, upload a file, clear the cache, etc. `WRITE` permissions give you the possibility to make changes to content owned by other users. However, you can still only see your own files and files that are shared with groups where you're in. `ALL` is gives you access to everything, you can see all files even those that are not shared with a group, you can edit everything, remove everything, basically do everything the API can facilitate.

For example your user account has `EXECUTE` permission for the File API. In this case the method `GET /api/files/` will return all files you own and all files that are shared with you through the group(s) you're in. You can only make changes to your own files. However, if your permissions get upgraded to `WRITE` you still see the same files, but now you can make changes to files shared with you through groups.

**NOTICE:** Please mind that if your permissions are lower than `ALL` and someone adds a file to a meeting where you are a participant, you can see the file in the meeting details. However, if the file is not shared to a group you are a member of, you can not access the file's contents.