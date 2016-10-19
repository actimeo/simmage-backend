CREATE OR REPLACE FUNCTION events.event_category_list()
RETURNS SETOF events.event_category
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT unnest(enum_range(null::events.event_category));
END;
$$;
COMMENT ON FUNCTION events.event_category_list() IS 'Returns the list of event categories';

CREATE OR REPLACE FUNCTION events.event_type_add(
  prm_token integer, 
  prm_category events.event_category, 
  prm_name text, 
  prm_individual_name boolean)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  INSERT INTO events.event_type (ety_category, ety_name, ety_individual_name)
    VALUES (prm_category, prm_name, prm_individual_name)
    RETURNING ety_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.event_type_add(prm_token integer, prm_category events.event_category, 
  prm_name text, prm_individual_name boolean) IS 'Add a new event type';

CREATE OR REPLACE FUNCTION events.event_type_add_details(
  prm_token integer, 
  prm_category events.event_category, 
  prm_name text, 
  prm_individual_name boolean,
  prm_topics integer[],
  prm_organizations integer[])
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  SELECT events.event_type_add(prm_token, prm_category, prm_name, prm_individual_name) INTO ret;
  PERFORM events.event_type_set_topics(prm_token, ret, prm_topics);
  PERFORM events.event_type_set_organizations(prm_token, ret, prm_organizations);
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.event_type_add_details(prm_token integer, prm_category events.event_category, 
  prm_name text, prm_individual_name boolean, prm_topics integer[], prm_organizations integer[])
  IS 'Add a new event type with topics and organizations';

CREATE OR REPLACE FUNCTION events.event_type_update(
  prm_token integer, 
  prm_ety_id integer, 
  prm_category events.event_category,
  prm_name text, 
  prm_individual_name boolean)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE events.event_type SET
    ety_category = prm_category,
    ety_name = prm_name,
    ety_individual_name = prm_individual_name
    WHERE ety_id = prm_ety_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION events.event_type_update(prm_token integer, prm_ety_id integer, 
  prm_category events.event_category, prm_name text, prm_individual_name boolean)
  IS 'Update an event type';

CREATE OR REPLACE FUNCTION events.event_type_update_details(
  prm_token integer, 
  prm_ety_id integer, 
  prm_category events.event_category,
  prm_name text, 
  prm_individual_name boolean,
  prm_topics integer[],
  prm_organizations integer[])

RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  PERFORM events.event_type_update(prm_token, prm_ety_id, prm_category, prm_name, prm_individual_name);
  PERFORM events.event_type_set_topics(prm_token, prm_ety_id, prm_topics);
  PERFORM events.event_type_set_organizations(prm_token, prm_ety_id, prm_organizations);
END;
$$;
COMMENT ON FUNCTION events.event_type_update(prm_token integer, prm_ety_id integer, 
  prm_category events.event_category, prm_name text, prm_individual_name boolean)
  IS 'Update an event type';

CREATE OR REPLACE FUNCTION events.event_type_delete(prm_token integer, prm_ety_id integer)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  DELETE FROM events.event_type_topic WHERE ety_id = prm_ety_id;
  DELETE FROM events.event_type_organization WHERE ety_id = prm_ety_id;
  DELETE FROM events.event_type WHERE ety_id = prm_ety_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION events.event_type_delete(prm_token integer, prm_ety_id integer) IS 'Delete an event topic (not recursive, links to organizations and topics should be deleted first)';

CREATE OR REPLACE FUNCTION events.event_type_get(prm_token integer, prm_ety_id integer)
RETURNS events.event_type
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret events.event_type;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT * INTO ret FROM events.event_type 
    WHERE ety_id = prm_ety_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.event_type_get(prm_token integer, prm_ety_id integer) IS 'Returns information about an event type';

DROP FUNCTION IF EXISTS events.event_type_list(prm_token integer, prm_category events.event_category);
DROP TYPE IF EXISTS events.event_type_list;
CREATE TYPE events.event_type_list AS (
  ety_id integer,
  ety_category events.event_category,
  ety_name text,
  ety_individual_name boolean,
  top_ids integer[],
  org_ids integer[]
);

CREATE OR REPLACE FUNCTION events.event_type_list(prm_token integer, prm_category events.event_category)
RETURNS SETOF events.event_type_list
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT
    ety_id, ety_category, ety_name, ety_individual_name,
    ARRAY(SELECT DISTINCT top_id FROM events.event_type_topic sub WHERE sub.ety_id = top.ety_id),
    ARRAY(SELECT DISTINCT org_id FROM events.event_type_organization sub WHERE sub.ety_id = top.ety_id)
    FROM events.event_type top
    WHERE (prm_category IS NULL OR prm_category = ety_category) ORDER BY ety_name;
