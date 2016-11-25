CREATE TYPE events.event_duration
  AS ENUM ('standard', 'ponctual', 'allday');

CREATE TABLE events.event (
  eve_id serial PRIMARY KEY,
  eve_title text NOT NULL,
  ety_id integer NOT NULL REFERENCES events.event_type,
  -- dates
  eve_duration events.event_duration NOT NULL DEFAULT 'standard',
  eve_start_time timestamp with time zone NOT NULL,
  eve_end_time timestamp with time zone NOT NULL,
  -- for synchronization
  eve_mod_date timestamp with time zone,
  eve_del_date timestamp with time zone
  -- other, optional info
  eve_place text,
  eve_cost money,
  eve_description text,
  eve_sumup text
);

CREATE TABLE events.event_topic (
  eve_id integer NOT NULL REFERENCES events.event,
  top_id integer NOT NULL REFERENCES organ.topic,
  CONSTRAINT event_topic_pkey PRIMARY KEY(eve_id, top_id)
);

CREATE TABLE documents.event_dossier (
  eve_id integer NOT NULL REFERENCES events.event,
  dos_id integer NOT NULL REFERENCES organ.dossier,
  CONSTRAINT event_dossier_pkey PRIMARY KEY(eve_id, dos_id)
);
