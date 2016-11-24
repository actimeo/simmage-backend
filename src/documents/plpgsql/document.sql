CREATE OR REPLACE FUNCTION documents.document_add(
  prm_token integer, 
  prm_par_id_responsible integer, 
  prm_dty_id integer, 
  prm_title text, 
  prm_description text, 
  prm_status documents.document_status, 
  prm_obtainment_date date, 
  prm_execution_date date, 
  prm_validity_date date, 
  prm_file text, 
  prm_topics integer[], 
  prm_dossiers integer[])
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  new_id integer;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  INSERT INTO documents.document (
    par_id_responsible, 
    dty_id, 
    doc_title,
    doc_description,
    doc_status,
    doc_obtainment_date,
    doc_execution_date,
    doc_validity_date,
    doc_file
   ) VALUES (
    prm_par_id_responsible, 
    prm_dty_id, 
    prm_title,
    prm_description,
    prm_status,
    prm_obtainment_date,
    prm_execution_date,
    prm_validity_date,
    prm_file
   ) RETURNING doc_id INTO new_id;

  PERFORM documents.document_set_topics(prm_token, new_id, prm_topics);
  PERFORM documents.document_set_dossiers(prm_token, new_id, prm_dossiers);
  RETURN new_id;
END;
$$;
COMMENT ON FUNCTION documents.document_add(
  prm_token integer, 
  prm_par_id_responsible integer, 
  prm_dty_id integer, 
  prm_title text, 
  prm_description text, 
  prm_status documents.document_status, 
  prm_obtainment_date date, 
  prm_execution_date date, 
  prm_validity_date date, 
  prm_file text, 
  prm_topics integer[], 
  prm_dossiers integer[])
 IS 'Add a new document';

CREATE OR REPLACE FUNCTION documents.document_set_topics(
  prm_token integer,
  prm_doc_id integer,
  prm_top_ids integer[])
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  IF NOT EXISTS (SELECT 1 FROM documents.document WHERE doc_id = prm_doc_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;

  IF prm_top_ids ISNULL THEN
    DELETE FROM documents.document_topic WHERE doc_id = prm_doc_id;
    RETURN;
  END IF;

  DELETE FROM documents.document_topic WHERE doc_id = prm_doc_id AND top_id <> ALL(prm_top_ids);

  FOREACH t IN ARRAY prm_top_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM documents.document_topic WHERE doc_id = prm_doc_id AND top_id = t) THEN
      INSERT INTO documents.document_topic (doc_id, top_id) VALUES (prm_doc_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION documents.document_set_topics(prm_token integer, prm_doc_id integer, prm_top_ids integer[])
IS 'Set topics of a document';

CREATE OR REPLACE FUNCTION documents.document_set_dossiers(
  prm_token integer,
  prm_doc_id integer,
  prm_dos_ids integer[])
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  t integer;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  IF NOT EXISTS (SELECT 1 FROM documents.document WHERE doc_id = prm_doc_id) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;

  IF prm_dos_ids ISNULL THEN
    DELETE FROM documents.document_dossier WHERE doc_id = prm_doc_id;
    RETURN;
  END IF;

  DELETE FROM documents.document_dossier WHERE doc_id = prm_doc_id AND dos_id <> ALL(prm_dos_ids);

  FOREACH t IN ARRAY prm_dos_ids
  LOOP
    IF NOT EXISTS (SELECT 1 FROM documents.document_dossier WHERE doc_id = prm_doc_id AND dos_id = t) THEN
      INSERT INTO documents.document_dossier (doc_id, dos_id) VALUES (prm_doc_id, t);
    END IF;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION documents.document_set_dossiers(prm_token integer, prm_doc_id integer, prm_dos_ids integer[])
IS 'Set dossiers of a document';

CREATE OR REPLACE FUNCTION documents.document_get(prm_token integer, prm_doc_id integer)
RETURNS documents.document
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret documents.document;
BEGIN
  PERFORM login._token_assert(prm_token, null);
  SELECT * INTO ret FROM documents.document WHERE doc_id = prm_doc_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION documents.document_get(prm_token integer, prm_doc_id integer) IS 'Returns information about a document';

CREATE OR REPLACE FUNCTION documents.document_topic_list(prm_token integer, prm_doc_id integer)
RETURNS SETOF organ.topic
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, null);
  RETURN QUERY SELECT topic.* FROM organ.topic
    INNER JOIN documents.document_topic USING (top_id)
    WHERE doc_id = prm_doc_id
    ORDER BY top_name;
END;
$$;
COMMENT ON FUNCTION documents.document_topic_list(prm_token integer, prm_doc_id integer) IS 'Retunrs the topics of a document';

CREATE OR REPLACE FUNCTION documents.document_dossier_list(prm_token integer, prm_doc_id integer)
RETURNS SETOF organ.dossier
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, null);
  RETURN QUERY SELECT dossier.* FROM organ.dossier
    INNER JOIN documents.document_dossier USING (dos_id)
    WHERE doc_id = prm_doc_id
    ORDER BY dos_id;
