CREATE OR REPLACE FUNCTION events.event_add(
  prm_token integer, 
  prm_title text, 
  prm_ety_id integer, 
  prm_duration events.event_duration,
  prm_status events.event_status,
  prm_start_time timestamp with time zone, 
  prm_end_time timestamp with time zone, 
  prm_place text, 
  prm_cost money, 
  prm_description text, 
  prm_sumup text, 
  prm_recurent boolean,
  prm_occurence text,
  prm_docctime integer,
  prm_mocctime text,
  prm_occrepeat integer,
  prm_topics integer[], 
  prm_dossiers integer[],
  prm_participants integer[],
  prm_resources integer[])
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  new_id integer;
  loop_id integer;
  ret integer;
  st_date date;
  fdnm_date date;
  get_id boolean := true;
  topics integer[];
  author_id integer;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  loop_id := 0;
  IF NOT prm_recurent THEN prm_occrepeat := 1; END IF;

  IF prm_ety_id IS NOT NULL THEN
    SELECT array_agg(top_id) INTO topics FROM events.event_type_topic WHERE ety_id = prm_ety_id;
  ELSE
    topics = prm_topics;
  END IF;

  SELECT par_id INTO author_id FROM login.user WHERE usr_token = prm_token;

  WHILE loop_id < prm_occrepeat LOOP
    INSERT INTO events.event (
      eve_title,
      ety_id,
      eve_duration,
      eve_status,
      eve_start_time,
      eve_end_time,
      eve_place,
      eve_cost,
      eve_description,
      eve_sumup,
      eve_author,
      eve_creation_date
    ) VALUES (
      prm_title,
      prm_ety_id,
      prm_duration,
      prm_status,
      prm_start_time,
      prm_end_time,
      prm_place,
      prm_cost,
      prm_description,
      prm_sumup,
      author_id,
      CURRENT_TIMESTAMP
    ) RETURNING eve_id INTO new_id;

    IF get_id = true THEN
      ret := new_id;
      get_id := false;
    END IF;

    PERFORM events.event_set_topics(prm_token, new_id, topics);
    PERFORM events.event_set_dossiers(prm_token, new_id, prm_dossiers);
    PERFORM events.event_set_participants(prm_token, new_id, prm_participants);
    PERFORM events.event_set_resources(prm_token, new_id, prm_resources);
    loop_id := loop_id + 1;

    IF prm_recurent THEN
      st_date := date (prm_start_time);
      CASE 
	WHEN prm_occurence = 'daily' THEN
	  prm_start_time := cast((st_date + prm_docctime) as text);

	WHEN prm_occurence = 'monthly' THEN
	    IF prm_mocctime = 'day' THEN
	      prm_start_time := cast((st_date + interval '1 month') as text);

	    ELSIF prm_mocctime = 'weekday' THEN
		-- next event is the Nth weekday of next month
		fdnm_date := date(date_trunc('month', st_date + interval '1 month'));			  -- 1st day of the next month
		prm_start_time := cast((
				    fdnm_date								  -- 1st day of the next month
				    + (7 - extract(dow from fdnm_date) + extract(dow from st_date))::integer % 7  -- (7 - 1st weekday code next month + weekday code of start_date)  % 7
				    + (ceil((date_part('day', st_date)) / 7) - 1)::integer * 7)		  -- (Nth weekday occurence of start_date month - 1) * 7
				  as text);

		IF date_part('day', st_date) > 28 THEN
		  -- If start_date day is after the 28th -> Nth = 5th occurence
		  -- same weekday can't appear 5 times in 2 consecutives months
		  -- Nth occurence => Last occurence (5th occurence => 4th occurence, hence the - 7 days)
		  prm_start_time := cast((date(prm_start_time) - 7) as text);
		END IF;

	    END IF;
      END CASE;
    END IF;

  END LOOP;

  RETURN ret;  
END;
$$;

COMMENT ON FUNCTION events.event_add(
  prm_token integer,
  prm_title text,
  prm_ety_id integer,
  prm_duration events.event_duration,
  prm_status events.event_status,
  prm_start_time timestamp with time zone,
  prm_end_time timestamp with time zone,
  prm_place text,
  prm_cost money,
  prm_description text,
  prm_sumup text,
  prm_recurent boolean,
  prm_occurence text,
  prm_docctime integer,
  prm_mocctime text,
  prm_occrepeat integer,
  prm_topics integer[],
  prm_dossiers integer[],
  prm_participants integer[],
  prm_resources integer[])
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

