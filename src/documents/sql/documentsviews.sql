SET search_path = documents;

CREATE TABLE documents.documentsview (
  dov_id serial PRIMARY KEY,
  dov_name text NOT NULL,
  dty_id integer REFERENCES documents.document_type
);

CREATE TABLE documents.documentsview_topic (
  dov_id integer NOT NULL REFERENCES documents.documentsview,
  top_id integer NOT NULL REFERENCES organ.topic,
  CONSTRAINT documentsview_topic_pkey PRIMARY KEY(dov_id, top_id)
);
