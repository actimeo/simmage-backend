CREATE OR REPLACE FUNCTION public._json_date_format(prm_date timestamp with time zone)
RETURNS text
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  RETURN to_char(prm_date, 'DD/MM/YYYY HH24:MI:SS');
END;
$$;
COMMENT ON FUNCTION public._json_date_format(prm_date timestamp with time zone) IS 'Return a timestamp to a specific format for JSON functions';

CREATE OR REPLACE FUNCTION public._format_retrieve_ids(prm_par_id integer, prm_table text, prm_order_field text, prm_asc_desc text)
RETURNS SETOF integer
LANGUAGE plpgsql
AS $$
BEGIN
  RETURN QUERY EXECUTE FORMAT('SELECT %s_id from %s._retrieve_%2$s_participant($1) order by %s %s', left(prm_table, 3), prm_table, prm_order_field, prm_asc_desc) USING prm_par_id;
END;
$$;
COMMENT ON FUNCTION public._format_retrieve_ids(prm_par_id integer, prm_table text, prm_order_field text, prm_asc_desc text)
IS 'Return a list of elements ids linked to a participant, ordered by a specified field and either in ascending or descending order';
