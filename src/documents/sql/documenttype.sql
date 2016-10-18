CREATE SCHEMA documents;

CREATE TABLE documents.document_type (
  dty_id SERIAL PRIMARY KEY,
  dty_name text NOT NULL,
  dty_individual_name boolean NOT NULL
);

CREATE TABLE documents.document_type_topic (
  dty_id integer NOT NULL REFERENCES documents.document_type,
  top_id integer NOT NULL REFERENCES organ.topic,
  CONSTRAINT document_type_topic_pkey PRIMARY KEY(dty_id, top_id)
);

CREATE TABLE documents.document_type_organization (
  dty_id integer NOT NULL REFERENCES documents.document_type,
  org_id integer NOT NULL REFERENCES organ.organization,
  CONSTRAINT document_type_organization_pkey PRIMARY KEY(dty_id, org_id)
);
