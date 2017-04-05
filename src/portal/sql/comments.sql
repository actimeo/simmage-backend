COMMENT ON SCHEMA portal IS 'This module is used to create portals.
Portals are views of person data. Several portals can be created, depending on institution category, employee, etc.

A portal contains:
- a main navigation, which will contain information about all persons,
- and a navigation for the dossiers.

Each navigation is composed of sections containing menu entries.

All functions from this module require the ''structure'' user right.';

COMMENT ON TABLE portal.mainmenu IS 'Menu entries of a main view';
COMMENT ON COLUMN portal.mainmenu.mme_id IS 'Unique identifier';
COMMENT ON COLUMN portal.mainmenu.mse_id IS 'Main section containing this menu entry';
COMMENT ON COLUMN portal.mainmenu.mme_name IS 'Menu name';
COMMENT ON COLUMN portal.mainmenu.mme_order IS 'Menu order in the section';
COMMENT ON COLUMN portal.mainmenu.mme_title IS '';
COMMENT ON COLUMN portal.mainmenu.mme_icon IS '';
COMMENT ON COLUMN portal.mainmenu.mme_content_type IS '';
COMMENT ON COLUMN portal.mainmenu.mme_content_id IS '';

COMMENT ON TABLE portal.mainsection IS 'The main view of a portal consists of menus regrouped in sections. This table defines these sections.';
COMMENT ON COLUMN portal.mainsection.mse_id IS 'Unique identifier';
COMMENT ON COLUMN portal.mainsection.por_id IS 'Portal containing this section';
COMMENT ON COLUMN portal.mainsection.mse_name IS 'Section name';
COMMENT ON COLUMN portal.mainsection.mse_order IS 'Order of the section in the portal';

COMMENT ON TABLE portal.personmenu IS 'Menu entries of a view for an entity type';
COMMENT ON COLUMN portal.personmenu.pme_id IS 'Unique identifier';
COMMENT ON COLUMN portal.personmenu.pse_id IS 'Section containing this menu entry';
COMMENT ON COLUMN portal.personmenu.pme_name IS 'Menu name';
COMMENT ON COLUMN portal.personmenu.pme_order IS 'Menu order in the section';

COMMENT ON TABLE portal.personsection IS 'A view of a portal for an entity type consists of menus regrouped in sections. 
This table defines these sections.';
COMMENT ON COLUMN portal.personsection.pse_id IS 'Unique identifier';
COMMENT ON COLUMN portal.personsection.por_id IS 'Portal containing this section';
COMMENT ON COLUMN portal.personsection.pse_name IS 'Section name';
COMMENT ON COLUMN portal.personsection.pse_order IS 'Order of the section in the portal for the entity type';

COMMENT ON TABLE portal.portal IS 'A portal is a particular view of the data contained in the database. It will be defined 
by several navigation views, one main view (mainsection and mainmenu) and one view per entity type 
(personsection and personmenu).';
COMMENT ON COLUMN portal.portal.por_id IS 'Unique identifier';
COMMENT ON COLUMN portal.portal.por_name IS 'Portal name';
COMMENT ON COLUMN portal.portal.por_description IS '';
