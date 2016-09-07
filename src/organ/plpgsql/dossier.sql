CREATE OR REPLACE FUNCTION organ.dossier_add_individual(prm_token integer, prm_firstname text, prm_lastname text, prm_birthdate date, prm_gender text, prm_external boolean)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  INSERT INTO organ.dossier(dos_firstname, dos_lastname, dos_birthdate, dos_gender, dos_external) VALUES (prm_firstname, prm_lastname, prm_birthdate, prm_gender, prm_external)
    RETURNING dos_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.dossier_add_individual(prm_token integer, prm_firstname text, prm_lastname text, prm_birthdate date, prm_gender text, prm_external boolean) IS 'Add a new dossier of an individual person';

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

CREATE OR REPLACE FUNCTION organ.dossier_set_individual_fields(prm_token integer, prm_id integer, prm_firstname text, prm_lastname text, prm_birthdate date, prm_gender text)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  UPDATE organ.dossier SET dos_firstname = prm_firstname, dos_lastname = prm_lastname, dos_birthdate = prm_birthdate, dos_gender = prm_gender WHERE dos_id = prm_id;
END;
$$;
COMMENT ON FUNCTION organ.dossier_set_individual_fields(prm_token integer, prm_id integer, prm_firstname text, prm_lastname text, prm_birthdate date, prm_gender text) IS 'Update the fields of an individual dossier';

CREATE OR REPLACE FUNCTION organ.dossier_set_external(prm_token integer, prm_id integer, prm_external boolean)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  UPDATE organ.dossier SET dos_external = prm_external WHERE dos_id = prm_id;
END;
$$;
COMMENT ON FUNCTION organ.dossier_set_external(prm_token integer, prm_id integer, prm_external boolean) IS 'Changes the external field of a dossier';

/* Dossier link functions */

CREATE OR REPLACE FUNCTION organ.dossier_link_add(prm_token integer, prm_id integer, prm_id_related integer, prm_relationship organ.dossier_relationship)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
  gender text;
  gender_rel text;
  scnd_relationship organ.dossier_relationship = null;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  IF (SELECT dos_grouped FROM organ.dossier WHERE dos_id = prm_id) AND (SELECT dos_grouped FROM organ.dossier WHERE dos_id = prm_id_related) THEN
    RAISE EXCEPTION 'Two grouped dossiers cannot be linked together.' USING ERRCODE = 'data_exception';
  ELSIF prm_relationship IS NULL AND NOT (SELECT dos_grouped FROM organ.dossier WHERE dos_id = prm_id) AND NOT (SELECT dos_grouped FROM organ.dossier WHERE dos_id = prm_id_related) THEN
    RAISE EXCEPTION 'A null relationship can be only set when linking a grouped dossier to an individual dossier.' USING ERRCODE = 'data_exception';
  END IF;
  INSERT INTO organ.dossier_link(dos_id, dos_id_related, dol_relationship) VALUES (prm_id, prm_id_related, prm_relationship)
    RETURNING dol_id INTO ret;
  IF prm_relationship IS NOT NULL THEN
    SELECT dos_gender INTO gender FROM organ.dossier WHERE dos_id = prm_id;
    SELECT dos_gender INTO gender_rel FROM organ.dossier WHERE dos_id = prm_id_related;
  END IF;
  scnd_relationship = organ.dossier_link_get_inverted_relationship(prm_relationship, gender, gender_rel);
  INSERT INTO organ.dossier_link(dos_id, dos_id_related, dol_relationship) VALUES (prm_id_related, prm_id, scnd_relationship);
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.dossier_link_add(prm_token integer, prm_id integer, prm_id_related integer, prm_relationship organ.dossier_relationship) IS 'Link dossiers with each other';

CREATE OR REPLACE FUNCTION organ.dossier_link_get(prm_token integer, prm_id integer)
RETURNS organ.dossier_link
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret organ.dossier_link;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT * INTO ret FROM organ.dossier_link WHERE dol_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.dossier_link_get(prm_token integer, prm_id integer) IS 'Return a link between two dossiers';

