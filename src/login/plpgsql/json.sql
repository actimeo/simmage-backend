CREATE OR REPLACE FUNCTION login.usergroup_group_json(prm_token integer, prm_ugr_id integer)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret json;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT array_to_json(array_agg(row_to_json("group", true)), true) INTO ret
    FROM organ.group 
    INNER JOIN login.usergroup_group USING (grp_id) 
    WHERE ugr_id = prm_ugr_id;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION login.usergroup_group_json(prm_token integer, prm_ugr_id integer) 
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
COMMENT ON FUNCTION login.usergroup_portal_json(prm_token integer, prm_por_id integer, req json) 
 IS 'Returns the portals authorized for a usergroup as json';

CREATE OR REPLACE FUNCTION login.usergroup_json(prm_token integer, prm_ugr_id integer, req json)
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
    CASE WHEN (req->>'ugr_id') IS NULL THEN NULL ELSE ugr_id END as ugr_id, 
    CASE WHEN (req->>'ugr_name') IS NULL THEN NULL ELSE ugr_name END as ugr_name, 
    CASE WHEN (req->>'groups') IS NULL THEN NULL ELSE
      login.usergroup_group_json(prm_token, ugr_id) END as groups,
    CASE WHEN (req->>'portals') IS NULL THEN NULL ELSE
      login.usergroup_portal_json(prm_token, ugr_id, req->'portals') END as portals
    FROM login.usergroup WHERE (prm_ugr_id IS NULL OR ugr_id = prm_ugr_id)
  ) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION login.usergroup_json(prm_token integer, prm_ugr_id integer, req json) 
 IS 'Returns a usergroup or the list of usergroups as json';
