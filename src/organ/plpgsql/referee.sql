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

CREATE OR REPLACE FUNCTION organ._participant_dossier_referee_list(prm_token integer, prm_dos_id integer)
RETURNS text
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret text;
BEGIN
  SELECT string_agg(ref_function, ', ') INTO ret FROM organ.referee
    INNER JOIN organ.participant_assignment USING(paa_id)
    INNER JOIN organ.dossier_assignment 
     ON dossier_assignment.grp_id = participant_assignment.grp_id
     AND dossier_assignment.doa_id = referee.doa_id
    INNER JOIN login."user" USING(par_id)
    WHERE usr_token = prm_token AND dos_id = prm_dos_id;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ._participant_dossier_referee_list(prm_token integer, prm_dos_id integer) IS 'Returns the list of referee functions of a participant for a dossier';
