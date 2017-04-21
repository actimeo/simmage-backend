CREATE TYPE events.event_duration
  AS ENUM ('standard', 'ponctual', 'allday');

CREATE TYPE events.event_status
  AS ENUM ('scheduled', 'confirmed', 'canceled');

CREATE TABLE events.event (
  eve_id serial PRIMARY KEY,
  eve_title text NOT NULL,
  ety_id integer REFERENCES events.event_type,
  -- dates
  eve_duration events.event_duration NOT NULL DEFAULT 'standard',
  eve_status events.event_status NOT NULL DEFAULT 'confirmed',
  eve_start_time timestamp with time zone NOT NULL,
  eve_end_time timestamp with time zone,
  -- for synchronization
  eve_mod_date timestamp with time zone,
  eve_del_date timestamp with time zone,
  -- other, optional info
  eve_place text,
  eve_cost money,
  eve_description text,
  eve_sumup text,
  eve_author integer NOT NULL REFERENCES organ.participant,
  eve_creation_date timestamp with time zone
);

CREATE TABLE events.event_topic (
  eve_id integer NOT NULL REFERENCES events.event,
  top_id integer NOT NULL REFERENCES organ.topic,
  CONSTRAINT event_topic_pkey PRIMARY KEY(eve_id, top_id)
);

CREATE TABLE events.event_dossier (
  eve_id integer NOT NULL REFERENCES events.event,
  dos_id integer NOT NULL REFERENCES organ.dossier,
  CONSTRAINT event_dossier_pkey PRIMARY KEY(eve_id, dos_id)
);

CREATE TABLE events.event_participant (
  eve_id integer NOT NULL REFERENCES events.event,
  par_id integer NOT NULL REFERENCES organ.participant,
  CONSTRAINT event_participant_pkey PRIMARY KEY(eve_id, par_id)
);

CREATE TABLE events.event_resource (
  eve_id integer NOT NULL REFERENCES events.event,
  res_id integer NOT NULL REFERENCES resources.resource,
  CONSTRAINT event_resource_pkey PRIMARY KEY(eve_id, res_id)
);
