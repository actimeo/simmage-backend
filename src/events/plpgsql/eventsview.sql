DROP FUNCTION IF EXISTS events.eventsview_list(prm_token integer);
DROP TYPE IF EXISTS events.eventsview_list;
CREATE TYPE events.eventsview_list AS (
  evv_id integer,
  evv_name text,
  evv_categories events.event_category[],
  ety_id integer,
  top_ids integer[]
);

CREATE FUNCTION events.eventsview_list(prm_token integer)
RETURNS SETOF events.eventsview_list
LANGUAGE plpgsql
STABLE
AS $$
DECLARE

BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT
    evv_id, evv_name, evv_categories, ety_id, 
    ARRAY(SELECT DISTINCT top_id FROM events.eventsview_topic sub WHERE sub.evv_id = top.evv_id ORDER BY top_id)
    FROM events.eventsview top
    ORDER BY evv_name;
END;
$$;
COMMENT ON FUNCTION events.eventsview_list(prm_token integer) IS 'Returns the list of events views';

CREATE OR REPLACE FUNCTION events.eventsview_set_topics(prm_token integer, prm_evv_id integer, prm_top_ids integer[])
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  -- Raise an exception if entity does not exist
  IF NOT EXISTS (SELECT 1 FROM events.eventsview WHERE evv_id = prm_evv_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  -- If list is NULL, remove all relations
  IF prm_top_ids ISNULL THEN
    DELETE FROM events.eventsview_topic WHERE evv_id = prm_evv_id;
    RETURN;
  END IF;
  -- Delete relations present in DB not present in list
  DELETE FROM events.eventsview_topic WHERE evv_id = prm_evv_id AND top_id <> ALL(prm_top_ids);
  -- Add relations in list not yet in DB
  FOREACH t IN ARRAY prm_top_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM events.eventsview_topic WHERE evv_id = prm_evv_id AND top_id = t) THEN
      INSERT INTO events.eventsview_topic (evv_id, top_id) VALUES (prm_evv_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION events.eventsview_set_topics(prm_token integer, prm_evv_id integer, prm_top_ids integer[]) IS 'Set topics attached to an events view';


CREATE OR REPLACE FUNCTION events.eventsview_add(prm_token integer, prm_name text, prm_categories events.event_category[], prm_ety_id integer, prm_top_ids integer[])
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  -- TODO verify that ety_id is compatible with categories/topics
  INSERT INTO events.eventsview (evv_name, evv_categories, ety_id) VALUES (prm_name, prm_categories, prm_ety_id)
    RETURNING evv_id INTO ret;
  PERFORM events.eventsview_set_topics(prm_token, ret, prm_top_ids);  
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.eventsview_add(prm_token integer, prm_name text, prm_categories events.event_category[], prm_ety_id integer, prm_top_ids integer[]) IS 'Add a new events view';

CREATE OR REPLACE FUNCTION events.eventsview_update(prm_token integer, prm_id integer, prm_name text, prm_categories events.event_category[], prm_ety_id integer, prm_top_ids integer[])
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE events.eventsview SET 
    evv_name = prm_name,
    evv_categories = prm_categories,
    ety_id = prm_ety_id
    WHERE evv_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  PERFORM events.eventsview_set_topics(prm_token, prm_id, prm_top_ids);    
END;
$$;
COMMENT ON FUNCTION events.eventsview_update(prm_token integer, prm_id integer, prm_name text, prm_categories events.event_category[], prm_ety_id integer, prm_top_ids integer[]) IS 'Update information about an events view';

DROP FUNCTION IF EXISTS events.eventsview_get(prm_token integer, prm_id integer);
DROP TYPE IF EXISTS events.eventsview_get;
CREATE TYPE events.eventsview_get AS (
  evv_id integer,
  evv_name text,
  evv_categories events.event_category[],
  ety_id integer,
  top_ids integer[]
);

CREATE FUNCTION events.eventsview_get(prm_token integer, prm_id integer)
RETURNS events.eventsview_get
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret events.eventsview_get;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT
    evv_id, evv_name, evv_categories, ety_id, 
    ARRAY(SELECT DISTINCT top_id FROM events.eventsview_topic sub WHERE sub.evv_id = top.evv_id ORDER BY top_id)
    INTO ret
    FROM events.eventsview top WHERE evv_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.eventsview_get(prm_token integer, prm_id integer) IS 'Returns information about an events view';

CREATE OR REPLACE FUNCTION events.eventsview_delete(prm_token integer, prm_id integer)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  DELETE FROM events.eventsview_topic WHERE evv_id = prm_id;
  DELETE FROM events.eventsview WHERE evv_id = prm_id;  
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION events.eventsview_delete(prm_token integer, prm_id integer) IS 'Delete an events view';

CREATE OR REPLACE FUNCTION events.eventsview_get_topics(prm_token integer, prm_id integer)
RETURNS SETOF text
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY
    SELECT top_name
    FROM organ.topic
    WHERE top_id = ANY(SELECT top_id FROM events.eventsview_topic WHERE evv_id = prm_id);
END;
$$;
COMMENT ON FUNCTION events.eventsview_get_topics(prm_token integer, prm_id integer) IS 'Return the list of topics associated to a view';