END;
$$;
COMMENT ON FUNCTION documents.document_dossier_list(prm_token integer, prm_doc_id integer) IS 'Retunrs the dossiers of a document';

-- 
-- JSON
-- 
CREATE OR REPLACE FUNCTION documents.document_topic_json(prm_token integer, prm_doc_id integer, req json)
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
      CASE WHEN (req->>'top_name') IS NULL THEN NULL ELSE  top_name END as top_name, 
      CASE WHEN (req->>'top_description') IS NULL THEN NULL ELSE top_description END as top_description,
      CASE WHEN (req->>'top_icon') IS NULL THEN NULL ELSE top_icon END as top_icon,
      CASE WHEN (req->>'top_color') IS NULL THEN NULL ELSE top_color END as top_color
      FROM organ.topic 
      INNER JOIN documents.document_topic USING (top_id) 
      WHERE doc_id = prm_doc_id
      ORDER BY top_name) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION documents.document_topic_json(prm_token integer, prm_doc_id integer, req json) IS 'Returns the topics of a document as json';

CREATE OR REPLACE FUNCTION documents.document_dossier_json(prm_token integer, prm_doc_id integer, req json)
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
      CASE WHEN (req->>'dos_id') IS NULL THEN NULL ELSE dos_id END as dos_id, 
      CASE WHEN (req->>'dos_firstname') IS NULL THEN NULL ELSE dos_firstname END as dos_firstname, 
      CASE WHEN (req->>'dos_lastname') IS NULL THEN NULL ELSE dos_lastname END as dos_lastname, 
      CASE WHEN (req->>'dos_birthdate') IS NULL THEN NULL ELSE dos_birthdate END as dos_birthdate, 
      CASE WHEN (req->>'dos_gender') IS NULL THEN NULL ELSE dos_gender END as dos_gender, 
      CASE WHEN (req->>'dos_grouped') IS NULL THEN NULL ELSE dos_grouped END as dos_grouped, 
      CASE WHEN (req->>'dos_external') IS NULL THEN NULL ELSE dos_external END as dos_external, 
      CASE WHEN (req->>'dos_groupname') IS NULL THEN NULL ELSE dos_groupname END as dos_groupname 
      FROM organ.dossier
      INNER JOIN documents.document_dossier USING (dos_id) 
      WHERE doc_id = prm_doc_id
      ORDER BY dos_id) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION documents.document_topic_json(prm_token integer, prm_doc_id integer, req json) IS 'Returns the topics of a document as json';

CREATE OR REPLACE FUNCTION documents.document_json(prm_token integer, prm_doc_ids integer[], req json)
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
    CASE WHEN (req->>'doc_id') IS NULL THEN NULL ELSE doc_id END as doc_id, 
    CASE WHEN (req->>'par_id_responsible') IS NULL THEN NULL ELSE par_id_responsible END as par_id_responsible, 
    CASE WHEN (req->>'dty_id') IS NULL THEN NULL ELSE dty_id END as dty_id, 
    CASE WHEN (req->>'doc_title') IS NULL THEN NULL ELSE doc_title END as doc_title, 
    CASE WHEN (req->>'doc_description') IS NULL THEN NULL ELSE doc_description END as doc_description, 
    CASE WHEN (req->>'doc_status') IS NULL THEN NULL ELSE doc_status END as doc_status, 
    CASE WHEN (req->>'doc_obtainment_date') IS NULL THEN NULL ELSE doc_obtainment_date END as doc_obtainment_date, 
    CASE WHEN (req->>'doc_execution_date') IS NULL THEN NULL ELSE doc_execution_date END as doc_execution_date, 
    CASE WHEN (req->>'doc_validity_date') IS NULL THEN NULL ELSE doc_validity_date END as doc_validity_date, 
    CASE WHEN (req->>'doc_file') IS NULL THEN NULL ELSE doc_file END as doc_file,
    CASE WHEN (req->>'topics') IS NULL THEN NULL ELSE
      documents.document_topic_json(prm_token, doc_id, req->'topics') END as topics,
    CASE WHEN (req->>'dossiers') IS NULL THEN NULL ELSE
      documents.document_dossier_json(prm_token, doc_id, req->'dossiers') END as dossiers
    FROM documents.document WHERE doc_id = ANY(prm_doc_ids)
  ) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION documents.document_json(prm_token integer, prm_doc_ids integer[], req json) IS 'Returns information about a document as json';

CREATE OR REPLACE FUNCTION documents.document_in_view_list(prm_token integer, prm_dov_id integer, req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  the_doc_id integer;
  
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RAISE WARNING 'go';
  RETURN documents.document_json(prm_token, (SELECT ARRAY(
   SELECT DISTINCT doc_id FROM documents.document
    INNER JOIN documents.document_topic USING(doc_id)
    INNER JOIN documents.documentsview_topic USING(top_id)
    INNER JOIN documents.documentsview USING(dov_id))), req);
END;
$$;
COMMENT ON FUNCTION documents.document_in_view_list(prm_token integer, prm_dov_id integer, req json) IS 'Returns the documents visible in a documents view';
