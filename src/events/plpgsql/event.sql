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
