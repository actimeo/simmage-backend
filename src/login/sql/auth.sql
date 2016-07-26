CREATE SCHEMA login;
SET search_path = login;

-- User groups
CREATE TABLE login.usergroup (
  ugr_id serial PRIMARY KEY,
  ugr_name text NOT NULL UNIQUE
);

CREATE TABLE login.usergroup_portal (
  ugp_id serial PRIMARY KEY,
  ugr_id integer NOT NULL REFERENCES login.usergroup,
  por_id integer NOT NULL REFERENCES portal.portal,
  UNIQUE (ugr_id, por_id)
);

CREATE TABLE login.usergroup_group (
  ugg_id serial PRIMARY KEY,
  ugr_id integer NOT NULL REFERENCES login.usergroup,
  grp_id integer NOT NULL REFERENCES organ.group,
  UNIQUE (ugr_id, grp_id)
);

-- Users
-- Specific rights for users
CREATE TYPE login.user_right AS ENUM (
  'structure',    -- can edit portal structure
  'organization', -- can edit organization
  'users'         -- can manage users
);

CREATE TABLE login."user" (
  usr_login text PRIMARY KEY,
  usr_salt text,
  usr_pwd text,
  usr_digest text,
  usr_rights login.user_right[],
  par_id integer REFERENCES organ.participant,
  ugr_id integer /*NOT NULL*/ REFERENCES login.usergroup,
  usr_token integer UNIQUE,
  usr_token_creation_date timestamp with time zone
);

INSERT INTO login.user(usr_login, usr_salt, usr_rights) values ('variation', pgcrypto.crypt('variation', pgcrypto.gen_salt('bf', 8)), '{users,structure,organization}');
INSERT INTO login.user(usr_login, usr_salt, usr_rights) values ('portaluser', pgcrypto.crypt('portal/user', pgcrypto.gen_salt('bf', 8)), '{structure}');
INSERT INTO login.user(usr_login, usr_salt, usr_rights) values ('organuser', pgcrypto.crypt('organ/user', pgcrypto.gen_salt('bf', 8)), '{organization}');

