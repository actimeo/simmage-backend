SET search_path = portal;

-- ALTER TYPE portal.mainview_element_type ADD VALUE 'sample2';

INSERT INTO portal.mainview_element (mve_type, mve_name) VALUES 
  ('sample2', 'Sample2 element 1'),
  ('sample2', 'Sample2 element 2'),
  ('sample2', 'Sample2 element 3');
