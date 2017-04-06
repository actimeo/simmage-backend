CREATE OR REPLACE FUNCTION portal.mainmenu_json(prm_token integer, prm_mse_id integer, req json)
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
      CASE WHEN (req->>'mme_id') IS NULL THEN NULL ELSE mme_id END as mme_id, 
      CASE WHEN (req->>'mme_name') IS NULL THEN NULL ELSE  mme_name END as mme_name,
      CASE WHEN (req->>'mme_icon') IS NULL THEN NULL ELSE  mme_icon END as mme_icon,
      CASE WHEN (req->>'mme_content_type') IS NULL THEN NULL ELSE  mme_content_type END as mme_content_type,
      CASE WHEN (req->>'mme_content_id') IS NULL THEN NULL ELSE  mme_content_id END as mme_content_id
      FROM portal.mainmenu 
      WHERE mse_id = prm_mse_id
      ORDER BY mme_order) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION portal.mainmenu_json(prm_token integer, prm_mse_id integer, req json) 
 IS 'Returns the mainmenus of a mainsection as json';

CREATE OR REPLACE FUNCTION portal.personmenu_json(prm_token integer, prm_pse_id integer, req json)
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
      CASE WHEN (req->>'pme_id') IS NULL THEN NULL ELSE pme_id END as pme_id, 
      CASE WHEN (req->>'pme_name') IS NULL THEN NULL ELSE  pme_name END as pme_name,
      CASE WHEN (req->>'pme_icon') IS NULL THEN NULL ELSE  pme_icon END as pme_icon,
      CASE WHEN (req->>'pme_content_type') IS NULL THEN NULL ELSE pme_content_type END as pme_content_type,
      CASE WHEN (req->>'pme_content_id') IS NULL THEN NULL ELSE pme_content_id END as pme_content_id
      FROM portal.personmenu 
      WHERE pse_id = prm_pse_id
      ORDER BY pme_order) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION portal.personmenu_json(prm_token integer, prm_pse_id integer, req json) 
 IS 'Returns the personmenus of a personsection as json';

CREATE OR REPLACE FUNCTION portal.mainsection_json(prm_token integer, prm_por_id integer, req json)
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
      CASE WHEN (req->>'mse_id') IS NULL THEN NULL ELSE mse_id END as mse_id, 
      CASE WHEN (req->>'mse_name') IS NULL THEN NULL ELSE  mse_name END as mse_name,
      CASE WHEN (req->>'mainmenus') IS NULL THEN NULL ELSE
        portal.mainmenu_json(prm_token, mse_id, req->'mainmenus') END AS mainmenus
      FROM portal.mainsection 
      WHERE por_id = prm_por_id
      ORDER BY mse_order) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION portal.mainsection_json(prm_token integer, prm_por_id integer, req json) 
 IS 'Returns the mainsections of a portal as json';

CREATE OR REPLACE FUNCTION portal.personsection_json(prm_token integer, prm_por_id integer, req json)
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
      CASE WHEN (req->>'pse_id') IS NULL THEN NULL ELSE pse_id END as pse_id, 
      CASE WHEN (req->>'pse_name') IS NULL THEN NULL ELSE  pse_name END as pse_name,
      CASE WHEN (req->>'personmenus') IS NULL THEN NULL ELSE
        portal.personmenu_json(prm_token, pse_id, req->'personmenus') END AS personmenus
      FROM portal.personsection 
      WHERE por_id = prm_por_id
      ORDER BY pse_order) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION portal.personsection_json(prm_token integer, prm_por_id integer, req json) 
 IS 'Returns the personsections of a portal as json';

CREATE OR REPLACE FUNCTION portal.portal_json(prm_token integer, prm_por_id integer, req json)
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
      CASE WHEN (req->>'por_id') IS NULL THEN NULL ELSE por_id END as por_id, 
      CASE WHEN (req->>'por_name') IS NULL THEN NULL ELSE  por_name END as por_name,
      CASE WHEN (req->>'por_description') IS NULL THEN NULL ELSE por_description END as por_description,
      CASE WHEN (req->>'mainsections') IS NULL THEN NULL ELSE
        portal.mainsection_json(prm_token, por_id, req->'mainsections') END AS mainsections,
      CASE WHEN (req->>'personsections') IS NULL THEN NULL ELSE
        portal.personsection_json(prm_token, por_id, req->'personsections') END AS personsections
      FROM portal.portal
      WHERE (prm_por_id IS NULL OR por_id = prm_por_id)
      ORDER BY portal.por_name) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION portal.portal_json(prm_token integer, prm_por_id integer, req json) 
 IS 'Returns information about a portal or all portals as json';
