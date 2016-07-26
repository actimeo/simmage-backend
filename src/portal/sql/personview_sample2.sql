SET search_path = portal;

--ALTER TYPE portal.personview_element_type ADD VALUE 'personsample2';

-- test data
INSERT INTO portal.personview_element (pve_type, pve_name, pve_entities) VALUES 
  ('personsample2', 'personsample2 element 1 staff/contact', '{staff, contact}'),
  ('personsample2', 'personsample2 element 2 patient/staff', '{patient, staff}'),
  ('personsample2', 'personsample2 element 3 patient', '{patient}'),
  ('personsample2', 'personsample2 element 4 staff', '{staff}'),
  ('personsample2', 'personsample2 element 5 ALL', '{patient, family, staff, contact}');
