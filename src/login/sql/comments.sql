COMMENT ON SCHEMA login IS 'This module is used for the user to authenticate. 
Each user has a login and a password.

Adding a user
-------------
A first user is created during installation, with the login ''variation'' and a password provided during 
installation.

Users with "users" privileges can add users.

A password is composed of at least 8 characters, from 3 different types from (uppercase, lowercase, digit, 
special char).

A password must be changed at least every 6(param) months.

User Authentication
-------------------
A user can authenticate with the function user_login(login, pwd). This function returns a token which is used to 
access other functions of the api.

If authentication fails 5 times in a row for the same user, the account is blocked during a certain period of time 
and/or can be unblocked from an administrator (depending on parametrization).

The token becomes invalid:
- after a certain period of inactivity (ie no function was called with this token)
- when user disconnects with function user_logout(token)

Staff member
------------
A user can be linked to a staff member. If so, the user will have access to patients assigned to groups at which 
the staff member is also assigned.
If a user is not linked to a staff member, he will be considered an admin and hace access to all patients.

Portals
-------
A user can have access to patients data through one or several portals.
';

COMMENT ON TYPE login.user_details IS 'Details of a user, returned by user_list call';

COMMENT ON TYPE login.user_info IS 'User information, returned by user_info call';

COMMENT ON TYPE login.user_login IS 'Type returned by user_login function';

COMMENT ON TYPE login.user_usergroup_type IS '';

COMMENT ON TABLE login.user IS 'Webservice users';
COMMENT ON COLUMN login.user.usr_login IS 'User login';
COMMENT ON COLUMN login.user.usr_salt IS 'Encrypted password';
COMMENT ON COLUMN login.user.usr_pwd IS 'Clear temporary password';
COMMENT ON COLUMN login.user.usr_digest IS 'Encrypted password for webdav';
COMMENT ON COLUMN login.user.usr_rights IS 'Array of special rights for this user';
COMMENT ON COLUMN login.user.par_id IS 'Participant attached to the user';
COMMENT ON COLUMN login.user.ugr_id IS 'Usergroup of the user, or null for an admin-only user';
COMMENT ON COLUMN login.user.usr_token IS 'Token id returned after authentication';
COMMENT ON COLUMN login.user.usr_token_creation_date IS 'Token creation date for validity';
COMMENT ON COLUMN login.user.usr_last_connection_date IS '';
COMMENT ON COLUMN login.user.usr_last_connection_ip IS '';

COMMENT ON TABLE login.usergroup IS 'Usergroup, defining rights for users';
COMMENT ON COLUMN login.usergroup.ugr_id IS 'Unique identifier';
COMMENT ON COLUMN login.usergroup.ugr_name IS 'Usergroup name';
COMMENT ON COLUMN login.usergroup.ugr_rights IS '';
COMMENT ON COLUMN login.usergroup.ugr_statuses IS '';

COMMENT ON TABLE login.usergroup_group_dossiers IS 'Dossiers from groups that users from a usergroup can view';
COMMENT ON COLUMN login.usergroup_group_dossiers.uggd_id IS 'Unique identifier';
COMMENT ON COLUMN login.usergroup_group_dossiers.ugr_id IS 'Usergroup identifier';
COMMENT ON COLUMN login.usergroup_group_dossiers.grp_id IS 'Group identifier';

COMMENT ON TABLE login.usergroup_group_participants IS 'Participants from groups that users from a usergroup can view';
COMMENT ON COLUMN login.usergroup_group_participants.uggp_id IS 'Unique identifier';
COMMENT ON COLUMN login.usergroup_group_participants.ugr_id IS 'Usergroup identifier';
COMMENT ON COLUMN login.usergroup_group_participants.grp_id IS 'Group identifier';

COMMENT ON TABLE login.usergroup_portal IS 'Permissions for users from group to use portals';
COMMENT ON COLUMN login.usergroup_portal.ugp_id IS 'Unique identifier';
COMMENT ON COLUMN login.usergroup_portal.ugr_id IS 'User group identifier';
COMMENT ON COLUMN login.usergroup_portal.por_id IS 'A portal the user can use';

COMMENT ON TABLE login.usergroup_topic IS '';
COMMENT ON COLUMN login.usergroup_topic.ugt_id IS '';
COMMENT ON COLUMN login.usergroup_topic.ugr_id IS '';
COMMENT ON COLUMN login.usergroup_topic.top_id IS '';
COMMENT ON COLUMN login.usergroup_topic.ugt_rights IS '';
