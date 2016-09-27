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

COMMENT ON TABLE login.user IS 'Webservice users';

COMMENT ON TABLE login.usergroup_portal IS 'Permissions for users from group to use portals';

COMMENT ON COLUMN login.user.usr_login IS 'User login';

COMMENT ON COLUMN login.user.usr_salt IS 'Encrypted password';

COMMENT ON COLUMN login.user.usr_pwd IS 'Clear temporary password';

COMMENT ON COLUMN login.user.usr_digest IS 'Encrypted password for webdav';

COMMENT ON COLUMN login.user.usr_rights IS 'Array of special rights for this user';

COMMENT ON COLUMN login.user.par_id IS 'Participant attached to the user';

COMMENT ON COLUMN login.user.ugr_id IS 'Usergroup of the user, or null for an admin-only user';

COMMENT ON COLUMN login.user.usr_token IS 'Token id returned after authentication';

COMMENT ON COLUMN login.user.usr_token_creation_date IS 'Token creation date for validity';

COMMENT ON COLUMN login.usergroup_portal.ugp_id IS 'Unique identifier';

COMMENT ON COLUMN login.usergroup_portal.ugr_id IS 'User group identifier';

COMMENT ON COLUMN login.usergroup_portal.por_id IS 'A portal the user can use';

COMMENT ON TYPE login.user_right IS 'Specific rights for users:
-- structure: can edit portals structure,
-- organization: can edit organization,
-- users: can manage users.';
