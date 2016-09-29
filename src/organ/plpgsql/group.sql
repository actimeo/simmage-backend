CREATE OR REPLACE FUNCTION organ.group_add(
  prm_token integer, 
  prm_org_id integer, 
  prm_name text, 
  prm_description text,
  prm_mandatory boolean,
  prm_orientation organ.group_orientation)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  INSERT INTO organ.group (org_id, grp_name, grp_description, grp_mandatory, grp_orienation) 
    VALUES (prm_org_id, prm_name, prm_description, prm_mandatory, prm_orientation)
    RETURNING grp_id INTO ret;
  RETURN ret;    
END;
$$;
COMMENT ON FUNCTION organ.group_add(prm_token integer, prm_org_id integer, prm_name text, prm_description text, prm_mandatory boolean, prm_orientation organ.group_orientation) 
IS 'Add a new group providing a particular service to an institution';

CREATE OR REPLACE FUNCTION organ.group_get(prm_token integer, prm_id integer)
RETURNS organ.group
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret organ.group;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT * INTO ret FROM organ.group WHERE grp_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.group_get(prm_token integer, prm_id integer) 
IS 'Get basic information about a service group';

CREATE OR REPLACE FUNCTION organ.group_update(
  prm_token integer,
  prm_id integer,
  prm_name text,
  prm_description text,
  prm_mandatory boolean,
  prm_orientation organ.group_orientation,
  prm_org_id integer)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  IF EXISTS (SELECT 1 FROM organ.group_exclusive_group WHERE grp_id = prm_id) THEN
    RAISE EXCEPTION 'The group with id=% is in an exclusive set and cannot be set as mandatory', prm_id
    USING ERRCODE = 'data_exception';
  END IF;
  UPDATE organ.group SET 
    grp_name = prm_name,
    grp_description = prm_description,
    grp_mandatory = prm_mandatory,
    grp_orientation = prm_orientation,
    org_id = prm_org_id
    WHERE grp_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION organ.group_update(prm_token integer, prm_id integer, prm_name text, prm_description text, prm_mandatory boolean, prm_orientation organ.group_orienation, prm_org_id integer) 
IS 'Update all informations about a group';

CREATE OR REPLACE FUNCTION organ.group_set(prm_token integer, prm_id integer, prm_description text)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE organ.group SET 
    grp_description = prm_description
    WHERE grp_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION organ.group_set(prm_token integer, prm_id integer, prm_description text) 
IS 'Set basic information about a service group';

CREATE OR REPLACE FUNCTION organ.group_set_orientation(
  prm_token integer, 
  prm_id integer, 
  prm_orientation organ.group_orientation)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE organ.group SET
    grp_orientation = prm_orientation
    WHERE grp_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION organ.group_set_orientation(prm_token integer, prm_id integer, 
  prm_orientation organ.group_orientation) 
IS 'Set the orientation of a group.
- organization : the default value - several dossiers are affected to the groups, 
  several participants are working on the group
- participant : only one participant is working on the group (doctor in his office, psychologist, etc...)';

DROP FUNCTION IF EXISTS organ.group_list(prm_token integer, prm_org_id integer);
DROP TYPE IF EXISTS organ.group_list;
CREATE TYPE organ.group_list AS (
  grp_id integer,
  grp_name text,
  grp_description text,
  grp_topics integer[],
  org_id integer,
  org_name text
);

CREATE FUNCTION organ.group_list(prm_token integer, prm_org_id integer, prm_internal boolean)
RETURNS SETOF organ.group_list
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  row organ.group_list;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  RETURN QUERY SELECT 
    grp_id, 
    grp_name, 
    grp_description, 
    ARRAY(SELECT top_id FROM organ.group_topic 
            INNER JOIN organ.topic USING(top_id) WHERE grp_id = grp.grp_id ORDER BY top_name),
    org.org_id,
    org_name
    FROM organ.group grp INNER JOIN organ.organization org
	  USING (org_id)
    WHERE (prm_org_id IS NULL OR grp.org_id = prm_org_id)
      AND (prm_internal IS NULL OR org.org_internal = prm_internal)
    ORDER BY org_name, grp_name;
END;
$$;
COMMENT ON FUNCTION organ.group_list(prm_token integer, prm_org_id integer, prm_internal boolean) 
IS 'Return a list of groups of a particular service, optionally active at a certain date';

