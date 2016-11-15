CREATE SCHEMA lists;

SET search_path = lists;

CREATE TABLE lists.listsview (
  liv_id serial PRIMARY KEY,
  liv_name text NOT NULL
);

