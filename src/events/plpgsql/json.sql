CREATE OR REPLACE FUNCTION events.event_type_topic_json(prm_token integer, prm_ety_id integer, req json)
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
      CASE WHEN (req->>'top_name') IS NULL THEN NULL ELSE top_name END as top_name,
      CASE WHEN (req->>'top_description') IS NULL THEN NULL ELSE top_description END as top_description,
      CASE WHEN (req->>'top_icon') IS NULL THEN NULL ELSE top_icon END as top_icon,
      CASE WHEN (req->>'top_color') IS NULL THEN NULL ELSE top_color END as top_color
      FROM organ.topic
      INNER JOIN events.event_type_topic USING (top_id)
      WHERE ety_id = prm_ety_id) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.event_type_topic_json(prm_token integer, prm_ety_id integer, req json)
  IS 'Returns the topics linked to an event type';

CREATE OR REPLACE FUNCTION events.event_type_organization_json(prm_token integer, prm_ety_id integer, req json)
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
      CASE WHEN (req->>'org_id') IS NULL THEN NULL ELSE org_id END as org_id,
      CASE WHEN (req->>'org_name') IS NULL THEN NULL ELSE org_name END as org_name,
      CASE WHEN (req->>'org_description') IS NULL THEN NULL ELSE org_description END as org_description
      FROM organ.organization
      INNER JOIN events.event_type_organization USING (org_id)
      WHERE ety_id = prm_ety_id) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.event_type_organization_json(prm_token integer, prm_ety_id integer, req json)
  IS 'Returns the organizations linked to an event type';

CREATE OR REPLACE FUNCTION events.event_type_json(prm_token integer, prm_ety_id integer, req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret json;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT
    CASE WHEN prm_ety_id IS NULL THEN
      array_to_json(array_agg(row_to_json(d)))
    ELSE unnest(array_agg(row_to_json(d))) END
  INTO ret
  FROM (SELECT
    CASE WHEN (req->>'ety_id') IS NULL THEN NULL ELSE ety_id END as ety_id,
    CASE WHEN (req->>'ety_name') IS NULL THEN NULL ELSE ety_name END as ety_name,
    CASE WHEN (req->>'ety_category') IS NULL THEN NULL ELSE ety_category END as ety_category,
    CASE WHEN (req->>'ety_individual_name') IS NULL THEN NULL ELSE ety_individual_name END as ety_individual_name,
    CASE WHEN (req->>'topics') IS NULL THEN NULL ELSE
	events.event_type_topic_json(prm_token, ety_id, req->'topics') END as topics,
    CASE WHEN (req->>'organizations') IS NULL THEN NULL ELSE
	events.event_type_organization_json(prm_token, ety_id, req->'organizations') END as organizations
    FROM events.event_type WHERE (prm_ety_id IS NULL OR ety_id = prm_ety_id)
  ) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.event_type_json (prm_token integer, prm_ety integer, req json)
  IS 'Returns an event type or the list of event types as json';

CREATE OR REPLACE FUNCTION events.event_type_filter_json(prm_token integer, prm_category events.event_category, prm_top_ids integer[], prm_filter boolean)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret json;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT array_to_json(array_agg(row_to_json(d))) INTO ret
    FROM (SELECT ety_id, ety_name, ety_category, ety_individual_name FROM events.event_type ev
	  WHERE (select array_agg(top_id) from events.event_type_topic top where ev.ety_id = top.ety_id) @> prm_top_ids
	  AND ety_category = prm_category
	  ORDER BY ety_name) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.event_type_filter_json(prm_token integer, prm_category events.event_category, prm_top_ids integer[], prm_filter boolean)
  IS 'Return a list of event types linked to a specific category and topics';

CREATE OR REPLACE FUNCTION events.event_type_list_json(prm_token integer, prm_evv_id integer, prm_top_ids integer[], prm_filter boolean)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret json;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT array_to_json(array_agg(row_to_json(d)))
  INTO ret
  FROM (SELECT t.cat, events.event_type_filter_json(prm_token, t.cat, prm_top_ids, prm_filter) as events
	FROM (SELECT unnest(evv_categories) as cat FROM events.eventsview WHERE evv_id = prm_evv_id) as t ) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.event_type_list_json(prm_token integer, prm_evv_id integer, prm_top_ids integer[], prm_filter boolean) IS 'Return a list of event types as json grouped by categories';
