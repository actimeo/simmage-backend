CREATE OR REPLACE FUNCTION organ.dossier_status_change(prm_token integer, prm_dos_id integer, prm_org_id integer, prm_value organ.dossier_status_value, prm_when date)
RETURNS VOID
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE

BEGIN
  -- TODO rights to change status?
  PERFORM login._token_assert(prm_token, NULL);
  UPDATE organ.dossier_status SET
   dst_end = prm_when - interval '1 day'
   WHERE 
     dos_id = prm_dos_id AND org_id = prm_org_id
     AND dst_end > prm_when - interval '1 day';

  INSERT INTO organ.dossier_status (dos_id, org_id, dst_value, dst_start)
    VALUES (prm_dos_id, prm_org_id, prm_value, prm_when);
END;
$$;
COMMENT ON FUNCTION organ.dossier_status_change(prm_token integer, dos_id integer, org_id integer, prm_value organ.dossier_status_value, prm_when date) IS 'Change a dossier status in an organization';

CREATE OR REPLACE FUNCTION organ.dossier_status_get(prm_token integer, prm_dos_id integer, prm_org_id integer, prm_when date)
RETURNS organ.dossier_status_value
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret organ.dossier_status_value;
BEGIN
  -- TODO rights on dossier
  PERFORM login._token_assert(prm_token, NULL);
  SELECT dst_value INTO ret FROM organ.dossier_status
   WHERE dos_id = prm_dos_id AND org_id = prm_org_id 
   AND prm_when BETWEEN dst_start AND dst_end;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.dossier_status_get(prm_token integer, prm_dos_id integer, prm_org_id integer, prm_when date) IS 'Return the status of a dossier in an organization at a specified date';

CREATE FUNCTION organ.dossier_status_list_json(prm_token integer, prm_dos_id integer, prm_when date, req json)
RETURNS json
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret json;
BEGIN
  -- TODO rights on dossier
  PERFORM login._token_assert(prm_token, NULL);
  SELECT array_to_json(array_agg(row_to_json(d))) INTO ret
    FROM (SELECT
      CASE WHEN (req->>'org_id') IS NULL THEN NULL ELSE org_id END as org_id, 
      CASE WHEN (req->>'org_name') IS NULL THEN NULL ELSE org_name END as org_name,
      CASE WHEN (req->>'dst_value') IS NULL THEN NULL ELSE 
        organ.dossier_status_get(prm_token, prm_dos_id, org_id, COALESCE(prm_when, CURRENT_DATE)) END as dst_value
      FROM organ.dossier_status
      INNER JOIN organ.organization USING(org_id)
      WHERE dos_id = prm_dos_id
      ) d;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION organ.dossier_status_list_json(prm_token integer, prm_dos_id integer, prm_when date, req json) IS 'Returns the list of statuses of a dossier in the different organizations';

CREATE OR REPLACE FUNCTION organ.dossier_status_value_list()
RETURNS SETOF organ.dossier_status_value
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT unnest(enum_range(null::organ.dossier_status_value));
END;
$$;
COMMENT ON FUNCTION organ.dossier_status_value_list() IS 'Returns the list of dossier statuses';
