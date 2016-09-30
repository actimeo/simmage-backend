CREATE SCHEMA events;

CREATE TYPE events.event_category AS ENUM (
  'incident',
  'expense',
  'meeting',
  'absence'
);

CREATE TABLE events.event_type (
  ety_id SERIAL PRIMARY KEY,
  ety_category events.event_category NOT NULL,
  ety_name text NOT NULL,
  ety_individual_name boolean NOT NULL
);

CREATE TABLE events.event_type_topic (
  ety_id integer NOT NULL REFERENCES events.event_type,
  top_id integer NOT NULL REFERENCES organ.topic,
  CONSTRAINT event_type_topic_pkey PRIMARY KEY(ety_id, top_id)
);

CREATE TABLE events.event_type_organization (
  ety_id integer NOT NULL REFERENCES events.event_type,
  org_id integer NOT NULL REFERENCES organ.organization,
  CONSTRAINT event_type_organization_pkey PRIMARY KEY(ety_id, org_id)
);
