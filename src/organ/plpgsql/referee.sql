SET search_path = organ;

CREATE OR REPLACE FUNCTION organ.referee_add(
  prm_token integer, 
  prm_grp_id integer, 
  prm_dos_id integer, 
  prm_par_id integer, 
  prm_function text)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  the_doa_id integer;
  the_paa_id integer;
  ret integer;
BEGIN
  PERFORM login._token_assert_any(prm_token, '{organization}');
  SELECT doa_id INTO the_doa_id FROM organ.dossier_assignment 
    WHERE dos_id = prm_dos_id AND grp_id = prm_grp_id;
  IF NOT FOUND THEN 
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  SELECT paa_id INTO the_paa_id FROM organ.participant_assignment 
    WHERE par_id = prm_par_id AND grp_id = prm_grp_id;
  IF NOT FOUND THEN 
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  INSERT INTO organ.referee (doa_id, paa_id, ref_function) 
    VALUES (the_doa_id, the_paa_id, prm_function)
    RETURNING ref_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.referee_add(
  prm_token integer, 
  prm_grp_id integer, 
  prm_dos_id integer, 
  prm_login integer,
  prm_function text) IS 'Add a referee';
