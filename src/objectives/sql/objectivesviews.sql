CREATE SCHEMA objectives;

SET search_path = objectives;

CREATE TABLE objectives.objectivesview (
  obv_id serial PRIMARY KEY,
  obv_name text NOT NULL
);

CREATE TABLE objectives.objectivesview_topic (
  obv_id integer NOT NULL REFERENCES objectives.objectivesview,
  top_id integer NOT NULL REFERENCES organ.topic,
  CONSTRAINT objectivesview_topic_pkey PRIMARY KEY(obv_id, top_id)
);
