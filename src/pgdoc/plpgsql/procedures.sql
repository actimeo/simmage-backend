
CREATE OR REPLACE FUNCTION pgdoc.list_schemas(prm_ignore varchar[])
RETURNS SETOF name
LANGUAGE PLPGSQL
STABLE
AS $$
BEGIN
   RETURN QUERY SELECT nspname FROM pg_namespace WHERE nspname NOT like all(prm_ignore) ORDER BY nspname;
END;
$$;

CREATE OR REPLACE FUNCTION pgdoc.schema_description(prm_schema name)
RETURNS text
LANGUAGE PLPGSQL
STABLE
AS $$
DECLARE
  ret text;
BEGIN
  SELECT pg_description.description INTO ret
    FROM pg_namespace
    LEFT JOIN pg_description ON pg_namespace.oid = pg_description.objoid AND pg_description.objsubid = 0
  WHERE
    nspname = $1;
  RETURN ret;
END;
$$;

CREATE OR REPLACE FUNCTION pgdoc.schema_list_tables(prm_schema name)
RETURNS SETOF name
LANGUAGE PLPGSQL
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT pg_class.relname 
    FROM pg_class
    LEFT JOIN pg_namespace ON pg_namespace.oid = pg_class.relnamespace
    WHERE pg_class.relkind = 'r' AND pg_namespace.nspname = $1
    ORDER BY pg_class.relname;
END;
$$;

CREATE OR REPLACE FUNCTION pgdoc.schema_list_types(prm_schema name)
RETURNS SETOF name
LANGUAGE PLPGSQL
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT pg_class.relname
    FROM pg_class
    LEFT JOIN pg_namespace ON pg_namespace.oid = pg_class.relnamespace
    WHERE pg_class.relkind = 'c' AND pg_namespace.nspname = $1
    ORDER BY pg_class.relname;
END;
$$;

CREATE OR REPLACE FUNCTION pgdoc.schema_list_enums(prm_schema name)
RETURNS SETOF name
LANGUAGE PLPGSQL
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT pg_type.typname
    FROM pg_type
    LEFT JOIN pg_namespace ON pg_namespace.oid = pg_type.typnamespace
    WHERE typtype = 'e' AND pg_namespace.nspname = $1
    ORDER BY pg_type.typname;
END;
$$;

CREATE OR REPLACE FUNCTION pgdoc.enum_description(prm_schema name, prm_enum name)
RETURNS text
LANGUAGE PLPGSQL
STABLE
AS $$
DECLARE
  ret text;
BEGIN
  SELECT pg_description.description INTO ret
    FROM pg_type
    LEFT JOIN pg_namespace ON pg_namespace.oid = pg_type.typnamespace
    LEFT JOIN pg_description ON pg_type.oid = pg_description.objoid AND pg_description.objsubid = 0
  WHERE
    typname = prm_enum AND nspname = prm_schema;
  RETURN ret;
END;
$$;

CREATE OR REPLACE FUNCTION pgdoc.enum_values(prm_schema name, prm_enum name)
RETURNS SETOF name
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT enumlabel FROM pg_catalog.pg_enum
    INNER JOIN pg_catalog.pg_type ON pg_type.oid=enumtypid
    INNER JOIN pg_namespace ON pg_type.typnamespace = pg_namespace.oid
    WHERE nspname = prm_schema AND typname = prm_enum
    ORDER BY enumsortorder;
END;
$$;

CREATE OR REPLACE FUNCTION pgdoc.schema_list_functions(prm_schema name)
RETURNS SETOF name
LANGUAGE PLPGSQL
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT pg_proc.proname
    FROM pg_proc
    LEFT JOIN pg_namespace ON pg_namespace.oid = pg_proc.pronamespace
    WHERE pg_namespace.nspname = $1
    ORDER BY pg_proc.proname;
END;
$$;

CREATE OR REPLACE FUNCTION pgdoc.table_description(prm_schema name, prm_table name)
RETURNS text
LANGUAGE PLPGSQL
STABLE
AS $$
DECLARE
  ret text;
BEGIN
  SELECT pg_description.description INTO ret
    FROM pg_class
    LEFT JOIN pg_namespace ON pg_namespace.oid = pg_class.relnamespace
    LEFT JOIN pg_description ON pg_class.oid = pg_description.objoid AND pg_description.objsubid = 0
  WHERE
    pg_class.relkind='r' AND relname = prm_table AND nspname = prm_schema;
  RETURN ret;
