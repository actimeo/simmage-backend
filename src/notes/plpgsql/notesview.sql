DROP FUNCTION IF EXISTS notes.notesview_list(prm_token integer);
DROP TYPE IF EXISTS notes.notesview_list;
CREATE TYPE notes.notesview_list AS (
  nov_id integer,
  nov_name text,
  top_ids integer[]
);

CREATE FUNCTION notes.notesview_list(prm_token integer)
RETURNS SETOF notes.notesview_list
LANGUAGE plpgsql
STABLE
AS $$
DECLARE

BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT
    nov_id, nov_name,
    ARRAY(SELECT DISTINCT top_id FROM notes.notesview_topic sub WHERE sub.nov_id = top.nov_id ORDER BY top_id)
    FROM notes.notesview top
    ORDER BY nov_name;
END;
$$;
COMMENT ON FUNCTION notes.notesview_list(prm_token integer) IS 'Returns the list of notes views';

CREATE OR REPLACE FUNCTION notes.notesview_set_topics(prm_token integer, prm_nov_id integer, prm_top_ids integer[])
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  -- Raise an exception if entity does not exist
  IF NOT EXISTS (SELECT 1 FROM notes.notesview WHERE nov_id = prm_nov_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  -- If list is NULL, remove all relations
  IF prm_top_ids ISNULL THEN
    DELETE FROM notes.notesview_topic WHERE nov_id = prm_nov_id;
    RETURN;
  END IF;
  -- Delete relations present in DB not present in list
  DELETE FROM notes.notesview_topic WHERE nov_id = prm_nov_id AND top_id <> ALL(prm_top_ids);
  -- Add relations in list not yet in DB
  FOREACH t IN ARRAY prm_top_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM notes.notesview_topic WHERE nov_id = prm_nov_id AND top_id = t) THEN
      INSERT INTO notes.notesview_topic (nov_id, top_id) VALUES (prm_nov_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION notes.notesview_set_topics(prm_token integer, prm_nov_id integer, prm_top_ids integer[]) IS 'Set topics attached to a notes view';

CREATE OR REPLACE FUNCTION notes.notesview_add(prm_token integer, prm_name text, prm_top_ids integer[])
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  INSERT INTO notes.notesview (nov_name) VALUES (prm_name)
    RETURNING nov_id INTO ret;
  PERFORM notes.notesview_set_topics(prm_token, ret, prm_top_ids);  
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION notes.notesview_add(prm_token integer, prm_name text, prm_top_ids integer[]) IS 'Add a new notes view';

CREATE OR REPLACE FUNCTION notes.notesview_update(prm_token integer, prm_id integer, prm_name text, prm_top_ids integer[])
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE notes.notesview SET 
    nov_name = prm_name
    WHERE nov_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  PERFORM notes.notesview_set_topics(prm_token, prm_id, prm_top_ids);    
END;
$$;
COMMENT ON FUNCTION notes.notesview_update(prm_token integer, prm_id integer, prm_name text, prm_top_ids integer[]) IS 'Update information about an notes view';

DROP FUNCTION IF EXISTS notes.notesview_get(prm_token integer, prm_id integer);
DROP TYPE IF EXISTS notes.notesview_get;
CREATE TYPE notes.notesview_get AS (
  nov_id integer,
  nov_name text,
  top_ids integer[]
);

CREATE FUNCTION notes.notesview_get(prm_token integer, prm_id integer)
RETURNS notes.notesview_get
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret notes.notesview_get;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT
    nov_id, nov_name,
    ARRAY(SELECT DISTINCT top_id FROM notes.notesview_topic sub WHERE sub.nov_id = top.nov_id ORDER BY top_id)
    INTO ret
    FROM notes.notesview top WHERE nov_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION notes.notesview_get(prm_token integer, prm_id integer) IS 'Returns information about an notes view';

CREATE OR REPLACE FUNCTION notes.notesview_delete(prm_token integer, prm_id integer)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  DELETE FROM notes.notesview_topic WHERE nov_id = prm_id;
  DELETE FROM notes.notesview WHERE nov_id = prm_id;  
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION notes.notesview_delete(prm_token integer, prm_id integer) IS 'Delete an notes view';

CREATE OR REPLACE FUNCTION notes.notesview_get_topics(prm_token integer,prm_id integer)
RETURNS SETOF organ.topic
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY
    SELECT *
    FROM organ.topic
    WHERE top_id = ANY(SELECT top_id FROM notes.notesview_topic WHERE nov_id = prm_id);
END;
$$;
COMMENT ON FUNCTION notes.notesview_get_topics(prm_token integer, prm_id integer) IS 'Return the list of topics associated to a view';
