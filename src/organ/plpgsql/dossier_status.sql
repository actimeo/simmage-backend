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

DROP FUNCTION IF EXISTS organ.dossier_status_list(prm_token integer, prm_dos_id integer, prm_when date);
DROP TYPE IF EXISTS organ.dossier_organization_status;

CREATE TYPE organ.dossier_organization_status AS (
  org_id integer,
  org_name text,
  dst_value organ.dossier_status_value
);

CREATE FUNCTION organ.dossier_status_list(prm_token integer, prm_dos_id integer, prm_when date)
RETURNS SETOF organ.dossier_organization_status
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  row organ.dossier_organization_status;
BEGIN
  -- TODO rights on dossier
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT DISTINCT org_id, org_name, organ.dossier_status_get(prm_token, prm_dos_id, org_id, prm_when)
    FROM organ.dossier_status
    INNER JOIN organ.organization USING(org_id);
END;
$$;
COMMENT ON FUNCTION organ.dossier_status_list(prm_token integer, prm_dos_id integer, prm_when date) IS 'Returns the list of statuses of a dossier in the different organizations';

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
