SET search_path = portal;

CREATE OR REPLACE FUNCTION personview_set(
  prm_token integer, 
  prm_pme_id integer, 
  prm_title text, 
  prm_icon text, 
  prm_pve_id integer)
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  UPDATE portal.personview SET pvi_title = prm_title, pvi_icon = prm_icon, pve_id = prm_pve_id
    WHERE pme_id = prm_pme_id;
  IF NOT FOUND THEN
    INSERT INTO portal.personview (pme_id, pvi_title, pvi_icon, pve_id) 
      VALUES(prm_pme_id, prm_title, prm_icon, prm_pve_id);
  END IF;
END;
$$;
COMMENT ON FUNCTION personview_set(prm_token integer, prm_pme_id integer, prm_title text, prm_icon text, 
  prm_pve_id integer) IS 'Set or update information about a view attached to a specified person menu';

CREATE OR REPLACE FUNCTION personview_delete(prm_token integer, prm_pme_id integer)
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  DELETE FROM portal.personview WHERE pme_id = prm_pme_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION personview_delete(prm_token integer, prm_pme_id integer) 
IS 'Delete a view attached to a person menu';

CREATE OR REPLACE FUNCTION personview_get(prm_token integer, prm_pme_id integer)
RETURNS portal.personview
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret portal.personview;
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  SELECT * INTO ret FROM portal.personview WHERE pme_id = prm_pme_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION personview_get(prm_token integer, prm_pme_id integer) 
IS 'Get information about a view attached to a person menu';

DROP FUNCTION IF EXISTS personview_get_details(prm_token integer, prm_pme_id integer);
DROP TYPE IF EXISTS portal.personview_get_details;
CREATE TYPE portal.personview_get_details AS (
  pme_id integer,
  pvi_title text,
  pvi_icon text,
  pve_id integer,
  pve_type portal.personview_element_type,
  pve_name text,
  pve_entities portal.entity[]
);

CREATE FUNCTION personview_get_details(prm_token integer, prm_pme_id integer)
RETURNS portal.personview_get_details
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret portal.personview_get_details;
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  SELECT pme_id, pvi_title, pvi_icon, pve_id, pve_type, pve_name, pve_entities INTO ret 
    FROM portal.personview 
    INNER JOIN portal.personview_element USING(pve_id)
    WHERE pme_id = prm_pme_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION personview_get_details(prm_token integer, prm_pme_id integer) 
IS 'Get detailled information about a view attached to a person menu, containing person view element information';

DROP FUNCTION IF EXISTS personview_details_list(prm_token integer, prm_entity portal.entity, prm_por_id integer);
DROP TYPE IF EXISTS personview_details_list;
CREATE TYPE personview_details_list AS (
  pme_id integer,
  pse_entity portal.entity,
  pse_name text,
  pme_name text,
  pvi_title text
);
COMMENT ON TYPE personview_details_list IS 'Type returned by personview_details_list function';
COMMENT ON COLUMN personview_details_list.pme_id IS 'personmenu/personview identifier';
COMMENT ON COLUMN personview_details_list.pse_entity IS 'Entity containing view';
COMMENT ON COLUMN personview_details_list.pse_name IS 'Name of the section containing view';
COMMENT ON COLUMN personview_details_list.pme_name IS 'Name of the menu containing view';
COMMENT ON COLUMN personview_details_list.pvi_title IS 'View title';

CREATE FUNCTION personview_details_list(prm_token integer, prm_entity portal.entity, prm_por_id integer)
RETURNS SETOF portal.personview_details_list
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  RETURN QUERY SELECT 
    personview.pme_id, 
    pse_entity, 
    pse_name,
    pme_name, 
    pvi_title 
    FROM portal.personview
      INNER JOIN portal.personmenu USING(pme_id)
      INNER JOIN portal.personsection USING(pse_id)
    WHERE (prm_entity ISNULL OR pse_entity = prm_entity)
      AND personsection.por_id = prm_por_id
    ORDER BY pse_entity, pse_order, pme_order;
END;
$$;
COMMENT ON FUNCTION personview_details_list(prm_token integer, prm_entity portal.entity, prm_por_id integer) 
IS 'Return the detailled list of person views for a given portal. It is possible to filter by entities 
specifying a non-null value for prm_entity';

CREATE OR REPLACE FUNCTION personview_element_type_list()
RETURNS SETOF portal.personview_element_type
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT unnest(enum_range(null::portal.personview_element_type));
END;
$$;
COMMENT ON FUNCTION personview_element_type_list() IS 'Return the list of types codes for person view elements';
