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

CREATE OR REPLACE FUNCTION events.event_type_delete(prm_token integer, prm_ety_id integer)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
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

CREATE OR REPLACE FUNCTION events.event_type_list(prm_token integer, prm_category events.event_category)
RETURNS SETOF events.event_type
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT * FROM events.event_type
    WHERE (prm_category IS NULL OR prm_category = ety_category) ORDER BY ety_name;
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
