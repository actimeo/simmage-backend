CREATE OR REPLACE FUNCTION organ.group_add(prm_token integer, prm_org_id integer, prm_name text, prm_description text)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  INSERT INTO organ.group (org_id, grp_name, grp_description) 
    VALUES (prm_org_id, prm_name, prm_description)
    RETURNING grp_id INTO ret;
  RETURN ret;    
END;
$$;
COMMENT ON FUNCTION organ.group_add(prm_token integer, prm_org_id integer, prm_name text, prm_description text) IS 'Add a new group providing a particular service to an institution';

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
COMMENT ON FUNCTION organ.group_get(prm_token integer, prm_id integer) IS 'Get basic information about a service group';

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
COMMENT ON FUNCTION organ.group_set(prm_token integer, prm_id integer, prm_description text) IS 'Set basic information about a service group';

DROP FUNCTION IF EXISTS organ.group_list(prm_token integer, prm_org_id integer);
DROP TYPE IF EXISTS organ.group_list;
CREATE TYPE organ.group_list AS (
  grp_id integer,
  grp_name text,
  grp_description text,
  grp_topics integer[]
);

CREATE FUNCTION organ.group_list(prm_token integer, prm_org_id integer)
RETURNS SETOF organ.group_list
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  row organ.group_list;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  IF prm_org_id ISNULL THEN
    RAISE EXCEPTION USING ERRCODE = 'null_value_not_allowed';
  END IF;
  RETURN QUERY SELECT grp_id, grp_name, grp_description, 
    ARRAY(SELECT top_id FROM organ.group_topic INNER JOIN organ.topic USING(top_id) WHERE grp_id = grp.grp_id ORDER BY top_name) FROM organ.group grp
    WHERE org_id = prm_org_id
    ORDER BY grp_name;
END;
$$;
COMMENT ON FUNCTION organ.group_list(prm_token integer, prm_org_id integer) IS 'Return a list of groups of a particular service, optionally active at a certain date';

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
COMMENT ON FUNCTION organ.group_rename(prm_token integer, prm_id integer, prm_name text) IS 'Rename of service group';

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
COMMENT ON FUNCTION organ.group_set_topics(prm_token integer, prm_id integer, prm_topics integer[]) IS 'Set the topics covered by a group';

CREATE OR REPLACE FUNCTION organ.group_set_mandatory(prm_token integer, prm_id integer, prm_mandatory boolean)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE organ.group SET grp_mandatory = prm_mandatory WHERE grp_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;  
END;
$$;
COMMENT ON FUNCTION organ.group_set_mandatory(prm_token integer, prm_id integer, prm_mandatory boolean) IS 'Set the mandatory property as true/false for a group';