CREATE OR REPLACE FUNCTION organ.group_delete(prm_token integer, prm_id integer)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  DELETE FROM organ.group WHERE grp_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION organ.group_delete(prm_token integer, prm_id integer) IS 'Delete a service group';

-- group_rename
CREATE OR REPLACE FUNCTION organ.group_rename(prm_token integer, prm_id integer, prm_name text)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE organ.group SET grp_name = prm_name WHERE grp_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;  

END;
$$;
COMMENT ON FUNCTION organ.group_rename(prm_token integer, prm_id integer, prm_name text) 
IS 'Rename of service group';

CREATE OR REPLACE FUNCTION organ.group_set_topics(prm_token integer, prm_id integer, prm_topics integer[])
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE 
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  IF prm_topics ISNULL THEN
    DELETE FROM organ.group_topic WHERE grp_id = prm_id; 
    RETURN;
  END IF;
  DELETE FROM organ.group_topic WHERE grp_id = prm_id AND top_id <> ALL(prm_topics); 
  FOREACH t IN ARRAY prm_topics 
  LOOP
    IF NOT EXISTS (SELECT 1 FROM organ.group_topic WHERE grp_id = prm_id AND top_id = t) THEN
      INSERT INTO organ.group_topic (grp_id, top_id) VALUES (prm_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION organ.group_set_topics(prm_token integer, prm_id integer, prm_topics integer[]) 
IS 'Set the topics covered by a group';

CREATE OR REPLACE FUNCTION organ.group_set_mandatory(prm_token integer, prm_id integer, prm_mandatory boolean)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  IF EXISTS (SELECT 1 FROM organ.group_exclusive_group WHERE grp_id = prm_id) THEN
    RAISE EXCEPTION 'The group with id=% is in an exclusive set and cannot be set as mandatory', prm_id 
      USING ERRCODE = 'data_exception';
  END IF;
  UPDATE organ.group SET grp_mandatory = prm_mandatory WHERE grp_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;  
END;
$$;
COMMENT ON FUNCTION organ.group_set_mandatory(prm_token integer, prm_id integer, prm_mandatory boolean) 
  IS 'Set the mandatory property as true/false for a group';

CREATE OR REPLACE FUNCTION organ.group_exclusive_new(prm_token integer, prm_name text, prm_grp_ids integer[])
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
  i integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  INSERT INTO organ.group_exclusive (gre_name) VALUES (prm_name)
    RETURNING gre_id INTO ret;
  FOREACH i IN ARRAY prm_grp_ids 
  LOOP
    -- forbid mandatory and exclusive group
    IF EXISTS (SELECT 1 FROM organ."group" WHERE grp_id = i AND grp_mandatory) THEN
      RAISE EXCEPTION 'The group with id=% is mandatory and cannot be added to an exclusive set', i 
        USING ERRCODE = 'data_exception';
    END IF;
    INSERT INTO organ.group_exclusive_group (gre_id, grp_id) VALUES (ret, i);
  END LOOP;  
  RETURN ret;  
END;
$$;
COMMENT ON FUNCTION organ.group_exclusive_new(prm_token integer, prm_name text, prm_grp_ids integer[]) 
IS 'Create a new set of exclusive groups. A group can be contained in no more than one set of exclusive groups.';

CREATE OR REPLACE FUNCTION organ.group_exclusive_with(prm_token integer, prm_grp_id integer)
RETURNS SETOF organ.group
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  row organ.group;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  RETURN QUERY SELECT "group".* FROM organ.group
    INNER JOIN organ.group_exclusive_group USING(grp_id)
    WHERE gre_id = (SELECT gre_id FROM organ.group_exclusive_group WHERE grp_id = prm_grp_id)
    ORDER BY grp_id;
END;
$$;
COMMENT ON FUNCTION organ.group_exclusive_with(prm_token integer, prm_grp_id integer) 
IS 'Returns the list of groups exclusive with the given group';

CREATE OR REPLACE FUNCTION organ.group_exclusive_delete(prm_token integer, prm_grp_id integer)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  gre integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  SELECT gre_id INTO gre FROM organ.group_exclusive_group WHERE grp_id = prm_grp_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  DELETE FROM organ.group_exclusive_group WHERE gre_id = gre;
  DELETE FROM organ.group_exclusive WHERE gre_id = gre;
END;
$$;
COMMENT ON FUNCTION organ.group_exclusive_delete(prm_token integer, prm_grp_id integer) 
IS 'Delete the set of exclusive groups containing the given group';
