In this section you will find Frequently Asked Questions (FAQ). The questions and other common issues are grouped in categories. For example questions related to user management are all placed under the section Users. If the answer to your question or issue is not listed here, please take a look at the [CMS documentation](CMS/CMS.html) .

## Users
The following questions/solutions are related to user management.

### How can I gain access to the CMS?
To access the CMS an existing user with `WRITE` permissions to the User API needs to create an account for you. When this is done correctly you will receive an e-mail

If you have just installed the CMS, be sure to import the `users.sql` file as well into the database. After doing so, you can login with the credentials below. Be sure to at least change the password after logging in.

| Username     | Password   |
|--------------|------------|
| admin        | password   |

### My username and password are correct but I cannot login.
In some cases using saved usernames and passwords which are automatically entered by your browser can result in displaying login failure messages. Preferably enter your username and password manually.

If logging in still fails, contact the system administrator and let him check your user account's permissions.

### How can I change my username?
You can not change your username. The only way this could be done is by letting the system administrator change your username in the `users` table in the MySQL database.

### I can see my permissions when looking at my user profile, but they are not visible when editing the user
This probably means that you do not have `WRITE` permissions on the User API. Changing permissions is only allowed by certain users. If you think your permissions are not sufficient, please contact the system administrator.

### How can I become a group member?
You can only add members to a group if you have `WRITE` permission for the User API. You can however, always leave the group. So if you want to become a member of a group, contact a user with `WRITE` permissions on the User API. For example the system administrator.

## Files, Documents and Presentations
The following questions and solutions are related to files, documents and presentations.

## The document I've added to a meeting is visible in the meeting details but other participants can not access the document
Only users with `ALL` permission for the Files API can access documents of all users. If you want others to access your documents, create a group with those users and share the document with the group.