CREATE OR REPLACE FUNCTION documents.document_type_topic_json(prm_token integer, prm_dty_id integer, req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE 
  ret json;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT array_to_json(array_agg(row_to_json(d))) INTO ret
    FROM (SELECT
      CASE WHEN (req->>'top_id') IS NULL THEN NULL ELSE top_id END as top_id,
      CASE WHEN (req->>'top_name') IS NULL THEN NULL ELSE top_name END as top_name,
      CASE WHEN (req->>'top_description') IS NULL THEN NULL ELSE top_description END as top_description,
      CASE WHEN (req->>'top_icon') IS NULL THEN NULL ELSE top_icon END as top_icon,
      CASE WHEN (req->>'top_color') IS NULL THEN NULL ELSE top_color END as top_color
      FROM organ.topic
      INNER JOIN documents.document_type_topic USING (top_id)
      WHERE dty_id = prm_dty_id) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION documents.document_type_topic_json(prm_token integer, prm_dty_id integer, req json)
  IS 'Returns the topics linked to a document type';

CREATE OR REPLACE FUNCTION documents.document_type_organization_json(prm_token integer, prm_dty_id integer, req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret json;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT array_to_json(array_agg(row_to_json(d))) INTO ret
    FROM (SELECT
      CASE WHEN (req->>'org_id') IS NULL THEN NULL ELSE org_id END as org_id,
      CASE WHEN (req->>'org_name') IS NULL THEN NULL ELSE org_name END as org_name,
      CASE WHEN (req->>'org_description') IS NULL THEN NULL ELSE org_description END as org_description
      FROM organ.organization
      INNER JOIN documents.document_type_organization USING (org_id)
      WHERE dty_id = prm_dty_id) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION documents.document_type_organization_json(prm_token integer, prm_dty_id integer, req json)
  IS 'Returns the organizations linked to a document type';

CREATE OR REPLACE FUNCTION documents.document_type_json(prm_token integer, prm_dty_id integer, req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret json;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT
    CASE WHEN prm_dty_id IS NULL THEN
      array_to_json(array_agg(row_to_json(d)))
    ELSE unnest(array_agg(row_to_json(d))) END
  INTO ret
  FROM (SELECT
    CASE WHEN (req->>'dty_id') IS NULL THEN NULL ELSE dty_id END as dty_id,
    CASE WHEN (req->>'dty_name') IS NULL THEN NULL ELSE dty_name END as dty_name,
    CASE WHEN (req->>'dty_individual_name') IS NULL THEN NULL ELSE dty_individual_name END as dty_individual_name,
    CASE WHEN (req->>'topics') IS NULL THEN NULL ELSE
	documents.document_type_topic_json(prm_token, dty_id, req->'topics') END as topics,
    CASE WHEN (req->>'organizations') IS NULL THEN NULL ELSE
	documents.document_type_organization_json(prm_token, dty_id, req->'organizations') END as organizations
    FROM documents.document_type WHERE (prm_dty_id IS NULL OR dty_id = prm_dty_id)
  ) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION documents.document_type_json (prm_token integer, prm_dty integer, req json)
  IS 'Returns a document type or the list of document types as json';
