CREATE OR REPLACE FUNCTION documents.document_type_add(
  prm_token integer, 
  prm_name text, 
  prm_individual_name boolean)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  INSERT INTO documents.document_type (dty_name, dty_individual_name)
    VALUES (prm_name, prm_individual_name)
    RETURNING dty_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION documents.document_type_add(prm_token integer, prm_name text, prm_individual_name boolean)
 IS 'Add a new document type';

CREATE OR REPLACE FUNCTION documents.document_type_add_details(
  prm_token integer, 
  prm_name text, 
  prm_individual_name boolean,
  prm_topics integer[],
  prm_organizations integer[])
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  SELECT documents.document_type_add(prm_token, prm_name, prm_individual_name) INTO ret;
  PERFORM documents.document_type_set_topics(prm_token, ret, prm_topics);
  PERFORM documents.document_type_set_organizations(prm_token, ret, prm_organizations);
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION documents.document_type_add_details(prm_token integer, 
  prm_name text, prm_individual_name boolean, prm_topics integer[], prm_organizations integer[])
  IS 'Add a new document type with topics and organizations';

CREATE OR REPLACE FUNCTION documents.document_type_update(
  prm_token integer, 
  prm_dty_id integer, 
  prm_name text, 
  prm_individual_name boolean)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE documents.document_type SET
    dty_name = prm_name,
    dty_individual_name = prm_individual_name
    WHERE dty_id = prm_dty_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION documents.document_type_update(prm_token integer, prm_dty_id integer, 
  prm_name text, prm_individual_name boolean)
  IS 'Update a document type';

CREATE OR REPLACE FUNCTION documents.document_type_update_details(
  prm_token integer, 
  prm_dty_id integer, 
  prm_name text, 
  prm_individual_name boolean,
  prm_topics integer[],
  prm_organizations integer[])

RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  PERFORM documents.document_type_update(prm_token, prm_dty_id, prm_name, prm_individual_name);
  PERFORM documents.document_type_set_topics(prm_token, prm_dty_id, prm_topics);
  PERFORM documents.document_type_set_organizations(prm_token, prm_dty_id, prm_organizations);
END;
$$;
COMMENT ON FUNCTION documents.document_type_update(prm_token integer, prm_dty_id integer, 
  prm_name text, prm_individual_name boolean)
  IS 'Update a document type';

CREATE OR REPLACE FUNCTION documents.document_type_delete(prm_token integer, prm_dty_id integer)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  DELETE FROM documents.document_type_topic WHERE dty_id = prm_dty_id;
  DELETE FROM documents.document_type_organization WHERE dty_id = prm_dty_id;
  DELETE FROM documents.document_type WHERE dty_id = prm_dty_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION documents.document_type_delete(prm_token integer, prm_dty_id integer) IS 'Delete a document type (not recursive, links to organizations and topics should be deleted first)';

CREATE OR REPLACE FUNCTION documents.document_type_get(prm_token integer, prm_dty_id integer)
RETURNS documents.document_type
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret documents.document_type;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT * INTO ret FROM documents.document_type 
    WHERE dty_id = prm_dty_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION documents.document_type_get(prm_token integer, prm_dty_id integer) IS 'Returns information about a document type';

DROP FUNCTION IF EXISTS documents.document_type_list(prm_token integer);
DROP TYPE IF EXISTS documents.document_type_list;
CREATE TYPE documents.document_type_list AS (
  dty_id integer,
  dty_name text,
  dty_individual_name boolean,
  top_ids integer[],
  org_ids integer[]
);

CREATE OR REPLACE FUNCTION documents.document_type_list(prm_token integer)
RETURNS SETOF documents.document_type_list
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT
    dty_id, dty_name, dty_individual_name,
    ARRAY(SELECT DISTINCT top_id FROM documents.document_type_topic sub WHERE sub.dty_id = top.dty_id),
    ARRAY(SELECT DISTINCT org_id FROM documents.document_type_organization sub WHERE sub.dty_id = top.dty_id)
    FROM documents.document_type top
    ORDER BY dty_name;
END;
$$;
COMMENT ON FUNCTION documents.document_type_list(prm_token integer) IS 'Returns the list of documents types';

CREATE OR REPLACE FUNCTION documents.document_type_filter(
  prm_token integer, 
  prm_top_ids integer[])
