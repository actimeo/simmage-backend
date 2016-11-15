DROP FUNCTION IF EXISTS lists.listsview_list(prm_token integer);
DROP TYPE IF EXISTS lists.listsview_list;
CREATE TYPE lists.listsview_list AS (
  liv_id integer,
  liv_name text
);

CREATE FUNCTION lists.listsview_list(prm_token integer)
RETURNS SETOF lists.listsview_list
LANGUAGE plpgsql
STABLE
AS $$
DECLARE

BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT
    liv_id, liv_name
    FROM lists.listsview top
    ORDER BY liv_name;
END;
$$;
COMMENT ON FUNCTION lists.listsview_list(prm_token integer) IS 'Returns the list of lists views';

CREATE OR REPLACE FUNCTION lists.listsview_add(prm_token integer, prm_name text)
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  INSERT INTO lists.listsview (liv_name) VALUES (prm_name)
    RETURNING liv_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION lists.listsview_add(prm_token integer, prm_name text) IS 'Add a new lists view';

CREATE OR REPLACE FUNCTION lists.listsview_update(prm_token integer, prm_id integer, prm_name text)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  UPDATE lists.listsview SET 
    liv_name = prm_name
    WHERE liv_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION lists.listsview_update(prm_token integer, prm_id integer, prm_name text) IS 'Update information about an lists view';

DROP FUNCTION IF EXISTS lists.listsview_get(prm_token integer, prm_id integer);
DROP TYPE IF EXISTS lists.listsview_get;
CREATE TYPE lists.listsview_get AS (
  liv_id integer,
  liv_name text
);

CREATE FUNCTION lists.listsview_get(prm_token integer, prm_id integer)
RETURNS lists.listsview_get
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret lists.listsview_get;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT
    liv_id, liv_name
    INTO ret
    FROM lists.listsview top WHERE liv_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION lists.listsview_get(prm_token integer, prm_id integer) IS 'Returns information about an lists view';

CREATE OR REPLACE FUNCTION lists.listsview_delete(prm_token integer, prm_id integer)
RETURNS void
LANGUAGE plpgsql
VOLATILE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{organization}');
  DELETE FROM lists.listsview WHERE liv_id = prm_id;  
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION lists.listsview_delete(prm_token integer, prm_id integer) IS 'Delete an lists view';
