SET search_path = portal;

-- add
CREATE OR REPLACE FUNCTION personview_element_add(
  prm_token integer, 
  prm_type portal.personview_element_type, 
  prm_name text, 
  prm_entities portal.entity[])
RETURNS integer
LANGUAGE plpgsql
AS $$
DECLARE
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  INSERT INTO portal.personview_element(pve_type, pve_name, pve_entities) 
    VALUES (prm_type, prm_name, prm_entities)
    RETURNING pve_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION personview_element_add(prm_token integer, prm_type portal.personview_element_type, 
  prm_name text, prm_entities portal.entity[]) IS 'Add a new person view element of a given type';
  
-- list
CREATE OR REPLACE FUNCTION personview_element_list(
  prm_token integer, 
  prm_type portal.personview_element_type, 
  prm_entity portal.entity)
RETURNS SETOF portal.personview_element
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  RETURN QUERY SELECT * FROM portal.personview_element 
    WHERE (prm_type ISNULL OR pve_type = prm_type)
      AND (prm_entity ISNULL OR prm_entity = ANY (pve_entities))
    ORDER BY pve_name;
END;
$$;
COMMENT ON FUNCTION personview_element_list(prm_token integer, prm_type portal.personview_element_type, 
  prm_entity portal.entity) IS 'List all the person view elements for a given type and a given entity type.
 - prm_type: if not null, filter on this person view element type
 - prm_entity: if not null, filter on this entity type';

-- rename
CREATE OR REPLACE FUNCTION personview_element_rename(prm_token integer, prm_id integer, prm_name text)
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  UPDATE portal.personview_element SET pve_name = prm_name WHERE pve_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION personview_element_rename(prm_token integer, prm_id integer, prm_name text) 
IS 'Rename a particular person view element';

-- delete
CREATE OR REPLACE FUNCTION personview_element_delete(prm_token integer, prm_id integer)
RETURNS VOID
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  DELETE FROM portal.personview_element WHERE pve_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION personview_element_delete(prm_token integer, prm_id integer) 
IS 'Delete a particular person view element';
