CREATE OR REPLACE FUNCTION events.event_add(
  prm_token integer, 
  prm_title text, 
  prm_ety_id integer, 
  prm_duration events.event_duration, 
  prm_start_time timestamp with time zone, 
  prm_end_time timestamp with time zone, 
  prm_place text, 
  prm_cost money, 
  prm_description text, 
  prm_sumup text, 
  prm_topics integer[], 
  prm_dossiers integer[])
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  new_id integer;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  INSERT INTO events.event (
    eve_title, 
    ety_id, 
    eve_duration, 
    eve_start_time, 
    eve_end_time, 
    eve_place, 
    eve_cost, 
    eve_description, 
    eve_sumup
   ) VALUES (
    prm_title, 
    prm_ety_id, 
    prm_duration, 
    prm_start_time, 
    prm_end_time, 
    prm_place, 
    prm_cost, 
    prm_description, 
    prm_sumup
   ) RETURNING eve_id INTO new_id;

  PERFORM events.event_set_topics(prm_token, new_id, prm_topics);
  PERFORM events.event_set_dossiers(prm_token, new_id, prm_dossiers);
  RETURN new_id;  
END;
$$;

COMMENT ON FUNCTION events.event_add(
  prm_token integer, 
  prm_title text, 
  prm_ety_id integer, 
  prm_duration events.event_duration, 
  prm_start_time timestamp with time zone, 
  prm_end_time timestamp with time zone, 
  prm_place text, 
  prm_cost money, 
  prm_description text, 
  prm_sumup text, 
  prm_topics integer[], 
  prm_dossiers integer[])
 IS 'Add a new event';

CREATE OR REPLACE FUNCTION events.event_set_topics(
  prm_token integer,
  prm_eve_id integer,
  prm_top_ids integer[])
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  IF NOT EXISTS (SELECT 1 FROM events.event WHERE eve_id = prm_eve_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;

  IF prm_top_ids ISNULL THEN
    DELETE FROM events.event_topic WHERE eve_id = prm_eve_id;
    RETURN;
  END IF;

  DELETE FROM events.event_topic WHERE eve_id = prm_eve_id AND top_id <> ALL(prm_top_ids);

  FOREACH t IN ARRAY prm_top_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM events.event_topic WHERE eve_id = prm_eve_id AND top_id = t) THEN
      INSERT INTO events.event_topic (eve_id, top_id) VALUES (prm_eve_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION events.event_set_topics(prm_token integer, prm_eve_id integer, prm_top_ids integer[])
IS 'Set topics of a event';

CREATE OR REPLACE FUNCTION events.event_set_dossiers(
  prm_token integer,
  prm_eve_id integer,
  prm_dos_ids integer[])
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  IF NOT EXISTS (SELECT 1 FROM events.event WHERE eve_id = prm_eve_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;

  IF prm_dos_ids ISNULL THEN
    DELETE FROM events.event_dossier WHERE eve_id = prm_eve_id;
    RETURN;
  END IF;

  DELETE FROM events.event_dossier WHERE eve_id = prm_eve_id AND dos_id <> ALL(prm_dos_ids);

  FOREACH t IN ARRAY prm_dos_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM events.event_dossier WHERE eve_id = prm_eve_id AND dos_id = t) THEN
      INSERT INTO events.event_dossier (eve_id, dos_id) VALUES (prm_eve_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION events.event_set_dossiers(prm_token integer, prm_eve_id integer, prm_dos_ids integer[])
IS 'Set dossiers of a event';

CREATE OR REPLACE FUNCTION events.event_get(prm_token integer, prm_eve_id integer)
RETURNS events.event
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret events.event;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  SELECT * INTO ret FROM events.event WHERE eve_id = prm_eve_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.event_get(prm_token integer, prm_eve_id integer) IS 'Returns information about a event';

CREATE OR REPLACE FUNCTION events.event_topic_list(prm_token integer, prm_eve_id integer)
RETURNS SETOF organ.topic
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, null);
  RETURN QUERY SELECT topic.* FROM organ.topic
    INNER JOIN events.event_topic USING (top_id)
    WHERE eve_id = prm_eve_id
    ORDER BY top_name;
END;
$$;
COMMENT ON FUNCTION events.event_topic_list(prm_token integer, prm_eve_id integer) IS 'Retunrs the topics of a event';

CREATE OR REPLACE FUNCTION events.event_dossier_list(prm_token integer, prm_eve_id integer)
RETURNS SETOF organ.dossier
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, null);
  RETURN QUERY SELECT dossier.* FROM organ.dossier
    INNER JOIN events.event_dossier USING (dos_id)
    WHERE eve_id = prm_eve_id
    ORDER BY dos_id;
END;
$$;
COMMENT ON FUNCTION events.event_dossier_list(prm_token integer, prm_eve_id integer) IS 'Retunrs the dossiers of a event';

-- 
-- JSON
-- 
CREATE OR REPLACE FUNCTION events.event_topic_json(prm_token integer, prm_eve_id integer, req json)
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
      INNER JOIN events.event_topic USING (top_id) 
      WHERE eve_id = prm_eve_id
      ORDER BY top_name) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.event_topic_json(prm_token integer, prm_eve_id integer, req json) IS 'Returns the topics of an event as json';

