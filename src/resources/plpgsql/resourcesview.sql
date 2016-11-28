DROP FUNCTION IF EXISTS resources.resourcesview_list(prm_token integer);
DROP TYPE IF EXISTS resources.resourcesview_list;
CREATE TYPE resources.resourcesview_list AS (
  rev_id integer,
  rev_name text,
  top_ids integer[]
);

CREATE FUNCTION resources.resourcesview_list(prm_token integer)
RETURNS SETOF resources.resourcesview_list
LANGUAGE plpgsql
STABLE
AS $$
DECLARE

BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT
    rev_id, rev_name,
    ARRAY(SELECT DISTINCT top_id FROM resources.resourcesview_topic sub WHERE sub.rev_id = top.rev_id ORDER BY top_id)
    FROM resources.resourcesview top
    ORDER BY rev_name;
END;
$$;
COMMENT ON FUNCTION resources.resourcesview_list(prm_token integer) IS 'Returns the list of resources views';

CREATE OR REPLACE FUNCTION resources.resourcesview_set_topics(prm_token integer, prm_rev_id integer, prm_top_ids integer[])
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  -- Raise an exception if entity does not exist
  IF NOT EXISTS (SELECT 1 FROM resources.resourcesview WHERE rev_id = prm_rev_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  -- If list is NULL, remove all relations
  IF prm_top_ids ISNULL THEN
    DELETE FROM resources.resourcesview_topic WHERE rev_id = prm_rev_id;
    RETURN;
  END IF;
  -- Delete relations present in DB not present in list
  DELETE FROM resources.resourcesview_topic WHERE rev_id = prm_rev_id AND top_id <> ALL(prm_top_ids);
  -- Add relations in list not yet in DB
  FOREACH t IN ARRAY prm_top_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM resources.resourcesview_topic WHERE rev_id = prm_rev_id AND top_id = t) THEN
      INSERT INTO resources.resourcesview_topic (rev_id, top_id) VALUES (prm_rev_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION resources.resourcesview_set_topics(prm_token integer, prm_rev_id integer, prm_top_ids integer[]) IS 'Set topics attached to a resources view';

CREATE OR REPLACE FUNCTION resources.resourcesview_add(prm_token integer, prm_name text, prm_top_ids integer[])
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  INSERT INTO resources.resourcesview (rev_name) VALUES (prm_name)
    RETURNING rev_id INTO ret;
  PERFORM resources.resourcesview_set_topics(prm_token, ret, prm_top_ids);  
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION resources.resourcesview_add(prm_token integer, prm_name text, prm_top_ids integer[]) IS 'Add a new resources view';

CREATE OR REPLACE FUNCTION resources.resourcesview_update(prm_token integer, prm_id integer, prm_name text, prm_top_ids integer[])
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE resources.resourcesview SET 
    rev_name = prm_name
    WHERE rev_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  PERFORM resources.resourcesview_set_topics(prm_token, prm_id, prm_top_ids);    
END;
$$;
COMMENT ON FUNCTION resources.resourcesview_update(prm_token integer, prm_id integer, prm_name text, prm_top_ids integer[]) IS 'Update information about an resources view';

DROP FUNCTION IF EXISTS resources.resourcesview_get(prm_token integer, prm_id integer);
DROP TYPE IF EXISTS resources.resourcesview_get;
CREATE TYPE resources.resourcesview_get AS (
  rev_id integer,
  rev_name text,
  top_ids integer[]
);

CREATE FUNCTION resources.resourcesview_get(prm_token integer, prm_id integer)
RETURNS resources.resourcesview_get
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret resources.resourcesview_get;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT
    rev_id, rev_name,
    ARRAY(SELECT DISTINCT top_id FROM resources.resourcesview_topic sub WHERE sub.rev_id = top.rev_id ORDER BY top_id)
    INTO ret
    FROM resources.resourcesview top WHERE rev_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION resources.resourcesview_get(prm_token integer, prm_id integer) IS 'Returns information about an resources view';

CREATE OR REPLACE FUNCTION resources.resourcesview_delete(prm_token integer, prm_id integer)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  DELETE FROM resources.resourcesview_topic WHERE rev_id = prm_id;
  DELETE FROM resources.resourcesview WHERE rev_id = prm_id;  
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION resources.resourcesview_delete(prm_token integer, prm_id integer) IS 'Delete an resources view';
