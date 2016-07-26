SET search_path = portal;

-- ALTER TYPE portal.mainview_element_type ADD VALUE 'sample1';

-- test data
INSERT INTO portal.mainview_element (mve_type, mve_name) VALUES 
  ('sample1', 'Sample1 element 1'),
  ('sample1', 'Sample1 element 2'),
  ('sample1', 'Sample1 element 3');
