CREATE OR REPLACE FUNCTION organ.dossier_add_individual(prm_token integer, prm_firstname text, prm_lastname text, prm_birthdate date, prm_external boolean)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  INSERT INTO organ.dossier(dos_firstname, dos_lastname, dos_birthdate, dos_external) VALUES (prm_firstname, prm_lastname, prm_birthdate, prm_external)
    RETURNING dos_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.dossier_add_individual(prm_token integer, prm_firstname text, prm_lastname text, prm_birthdate date, prm_external boolean) IS 'Add a new dossier of an individual person';

CREATE OR REPLACE FUNCTION organ.dossier_add_grouped(prm_token integer, prm_groupname text, prm_external boolean)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  INSERT INTO organ.dossier(dos_groupname, dos_external, dos_grouped) VALUES (prm_groupname, prm_external, true)
    RETURNING dos_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.dossier_add_grouped(prm_token integer, prm_groupname text, prm_external boolean) IS 'Add a new dossier for a whole group (family)';

CREATE OR REPLACE FUNCTION organ.dossier_get(prm_token integer, prm_id integer)
RETURNS organ.dossier
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret organ.dossier;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT * INTO ret FROM organ.dossier WHERE dos_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.dossier_get(prm_token integer, prm_id integer) IS 'Get information about a dossier';

CREATE OR REPLACE FUNCTION organ.dossier_set_groupname(prm_token integer, prm_id integer, prm_groupname text)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  UPDATE organ.dossier SET dos_groupname = prm_groupname WHERE dos_id = prm_id;
END;
$$;
COMMENT ON FUNCTION organ.dossier_set_groupname(prm_token integer, prm_id integer, prm_groupname text) IS 'Changes the groupname of a dossier';
