CREATE SCHEMA organ;

SET search_path = organ;

CREATE TABLE organ.topic (
  top_id serial PRIMARY KEY,
  top_name text NOT NULL UNIQUE,
  top_description text NOT NULL DEFAULT '',
  top_icon text NOT NULL DEFAULT 'health',
  top_color text NOT NULL DEFAULT '#000000'
);

CREATE TABLE organ.organization (
  org_id serial PRIMARY KEY,
  org_name text NOT NULL UNIQUE,
  org_description text NOT NULL DEFAULT '',
  org_internal boolean NOT NULL
);

CREATE TYPE organ.gender 
  as ENUM ('male', 'female');

CREATE TABLE organ.dossier (
  dos_id serial PRIMARY KEY,
  dos_firstname text,
  dos_lastname text,
  dos_birthdate date,
  dos_gender organ.gender,
  dos_grouped boolean NOT NULL DEFAULT false,
  dos_external boolean NOT NULL DEFAULT false,
  dos_groupname text,
  UNIQUE(dos_firstname, dos_lastname, dos_birthdate)
);
ALTER TABLE organ.dossier
ADD CONSTRAINT CC_Check_field_grouped_based
CHECK ((dos_grouped = true 
        AND dos_groupname IS NOT NULL 
	AND dos_firstname IS NULL 
	AND dos_lastname IS NULL 
	AND dos_birthdate IS NULL 
	AND dos_gender IS NULL)
       OR 
       (dos_grouped = false 
        AND dos_groupname IS NULL 
	AND dos_firstname IS NOT NULL 
	AND dos_lastname IS NOT NULL 
	AND dos_birthdate IS NOT NULL 
	AND dos_gender IS NOT NULL));

CREATE TYPE organ.dossier_relationship 
  as ENUM ('brother', 'sister', 'father', 'mother', 'son', 'daughter', 'husband', 'wife', 'family', 'nephew', 'niece');

CREATE TABLE organ.dossier_link (
  dol_id serial PRIMARY KEY,
  dos_id integer NOT NULL REFERENCES organ.dossier,
  dos_id_related integer NOT NULL REFERENCES organ.dossier,
  dol_relationship organ.dossier_relationship,
  UNIQUE(dos_id, dos_id_related)
);
ALTER TABLE organ.dossier_link
ADD CONSTRAINT CC_Check_dossiers_linked_not_duplicate
CHECK (dos_id != dos_id_related);

-- DOSSIER STATUS
CREATE TYPE organ.dossier_status_value
  AS ENUM ('preadmission', 'admission', 'present', 'left');

CREATE TABLE organ.dossier_status (
  dst_id serial PRIMARY KEY,
  dos_id integer NOT NULL REFERENCES organ.dossier,
  org_id integer NOT NULL REFERENCES organ.organization,
  dst_value organ.dossier_status_value NOT NULL,
  dst_start date NOT NULL DEFAULT '-infinity',
  dst_end date NOT NULL DEFAULT 'infinity'
);

CREATE TYPE organ.group_orientation as ENUM ('organization', 'participant');
CREATE TABLE organ.group (
  grp_id serial PRIMARY KEY,
  org_id integer NOT NULL REFERENCES organ.organization,
  grp_name text NOT NULL,
  grp_description text NOT NULL DEFAULT '',
  grp_mandatory boolean NOT NULL DEFAULT FALSE,
  grp_orientation organ.group_orientation NOT NULL DEFAULT 'organization',
  UNIQUE(org_id, grp_name)
);

CREATE TABLE organ.group_topic (
  grt_id serial PRIMARY KEY,
  grp_id integer NOT NULL REFERENCES organ.group,
  top_id integer NOT NULL REFERENCES organ.topic,
  UNIQUE(grp_id, top_id)
);

CREATE TABLE organ.group_exclusive (
  gre_id serial PRIMARY KEY,
  gre_name text NOT NULL UNIQUE
);

CREATE TABLE organ.group_exclusive_group (
  geg_id serial PRIMARY KEY,
  gre_id integer NOT NULL REFERENCES organ.group_exclusive,
  grp_id integer NOT NULL REFERENCES organ.group UNIQUE  
);

CREATE TABLE organ.dossier_assignment (
  doa_id serial PRIMARY KEY,
  dos_id integer NOT NULL REFERENCES organ.dossier,
  grp_id integer NOT NULL REFERENCES organ.group,
  doa_visible boolean NOT NULL DEFAULT TRUE
);

-- PARTICIPANTS
CREATE TABLE organ.participant (
  par_id serial PRIMARY KEY,
  par_firstname text NOT NULL,
  par_lastname text NOT NULL,
  par_email text,
  UNIQUE(par_firstname, par_lastname)
);

CREATE TABLE organ.participant_assignment (
  paa_id serial PRIMARY KEY,
  grp_id integer NOT NULL REFERENCES organ.group,
  par_id integer NOT NULL REFERENCES organ.participant
);

CREATE TABLE organ.referee (
  ref_id serial PRIMARY KEY,
  doa_id integer NOT NULL REFERENCES organ.dossier_assignment,
  paa_id integer NOT NULL REFERENCES organ.participant_assignment,
  ref_function text
);
