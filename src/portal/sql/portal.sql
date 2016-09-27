CREATE SCHEMA portal;

SET search_path = portal;

CREATE TYPE portal.entity AS ENUM (
  'patient',
  'staff',
  'contact',
  'family'
);
/*
CREATE TYPE portal.topics AS ENUM (
  'social',
  'education',
  'health',
  'pedagogy',
  'justice',
  'employment',
  'lodging',
  'sport',
  'culture',
  'transport',
  'decisionmaker',
  'financer',
  'support',
  'entertainment',
  'juridic_protection',
  'catering',
  'housekeeping',
  'personal_assistance',
  'financial_assistance',
  'equipment',
  'family',
  'project',
  'visit',
  'medical_care',
  'dietetic',
  'occupational_therapy',
  'physiotherapy',
  'kinesitherapy',
  'speech_therapy',
  'psychomotor',
  'psychology',
  'residence_right',
  'formalities_assistance'
);
*/
CREATE TABLE portal (
  por_id serial PRIMARY KEY,
  por_name text NOT NULL UNIQUE,
  por_description text NOT NULL DEFAULT ''
);

CREATE TABLE mainsection (
  mse_id serial PRIMARY KEY,
  por_id integer NOT NULL REFERENCES portal.portal,
  mse_name text NOT NULL,
  mse_order integer NOT NULL CHECK (mse_order > 0) ,
  UNIQUE(por_id, mse_name),
  UNIQUE(por_id, mse_order)
);

CREATE TABLE mainmenu (
  mme_id serial PRIMARY KEY,
  mse_id integer NOT NULL REFERENCES portal.mainsection,
  mme_name text NOT NULL,
  mme_order integer NOT NULL CHECK (mme_order > 0) ,
  UNIQUE(mse_id, mme_name),
  UNIQUE(mse_id, mme_order) 
);

CREATE TABLE personsection (
  pse_id serial PRIMARY KEY,  
  por_id integer NOT NULL REFERENCES portal.portal,
  pse_entity portal.entity,
  pse_name text NOT NULL,
  pse_order integer NOT NULL CHECK (pse_order > 0) ,
  UNIQUE(por_id, pse_entity, pse_name),
  UNIQUE(por_id, pse_entity, pse_order)
);

CREATE TABLE personmenu (
  pme_id serial PRIMARY KEY,
  pse_id integer NOT NULL REFERENCES portal.personsection,
  pme_name text NOT NULL,
  pme_order integer NOT NULL CHECK (pme_order > 0) ,
  UNIQUE(pse_id, pme_name),
  UNIQUE(pse_id, pme_order) 
);

/* person views */
/* ************ */
CREATE TYPE portal.personview_element_type AS ENUM (
  'personsample1',
  'personsample2'
);

CREATE TABLE personview_element (
  pve_id serial PRIMARY KEY,
  pve_type portal.personview_element_type NOT NULL,
  pve_name text NOT NULL,
  pve_entities portal.entity[] NOT NULL 
    CHECK (pve_entities <> '{}'),
  UNIQUE(pve_type, pve_name)
);

CREATE TABLE personview (
  pme_id integer PRIMARY KEY REFERENCES portal.personmenu,
  pvi_title text NOT NULL,
  pvi_icon text NOT NULL,
  pve_id integer NOT NULL REFERENCES portal.personview_element
);

/* main views */
/* ********** */
CREATE TYPE portal.mainview_element_type AS ENUM (
  'sample1',
  'sample2'
);

CREATE TABLE mainview_element (
  mve_id serial PRIMARY KEY,
  mve_type portal.mainview_element_type NOT NULL,
  mve_name text NOT NULL,
  UNIQUE(mve_type, mve_name)
);

CREATE TABLE mainview (
  mme_id integer PRIMARY KEY REFERENCES portal.mainmenu,
  mvi_title text NOT NULL,
  mvi_icon text NOT NULL,
  mve_id integer NOT NULL REFERENCES portal.mainview_element,
  pme_id_associated integer REFERENCES portal.personview(pme_id)
);

-- portal parameters
CREATE TYPE portal.param_type AS ENUM (
  'topic', 
  'bool'
);

CREATE TYPE portal.param AS ENUM (
  'topic.topic',
  'add-patient.bool',
  'add-staff.bool',
  'add-contact.bool',
  'add-family.bool',
  'delete-notes.bool'  
);

CREATE TABLE portal.param_value (
  por_id integer NOT NULL REFERENCES portal.portal,
  pva_param portal.param NOT NULL,
  pva_value_bool boolean,
/*  pva_value_topic portal.topics, */
  CONSTRAINT pva_pkey PRIMARY KEY(por_id, pva_param)
);
--  TODO comment, one value NOTNULL exactly

