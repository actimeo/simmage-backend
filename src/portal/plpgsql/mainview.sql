/*
mainview_add(mme_id, title, icon, pme_associated): integer
mainview_delete(mme_id): void
mainview_get: mainview
mainview_set(mme_id, title, icon, pme_associated): void
*/

SET search_path = portal;

CREATE OR REPLACE FUNCTION mainview_set(prm_token integer, prm_mme_id integer, prm_title text, prm_icon text, prm_mve_id integer, prm_pme_id_associated integer)
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  UPDATE portal.mainview SET 
    mvi_title = prm_title, 
    mvi_icon = prm_icon,
    mve_id = prm_mve_id,
    pme_id_associated = prm_pme_id_associated
    WHERE mme_id = prm_mme_id;
  IF NOT FOUND THEN
    INSERT INTO portal.mainview (mme_id, mvi_title, mvi_icon, mve_id, pme_id_associated) 
      VALUES(prm_mme_id, prm_title, prm_icon, prm_mve_id, prm_pme_id_associated);
  END IF;
END;
$$;
COMMENT ON FUNCTION mainview_set(prm_token integer, prm_mme_id integer, prm_title text, prm_icon text, prm_mve_id integer, prm_pme_id_associated integer) IS 'Set or update information about a view attached to a specified main menu';

CREATE OR REPLACE FUNCTION mainview_delete(prm_token integer, prm_mme_id integer)
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  DELETE FROM portal.mainview WHERE mme_id = prm_mme_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION mainview_delete(prm_token integer, prm_mme_id integer) IS 'Delete a view attached to a main menu';

CREATE OR REPLACE FUNCTION mainview_get(prm_token integer, prm_mme_id integer)
RETURNS portal.mainview
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret portal.mainview;
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  SELECT * INTO ret FROM portal.mainview WHERE mme_id = prm_mme_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION mainview_get(prm_token integer, prm_mme_id integer) IS 'Get information about a view attached to a main menu';

DROP FUNCTION IF EXISTS mainview_get_details(prm_token integer, prm_mme_id integer);
DROP TYPE IF EXISTS portal.mainview_get_details;
CREATE TYPE portal.mainview_get_details AS (
  mme_id integer,
  mvi_title text,
  mvi_icon text,
  mve_id integer,
  pme_id_associated integer,
  mve_type portal.mainview_element_type,
  mve_name text
);

CREATE FUNCTION mainview_get_details(prm_token integer, prm_mme_id integer)
RETURNS portal.mainview_get_details
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret portal.mainview_get_details;
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  SELECT mme_id, mvi_title, mvi_icon, mve_id, pme_id_associated, mve_type, mve_name INTO ret 
    FROM portal.mainview 
    INNER JOIN portal.mainview_element USING(mve_id)
    WHERE mme_id = prm_mme_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION mainview_get_details(prm_token integer, prm_mme_id integer) IS 'Get detailled information about a view attached to a main menu, containing main view element information';

CREATE OR REPLACE FUNCTION mainview_element_type_list()
RETURNS SETOF portal.mainview_element_type
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT unnest(enum_range(null::portal.mainview_element_type));
END;
$$;
COMMENT ON FUNCTION mainview_element_type_list() IS 'Return the list of types codes for main view elements';