CREATE OR REPLACE FUNCTION events.event_set_participants(
  prm_token integer,
  prm_eve_id integer,
  prm_par_ids integer[])
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

  IF prm_par_ids ISNULL THEN
    DELETE FROM events.event_participant WHERE eve_id = prm_eve_id;
    RETURN;
  END IF;

  DELETE FROM events.event_participant WHERE eve_id = prm_eve_id AND par_id <> ALL(prm_par_ids);

  FOREACH t IN ARRAY prm_par_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM events.event_participant WHERE eve_id = prm_eve_id AND par_id = t) THEN
      INSERT INTO events.event_participant (eve_id, par_id) VALUES (prm_eve_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION events.event_set_participants(prm_token integer, prm_eve_id integer, prm_par_ids integer[])
IS 'Set participants for an event';

CREATE OR REPLACE FUNCTION events.event_set_resources(
  prm_token integer,
  prm_eve_id integer,
  prm_res_ids integer[])
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

  IF prm_res_ids ISNULL THEN
    DELETE FROM events.event_resource WHERE eve_id = prm_eve_id;
    RETURN;
  END IF;

  DELETE FROM events.event_resource WHERE eve_id = prm_eve_id AND res_id <> ALL(prm_res_ids);

  FOREACH t IN ARRAY prm_res_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM events.event_resource WHERE eve_id = prm_eve_id AND res_id = t) THEN
      INSERT INTO events.event_resource (eve_id, res_id) VALUES (prm_eve_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION events.event_set_resources(prm_token integer, prm_eve_id integer, prm_res_ids integer[])
IS 'Set resources for an event';

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

CREATE OR REPLACE FUNCTION events.event_update(
  prm_token integer,
  prm_eve_id integer,
  prm_title text,
  prm_ety_id integer,
  prm_duration events.event_duration,
  prm_status events.event_status,
  prm_start_time timestamp with time zone,
  prm_end_time timestamp with time zone,
  prm_place text,
  prm_cost money,
  prm_description text,
  prm_sumup text,
  prm_recurent boolean,
  prm_occurence text,
  prm_docctime integer,
  prm_mocctime text,
  prm_occrepeat integer,
  prm_topics integer[],
  prm_dossiers integer[],
  prm_participants integer[],
  prm_resources integer[])
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  topics integer[];
BEGIN
  PERFORM login._token_assert(prm_token, null);

  IF NOT EXISTS (SELECT 1 FROM events.event WHERE eve_id = prm_eve_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;

  IF prm_ety_id IS NOT NULL THEN
    SELECT array_agg(top_id) INTO topics FROM events.event_type_topic WHERE ety_id = prm_ety_id;
  ELSE
    topics = prm_topics;
  END IF;

  UPDATE events.event SET
      eve_title = prm_title,
      ety_id = prm_ety_id,
      eve_duration = prm_duration,
      eve_status = prm_status,
      eve_start_time = prm_start_time,
      eve_end_time = prm_end_time,
      eve_place = prm_place,
      eve_cost = prm_cost,
      eve_description = prm_description,
      eve_sumup = prm_sumup
    WHERE eve_id = e_id;

    PERFORM events.event_set_topics(prm_token, prm_eve_id, topics);
    PERFORM events.event_set_dossiers(prm_token, prm_eve_id, prm_dossiers);
    PERFORM events.event_set_participants(prm_token, prm_eve_id, prm_participants);
    PERFORM events.event_set_resources(prm_token, prm_eve_id, prm_resources);

END;
$$;
COMMENT ON FUNCTION events.event_update(
  prm_token integer,
  prm_eve_id integer,
  prm_title text,
  prm_ety_id integer,
  prm_duration events.event_duration,
  prm_status events.event_status,
  prm_start_time timestamp with time zone,
  prm_end_time timestamp with time zone,
  prm_place text,
  prm_cost money,
  prm_description text,
  prm_sumup text,
  prm_recurent boolean,
  prm_occurence text,
  prm_docctime integer,
  prm_mocctime text,
  prm_occrepeat integer,
  prm_topics integer[],
  prm_dossiers integer[],
  prm_participants integer[],
  prm_resources integer[]) 
IS 'Update an event';

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

CREATE OR REPLACE FUNCTION events.event_participant_list(prm_token integer, prm_eve_id integer)
RETURNS SETOF organ.participant
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, null);
  RETURN QUERY SELECT participant.* FROM organ.participant
    INNER JOIN events.event_participant USING (par_id)
    WHERE eve_id = prm_eve_id
    ORDER BY par_id;
END;
$$;
COMMENT ON FUNCTION events.event_participant_list(prm_token integer, prm_eve_id integer) IS 'Retunrs the participants of a event';

CREATE OR REPLACE FUNCTION events.event_resource_list(prm_token integer, prm_eve_id integer)
RETURNS SETOF resources.resource
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, null);
  RETURN QUERY SELECT resource.* FROM resources.resource
    INNER JOIN events.event_resource USING (res_id)
    WHERE eve_id = prm_eve_id
    ORDER BY res_id;
END;
$$;
COMMENT ON FUNCTION events.event_resource_list(prm_token integer, prm_eve_id integer) IS 'Retunrs the resources of a event';

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

