CREATE OR REPLACE FUNCTION resources.resource_add(
  prm_token integer, 
  prm_name text,
  prm_topics integer[])
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  new_id integer;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  INSERT INTO resources.resource (res_name)
    VALUES (prm_name)
    RETURNING res_id INTO new_id;
  PERFORM resources.resource_set_topics(prm_token, new_id, prm_topics);
  RETURN new_id;
END;
$$;
COMMENT ON FUNCTION resources.resource_add(
  prm_token integer,
  prm_name text,
  prm_topics integer[])
 IS 'Add a new resource';

CREATE OR REPLACE FUNCTION resources.resource_set_topics(
  prm_token integer,
  prm_res_id integer,
  prm_top_ids integer[])
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  IF NOT EXISTS (SELECT 1 FROM resources.resource WHERE res_id = prm_res_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;

  IF prm_top_ids ISNULL THEN
    DELETE FROM resources.resource_topic WHERE res_id = prm_res_id;
    RETURN;
  END IF;

  DELETE FROM resources.resource_topic WHERE res_id = prm_res_id AND top_id <> ALL(prm_top_ids);

  FOREACH t IN ARRAY prm_top_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM resources.resource_topic WHERE res_id = prm_res_id AND top_id = t) THEN
      INSERT INTO resources.resource_topic (res_id, top_id) VALUES (prm_res_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION resources.resource_set_topics(prm_token integer, prm_res_id integer, prm_top_ids integer[])
IS 'Set topics of a resource';

CREATE OR REPLACE FUNCTION resources.resource_get(prm_token integer, prm_res_id integer)
RETURNS resources.resource
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret resources.resource;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  SELECT * INTO ret FROM resources.resource WHERE res_id = prm_res_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION resources.resource_get(prm_token integer, prm_res_id integer) IS 'Returns information about a resource';

CREATE OR REPLACE FUNCTION resources.resource_topic_list(prm_token integer, prm_res_id integer)
RETURNS SETOF organ.topic
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, null);
  RETURN QUERY SELECT topic.* FROM organ.topic
    INNER JOIN resources.resource_topic USING (top_id)
    WHERE res_id = prm_res_id
    ORDER BY top_name;
END;
$$;
COMMENT ON FUNCTION resources.resource_topic_list(prm_token integer, prm_res_id integer) IS 'Retunrs the topics of a resource';

-- 
-- JSON
-- 
CREATE OR REPLACE FUNCTION resources.resource_topic_json(prm_token integer, prm_res_id integer, req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret json;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT array_to_json(array_agg(row_to_json(d))) INTO ret
    FROM (SELECT
      CASE WHEN (req->>'top_id') IS NULL THEN NULL ELSE top_id END as top_id, 
      CASE WHEN (req->>'top_name') IS NULL THEN NULL ELSE  top_name END as top_name, 
      CASE WHEN (req->>'top_description') IS NULL THEN NULL ELSE top_description END as top_description,
      CASE WHEN (req->>'top_icon') IS NULL THEN NULL ELSE top_icon END as top_icon,
      CASE WHEN (req->>'top_color') IS NULL THEN NULL ELSE top_color END as top_color
      FROM organ.topic 
      INNER JOIN resources.resource_topic USING (top_id) 
      WHERE res_id = prm_res_id
      ORDER BY top_name) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION resources.resource_topic_json(prm_token integer, prm_res_id integer, req json) IS 'Returns the topics of a resource as json';

CREATE OR REPLACE FUNCTION resources.resource_json(prm_token integer, prm_res_ids integer[], req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret json;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT array_to_json(array_agg(row_to_json(d))) INTO ret
  FROM (SELECT 
    CASE WHEN (req->>'res_id') IS NULL THEN NULL ELSE res_id END as res_id, 
    CASE WHEN (req->>'res_name') IS NULL THEN NULL ELSE res_name END as res_name, 
    CASE WHEN (req->>'topics') IS NULL THEN NULL ELSE
      resources.resource_topic_json(prm_token, res_id, req->'topics') END as topics
    FROM resources.resource 
      WHERE res_id = ANY(prm_res_ids)
  ) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION resources.resource_json(prm_token integer, prm_res_ids integer[], req json) IS 'Returns information about a resource as json';

CREATE OR REPLACE FUNCTION resources.resource_in_view_list(
  prm_token integer, 
  prm_rev_id integer, 
  req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  the_res_id integer;
  
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN resources.resource_json(prm_token, (SELECT ARRAY(
   SELECT DISTINCT res_id FROM resources.resource
    INNER JOIN resources.resource_topic USING(res_id)
    INNER JOIN resources.resourcesview_topic USING(top_id)
    INNER JOIN resources.resourcesview USING(rev_id)
    WHERE rev_id = prm_rev_id
    )), req);
END;
$$;
COMMENT ON FUNCTION resources.resource_in_view_list(
  prm_token integer, 
  prm_rev_id integer, 
  req json)
 IS 'Returns the resources visible in a resources view';
