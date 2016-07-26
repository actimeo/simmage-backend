SET search_path = portal;

-----------------
-- PERSONSECTION --
-----------------

CREATE OR REPLACE FUNCTION personsection_add(prm_token integer, prm_por_id integer, prm_entity portal.entity, prm_name text)
RETURNS integer
LANGUAGE plpgsql
AS $$
DECLARE
  new_order integer;
  ret integer;
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  SELECT COALESCE(MAX(pse_order), 0) + 1 INTO new_order FROM portal.personsection 
    WHERE por_id = prm_por_id AND pse_entity = prm_entity;
  INSERT INTO portal.personsection (por_id, pse_entity, pse_name, pse_order)
    VALUES (prm_por_id, prm_entity, prm_name, new_order)
    RETURNING pse_id INTO ret;
  RETURN ret;
END;
$$;
COMMENT ON FUNCTION personsection_add(prm_token integer, prm_por_id integer, prm_entity portal.entity, prm_name text) 
IS 'Add a section to an entity view of a portal';

CREATE OR REPLACE FUNCTION personsection_list(prm_token integer, prm_por_id integer, prm_entity portal.entity)
RETURNS SETOF portal.personsection
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, NULL);
  RETURN QUERY SELECT * FROM portal.personsection 
    WHERE por_id = prm_por_id AND pse_entity = prm_entity
    ORDER BY pse_order;
END;
$$;
COMMENT ON FUNCTION personsection_list(prm_token integer, prm_por_id integer, prm_entity portal.entity) 
IS 'List the sections of an entity view';

CREATE OR REPLACE FUNCTION personsection_rename(prm_token integer, prm_id integer, prm_name text)
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  UPDATE portal.personsection SET pse_name = prm_name WHERE pse_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION personsection_rename(prm_token integer, prm_id integer, prm_name text) 
IS 'Rename a section of an entity view';

CREATE OR REPLACE FUNCTION personsection_delete(prm_token integer, prm_id integer)
RETURNS VOID
LANGUAGE plpgsql
AS $$
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  DELETE FROM portal.personsection WHERE pse_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
END;
$$;
COMMENT ON FUNCTION personsection_delete(prm_token integer, prm_id integer) 
IS 'Delete a section of an entity view';

CREATE OR REPLACE FUNCTION personsection_move_before_position(prm_token integer, prm_id integer, prm_position integer)
RETURNS VOID
LANGUAGE plpgsql
AS $$
DECLARE 
  por integer;
  ent portal.entity;
  i integer;
  mse integer;
  l integer; -- length of list + margin
BEGIN
  PERFORM login._token_assert(prm_token, '{structure}');
  SELECT por_id, pse_entity INTO por, ent FROM portal.personsection WHERE pse_id = prm_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  IF prm_position > 1 + (SELECT count(*) FROM portal.personsection WHERE por_id = por) THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;
  IF prm_position < 1 THEN
    RAISE EXCEPTION USING ERRCODE = 'no_data_found';
  END IF;

  SELECT COUNT(*) + 10 INTO l FROM portal.personsection WHERE por_id = por AND pse_entity = ent;
  -- offset and make space between indices
  UPDATE portal.personsection SET pse_order = l + 2 * pse_order WHERE por_id = por AND pse_entity = ent;

  -- reorder
  UPDATE portal.personsection SET pse_order = l + 2 * prm_position - 1 WHERE pse_id = prm_id;

  -- 1-increment indices again
  i = 1;
  FOR mse IN
    SELECT pse_id FROM portal.personsection WHERE por_id = por AND pse_entity = ent ORDER BY pse_order
  LOOP
    UPDATE portal.personsection SET pse_order = i WHERE pse_id = mse;
    i = i + 1;
  END LOOP;
END;
$$;
COMMENT ON FUNCTION personsection_move_before_position(prm_token integer, prm_id integer, prm_position integer) 
IS 'Move a section of an entity view before a given position';

-- todo clean
