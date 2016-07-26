SET search_path = portal;

-- ALTER TYPE portal.personview_element_type ADD VALUE 'personsample1';

-- test data
INSERT INTO portal.personview_element (pve_type, pve_name, pve_entities) VALUES 
  ('personsample1', 'personsample1 element 1 staff/contact', '{staff, contact}'),
  ('personsample1', 'personsample1 element 2 patient/staff', '{patient, staff}'),
  ('personsample1', 'personsample1 element 3 patient', '{patient}'),
  ('personsample1', 'personsample1 element 4 staff', '{staff}'),
  ('personsample1', 'personsample1 element 5 ALL', '{patient, family, staff, contact}');