END;
$$;

CREATE OR REPLACE FUNCTION pgdoc.table_column_is_primary_key(
  prm_namespace oid, 
  prm_class oid, 
  prm_colnum integer)
RETURNS boolean
LANGUAGE plpgsql
STABLE 
AS $$
DECLARE
  ret boolean;
BEGIN
  RETURN EXISTS (SELECT 1 FROM pg_constraint
    WHERE contype = 'p'
    AND connamespace = prm_namespace
    AND conrelid = prm_class
    and prm_colnum = ALL(conkey)
    );
END;
$$;

CREATE OR REPLACE FUNCTION pgdoc.table_column_is_unique(
  prm_namespace oid, 
  prm_class oid, 
  prm_colnum integer)
RETURNS boolean
LANGUAGE plpgsql
STABLE 
AS $$
DECLARE
  ret boolean;
BEGIN
  RETURN EXISTS (SELECT 1 FROM pg_constraint
    WHERE contype = 'u'
    AND connamespace = prm_namespace
    AND conrelid = prm_class
    and prm_colnum = ALL(conkey)
    );
END;
$$;

CREATE OR REPLACE FUNCTION pgdoc.table_column_foreign_key(
  prm_namespace oid, 
  prm_class oid, 
  prm_colnum integer)
RETURNS text
LANGUAGE plpgsql
STABLE 
AS $$
DECLARE
  ret text;
BEGIN
  SELECT pg_namespace.nspname || '.' || pg_class.relname || '.' || pg_attribute.attname
    INTO ret
    FROM pg_constraint 
    INNER JOIN pg_class on pg_class.oid = confrelid
    INNER JOIN pg_namespace ON pg_namespace.oid = pg_class.relnamespace
    INNER JOIN pg_attribute ON pg_attribute.attrelid = pg_class.oid AND attnum = ANY(confkey)
    WHERE connamespace = prm_namespace
    AND conrelid = prm_class
    and prm_colnum = ALL(conkey) AND contype = 'f';
  RETURN ret;
END;
$$;

DROP FUNCTION IF EXISTS pgdoc.table_columns(prm_schema name, prm_table name);
DROP TYPE IF EXISTS pgdoc.table_columns;
CREATE TYPE pgdoc.table_columns AS (
  col smallint,
  colname name,
  isnotnull boolean,
  hasdefault boolean,
  deftext text,
  description text,
  typname name,
  typlen integer,
  is_primary_key boolean,
  is_unique boolean,
  foreign_key text
);

CREATE FUNCTION pgdoc.table_columns(prm_schema name, prm_table name)
RETURNS SETOF pgdoc.table_columns
LANGUAGE PLPGSQL
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT 
    attnum,
    attname,
    attnotnull,
    atthasdef,
    pg_attrdef.adsrc,
    description,
    pg_type.typname,
    CASE WHEN pg_attribute.atttypmod > 4 
      THEN pg_attribute.atttypmod - 4 
      ELSE atttypmod END,
    pgdoc.table_column_is_primary_key(pg_namespace.oid, pg_class.oid, attnum),
    pgdoc.table_column_is_unique(pg_namespace.oid, pg_class.oid, attnum),
    pgdoc.table_column_foreign_key(pg_namespace.oid, pg_class.oid, attnum)
  FROM pg_attribute  
    INNER JOIN pg_class ON pg_class.oid = pg_attribute.attrelid
    INNER JOIN pg_namespace ON pg_namespace.oid = pg_class.relnamespace
    LEFT JOIN pg_description
      ON pg_description.objoid = pg_attribute.attrelid
      AND pg_description.objsubid = pg_attribute.attnum
    LEFT JOIN pg_attrdef 
      ON pg_attrdef.adrelid = pg_class.oid
      AND pg_attrdef.adnum = pg_attribute.attnum
    INNER JOIN pg_type ON pg_type.oid = pg_attribute.atttypid
  WHERE
    pg_class.relname = prm_table 
    AND pg_class.relkind = 'r'
    AND pg_namespace.nspname = prm_schema
    AND attnum > 0
  ORDER BY attnum;
