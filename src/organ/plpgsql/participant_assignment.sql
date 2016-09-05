CREATE OR REPLACE FUNCTION organ.participant_assignment_add(
  prm_token integer, 
  prm_grp_id integer, 
  prm_par_id integer)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  INSERT INTO organ.participant_assignment (grp_id, par_id)
    VALUES (prm_grp_id, prm_par_id)
    RETURNING paa_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.participant_assignment_add(prm_token integer, prm_grp_id integer, prm_par_id integer) 
IS 'Assign a participant to a new group';

CREATE OR REPLACE FUNCTION organ.participant_assignment_delete(
  prm_token integer, 
  prm_grp_id integer, 
  prm_par_id integer)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE

BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  DELETE FROM organ.participant_assignment 
    WHERE grp_id = prm_grp_id AND par_id = prm_par_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION organ.participant_assignment_delete(prm_token integer, prm_grp_id integer, prm_par_id integer)
IS 'Unassign a participant from a group';

CREATE OR REPLACE FUNCTION organ.participant_assignment_list_participants(prm_token integer, prm_grp_id integer)
RETURNS SETOF organ.participant
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  row organ.participant;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  RETURN QUERY SELECT participant.*
    FROM organ.participant_assignment
    INNER JOIN organ.participant USING(par_id)
    WHERE grp_id = prm_grp_id;
END;
$$;
COMMENT ON FUNCTION organ.participant_assignment_list_participants(prm_token integer, prm_grp_id integer) 
IS 'Return the list of participants assigned to a group';

CREATE OR REPLACE FUNCTION organ.participant_assignment_list_groups(prm_token integer, prm_par_id integer)
RETURNS SETOF organ.group
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  row organ.group;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  RETURN QUERY SELECT "group".*
    FROM organ.participant_assignment
    INNER JOIN organ.group USING(grp_id)
    WHERE par_id = prm_par_id;
END;
$$;
COMMENT ON FUNCTION organ.participant_assignment_list_groups(prm_token integer, prm_par_id integer) 
IS 'Return the list of groups a participant is assigned to';
