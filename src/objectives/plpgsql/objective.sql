CREATE OR REPLACE FUNCTION objectives.objective_add(
  prm_token integer, 
  prm_name text,
  prm_status objectives.objective_status,
  prm_start_date date,
  prm_end_date date,
  prm_topics integer[], 
  prm_dossier integer)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  new_id integer;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  INSERT INTO objectives.objective (obj_name, dos_id, obj_status, obj_start_date, obj_end_date)
    VALUES (prm_name, prm_dossier, prm_status, prm_start_date, prm_end_date)
    RETURNING obj_id INTO new_id;
  PERFORM objectives.objective_set_topics(prm_token, new_id, prm_topics);
  RETURN new_id;
END;
$$;
COMMENT ON FUNCTION objectives.objective_add(
  prm_token integer,
  prm_name text,
  prm_status objectives.objective_status,
  prm_start_date date,
  prm_end_date date,
  prm_topics integer[], 
  prm_dossier integer)
 IS 'Add a new objective';

CREATE OR REPLACE FUNCTION objectives.objective_set_topics(
  prm_token integer,
  prm_obj_id integer,
  prm_top_ids integer[])
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  IF NOT EXISTS (SELECT 1 FROM objectives.objective WHERE obj_id = prm_obj_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;

  IF prm_top_ids ISNULL THEN
    DELETE FROM objectives.objective_topic WHERE obj_id = prm_obj_id;
    RETURN;
  END IF;

  DELETE FROM objectives.objective_topic WHERE obj_id = prm_obj_id AND top_id <> ALL(prm_top_ids);

  FOREACH t IN ARRAY prm_top_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM objectives.objective_topic WHERE obj_id = prm_obj_id AND top_id = t) THEN
      INSERT INTO objectives.objective_topic (obj_id, top_id) VALUES (prm_obj_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION objectives.objective_set_topics(prm_token integer, prm_obj_id integer, prm_top_ids integer[])
IS 'Set topics of a objective';

CREATE OR REPLACE FUNCTION objectives.objective_get(prm_token integer, prm_obj_id integer)
RETURNS objectives.objective
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret objectives.objective;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  SELECT * INTO ret FROM objectives.objective WHERE obj_id = prm_obj_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION objectives.objective_get(prm_token integer, prm_obj_id integer) IS 'Returns information about a objective';

CREATE OR REPLACE FUNCTION objectives.objective_update(
  prm_token integer,
  prm_obj_id integer,
  prm_name text,
  prm_status objectives.objective_status,
  prm_start_date date,
  prm_end_date date,
  prm_topics integer[],
  prm_dossier integer)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, null);
  IF NOT EXISTS (SELECT 1 FROM objectives.objective WHERE obj_id = prm_obj_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;

  UPDATE objectives.objective SET
    obj_name = prm_name,
    obj_status = prm_status,
    obj_start_date = prm_start_date,
    obj_end_date = prm_end_date,
    dos_id = prm_dossier
    WHERE obj_id = prm_obj_id;

  PERFORM objectives.objective_set_topics(prm_token, prm_obj_id, prm_topics);
END;
$$;
COMMENT ON FUNCTION objectives.objective_update(
  prm_token integer,
  prm_obj_id integer,
  prm_name text,
  prm_status objectives.objective_status,
  prm_start_date date,
  prm_end_date date,
  prm_topics integer[],
  prm_dossier integer)
IS 'Update an objective';

CREATE OR REPLACE FUNCTION objectives.objective_topic_list(prm_token integer, prm_obj_id integer)
RETURNS SETOF organ.topic
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, null);
  RETURN QUERY SELECT topic.* FROM organ.topic
    INNER JOIN objectives.objective_topic USING (top_id)
    WHERE obj_id = prm_obj_id
    ORDER BY top_name;
END;
$$;
COMMENT ON FUNCTION objectives.objective_topic_list(prm_token integer, prm_obj_id integer) IS 'Retunrs the topics of a objective';