END;
$$;

CREATE OR REPLACE FUNCTION pgdoc.type_description(prm_schema name, prm_type name)
RETURNS text
LANGUAGE PLPGSQL
STABLE
AS $$
DECLARE
  ret text;
BEGIN
  SELECT pg_description.description INTO ret
    FROM pg_type
    LEFT JOIN pg_namespace ON pg_namespace.oid = pg_type.typnamespace
    LEFT JOIN pg_description ON pg_type.oid = pg_description.objoid AND pg_description.objsubid = 0
  WHERE
    typname = prm_type AND nspname = prm_schema;
  RETURN ret;
END;
$$;

DROP FUNCTION IF EXISTS pgdoc.type_columns(prm_schema name, prm_type name);
DROP TYPE IF EXISTS pgdoc.type_columns;
CREATE TYPE pgdoc.type_columns AS (
  col smallint,
  colname name,
  description text,
  typname name,
  typlen integer
);

CREATE FUNCTION pgdoc.type_columns(prm_schema name, prm_type name)
RETURNS SETOF pgdoc.type_columns
LANGUAGE PLPGSQL
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT 
    attnum,
    attname,
    description,
    pg_type.typname,
    CASE WHEN pg_attribute.atttypmod > 4 
      THEN pg_attribute.atttypmod - 4 
      ELSE atttypmod END
  FROM pg_attribute  
    INNER JOIN pg_class ON pg_class.oid = pg_attribute.attrelid
    INNER JOIN pg_namespace ON pg_namespace.oid = pg_class.relnamespace
    LEFT JOIN pg_description
      ON pg_description.objoid = pg_attribute.attrelid
      AND pg_description.objsubid = pg_attribute.attnum
    INNER JOIN pg_type ON pg_type.oid = pg_attribute.atttypid
  WHERE
    pg_class.relname = prm_type
    AND (pg_class.relkind = 'c' OR pg_class.relkind = 'r')
    AND pg_namespace.nspname = prm_schema
    AND attnum > 0
  ORDER BY attnum;
END;
$$;

CREATE OR REPLACE FUNCTION pgdoc.functions_returning_type(prm_schema name, prm_type name)
RETURNS SETOF name
LANGUAGE PLPGSQL
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT 
    pg_proc.proname --|| '(' || array_to_string(pg_proc.proargnames, ', ') || ')' 
    FROM pg_proc
    INNER JOIN pg_namespace on pg_proc.pronamespace = pg_namespace.oid
    INNER JOIN pg_type ON pg_proc.prorettype = pg_type.oid
    WHERE pg_namespace.nspname = prm_schema
    AND pg_type.typname = prm_type
    ORDER BY pg_proc.proname; --|| '(' || array_to_string(pg_proc.proargnames, ', ') || ')';
END;
$$;

DROP FUNCTION IF EXISTS pgdoc.function_details(prm_schema name, prm_function name);
DROP TYPE IF EXISTS pgdoc.function_details;
CREATE TYPE pgdoc.function_details AS (
  description text,
  src text,
  rettype_schema name,
  rettype_name name,
  retset boolean,
  lang name,
  volatility "char"
);

CREATE FUNCTION pgdoc.function_details(prm_schema name, prm_function name) 
RETURNS pgdoc.function_details
LANGUAGE PLPGSQL
STABLE
AS $$
DECLARE
  ret pgdoc.function_details;
BEGIN
  SELECT 
    description,
    regexp_replace(prosrc, '^\s+', ''),
    retschema.nspname,
    rettype.typname,
    proretset,
    pg_language.lanname,
    pg_proc.provolatile
  INTO ret
  FROM pg_proc
     LEFT JOIN pg_description ON pg_description.objoid = pg_proc.oid
    INNER JOIN pg_namespace ON pg_namespace.oid = pg_proc.pronamespace
    INNER JOIN pg_type rettype ON rettype.oid = prorettype
    INNER JOIN pg_namespace retschema ON retschema.oid = rettype.typnamespace
    INNER JOIN pg_language ON pg_language.oid = pg_proc.prolang
  WHERE pg_namespace.nspname = prm_schema AND proname = prm_function;
  RETURN ret;
END;
$$;

