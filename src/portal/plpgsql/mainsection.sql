SET search_path = portal;

-----------------
-- MAINSECTION --
-----------------

CREATE OR REPLACE FUNCTION mainsection_add(prm_token integer, prm_por_id integer, prm_name text)
RETURNS integer
LANGUAGE plpgsql
AS $$
DECLARE
  new_order integer;
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  SELECT COALESCE(MAX(mse_order), 0) + 1 INTO new_order FROM portal.mainsection WHERE por_id = prm_por_id;
  INSERT INTO portal.mainsection (por_id, mse_name, mse_order)
    VALUES (prm_por_id, prm_name, new_order)
    RETURNING mse_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION mainsection_add(prm_token integer, prm_por_id integer, prm_name text) 
IS 'Add a new section to the main view of a portal';

CREATE OR REPLACE FUNCTION mainsection_list(prm_token integer, prm_por_id integer)
RETURNS SETOF portal.mainsection
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT * FROM portal.mainsection 
    WHERE por_id = prm_por_id ORDER BY mse_order;
END;
$$;
COMMENT ON FUNCTION mainsection_list(prm_token integer, prm_por_id integer) 
IS 'List the sections of a portal main view';

CREATE OR REPLACE FUNCTION mainsection_rename(prm_token integer, prm_id integer, prm_name text)
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  UPDATE portal.mainsection SET mse_name = prm_name WHERE mse_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION mainsection_rename(prm_token integer, prm_id integer, prm_name text) 
IS 'Rename a particular section of a portal main view';

CREATE OR REPLACE FUNCTION mainsection_delete(prm_token integer, prm_id integer)
RETURNS VOID
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  DELETE FROM portal.mainsection WHERE mse_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION mainsection_delete(prm_token integer, prm_id integer) 
IS 'Delete a particular section of a portal main view';

CREATE OR REPLACE FUNCTION mainsection_move_before_position(
  prm_token integer, 
  prm_id integer, 
  prm_position integer)
RETURNS VOID
LANGUAGE plpgsql
AS $$
DECLARE 
  por integer;
  i integer;
  mse integer;
  l integer; -- length of list + margin
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  SELECT por_id INTO por FROM portal.mainsection WHERE mse_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  IF prm_position > 1 + (SELECT count(*) FROM portal.mainsection WHERE por_id = por) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  IF prm_position < 1 THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;

  SELECT COUNT(*) + 10 INTO l FROM portal.mainsection WHERE por_id = por;
  -- offset and make space between indices
  UPDATE portal.mainsection SET mse_order = l + 2 * mse_order WHERE por_id = por;

  -- reorder
  UPDATE portal.mainsection SET mse_order = l + 2 * prm_position - 1 WHERE mse_id = prm_id;

  -- 0-offset and 1-increment indices again
  i = 1;
  FOR mse IN
    SELECT mse_id FROM portal.mainsection WHERE por_id = por ORDER BY mse_order
  LOOP
    UPDATE portal.mainsection SET mse_order = i WHERE mse_id = mse;
    i = i + 1;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION mainsection_move_before_position(prm_token integer, prm_id integer, prm_position integer) 
IS 'Move a particular section of a portal main view before a given position';

-- todo clean
