SET search_path = organ;

CREATE OR REPLACE FUNCTION organ.participant_add(prm_token integer, prm_firstname text, prm_lastname text)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  INSERT INTO organ.participant (par_firstname, par_lastname) VALUES (prm_firstname, prm_lastname)
    RETURNING par_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.participant_add(prm_token integer, prm_firstname text, prm_lastname text) 
IS 'Add a new participant';