CREATE OR REPLACE FUNCTION events.event_dossier_json(prm_token integer, prm_eve_id integer, req json)
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
      CASE WHEN (req->>'dos_id') IS NULL THEN NULL ELSE dos_id END as dos_id, 
      CASE WHEN (req->>'dos_firstname') IS NULL THEN NULL ELSE dos_firstname END as dos_firstname, 
      CASE WHEN (req->>'dos_lastname') IS NULL THEN NULL ELSE dos_lastname END as dos_lastname, 
      CASE WHEN (req->>'dos_birthdate') IS NULL THEN NULL ELSE dos_birthdate END as dos_birthdate, 
      CASE WHEN (req->>'dos_gender') IS NULL THEN NULL ELSE dos_gender END as dos_gender, 
      CASE WHEN (req->>'dos_grouped') IS NULL THEN NULL ELSE dos_grouped END as dos_grouped, 
      CASE WHEN (req->>'dos_external') IS NULL THEN NULL ELSE dos_external END as dos_external, 
      CASE WHEN (req->>'dos_groupname') IS NULL THEN NULL ELSE dos_groupname END as dos_groupname 
      FROM organ.dossier
      INNER JOIN events.event_dossier USING (dos_id) 
      WHERE eve_id = prm_eve_id
      ORDER BY dos_id) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.event_topic_json(prm_token integer, prm_eve_id integer, req json) IS 'Returns the topics of an event as json';

CREATE OR REPLACE FUNCTION events.event_json(prm_token integer, prm_eve_ids integer[], req json)
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
    CASE WHEN (req->>'eve_id') IS NULL THEN NULL ELSE eve_id END as eve_id, 
    CASE WHEN (req->>'eve_title') IS NULL THEN NULL ELSE eve_title END as eve_title, 
    CASE WHEN (req->>'ety_id') IS NULL THEN NULL ELSE ety_id END as ety_id, 
    CASE WHEN (req->>'ety_name') IS NULL THEN NULL ELSE ety_name END as ety_name, 
    CASE WHEN (req->>'eve_duration') IS NULL THEN NULL ELSE eve_duration END as eve_duration, 
    CASE WHEN (req->>'eve_start_time') IS NULL THEN NULL ELSE eve_start_time END as eve_start_time, 
    CASE WHEN (req->>'eve_end_time') IS NULL THEN NULL ELSE eve_end_time END as eve_end_time, 
    CASE WHEN (req->>'eve_place') IS NULL THEN NULL ELSE eve_place END as eve_place, 
    CASE WHEN (req->>'eve_cost') IS NULL THEN NULL ELSE eve_cost END as eve_cost, 
    CASE WHEN (req->>'eve_description') IS NULL THEN NULL ELSE eve_description END as eve_description, 
    CASE WHEN (req->>'eve_sumup') IS NULL THEN NULL ELSE eve_sumup END as eve_sumup, 
    CASE WHEN (req->>'topics') IS NULL THEN NULL ELSE
      events.event_topic_json(prm_token, eve_id, req->'topics') END as topics,
    CASE WHEN (req->>'dossiers') IS NULL THEN NULL ELSE
      events.event_dossier_json(prm_token, eve_id, req->'dossiers') END as dossiers
    FROM events.event 
      LEFT JOIN events.event_type USING(ety_id)
      WHERE eve_id = ANY(prm_eve_ids)
  ) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.event_json(prm_token integer, prm_eve_ids integer[], req json) IS 'Returns information about a event as json';

CREATE OR REPLACE FUNCTION events.event_in_view_list(
  prm_token integer, 
  prm_evv_id integer, 
  prm_grp_id integer, 
  req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  the_eve_id integer;
  
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN events.event_json(prm_token, (SELECT ARRAY(
   SELECT DISTINCT eve_id FROM events.event
    INNER JOIN events.event_topic USING(eve_id)
    INNER JOIN events.eventsview_topic USING(top_id)
    INNER JOIN events.eventsview USING(evv_id)
    INNER JOIN events.event_dossier USING(eve_id)
    INNER JOIN organ.dossiers_authorized_for_user(prm_token) 
      ON dossiers_authorized_for_user = event_dossier.dos_id
    WHERE evv_id = prm_evv_id AND
      (prm_grp_id IS NULL OR 
       prm_grp_id = ANY(SELECT grp_id FROM organ.dossier_assignment WHERE dossier_assignment.dos_id = event_dossier.dos_id)
    ))), req);
END;
$$;
COMMENT ON FUNCTION events.event_in_view_list(
  prm_token integer, 
  prm_evv_id integer,
  prm_grp_id integer, 
  req json)
 IS 'Returns the events visible in a events view';

CREATE OR REPLACE FUNCTION events.event_duration_list()
RETURNS SETOF events.event_duration
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT unnest(enum_range(null::events.event_duration));
END;
$$;
COMMENT ON FUNCTION events.event_duration_list() IS 'Returns the list of event durations';