-- 
-- JSON
-- 
CREATE OR REPLACE FUNCTION objectives.objective_topic_json(prm_token integer, prm_obj_id integer, req json)
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
      INNER JOIN objectives.objective_topic USING (top_id) 
      WHERE obj_id = prm_obj_id
      ORDER BY top_name) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION objectives.objective_topic_json(prm_token integer, prm_obj_id integer, req json) IS 'Returns the topics of a objective as json';

CREATE OR REPLACE FUNCTION objectives.objective_dossier_json(prm_token integer, prm_obj_id integer, req json)
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
      INNER JOIN objectives.objective USING(dos_id)
      WHERE obj_id = prm_obj_id) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION objectives.objective_topic_json(prm_token integer, prm_obj_id integer, req json) IS 'Returns the topics of a objective as json';

CREATE OR REPLACE FUNCTION objectives.objective_json(prm_token integer, prm_obj_ids integer[], req json)
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
    CASE WHEN (req->>'obj_id') IS NULL THEN NULL ELSE obj_id END as obj_id, 
    CASE WHEN (req->>'obj_name') IS NULL THEN NULL ELSE obj_name END as obj_name, 
    CASE WHEN (req->>'obj_status') IS NULL THEN NULL ELSE obj_status END as obj_status,
    CASE WHEN (req->>'obj_start_date') IS NULL THEN NULL ELSE obj_start_date END as obj_start_date,
    CASE WHEN (req->>'obj_end_date') IS NULL THEN NULL ELSE obj_end_date END as obj_end_date,
    CASE WHEN (req->>'topics') IS NULL THEN NULL ELSE
      objectives.objective_topic_json(prm_token, obj_id, req->'topics') END as topics,
    CASE WHEN (req->>'dossier') IS NULL THEN NULL ELSE
      objectives.objective_dossier_json(prm_token, obj_id, req->'dossier') END as dossier
    FROM objectives.objective 
      WHERE obj_id = ANY(prm_obj_ids)
  ) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION objectives.objective_json(prm_token integer, prm_obj_ids integer[], req json) IS 'Returns information about a objective as json';

CREATE OR REPLACE FUNCTION objectives.objective_in_view_list(
  prm_token integer, 
  prm_obv_id integer, 
  prm_grp_id integer, 
  req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  the_obj_id integer;
  
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN objectives.objective_json(prm_token, (SELECT ARRAY(
   SELECT DISTINCT obj_id FROM objectives.objective
    INNER JOIN objectives.objective_topic USING(obj_id)
    INNER JOIN objectives.objectivesview_topic USING(top_id)
    INNER JOIN objectives.objectivesview USING(obv_id)
    INNER JOIN organ.dossiers_authorized_for_user(prm_token) 
      ON dossiers_authorized_for_user = objective.dos_id
    WHERE obv_id = prm_obv_id AND
      (prm_grp_id IS NULL OR 
       prm_grp_id = ANY(SELECT grp_id FROM organ.dossier_assignment WHERE dossier_assignment.dos_id = objective.dos_id)
    ))), req);
END;
$$;
COMMENT ON FUNCTION objectives.objective_in_view_list(
  prm_token integer, 
  prm_obv_id integer, 
  prm_grp_id integer, 
  req json)
 IS 'Returns the objectives visible in a objectives view';

CREATE OR REPLACE FUNCTION objectives.objective_delete(prm_token integer, prm_obj_id integer)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, null);
  IF NOT EXISTS (SELECT 1 FROM objectives.objective WHERE obj_id = prm_obj_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;

  DELETE FROM objectives.objective_topic WHERE obj_id = prm_obj_id;
  DELETE FROM objectives.objective WHERE obj_id = prm_obj_id;
END;
$$;
COMMENT ON FUNCTION objectives.objective_delete(prm_token integer, prm_obj_id integer) IS 'Delete an objective and its links with any topic';
