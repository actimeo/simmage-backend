CREATE SCHEMA resources;

SET search_path = resources;

CREATE TABLE resources.resourcesview (
  rev_id serial PRIMARY KEY,
  rev_name text NOT NULL
);

CREATE TABLE resources.resourcesview_topic (
  rev_id integer NOT NULL REFERENCES resources.resourcesview,
  top_id integer NOT NULL REFERENCES organ.topic,
  CONSTRAINT resourcesview_topic_pkey PRIMARY KEY(rev_id, top_id)
);
