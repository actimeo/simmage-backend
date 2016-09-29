SET search_path = organ;

CREATE OR REPLACE FUNCTION organ.topic_add(
  prm_token integer, 
  prm_name text, 
  prm_description text,
  prm_icon text,
  prm_color text
)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  INSERT INTO organ.topic (top_name, top_description, top_icon, top_color) 
    VALUES (prm_name, prm_description, prm_icon, prm_color)
    RETURNING top_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.topic_add(prm_token integer, prm_name text, prm_description text, 
  prm_icon text, prm_color text) IS 'Add a new topic';

CREATE OR REPLACE FUNCTION organ.topics_list(prm_token integer)
RETURNS setof organ.topic
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT * FROM organ.topic ORDER BY top_name;
END;
$$;
COMMENT ON FUNCTION organ.topics_list(prm_token integer) IS 'Returns the list of topics';

CREATE OR REPLACE FUNCTION organ.topic_delete(prm_token integer, prm_id integer)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE

BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  DELETE FROM organ.topic WHERE top_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION organ.topic_delete(prm_token integer, prm_id integer) IS 'Delete a topic';

CREATE OR REPLACE FUNCTION organ.topic_get(prm_token integer, prm_id integer)
RETURNS organ.topic
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret organ.topic;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT * INTO ret FROM organ.topic WHERE top_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.topic_get(prm_token integer, prm_id integer) IS 'Returns information about a topic';

CREATE OR REPLACE FUNCTION organ.topic_update(
  prm_token integer, 
  prm_id integer, 
  prm_name text, 
  prm_description text,
  prm_icon text,
  prm_color text
)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE organ.topic SET 
    top_name = prm_name, 
    top_description = prm_description,
    top_icon = prm_icon,
    top_color = prm_color
    WHERE top_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION organ.topic_update(prm_token integer, prm_id integer, prm_name text, 
prm_description text, prm_icon text, prm_color text) IS 'Update topic information';
