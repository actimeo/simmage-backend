-- user_add
-- user_delete
-- user_change_rights

SET search_path = login;

CREATE OR REPLACE FUNCTION _token_assert (
  prm_token integer, 
  prm_rights login.user_right[]) 
RETURNS VOID
LANGUAGE plpgsql
AS $$
DECLARE
  usr login."user";
BEGIN
  SELECT * INTO usr FROM login."user" WHERE 
    usr_token = prm_token AND
    (prm_rights ISNULL OR prm_rights <@ usr_rights); -- <@ 'is contained by'
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'insufficient_privilege';
  END IF;
END;
$$;
COMMENT ON FUNCTION _token_assert (prm_token integer, prm_rights login.user_right[])
IS '[INTERNAL] Assert that a token is valid.
Also assert that the user owns all the rights given in parameter.
If some assertion fails, an ''insufficient_privilege'' exception is raised.';

CREATE OR REPLACE FUNCTION login._token_assert_other_login(
  prm_token integer, 
  prm_login varchar)
RETURNS VOID
LANGUAGE plpgsql
AS $$
BEGIN
  IF EXISTS (
    SELECT 1 FROM login."user" WHERE 
      usr_token = prm_token AND 
      usr_login = prm_login) THEN
    RAISE EXCEPTION USING ERRCODE = 'invalid_authorization_specification';
  END IF;
END;
$$;
COMMENT ON FUNCTION login._token_assert_other_login(prm_token integer, prm_login varchar) 
IS '[INTERNAL] Assert that the login and token are not associated.';

CREATE OR REPLACE FUNCTION _user_token_create (
  prm_login varchar) 
RETURNS varchar
  LANGUAGE plpgsql
  AS $$
DECLARE
	tok integer DEFAULT NULL;
	found BOOLEAN DEFAULT true;
BEGIN
  WHILE found LOOP
    tok = (RANDOM()*1000000000)::int;
    IF NOT EXISTS (SELECT 1 FROM login."user" WHERE usr_token = tok) THEN
      found = false;
    END IF;
  END LOOP;
  UPDATE login."user" SET 
    usr_token = tok, 
    usr_token_creation_date = CURRENT_TIMESTAMP 
    WHERE usr_login = prm_login;
  RETURN tok;
END;
$$;
COMMENT ON FUNCTION login._user_token_create (prm_login varchar) IS 
'[INTERNAL] Create a new token for the given user';


DROP FUNCTION IF EXISTS user_login(
  prm_login character varying, 
  prm_pwd character varying, 
  prm_rights login.user_right[]);
DROP TYPE IF EXISTS user_login;
CREATE TYPE user_login AS (
  usr_token integer,
  usr_temp_pwd boolean,
  usr_rights login.user_right[],
  par_id integer,
  ugr_id integer,
  par_firstname text,
  par_lastname text
);
COMMENT ON TYPE user_login IS 'Type returned by user_login function';
COMMENT ON COLUMN user_login.usr_token IS 'Token to use for other functions';
COMMENT ON COLUMN user_login.usr_temp_pwd IS 'True if the password is temporary';
COMMENT ON COLUMN user_login.usr_rights IS 'List of rights owned by the user.';
COMMENT ON COLUMN user_login.par_id IS 'Participant linked with this user.';

CREATE OR REPLACE FUNCTION user_login(
  prm_login character varying, 
  prm_pwd character varying, 
  prm_rights login.user_right[]) 
RETURNS user_login
  LANGUAGE plpgsql
  AS $$
DECLARE
  row login.user_login;
  usr varchar;
  tok integer DEFAULT NULL;
BEGIN
  SELECT usr_login INTO usr FROM login."user"
    WHERE usr_login = prm_login AND 
    pgcrypto.crypt (prm_pwd, usr_salt) = usr_salt AND
    (prm_rights ISNULL OR prm_rights <@ usr_rights); -- <@: 'is contained by'
  IF NOT FOUND THEN 
    RAISE EXCEPTION USING ERRCODE = 'invalid_authorization_specification';
  END IF;
  SELECT usr_token INTO tok FROM login."user" WHERE usr_token NOTNULL AND usr_login = usr;
  IF NOT FOUND THEN
    SELECT * INTO tok FROM login._user_token_create (usr);
  END IF;
  SELECT DISTINCT tok, (usr_pwd NOTNULL), usr_rights, par_id, ugr_id, par_firstname, par_lastname INTO row 
    FROM login."user"
    LEFT JOIN organ.participant USING(par_id)
    WHERE usr_login = usr;
  RETURN row;
END;
$$;
COMMENT ON FUNCTION login.user_login(character varying, character varying, prm_rights login.user_right[]) IS 
'Authenticate a user from its login and password.
If prm_rights is not null, also verify that user owns all the specified rights.
If authorization is ok, returns:
 - usr_token: a new token to be used for the following operations
 - usr_temp_pwd: true if the user is using a temporary password
 - usr_rights: the list of rights owned by the user.
If authorization fails, an exception is raised with code invalid_authorization_specification
';

CREATE OR REPLACE FUNCTION user_logout (
  prm_token integer)
