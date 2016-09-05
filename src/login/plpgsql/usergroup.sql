SET search_path = login;

CREATE OR REPLACE FUNCTION login.usergroup_add(
  prm_token integer, 
  prm_name text)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{users}');
  INSERT INTO login.usergroup (ugr_name) VALUES (prm_name)
    RETURNING ugr_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION login.usergroup_add(prm_token integer, prm_name text) IS 'Add a new user group';

CREATE OR REPLACE FUNCTION login.usergroup_list(
  prm_token integer)
RETURNS SETOF login.usergroup
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT * FROM login.usergroup ORDER BY ugr_name;
END;
$$;
COMMENT ON FUNCTION login.usergroup_list(prm_token integer) IS 'List the users groups';

CREATE OR REPLACE FUNCTION login.usergroup_set_portals(
  prm_token integer, 
  prm_ugr_id integer, 
  prm_por_ids integer[])
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{users}');
  -- Raise an exception if entity does not exist
  IF NOT EXISTS (SELECT 1 FROM login.usergroup WHERE ugr_id = prm_ugr_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  -- If list is NULL, remove all relations
  IF prm_por_ids ISNULL THEN
    DELETE FROM login.usergroup_portal WHERE ugr_id = prm_ugr_id;
    RETURN;
  END IF;
  -- Delete relations present in DB not present in list
  DELETE FROM login.usergroup_portal WHERE ugr_id = prm_ugr_id AND por_id <> ALL(prm_por_ids);
  -- Add relations in list not yet in DB
  FOREACH t IN ARRAY prm_por_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM login.usergroup_portal WHERE ugr_id = prm_ugr_id AND por_id = t) THEN
      INSERT INTO login.usergroup_portal (ugr_id, por_id) VALUES (prm_ugr_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION login.usergroup_set_portals(prm_token integer, prm_ugr_id integer, prm_por_ids integer[]) 
IS 'Set authorized portals for a user group';

CREATE OR REPLACE FUNCTION login.usergroup_set_groups(
  prm_token integer, 
  prm_ugr_id integer, 
  prm_grp_ids integer[])
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{users}');
  -- Raise an exception if entity does not exist
  IF NOT EXISTS (SELECT 1 FROM login.usergroup WHERE ugr_id = prm_ugr_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  -- If list is NULL, remove all relations
  IF prm_grp_ids ISNULL THEN
    DELETE FROM login.usergroup_group WHERE ugr_id = prm_ugr_id;
    RETURN;
  END IF;
  -- Delete relations present in DB not present in list
  DELETE FROM login.usergroup_group WHERE ugr_id = prm_ugr_id AND grp_id <> ALL(prm_grp_ids);
  -- Add relations in list not yet in DB
  FOREACH t IN ARRAY prm_grp_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM login.usergroup_group WHERE ugr_id = prm_ugr_id AND grp_id = t) THEN
      INSERT INTO login.usergroup_group (ugr_id, grp_id) VALUES (prm_ugr_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION login.usergroup_set_groups(prm_token integer, prm_ugr_id integer, prm_grp_ids integer[]) 
IS 'Set authorized groups for a user group';

CREATE OR REPLACE FUNCTION login.usergroup_portal_list(
  prm_token integer, 
  prm_ugr_id integer)
RETURNS SETOF portal.portal
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT portal.* FROM portal.portal
    INNER JOIN login.usergroup_portal USING(por_id)
    WHERE ugr_id = prm_ugr_id
    ORDER BY por_name;
END;
$$;
COMMENT ON FUNCTION login.usergroup_portal_list(prm_token integer, prm_ugr_id integer) 
IS 'Returns the portals authorized for a user group';

CREATE OR REPLACE FUNCTION login.usergroup_group_list(
  prm_token integer, 
  prm_ugr_id integer)
RETURNS SETOF organ.group
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT "group".* FROM organ.group
    INNER JOIN login.usergroup_group USING(grp_id)
    WHERE ugr_id = prm_ugr_id
    ORDER BY grp_name;
END;
$$;
COMMENT ON FUNCTION login.usergroup_group_list(prm_token integer, prm_ugr_id integer) 
IS 'Returns the groups authorized for a user group';
