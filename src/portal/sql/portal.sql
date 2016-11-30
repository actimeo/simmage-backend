CREATE SCHEMA portal;

SET search_path = portal;

CREATE TYPE portal.entity AS ENUM (
  'patient',
  'staff',
  'contact',
  'family'
);

CREATE TYPE portal.mainmenu_content_type AS ENUM (
  'documents',
  'events',
  'lists',
  'notes',
  'resources'
);

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
  mme_order integer NOT NULL CHECK (mme_order > 0),
  mme_title text NOT NULL,
  mme_icon text NOT NULL,
  mme_content_type portal.mainmenu_content_type,
  mme_content_id integer,
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



