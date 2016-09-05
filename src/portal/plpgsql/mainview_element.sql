SET search_path = portal;

-- add
CREATE OR REPLACE FUNCTION mainview_element_add(
  prm_token integer, 
  prm_type portal.mainview_element_type, 
  prm_name text)
RETURNS integer
LANGUAGE plpgsql
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  INSERT INTO portal.mainview_element(mve_type, mve_name) 
    VALUES (prm_type, prm_name)
    RETURNING mve_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION mainview_element_add(prm_token integer, prm_type portal.mainview_element_type, prm_name text) 
IS 'Add a new main view element of a given type';
  
-- list
CREATE OR REPLACE FUNCTION mainview_element_list(
  prm_token integer, 
  prm_type portal.mainview_element_type)
RETURNS SETOF portal.mainview_element
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  RETURN QUERY SELECT * FROM portal.mainview_element 
    WHERE (prm_type ISNULL OR mve_type = prm_type)
    ORDER BY mve_name;
END;
$$;
COMMENT ON FUNCTION mainview_element_list(prm_token integer, prm_type portal.mainview_element_type) 
IS 'List all the mainview elements for a given type, or all types if given type is null';

-- rename
CREATE OR REPLACE FUNCTION mainview_element_rename(prm_token integer, prm_id integer, prm_name text)
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  UPDATE portal.mainview_element SET mve_name = prm_name WHERE mve_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION mainview_element_rename(prm_token integer, prm_id integer, prm_name text) 
IS 'Rename a particular main view element';

-- delete
CREATE OR REPLACE FUNCTION mainview_element_delete(prm_token integer, prm_id integer)
RETURNS VOID
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  DELETE FROM portal.mainview_element WHERE mve_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION mainview_element_delete(prm_token integer, prm_id integer) 
IS 'Delete a particular main view element';
