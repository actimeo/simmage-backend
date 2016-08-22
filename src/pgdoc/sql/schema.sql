CREATE SCHEMA pgdoc;

CREATE TYPE pgdoc.typ AS ENUM (
  'schema',
  'table', 
  'column',
  'enum',
  'type'
);
