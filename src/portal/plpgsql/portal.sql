SET search_path = portal;

------------
-- PORTAL --
------------

CREATE OR REPLACE FUNCTION portal_add(prm_token integer, prm_name text)
RETURNS integer
LANGUAGE plpgsql
AS $$
DECLARE 
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  INSERT INTO portal.portal (por_name) VALUES(prm_name)
    RETURNING por_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION portal_add(prm_token integer, prm_name text) IS 'Add a new portal';

CREATE OR REPLACE FUNCTION portal_list(prm_token integer)
RETURNS SETOF portal.portal
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  RETURN QUERY SELECT * FROM portal.portal ORDER BY por_name;
END;
$$;
COMMENT ON FUNCTION portal_list(prm_token integer) IS 'List all the portals';

CREATE OR REPLACE FUNCTION portal_rename(prm_token integer, prm_id integer, prm_name text)
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  UPDATE portal.portal SET por_name = prm_name WHERE por_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION portal_rename(prm_token integer, prm_id integer, prm_name text) IS 'Rename a particular portal';

CREATE OR REPLACE FUNCTION portal_delete(prm_token integer, prm_id integer)
RETURNS VOID
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  PERFORM portal.param_value_delete_all(prm_token, prm_id);
  DELETE FROM portal.portal WHERE por_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION portal_delete(prm_token integer, prm_id integer) IS 'Delete a particular portal';

CREATE OR REPLACE FUNCTION portal_clean(prm_token integer, prm_id integer)
RETURNS VOID
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  DELETE FROM portal.mainsection WHERE por_id = prm_id;
  DELETE FROM portal.personsection WHERE por_id = prm_id;
  DELETE FROM portal.portal WHERE por_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION portal_clean(prm_token integer, prm_id integer) IS 'Clean a portal (recursively removing all its menus)';
