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
