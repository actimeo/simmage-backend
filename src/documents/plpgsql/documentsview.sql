DROP FUNCTION IF EXISTS documents.documentsview_list(prm_token integer);
DROP TYPE IF EXISTS documents.documentsview_list;
CREATE TYPE documents.documentsview_list AS (
  dov_id integer,
  dov_name text,
  dty_id integer,
  top_ids integer[]
);

CREATE FUNCTION documents.documentsview_list(prm_token integer)
RETURNS SETOF documents.documentsview_list
LANGUAGE plpgsql
STABLE
AS $$
DECLARE

BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT
    dov_id, dov_name, dty_id, 
    ARRAY(SELECT DISTINCT top_id FROM documents.documentsview_topic sub WHERE sub.dov_id = top.dov_id ORDER BY top_id)
    FROM documents.documentsview top
    ORDER BY dov_name;
END;
$$;
COMMENT ON FUNCTION documents.documentsview_list(prm_token integer) IS 'Returns the list of documents views';

CREATE OR REPLACE FUNCTION documents.documentsview_set_topics(prm_token integer, prm_dov_id integer, prm_top_ids integer[])
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  -- Raise an exception if entity does not exist
  IF NOT EXISTS (SELECT 1 FROM documents.documentsview WHERE dov_id = prm_dov_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  -- If list is NULL, remove all relations
  IF prm_top_ids ISNULL THEN
    DELETE FROM documents.documentsview_topic WHERE dov_id = prm_dov_id;
    RETURN;
  END IF;
  -- Delete relations present in DB not present in list
  DELETE FROM documents.documentsview_topic WHERE dov_id = prm_dov_id AND top_id <> ALL(prm_top_ids);
  -- Add relations in list not yet in DB
  FOREACH t IN ARRAY prm_top_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM documents.documentsview_topic WHERE dov_id = prm_dov_id AND top_id = t) THEN
      INSERT INTO documents.documentsview_topic (dov_id, top_id) VALUES (prm_dov_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION documents.documentsview_set_topics(prm_token integer, prm_dov_id integer, prm_top_ids integer[]) IS 'Set topics attached to an documents view';


CREATE OR REPLACE FUNCTION documents.documentsview_add(prm_token integer, prm_name text, prm_dty_id integer, prm_top_ids integer[])
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  -- TODO verify that dty_id is compatible with categories/topics
  INSERT INTO documents.documentsview (dov_name, dty_id) VALUES (prm_name, prm_dty_id)
    RETURNING dov_id INTO ret;
  PERFORM documents.documentsview_set_topics(prm_token, ret, prm_top_ids);  
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION documents.documentsview_add(prm_token integer, prm_name text, prm_dty_id integer, prm_top_ids integer[]) IS 'Add a new documents view';

CREATE OR REPLACE FUNCTION documents.documentsview_update(prm_token integer, prm_id integer, prm_name text, prm_dty_id integer, prm_top_ids integer[])
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE documents.documentsview SET 
    dov_name = prm_name,
    dty_id = prm_dty_id
    WHERE dov_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  PERFORM documents.documentsview_set_topics(prm_token, prm_id, prm_top_ids);    
END;
$$;
COMMENT ON FUNCTION documents.documentsview_update(prm_token integer, prm_id integer, prm_name text, prm_dty_id integer, prm_top_ids integer[]) IS 'Update information about an documents view';

DROP FUNCTION IF EXISTS documents.documentsview_get(prm_token integer, prm_id integer);
DROP TYPE IF EXISTS documents.documentsview_get;
CREATE TYPE documents.documentsview_get AS (
  dov_id integer,
  dov_name text,
  dty_id integer,
  top_ids integer[]
);

CREATE FUNCTION documents.documentsview_get(prm_token integer, prm_id integer)
RETURNS documents.documentsview_get
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret documents.documentsview_get;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT
    dov_id, dov_name, dty_id, 
    ARRAY(SELECT DISTINCT top_id FROM documents.documentsview_topic sub WHERE sub.dov_id = top.dov_id ORDER BY top_id)
    INTO ret
    FROM documents.documentsview top WHERE dov_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION documents.documentsview_get(prm_token integer, prm_id integer) IS 'Returns information about an documents view';

CREATE OR REPLACE FUNCTION documents.documentsview_delete(prm_token integer, prm_id integer)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  DELETE FROM documents.documentsview_topic WHERE dov_id = prm_id;
  DELETE FROM documents.documentsview WHERE dov_id = prm_id;  
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION documents.documentsview_delete(prm_token integer, prm_id integer) IS 'Delete an documents view';

CREATE OR REPLACE FUNCTION documents.documentsview_get_topics(prm_token integer, prm_id integer)
RETURNS SETOF text
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY
    SELECT top_name
    FROM organ.topic
    WHERE top_id = ANY(SELECT top_id FROM documents.documentsview_topic WHERE dov_id = prm_id);
END;
$$;
COMMENT ON FUNCTION documents.documentsview_get_topics(prm_token integer, prm_id integer) IS 'Return the list of topics associated to a view';