CREATE OR REPLACE FUNCTION events.event_participant_json(prm_token integer, prm_eve_id integer, req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret json;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  SELECT array_to_json(array_agg(row_to_json(d))) INTO ret
    FROM (SELECT
      CASE WHEN (req->>'par_id') IS NULL THEN NULL ELSE par_id END as par_id,
      CASE WHEN (req->>'par_firstname') IS NULL THEN NULL ELSE par_firstname END as par_firstname,
      CASE WHEN (req->>'par_lastname') IS NULL THEN NULL ELSE par_lastname END as par_lastname,
      CASE WHEN (req->>'par_email') IS NULL THEN NULL ELSE par_email END as par_email
      FROM organ.participant
      INNER JOIN events.event_participant USING (par_id)
      WHERE eve_id = prm_eve_id
      ORDER BY par_id) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.event_participant_json(prm_token integer, prm_eve_id integer, req json) IS 'Returns the participants of an event as json';

CREATE OR REPLACE FUNCTION events.event_resource_json(prm_token integer, prm_eve_id integer, req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
   ret json;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  SELECT array_to_json(array_agg(row_to_json(d))) INTO ret
    FROM (SELECT
      CASE WHEN (req->>'res_id') IS NULL THEN NULL ELSE res_id END as res_id,
      CASE WHEN (req->>'res_name') IS NULL THEN NULL ELSE res_name END as res_name
      FROM resources.resource
      INNER JOIN events.event_resource USING (res_id)
      WHERE eve_id = prm_eve_id
      ORDER BY res_id) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION events.event_resource_json(prm_token integer, prm_eve_id integer, req json) IS 'Retunrs the resources of an event as json';

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
    CASE WHEN (req->>'ety_category') IS NULL THEN NULL ELSE ety_category END as ety_category,
    CASE WHEN (req->>'eve_duration') IS NULL THEN NULL ELSE eve_duration END as eve_duration,
    CASE WHEN (req->>'eve_status') IS NULL THEN NULL ELSE eve_status END as eve_status,
    CASE WHEN (req->>'eve_start_time') IS NULL THEN NULL ELSE eve_start_time END as eve_start_time,
    CASE WHEN (req->>'eve_end_time') IS NULL THEN NULL ELSE eve_end_time END as eve_end_time,
    CASE WHEN (req->>'eve_place') IS NULL THEN NULL ELSE eve_place END as eve_place,
    CASE WHEN (req->>'eve_cost') IS NULL THEN NULL ELSE eve_cost END as eve_cost,
    CASE WHEN (req->>'eve_description') IS NULL THEN NULL ELSE eve_description END as eve_description,
    CASE WHEN (req->>'eve_sumup') IS NULL THEN NULL ELSE eve_sumup END as eve_sumup,
    CASE WHEN (req->>'eve_creation_date') IS NULL THEN NULL ELSE eve_creation_date END as eve_creation_date,
    CASE WHEN (req->>'author') IS NULL THEN NULL ELSE
      organ.participant_json(prm_token, eve_author, req->'author') END as author,
    CASE WHEN (req->>'topics') IS NULL THEN NULL ELSE
      events.event_topic_json(prm_token, eve_id, req->'topics') END as topics,
    CASE WHEN (req->>'dossiers') IS NULL THEN NULL ELSE
      events.event_dossier_json(prm_token, eve_id, req->'dossiers') END as dossiers,
    CASE WHEN (req->>'participants') IS NULL THEN NULL ELSE
      events.event_participant_json(prm_token, eve_id, req->'participants') END as participants,
    CASE WHEN (req->>'resources') IS NULL THEN NULL ELSE
      events.event_resource_json(prm_token, eve_id, req->'resources') END as resources
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

CREATE OR REPLACE FUNCTION events.event_delete(prm_token integer, prm_eve_id integer)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, null);
  IF NOT EXISTS (SELECT 1 FROM events.event WHERE eve_id = prm_eve_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;

  DELETE FROM events.event_resource WHERE eve_id = prm_eve_id;
  DELETE FROM events.event_participant WHERE eve_id = prm_eve_id;
  DELETE FROM events.event_dossier WHERE eve_id = prm_eve_id;
  DELETE FROM events.event_topic WHERE eve_id = prm_eve_id;
  DELETE FROM events.event WHERE eve_id = prm_eve_id;
END;
$$;
COMMENT ON FUNCTION events.event_delete(prm_token integer, prm_eve_id integer) IS 'Delete an event and remove all its links with other objects';

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

CREATE OR REPLACE FUNCTION events.event_user_participant_list(prm_token integer, req json)
RETURNS JSON
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret json;
  participant integer;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  SELECT par_id INTO participant FROM login.user WHERE usr_token = prm_token;
  RETURN events.event_json(prm_token, (SELECT ARRAY(
    SELECT DISTINCT eve_id FROM events.event
      LEFT JOIN events.event_participant USING(eve_id)
      WHERE eve_author = participant OR par_id = participant)), req);
END;
$$;
COMMENT ON FUNCTION events.event_user_participant_list(prm_token integer, req json) IS 'Returns all events the user is supposed to attend';

CREATE OR REPLACE FUNCTION events.event_status_list()
RETURNS SETOF events.event_status
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT unnest(enum_range(null::events.event_status));
END;
$$;
COMMENT ON FUNCTION events.event_status_list() IS 'Returns the list of event statuses';
