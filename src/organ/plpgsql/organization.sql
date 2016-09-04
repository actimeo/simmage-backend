SET search_path = organ;

CREATE OR REPLACE FUNCTION organ.organization_add(
  prm_token integer, 
  prm_name text, 
  prm_description text, 
  prm_internal boolean)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  INSERT INTO organ.organization (org_name, org_internal, org_description) 
    VALUES (prm_name, prm_internal, prm_description)
    RETURNING org_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.organization_add(prm_token integer, prm_name text, prm_description text, 
  prm_internal boolean) IS 'Add a new organization';

CREATE OR REPLACE FUNCTION organ.organization_get(prm_token integer, prm_id integer)
RETURNS organ.organization
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret organ.organization;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT * INTO ret FROM organ.organization WHERE org_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.organization_get(prm_token integer, prm_id integer) 
IS 'Return basic information about a particular organization';

CREATE OR REPLACE FUNCTION organ.organization_delete(prm_token integer, prm_id integer)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  DELETE FROM organ.organization WHERE org_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION organ.organization_delete(prm_token integer, prm_id integer) 
IS 'Delete a particular organization';

CREATE OR REPLACE FUNCTION organ.organization_list(prm_token integer)
RETURNS SETOF organ.organization
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT * FROM organ.organization ORDER BY org_name;
END;
$$;
COMMENT ON FUNCTION organ.organization_list(prm_token integer) IS 'Return the list of all the organizations';

CREATE OR REPLACE FUNCTION organ.organization_rename(prm_token integer, prm_id integer, prm_name text)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE organ.organization SET org_name = prm_name WHERE org_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION organ.organization_rename(prm_token integer, prm_id integer, prm_name text) 
IS 'Rename a particular organization';

CREATE OR REPLACE FUNCTION organ.organization_set(prm_token integer, prm_id integer, prm_description text)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE

BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE organ.organization SET org_description = prm_description
    WHERE org_id = prm_id;
END;
$$;
COMMENT ON FUNCTION organ.organization_set(prm_token integer, prm_id integer, prm_description text) 
IS 'Set information about an organization';
