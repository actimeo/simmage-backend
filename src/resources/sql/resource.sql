CREATE TABLE resources.resource (
  res_id serial PRIMARY KEY,
  res_name text NOT NULL
);

CREATE TABLE resources.resource_topic (
  res_id integer NOT NULL REFERENCES resources.resource,
  top_id integer NOT NULL REFERENCES organ.topic,
  CONSTRAINT resource_topic_pkey PRIMARY KEY(res_id, top_id)
);