RETURNS SETOF documents.document_type_list
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
    RETURN QUERY SELECT * FROM
      (SELECT DISTINCT
	dty_id, dty_name, dty_individual_name,
	ARRAY(SELECT DISTINCT top_id FROM documents.document_type_topic sub WHERE sub.dty_id = top.dty_id) as topics,
	ARRAY(SELECT DISTINCT org_id FROM documents.document_type_organization sub WHERE sub.dty_id = top.dty_id)
	FROM documents.document_type top
	INNER JOIN documents.document_type_topic USING(dty_id)
	WHERE document_type_topic.top_id = ANY (prm_top_ids)
	ORDER BY dty_name) AS doctypes
      WHERE prm_top_ids <@ topics;
END;
$$;
COMMENT ON FUNCTION documents.document_type_filter(prm_token integer, prm_top_ids integer[]) IS 'Returns the list of documents types filtered by categories and topics ';

CREATE OR REPLACE FUNCTION documents.document_type_set_topics(prm_token integer, prm_dty_id integer, prm_top_ids integer[])
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  -- Raise an exception if entity does not exist
  IF NOT EXISTS (SELECT 1 FROM documents.document_type WHERE dty_id = prm_dty_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  -- If list is NULL, remove all relations
  IF prm_top_ids ISNULL THEN
    DELETE FROM documents.document_type_topic WHERE dty_id = prm_dty_id;
    RETURN;
  END IF;
  -- Delete relations present in DB not present in list
  DELETE FROM documents.document_type_topic WHERE dty_id = prm_dty_id AND top_id <> ALL(prm_top_ids);
  -- Add relations in list not yet in DB
  FOREACH t IN ARRAY prm_top_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM documents.document_type_topic WHERE dty_id = prm_dty_id AND top_id = t) THEN
      INSERT INTO documents.document_type_topic (dty_id, top_id) VALUES (prm_dty_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION documents.document_type_set_topics(prm_token integer, prm_dty_id integer, prm_top_ids integer[]) IS 'Set topics attached to a document type';

CREATE OR REPLACE FUNCTION documents.document_type_topics_list(prm_token integer, prm_dty_id integer)
RETURNS SETOF organ.topic
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT topic.*
    FROM documents.document_type_topic
    INNER JOIN organ.topic USING(top_id)
    WHERE dty_id = prm_dty_id
    ORDER BY top_name;
END;
$$;
COMMENT ON FUNCTION documents.document_type_topics_list(prm_token integer, prm_dty_id integer) IS 'Returns the topics attached to a document type';

CREATE OR REPLACE FUNCTION documents.document_type_set_organizations(prm_token integer, prm_dty_id integer, prm_org_ids integer[])
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  -- Raise an exception if some organization is external
  IF EXISTS (SELECT 1 FROM organ.organization
               WHERE org_id = ANY (prm_org_ids)
               AND NOT org_internal) THEN
    RAISE EXCEPTION 'Organizations should be internal' 
      USING ERRCODE = 'data_exception';
  END IF;
  -- Raise an exception if entity does not exist
  IF NOT EXISTS (SELECT 1 FROM documents.document_type WHERE dty_id = prm_dty_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  -- If list is NULL, remove all relations
  IF prm_org_ids ISNULL THEN
    DELETE FROM documents.document_type_organization WHERE dty_id = prm_dty_id;
    RETURN;
  END IF;
  -- Delete relations present in DB not present in list
  DELETE FROM documents.document_type_organization WHERE dty_id = prm_dty_id AND org_id <> ALL(prm_org_ids);
  -- Add relations in list not yet in DB
  FOREACH t IN ARRAY prm_org_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM documents.document_type_organization WHERE dty_id = prm_dty_id AND org_id = t) THEN
      INSERT INTO documents.document_type_organization (dty_id, org_id) VALUES (prm_dty_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION documents.document_type_set_organizations(prm_token integer, prm_dty_id integer, prm_org_ids integer[]) IS 'Set organizations using a document type';

CREATE OR REPLACE FUNCTION documents.document_type_organizations_list(prm_token integer, prm_dty_id integer)
RETURNS SETOF organ.organization
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT organization.*
    FROM documents.document_type_organization
    INNER JOIN organ.organization USING(org_id)
    WHERE dty_id = prm_dty_id
    ORDER BY org_name;
END;
$$;
COMMENT ON FUNCTION documents.document_type_organizations_list(prm_token integer, prm_dty_id integer) IS 'Returns the organizations using a document type';
