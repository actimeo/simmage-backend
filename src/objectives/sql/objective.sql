CREATE TYPE objectives.objective_status
  as ENUM ('waiting', 'started', 'reached', 'failed');

CREATE TABLE objectives.objective (
  obj_id serial PRIMARY KEY,
  obj_name text NOT NULL,
  dos_id integer NOT NULL REFERENCES organ.dossier,
  obj_status objectives.objective_status NOT NULL,
  obj_start_date date NOT NULL,
  obj_end_date date
);

CREATE TABLE objectives.objective_topic (
  obj_id integer NOT NULL REFERENCES objectives.objective,
  top_id integer NOT NULL REFERENCES organ.topic,
  CONSTRAINT objective_topic_pkey PRIMARY KEY(obj_id, top_id)
);

