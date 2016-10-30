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

CREATE OR REPLACE FUNCTION organ.participant_json(prm_token integer, prm_par_id integer, req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret json;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT row_to_json(d) INTO ret
    FROM (SELECT
      CASE WHEN (req->>'par_id') IS NULL THEN NULL ELSE par_id END as par_id, 
      CASE WHEN (req->>'par_firstname') IS NULL THEN NULL ELSE  par_firstname END as par_firstname, 
      CASE WHEN (req->>'par_lastname') IS NULL THEN NULL ELSE  par_lastname END as par_lastname, 
      CASE WHEN (req->>'par_email') IS NULL THEN NULL ELSE par_email END as par_email
      FROM organ.participant 
      WHERE par_id = prm_par_id) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.participant_json(prm_token integer, prm_par_id integer, req json) 
 IS 'Returns the indormation about a participant as json';
