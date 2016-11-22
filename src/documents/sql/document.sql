CREATE TYPE documents.document_status 
  as ENUM ('todo', 'requested', 'done');

CREATE TABLE documents.document (
  doc_id SERIAL PRIMARY KEY,
  par_id_responsible integer REFERENCES organ.participant,
  dty_id integer NOT NULL REFERENCES documents.document_type,
  doc_title text NOT NULL,
  doc_description text NOT NULL,
  doc_status documents.document_status NOT NULL,
  doc_obtainment_date date,
  doc_execution_date date,
  doc_validity_date date,
  doc_file text
);

CREATE TABLE documents.document_topic (
  doc_id integer NOT NULL REFERENCES documents.document,
  top_id integer NOT NULL REFERENCES organ.topic,
  CONSTRAINT document_topic_pkey PRIMARY KEY(doc_id, top_id)
);

CREATE TABLE documents.document_dossier (
  doc_id integer NOT NULL REFERENCES documents.document,
  dos_id integer NOT NULL REFERENCES organ.dossier,
  CONSTRAINT document_dossier_pkey PRIMARY KEY(doc_id, dos_id)
);
