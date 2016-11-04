CREATE OR REPLACE FUNCTION login.usergroup_group_json(prm_token integer, prm_ugr_id integer, req json)
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
      INNER JOIN login.usergroup_group USING (grp_id) 
      WHERE ugr_id = prm_ugr_id) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION login.usergroup_group_json(prm_token integer, prm_ugr_id integer, req json) 
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
      WHERE ugr_id = prm_ugr_id) d;
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
    CASE WHEN (req->>'groups') IS NULL THEN NULL ELSE
      login.usergroup_group_json(prm_token, ugr_id, req->'groups') END as groups,
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
