CREATE SCHEMA login;
SET search_path = login;

-- User groups
-- Specific rights for usergroups
CREATE TYPE login.usergroup_right AS ENUM (
  'internal_dossier_add',
  'external_dossier_add'
);

-- Specific rights for usergroups on topics
CREATE TYPE login.usergroup_topic_right AS ENUM (
  'event_read',	  -- can view events ?
  'event_add',	  -- can add an event
  'event_update'  -- can modify an event ?
);

CREATE TABLE login.usergroup (
  ugr_id serial PRIMARY KEY,
  ugr_name text NOT NULL UNIQUE,
  ugr_rights login.usergroup_right[],
  ugr_statuses organ.dossier_status_value[]
);

CREATE TABLE login.usergroup_portal (
  ugp_id serial PRIMARY KEY,
  ugr_id integer NOT NULL REFERENCES login.usergroup,
  por_id integer NOT NULL REFERENCES portal.portal,
  UNIQUE (ugr_id, por_id)
);

CREATE TABLE login.usergroup_group_dossiers (
  uggd_id serial PRIMARY KEY,
  ugr_id integer NOT NULL REFERENCES login.usergroup,
  grp_id integer NOT NULL REFERENCES organ.group,
  UNIQUE (ugr_id, grp_id)
);

CREATE TABLE login.usergroup_group_participants (
  uggp_id serial PRIMARY KEY,
  ugr_id integer NOT NULL REFERENCES login.usergroup,
  grp_id integer NOT NULL REFERENCES organ.group,
  UNIQUE (ugr_id, grp_id)
);

CREATE TABLE login.usergroup_topic (
  ugt_id serial PRIMARY KEY,
  ugr_id integer NOT NULL REFERENCES login.usergroup,
  top_id integer NOT NULL REFERENCES organ.topic,
  ugt_rights login.usergroup_topic_right[],
  UNIQUE (ugr_id, top_id)
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
  par_id integer NOT NULL UNIQUE REFERENCES organ.participant,
  ugr_id integer REFERENCES login.usergroup,
  usr_token integer UNIQUE,
  usr_token_creation_date timestamp with time zone,
  usr_last_connection_date timestamp with time zone,
  usr_last_connection_ip inet -- ipv4/ipv6 address
);

INSERT INTO organ.participant (par_firstname, par_lastname) 
VALUES ('Variation', 'User'), ('Portal', 'User'), ('Organ', 'user');

INSERT INTO login.user(usr_login, usr_salt, usr_rights, par_id) 
  VALUES ('variation', pgcrypto.crypt('variation', pgcrypto.gen_salt('bf', 8)), 
  	  '{users,structure,organization}',
	  (SELECT par_id FROM organ.participant WHERE par_firstname='Variation'));

INSERT INTO login.user(usr_login, usr_salt, usr_rights, par_id) 
  VALUES ('portaluser', pgcrypto.crypt('portal/user', pgcrypto.gen_salt('bf', 8)),
   	  '{structure}',
	  (SELECT par_id FROM organ.participant WHERE par_firstname='Portal'));

INSERT INTO login.user(usr_login, usr_salt, usr_rights, par_id) 
  VALUES ('organuser', pgcrypto.crypt('organ/user', pgcrypto.gen_salt('bf', 8)), 
  	  '{organization}',
	  (SELECT par_id FROM organ.participant WHERE par_firstname='Organ'));

