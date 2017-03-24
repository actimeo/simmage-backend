CREATE TYPE documents.document_status 
  as ENUM ('scheduled', 'in progress', 'available');

CREATE TABLE documents.document (
  doc_id SERIAL PRIMARY KEY,
  par_id_responsible integer REFERENCES organ.participant,
  dty_id integer REFERENCES documents.document_type,
  doc_title text NOT NULL,
  doc_description text,
  doc_status documents.document_status NOT NULL,
  doc_deadline date,
  doc_execution_date date,
  doc_validity_date date,
  doc_file text,
  doc_author integer NOT NULL REFERENCES organ.participant,
  doc_creation_date timestamp with time zone
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

CREATE TABLE documents.document_responsible_attribution (
  doc_id integer NOT NULL REFERENCES documents.document,
  par_id_responsible integer REFERENCES organ.participant,
  dra_attribution_date timestamp with time zone NOT NULL,
  dra_achievement_date timestamp with time zone,
  CONSTRAINT document_responsible_attribution_pkey PRIMARY KEY(doc_id, par_id_responsible, dra_attribution_date)
);