DROP FUNCTION IF EXISTS pgdoc.function_arguments(prm_schema name, prm_function name);
DROP TYPE IF EXISTS pgdoc.function_arguments;
CREATE TYPE pgdoc.function_arguments AS (
  argtype text,
  argname text
);

CREATE FUNCTION pgdoc.function_arguments(prm_schema name, prm_function name)
RETURNS SETOF pgdoc.function_arguments
LANGUAGE PLPGSQL
STABLE
AS $$
BEGIN
  RETURN QUERY SELECT 
    format_type(unnest(proargtypes), null) , 
    unnest(proargnames) 
    FROM pg_proc
    INNER JOIN pg_namespace ON pg_namespace.oid = pg_proc.pronamespace
    WHERE proname = prm_function AND nspname = prm_schema;
END;
$$;

DROP FUNCTION IF EXISTS pgdoc.comments_get_all(prm_ignore_schema text[]);
DROP TYPE IF EXISTS pgdoc.comments_get_all;
CREATE TYPE pgdoc.comments_get_all AS (
  schema name,
  typ pgdoc.typ,
  name text,
  subname text,
  comment text,
  num integer
);

CREATE FUNCTION pgdoc.comments_get_all(prm_ignore_schema text[])
RETURNS SETOF pgdoc.comments_get_all
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  row pgdoc.comments_get_all;
BEGIN
  RETURN QUERY 
    SELECT 
      nspname as schema, 
      'schema'::pgdoc.typ as typ, 
      nspname::text as nam, 
      NULL, 
      pg_description.description, 
      0 as num
    FROM pg_namespace
    LEFT JOIN pg_description ON pg_namespace.oid = pg_description.objoid AND pg_description.objsubid = 0
    WHERE nspname NOT like all(prm_ignore_schema) 
  UNION
    SELECT nspname, 'table'::pgdoc.typ, relname::text, NULL, pg_description.description, 0
    FROM pg_class
    LEFT JOIN pg_namespace ON pg_namespace.oid = pg_class.relnamespace
    LEFT JOIN pg_description ON pg_class.oid = pg_description.objoid AND pg_description.objsubid = 0
    WHERE pg_class.relkind='r' AND nspname NOT like all(prm_ignore_schema) 

  UNION
    SELECT nspname, 'column'::pgdoc.typ, relname, attname, pg_description.description, attnum
    FROM pg_attribute  
    INNER JOIN pg_class ON pg_class.oid = pg_attribute.attrelid
    INNER JOIN pg_namespace ON pg_namespace.oid = pg_class.relnamespace
    LEFT JOIN pg_description
      ON pg_description.objoid = pg_attribute.attrelid
      AND pg_description.objsubid = pg_attribute.attnum
    LEFT JOIN pg_attrdef 
      ON pg_attrdef.adrelid = pg_class.oid
      AND pg_attrdef.adnum = pg_attribute.attnum
    INNER JOIN pg_type ON pg_type.oid = pg_attribute.atttypid
    WHERE pg_class.relkind = 'r'
    AND pg_namespace.nspname NOT like all(prm_ignore_schema) 
    AND attnum > 0

  UNION
    SELECT nspname, 'enum'::pgdoc.typ, typname::text, NULL, pg_description.description, 0
    FROM pg_type
    LEFT JOIN pg_namespace ON pg_namespace.oid = pg_type.typnamespace
    LEFT JOIN pg_description ON pg_type.oid = pg_description.objoid AND pg_description.objsubid = 0
    WHERE typtype = 'e' AND nspname NOT like all(prm_ignore_schema) 
  UNION
    SELECT nspname, 'type'::pgdoc.typ, pg_type.typname::text, NULL, pg_description.description, 0
    FROM pg_type
    INNER JOIN pg_class ON pg_type.typrelid = pg_class.oid
    LEFT JOIN pg_namespace ON pg_namespace.oid = pg_type.typnamespace
    LEFT JOIN pg_description ON pg_type.oid = pg_description.objoid AND pg_description.objsubid = 0
    WHERE pg_class.relkind = 'c' AND nspname NOT like all(prm_ignore_schema) 
  ORDER BY schema, typ, nam, num;
END;
$$;
COMMENT ON FUNCTION pgdoc.comments_get_all(prm_ignore_schema text[]) 
IS 'Return comments for schemas, tables, types, enums';
