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

CREATE OR REPLACE FUNCTION organ.organization_list(prm_token integer, prm_internal boolean)
RETURNS SETOF organ.organization
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT * FROM organ.organization 
    WHERE (prm_internal ISNULL OR org_internal = prm_internal)
    ORDER BY org_name;
END;
$$;
COMMENT ON FUNCTION organ.organization_list(prm_token integer, prm_internal boolean) 
IS 'Return the list of all the organizations';

CREATE OR REPLACE FUNCTION organ.organization_set(prm_token integer, prm_id integer, prm_name text, prm_description text, prm_external boolean)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE organ.organization SET org_name = prm_name AND org_description = prm_description AND org_external = prm_external
    WHERE org_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION organ.organization_set(prm_token integer, prm_id integer, prm_description text, prm_description text, prm_external boolean) IS 'Update informations about an organization';
