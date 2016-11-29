DROP FUNCTION IF EXISTS objectives.objectivesview_list(prm_token integer);
DROP TYPE IF EXISTS objectives.objectivesview_list;
CREATE TYPE objectives.objectivesview_list AS (
  obv_id integer,
  obv_name text,
  top_ids integer[]
);

CREATE FUNCTION objectives.objectivesview_list(prm_token integer)
RETURNS SETOF objectives.objectivesview_list
LANGUAGE plpgsql
STABLE
AS $$
DECLARE

BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT
    obv_id, obv_name,
    ARRAY(SELECT DISTINCT top_id FROM objectives.objectivesview_topic sub WHERE sub.obv_id = top.obv_id ORDER BY top_id)
    FROM objectives.objectivesview top
    ORDER BY obv_name;
END;
$$;
COMMENT ON FUNCTION objectives.objectivesview_list(prm_token integer) IS 'Returns the list of objectives views';

CREATE OR REPLACE FUNCTION objectives.objectivesview_set_topics(prm_token integer, prm_obv_id integer, prm_top_ids integer[])
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  -- Raise an exception if entity does not exist
  IF NOT EXISTS (SELECT 1 FROM objectives.objectivesview WHERE obv_id = prm_obv_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  -- If list is NULL, remove all relations
  IF prm_top_ids ISNULL THEN
    DELETE FROM objectives.objectivesview_topic WHERE obv_id = prm_obv_id;
    RETURN;
  END IF;
  -- Delete relations present in DB not present in list
  DELETE FROM objectives.objectivesview_topic WHERE obv_id = prm_obv_id AND top_id <> ALL(prm_top_ids);
  -- Add relations in list not yet in DB
  FOREACH t IN ARRAY prm_top_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM objectives.objectivesview_topic WHERE obv_id = prm_obv_id AND top_id = t) THEN
      INSERT INTO objectives.objectivesview_topic (obv_id, top_id) VALUES (prm_obv_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION objectives.objectivesview_set_topics(prm_token integer, prm_obv_id integer, prm_top_ids integer[]) IS 'Set topics attached to a objectives view';

CREATE OR REPLACE FUNCTION objectives.objectivesview_add(prm_token integer, prm_name text, prm_top_ids integer[])
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  INSERT INTO objectives.objectivesview (obv_name) VALUES (prm_name)
    RETURNING obv_id INTO ret;
  PERFORM objectives.objectivesview_set_topics(prm_token, ret, prm_top_ids);  
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION objectives.objectivesview_add(prm_token integer, prm_name text, prm_top_ids integer[]) IS 'Add a new objectives view';

CREATE OR REPLACE FUNCTION objectives.objectivesview_update(prm_token integer, prm_id integer, prm_name text, prm_top_ids integer[])
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE objectives.objectivesview SET 
    obv_name = prm_name
    WHERE obv_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  PERFORM objectives.objectivesview_set_topics(prm_token, prm_id, prm_top_ids);    
END;
$$;
COMMENT ON FUNCTION objectives.objectivesview_update(prm_token integer, prm_id integer, prm_name text, prm_top_ids integer[]) IS 'Update information about an objectives view';

DROP FUNCTION IF EXISTS objectives.objectivesview_get(prm_token integer, prm_id integer);
DROP TYPE IF EXISTS objectives.objectivesview_get;
CREATE TYPE objectives.objectivesview_get AS (
  obv_id integer,
  obv_name text,
  top_ids integer[]
);

CREATE FUNCTION objectives.objectivesview_get(prm_token integer, prm_id integer)
RETURNS objectives.objectivesview_get
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret objectives.objectivesview_get;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT
    obv_id, obv_name,
    ARRAY(SELECT DISTINCT top_id FROM objectives.objectivesview_topic sub WHERE sub.obv_id = top.obv_id ORDER BY top_id)
    INTO ret
    FROM objectives.objectivesview top WHERE obv_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION objectives.objectivesview_get(prm_token integer, prm_id integer) IS 'Returns information about an objectives view';

CREATE OR REPLACE FUNCTION objectives.objectivesview_delete(prm_token integer, prm_id integer)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  DELETE FROM objectives.objectivesview_topic WHERE obv_id = prm_id;
  DELETE FROM objectives.objectivesview WHERE obv_id = prm_id;  
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION objectives.objectivesview_delete(prm_token integer, prm_id integer) IS 'Delete an objectives view';