END;
$$;
COMMENT ON FUNCTION events.event_type_list(prm_token integer, prm_category events.event_category) IS 'Returns the list of events types for one or all categories of events ';

CREATE OR REPLACE FUNCTION events.event_type_filter(
  prm_token integer, 
  prm_categories events.event_category[],
  prm_top_ids integer[])
RETURNS SETOF events.event_type_list
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
    RETURN QUERY SELECT DISTINCT
    ety_id, ety_category, ety_name, ety_individual_name,
    ARRAY(SELECT DISTINCT top_id FROM events.event_type_topic sub WHERE sub.ety_id = top.ety_id),
    ARRAY(SELECT DISTINCT org_id FROM events.event_type_organization sub WHERE sub.ety_id = top.ety_id)
    FROM events.event_type top
    INNER JOIN events.event_type_topic USING(ety_id)
    WHERE (prm_categories IS NULL OR ety_category = ANY (prm_categories)) 
    AND (prm_top_ids IS NULL OR event_type_topic.top_id = ANY (prm_top_ids))
    ORDER BY ety_name;
END;
$$;
COMMENT ON FUNCTION events.event_type_list(prm_token integer, prm_category events.event_category) IS 'Returns the list of events types for one or all categories of events ';

CREATE OR REPLACE FUNCTION events.event_type_set_topics(prm_token integer, prm_ety_id integer, prm_top_ids integer[])
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  -- Raise an exception if entity does not exist
  IF NOT EXISTS (SELECT 1 FROM events.event_type WHERE ety_id = prm_ety_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  -- If list is NULL, remove all relations
  IF prm_top_ids ISNULL THEN
    DELETE FROM events.event_type_topic WHERE ety_id = prm_ety_id;
    RETURN;
  END IF;
  -- Delete relations present in DB not present in list
  DELETE FROM events.event_type_topic WHERE ety_id = prm_ety_id AND top_id <> ALL(prm_top_ids);
  -- Add relations in list not yet in DB
  FOREACH t IN ARRAY prm_top_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM events.event_type_topic WHERE ety_id = prm_ety_id AND top_id = t) THEN
      INSERT INTO events.event_type_topic (ety_id, top_id) VALUES (prm_ety_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION events.event_type_set_topics(prm_token integer, prm_ety_id integer, prm_top_ids integer[]) IS 'Set topics attached to an event type';

CREATE OR REPLACE FUNCTION events.event_type_topics_list(prm_token integer, prm_ety_id integer)
RETURNS SETOF organ.topic
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT topic.*
    FROM events.event_type_topic
    INNER JOIN organ.topic USING(top_id)
    WHERE ety_id = prm_ety_id
    ORDER BY top_name;
END;
$$;
COMMENT ON FUNCTION events.event_type_topics_list(prm_token integer, prm_ety_id integer) IS 'Returns the topics attached to an event type';

CREATE OR REPLACE FUNCTION events.event_type_set_organizations(prm_token integer, prm_ety_id integer, prm_org_ids integer[])
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  -- Raise an exception if some organization is external
  IF EXISTS (SELECT 1 FROM organ.organization
               WHERE org_id = ANY (prm_org_ids)
               AND NOT org_internal) THEN
    RAISE EXCEPTION 'Organizations should be internal' 
      USING ERRCODE = 'data_exception';
  END IF;
  -- Raise an exception if entity does not exist
  IF NOT EXISTS (SELECT 1 FROM events.event_type WHERE ety_id = prm_ety_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  -- If list is NULL, remove all relations
  IF prm_org_ids ISNULL THEN
    DELETE FROM events.event_type_organization WHERE ety_id = prm_ety_id;
    RETURN;
  END IF;
  -- Delete relations present in DB not present in list
  DELETE FROM events.event_type_organization WHERE ety_id = prm_ety_id AND org_id <> ALL(prm_org_ids);
  -- Add relations in list not yet in DB
  FOREACH t IN ARRAY prm_org_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM events.event_type_organization WHERE ety_id = prm_ety_id AND org_id = t) THEN
      INSERT INTO events.event_type_organization (ety_id, org_id) VALUES (prm_ety_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION events.event_type_set_organizations(prm_token integer, prm_ety_id integer, prm_org_ids integer[]) IS 'Set organizations using an event type';

CREATE OR REPLACE FUNCTION events.event_type_organizations_list(prm_token integer, prm_ety_id integer)
RETURNS SETOF organ.organization
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT organization.*
    FROM events.event_type_organization
    INNER JOIN organ.organization USING(org_id)
    WHERE ety_id = prm_ety_id
    ORDER BY org_name;
END;
$$;
COMMENT ON FUNCTION events.event_type_organizations_list(prm_token integer, prm_ety_id integer) IS 'Returns the organizations using an event type';

