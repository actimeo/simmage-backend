SET search_path = organ;

CREATE OR REPLACE FUNCTION organ.participant_add(prm_token integer, prm_firstname text, prm_lastname text)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert_any(prm_token, '{organization, users}');
  INSERT INTO organ.participant (par_firstname, par_lastname) VALUES (prm_firstname, prm_lastname)
    RETURNING par_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.participant_add(prm_token integer, prm_firstname text, prm_lastname text) 
IS 'Add a new participant';

CREATE OR REPLACE FUNCTION organ.participant_list(prm_token integer)
RETURNS SETOF organ.participant
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert_any(prm_token, NULL);
  RETURN QUERY SELECT * FROM organ.participant
    ORDER BY par_lastname, par_firstname;
END;
$$;
COMMENT ON FUNCTION organ.participant_list(prm_token integer) IS 'Return the list or participants';