CREATE OR REPLACE FUNCTION organ.dossier_link_list(prm_token integer, prm_id integer)
RETURNS SETOF organ.dossier_link
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT * FROM organ.dossier_link WHERE dos_id = prm_id;
END;
$$;
COMMENT ON FUNCTION organ.dossier_link_list(prm_token integer, prm_id integer) IS 'Return a list of dossiers linked to another';

CREATE OR REPLACE FUNCTION organ.dossier_link_set(prm_token integer, prm_id integer, prm_relationship organ.dossier_relationship)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  dos_id1 integer;
  dos_id2 integer;
  scnd_relationship organ.dossier_relationship;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT dos_id, dos_id_related INTO dos_id1, dos_id2 FROM organ.dossier_link WHERE dol_id = prm_id;
  IF prm_relationship IS NULL AND NOT (SELECT dos_grouped FROM organ.dossier WHERE dos_id = dos_id1) AND NOT (SELECT dos_grouped FROM organ.dossier WHERE dos_id = dos_id2) THEN
    RAISE EXCEPTION 'A null relationship can only be set when linking a grouped dossier to an individual dossier' USING ERRCODE = 'data_exception';
  ELSIF prm_relationship IS NOT NULL AND ((SELECT dos_grouped FROM organ.dossier WHERE dos_id = dos_id1) OR (SELECT dos_grouped FROM organ.dossier WHERE dos_id = dos_id2)) THEN
    RAISE EXCEPTION 'Cannot set a defined relationship if a dossier is a grouped one' USING ERRCODE = 'data_exception';
  END IF;
  UPDATE organ.dossier_link SET dol_relationship = prm_relationship WHERE dol_id = prm_id;
  scnd_relationship = organ.dossier_link_get_inverted_relationship(prm_relationship, (SELECT dos_gender FROM organ.dossier WHERE dos_id = dos_id1), (SELECT dos_gender FROM organ.dossier WHERE dos_id = dosid2));
  UPDATE organ.dossier_link SET dol_relationship = scnd_relationship WHERE dos_id = dos_id2 AND dos_id_related = dos_id1;
END;
$$;
COMMENT ON FUNCTION organ.dossier_link_set(prm_token integer, prm_id integer, prm_relationship organ.dossier_relationship) IS 'Update the link between two dossiers';

CREATE OR REPLACE FUNCTION organ.dossier_link_get_inverted_relationship(prm_relationship organ.dossier_relationship, prm_gender text, prm_gender_rel text)
RETURNS organ.dossier_relationship
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  scnd_relationship organ.dossier_relationship = null;
BEGIN
  IF (prm_gender = 'male' AND (prm_relationship = 'sister' OR prm_relationship = 'mother' OR prm_relationship = 'daughter' OR prm_relationship = 'wife')) OR (prm_gender = 'female' AND (prm_relationship = 'brother' OR prm_relationship = 'father' OR prm_relationship = 'son' OR prm_relationship = 'husband')) THEN
    RAISE EXCEPTION 'Relationship conflicts with gender' USING ERRCODE = 'data_exception';
  END IF;
  IF prm_gender_rel = 'male' THEN
    CASE prm_relationship
      WHEN 'brother', 'sister' THEN
	scnd_relationship = 'brother';
      WHEN 'father', 'mother' THEN
	scnd_relationship = 'son';
      WHEN 'son', 'daughter' THEN
	scnd_relationship = 'father';
      WHEN 'wife' THEN
        scnd_relationship = 'husband';
    END CASE;
  ELSIF prm_gender_rel = 'female' THEN
    CASE prm_relationship
      WHEN 'brother', 'sister' THEN
        scnd_relationship = 'sister';
      WHEN 'father', 'mother' THEN
        scnd_relationship = 'daughter';
      WHEN 'son', 'daughter' THEN
        scnd_relationship = 'mother';
      WHEN 'husband' THEN
        scnd_relationship = 'wife';
    END CASE;
  END IF;
  RETURN scnd_relationship;
END;
$$;
