CREATE TABLE notes.note (
  not_id serial PRIMARY KEY,
  not_text text NOT NULL,
  not_creation_date timestamp with time zone,
  not_event_date timestamp with time zone,
  not_object text,
  not_author integer NOT NULL REFERENCES organ.participant
);

CREATE TABLE notes.note_topic (
  not_id integer NOT NULL REFERENCES notes.note,
  top_id integer NOT NULL REFERENCES organ.topic,
  CONSTRAINT note_topic_pkey PRIMARY KEY(not_id, top_id)
);

CREATE TABLE notes.note_dossier (
  not_id integer NOT NULL REFERENCES notes.note,
  dos_id integer NOT NULL REFERENCES organ.dossier,
  CONSTRAINT note_dossier_pkey PRIMARY KEY(not_id, dos_id)
);

CREATE TABLE notes.note_recipient (
  nor_id serial PRIMARY KEY,
  not_id integer NOT NULL REFERENCES notes.note,
  par_id integer NOT NULL REFERENCES organ.participant,
  nor_for_action boolean NOT NULL,
  nor_done boolean DEFAULT FALSE
);
