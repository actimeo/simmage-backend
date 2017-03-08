CREATE OR REPLACE FUNCTION login.usergroup_group_dossiers_json(prm_token integer, prm_ugr_id integer, req json)
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
      CASE WHEN (req->>'grp_id') IS NULL THEN NULL ELSE grp_id END as grp_id, 
      CASE WHEN (req->>'grp_name') IS NULL THEN NULL ELSE  grp_name END as grp_name, 
      CASE WHEN (req->>'grp_description') IS NULL THEN NULL ELSE grp_description END as grp_description
      FROM organ.group 
      INNER JOIN login.usergroup_group_dossiers USING (grp_id) 
      WHERE ugr_id = prm_ugr_id) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION login.usergroup_group_dossiers_json(prm_token integer, prm_ugr_id integer, req json) 
 IS 'Returns the groups authorized for a usergroup as json';

CREATE OR REPLACE FUNCTION login.usergroup_group_participants_json(prm_token integer, prm_ugr_id integer, req json)
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
      CASE WHEN (req->>'grp_id') IS NULL THEN NULL ELSE grp_id END as grp_id, 
      CASE WHEN (req->>'grp_name') IS NULL THEN NULL ELSE  grp_name END as grp_name, 
      CASE WHEN (req->>'grp_description') IS NULL THEN NULL ELSE grp_description END as grp_description
      FROM organ.group 
      INNER JOIN login.usergroup_group_participants USING (grp_id) 
      WHERE ugr_id = prm_ugr_id) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION login.usergroup_group_participants_json(prm_token integer, prm_ugr_id integer, req json) 
 IS 'Returns the groups authorized for a usergroup as json';

CREATE OR REPLACE FUNCTION login.usergroup_portal_json(prm_token integer, prm_ugr_id integer, req json)
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
      CASE WHEN (req->>'por_id') IS NULL THEN NULL ELSE por_id END as por_id, 
      CASE WHEN (req->>'por_name') IS NULL THEN NULL ELSE  por_name END as por_name, 
      CASE WHEN (req->>'por_description') IS NULL THEN NULL ELSE por_description END as por_description
      FROM portal.portal 
      INNER JOIN login.usergroup_portal USING (por_id) 
      WHERE ugr_id = prm_ugr_id) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION login.usergroup_portal_json(prm_token integer, prm_ugr_id integer, req json) 
 IS 'Returns the portals authorized for a usergroup as json';

CREATE OR REPLACE FUNCTION login.usergroup_topic_json(prm_token integer, prm_ugr_id integer, req json)
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
      CASE WHEN (req->>'top_color') IS NULL THEN NULL ELSE top_color END as top_color,
      CASE WHEN (req->>'ugt_rights') IS NULL THEN NULL ELSE ugt_rights END AS ugt_rights
      FROM organ.topic 
      INNER JOIN login.usergroup_topic USING (top_id) 
      WHERE ugr_id = prm_ugr_id
      ORDER BY top_name) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION login.usergroup_topic_json(prm_token integer, prm_ugr_id integer, req json) 
 IS 'Returns the topics authorized for a usergroup as json';

CREATE OR REPLACE FUNCTION login.usergroup_json(prm_token integer, prm_ugr_id integer, req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret json;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT 
    CASE WHEN prm_ugr_id IS NULL THEN
      array_to_json(array_agg(row_to_json(d))) 
    ELSE unnest(array_agg(row_to_json(d))) END
  INTO ret
  FROM (SELECT 
    CASE WHEN (req->>'ugr_id') IS NULL THEN NULL ELSE ugr_id END as ugr_id, 
    CASE WHEN (req->>'ugr_name') IS NULL THEN NULL ELSE ugr_name END as ugr_name, 
    CASE WHEN (req->>'ugr_rights') IS NULL THEN NULL ELSE ugr_rights END as ugr_rights, 
    CASE WHEN (req->>'ugr_statuses') IS NULL THEN NULL ELSE ugr_statuses END as ugr_statuses, 
    CASE WHEN (req->>'dossiers') IS NULL THEN NULL ELSE
      login.usergroup_group_dossiers_json(prm_token, ugr_id, req->'dossiers') END as dossiers,
    CASE WHEN (req->>'participants') IS NULL THEN NULL ELSE
      login.usergroup_group_participants_json(prm_token, ugr_id, req->'participants') END as participants,
    CASE WHEN (req->>'portals') IS NULL THEN NULL ELSE
      login.usergroup_portal_json(prm_token, ugr_id, req->'portals') END as portals,
    CASE WHEN (req->>'topics') IS NULL THEN NULL ELSE
      login.usergroup_topic_json(prm_token, ugr_id, req->'topics') END as topics
    FROM login.usergroup WHERE (prm_ugr_id IS NULL OR ugr_id = prm_ugr_id)
  ) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION login.usergroup_json(prm_token integer, prm_ugr_id integer, req json) 
 IS 'Returns a usergroup or the list of usergroups as json';

CREATE OR REPLACE FUNCTION login.user_json(prm_token integer, req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret json;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT row_to_json(d) INTO ret
    FROM (SELECT
      CASE WHEN (req->>'usr_login') IS NULL THEN NULL 
        ELSE usr_login END as usr_login,
      CASE WHEN (req->>'usr_pwd') IS NULL THEN NULL 
        ELSE usr_pwd END AS usr_pwd,
      CASE WHEN (req->>'usr_rights') IS NULL THEN NULL 
        ELSE usr_rights END AS usr_rights,
      CASE WHEN (req->>'usr_last_connection_date') IS NULL THEN NULL 
        ELSE usr_last_connection_date END AS usr_last_connection_date,
      CASE WHEN (req->>'usr_last_connection_ip') IS NULL THEN NULL 
        ELSE usr_last_connection_ip END AS usr_last_connection_ip,
      CASE WHEN (req->>'participant') IS NULL THEN NULL 
        WHEN par_id IS NULL THEN NULL
	ELSE organ.participant_json(prm_token, par_id, req->'participant') END AS participant,
      CASE WHEN (req->>'usergroup') IS NULL THEN NULL 
        WHEN ugr_id IS NULL THEN NULL
	ELSE login.usergroup_json(prm_token, ugr_id, req->'usergroup') END AS usergroup
      FROM login."user"
      LEFT JOIN organ.participant USING(par_id)
      LEFT JOIN login.usergroup USING(ugr_id)
      WHERE usr_token = prm_token
      ) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION login.user_json(prm_token integer, req json)
 IS 'Returns information about the connected user';
