CREATE TABLE objectives.objective (
  obj_id serial PRIMARY KEY,
  obj_name text NOT NULL,
  obj_open boolean NOT NULL,
  obj_deadline date
);

CREATE TABLE objectives.objective_topic (
  obj_id integer NOT NULL REFERENCES objectives.objective,
  top_id integer NOT NULL REFERENCES organ.topic,
  CONSTRAINT objective_topic_pkey PRIMARY KEY(obj_id, top_id)
);

CREATE TABLE objectives.objective_dossier (
  obj_id integer NOT NULL REFERENCES objectives.objective,
  dos_id integer NOT NULL REFERENCES organ.dossier,
  CONSTRAINT objective_dossier_pkey PRIMARY KEY(obj_id, dos_id)
);
