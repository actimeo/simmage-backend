SET search_path = portal;

--------------
-- MAINMENU --
--------------

CREATE OR REPLACE FUNCTION mainmenu_add(prm_token integer, prm_mse_id integer, prm_name text)
RETURNS integer
LANGUAGE plpgsql
AS $$
DECLARE
  new_order integer;
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  SELECT COALESCE(MAX(mme_order), 0) + 1 INTO new_order FROM portal.mainmenu WHERE mse_id = prm_mse_id;
  INSERT INTO portal.mainmenu (mse_id, mme_name, mme_order)
    VALUES (prm_mse_id, prm_name, new_order)
    RETURNING mme_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION mainmenu_add(prm_token integer, prm_mse_id integer, prm_name text) 
IS 'Add a menu entry to a section of a portal main view';

CREATE OR REPLACE FUNCTION mainmenu_list(prm_token integer, prm_mse_id integer)
RETURNS SETOF portal.mainmenu
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT * FROM portal.mainmenu
    WHERE mse_id = prm_mse_id ORDER BY mme_order;
END;
$$;
COMMENT ON FUNCTION mainmenu_list(prm_token integer, prm_mse_id integer) 
IS 'List the menu entries in a section of a portal main view';

CREATE OR REPLACE FUNCTION mainmenu_rename(prm_token integer, prm_id integer, prm_name text)
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  UPDATE portal.mainmenu SET mme_name = prm_name WHERE mme_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION mainmenu_rename(prm_token integer, prm_id integer, prm_name text) 
IS 'Rename a particular menu entry of a portal main view';

CREATE OR REPLACE FUNCTION mainmenu_delete(prm_token integer, prm_id integer)
RETURNS VOID
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  DELETE FROM portal.mainmenu WHERE mme_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION mainmenu_delete(prm_token integer, prm_id integer) 
IS 'Delete a particular menu entry of a portal main view';

CREATE OR REPLACE FUNCTION mainmenu_move_before_position(prm_token integer, prm_id integer, prm_position integer)
RETURNS VOID
LANGUAGE plpgsql
AS $$
DECLARE 
  mse integer;
  i integer;
  mme integer;
  l integer; -- length of list + margin
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  SELECT mse_id INTO mse FROM portal.mainmenu WHERE mme_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  IF prm_position > 1 + (SELECT count(*) FROM portal.mainmenu WHERE mse_id = mse) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  IF prm_position < 1 THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;

  SELECT COUNT(*) + 10 INTO l FROM portal.mainmenu WHERE mse_id = mse;
  -- offset and make space between indices
  UPDATE portal.mainmenu SET mme_order = l + 2 * mme_order WHERE mse_id = mse;

  -- reorder
  UPDATE portal.mainmenu SET mme_order = l + 2 * prm_position - 1 WHERE mme_id = prm_id;

  -- 1-increment indices again
  i = 1;
  FOR mme IN
    SELECT mme_id FROM portal.mainmenu WHERE mse_id = mse ORDER BY mme_order
  LOOP
    UPDATE portal.mainmenu SET mme_order = i WHERE mme_id = mme;
    i = i + 1;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION mainmenu_move_before_position(prm_token integer, prm_id integer, prm_position integer) 
IS 'Move a menu entry of a portal main view before a given position';
