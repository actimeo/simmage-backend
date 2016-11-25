CREATE SCHEMA notes;

SET search_path = notes;

CREATE TABLE notes.notesview (
  nov_id serial PRIMARY KEY,
  nov_name text NOT NULL
);

CREATE TABLE notes.notesview_topic (
  nov_id integer NOT NULL REFERENCES notes.notesview,
  top_id integer NOT NULL REFERENCES organ.topic,
  CONSTRAINT notesview_topic_pkey PRIMARY KEY(nov_id, top_id)
);