RETURNS VOID
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  UPDATE login."user" SET usr_token = NULL, usr_token_creation_date = NULL
    WHERE "user".usr_token = prm_token;
END;
$$;
COMMENT ON FUNCTION login.user_logout(integer) IS 
'Disconnect the user. The user token will not be usable anymore after this call.';

CREATE OR REPLACE FUNCTION user_change_password(
  prm_token integer, 
  prm_password varchar)
RETURNS VOID
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  UPDATE login."user" SET usr_pwd = NULL, usr_salt = pgcrypto.crypt (prm_password, pgcrypto.gen_salt('bf', 8)) 
    WHERE usr_token = prm_token;
END;
$$;
COMMENT ON FUNCTION user_change_password(prm_token integer, prm_password varchar) IS
'Change the password of the current user.';

CREATE OR REPLACE FUNCTION user_regenerate_password(
  prm_token integer, 
  prm_login varchar)
RETURNS varchar
LANGUAGE plpgsql
AS $$
DECLARE
  newpwd varchar;
BEGIN
  PERFORM login._token_assert(prm_token, '{users}');
  PERFORM login._token_assert_other_login(prm_token, prm_login);
  newpwd = LPAD((random()*1000000)::int::varchar, 6, '0');
  UPDATE login."user" SET 
    usr_salt = pgcrypto.crypt (newpwd, pgcrypto.gen_salt('bf', 8)), 
    usr_pwd = newpwd
    WHERE usr_login = prm_login;
  RETURN newpwd;
END;
$$;
COMMENT ON FUNCTION user_regenerate_password(prm_token integer, prm_login varchar) IS
'Regenerate a temporary password for the user given in parameter.
The user given in parameter cannot be the current user.
';

CREATE OR REPLACE FUNCTION user_add(
  prm_token integer, 
  prm_login text, 
  prm_rights login.user_right[], 
  prm_par_id integer) 
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert (prm_token, '{users}');
  INSERT INTO login."user" (usr_login, usr_rights, par_id) VALUES (prm_login, prm_rights, prm_par_id);  
  PERFORM login.user_regenerate_password(prm_token, prm_login);
END;
$$;
COMMENT ON FUNCTION user_add(prm_token integer, prm_login text, prm_rights login.user_right[], prm_par_id integer) 
IS 'Create a new user aith the specified rights, and link him to a participant. If prm_par_id is null, 
this user will have access to all patients. A new temporary password is generated.';

DROP FUNCTION IF EXISTS login.user_info(prm_token integer, prm_login text);
DROP TYPE IF EXISTS login.user_info;
CREATE TYPE login.user_info AS (
  usr_login text,
  usr_temp_pwd text,
  usr_rights login.user_right[],
  par_id integer,
  ugr_id integer,
  par_firstname text,
  par_lastname text
);

CREATE FUNCTION login.user_info(
  prm_token integer, 
  prm_login text)
RETURNS login.user_info
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret login.user_info;
BEGIN
  PERFORM login._token_assert (prm_token, '{users}');
  SELECT usr_login, usr_pwd, usr_rights, par_id, ugr_id, par_firstname, par_lastname INTO ret 
    FROM login."user" 
    LEFT JOIN organ.participant USING(par_id)
    WHERE usr_login = prm_login;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION login.user_info(prm_token integer, prm_login text) IS 'Return information about a user';

CREATE OR REPLACE FUNCTION login.user_participant_set(
  prm_token integer, 
  prm_login text, 
  prm_par_id integer)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert (prm_token, '{users}');
  UPDATE login.user SET par_id = prm_par_id WHERE usr_login = prm_login;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION login.user_participant_set(prm_token integer, prm_login text, prm_par_id integer) 
IS 'Link a participant to a user';

CREATE OR REPLACE FUNCTION login.user_usergroup_set(
  prm_token integer, 
  prm_login text, 
  prm_ugr_id integer)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert (prm_token, '{users}');
  UPDATE login.user SET ugr_id = prm_ugr_id WHERE usr_login = prm_login;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION login.user_usergroup_set(prm_token integer, prm_login text, prm_ugr_id integer) 
IS 'Place a user in a user group';

DROP FUNCTION IF EXISTS login.user_list(prm_token integer);
DROP TYPE IF EXISTS login.user_details;
CREATE TYPE login.user_details AS (
  usr_login text,
  usr_rights login.user_right[],
  par_id integer,
  par_firstname text,
  par_lastname text,
  ugr_id integer,
  ugr_name text
);

CREATE FUNCTION login.user_list(prm_token integer)
RETURNS SETOF login.user_details
LANGUAGE plpgsql
STABLE
AS $$
DECLARE

BEGIN
  RETURN QUERY 
    SELECT 
      usr_login, usr_rights,
      par_id, par_firstname, par_lastname,
      ugr_id, ugr_name
      FROM login.user 
      LEFT JOIN organ.participant USING(par_id)
      LEFT JOIN login.usergroup USING(ugr_id)
      ORDER BY usr_login;
END;
$$;
COMMENT ON FUNCTION login.user_list(prm_token integer) IS 'Return the list of users';
