COMMENT ON SCHEMA portal IS 'This module is used to create portals.
Portals are views of person data. Several portals can be created, depending on institution category, employee, etc.

A portal contains:
- a main navigation, which will contain information about all persons,
- and a navigation for each entity type (patient, staff, etc), which will contain information about 
  a particular person of this type.

Each navigation is composed of sections containing menu entries.

All functions from this module require the ''structure'' user right.';

COMMENT ON TABLE portal.mainmenu IS 'Menu entries of a main view';

COMMENT ON TABLE portal.mainsection 
IS 'The main view of a portal consists of menus regrouped in sections. This table defines these sections.';

COMMENT ON TABLE portal.mainview IS 'Common information about a page displayed by a main menu.';

COMMENT ON TABLE portal.mainview_element IS 'Elements that can be attached to a main view';

COMMENT ON TABLE portal.personmenu IS 'Menu entries of a view for an entity type';

COMMENT ON TABLE portal.personsection 
IS 'A view of a portal for an entity type consists of menus regrouped in sections. 
This table defines these sections.';

COMMENT ON TABLE portal.personview IS 'Common information about a page displayed by an entity menu';

COMMENT ON TABLE portal.personview_element IS 'Elements that can be attached to a person view';

COMMENT ON TABLE portal.portal 
IS 'A portal is a particular view of the data contained in the database. It will be defined 
by several navigation views, one main view (mainsection and mainmenu) and one view per entity type 
(personsection and personmenu).';

COMMENT ON COLUMN portal.mainmenu.mme_id IS 'Unique identifier';

COMMENT ON COLUMN portal.mainmenu.mse_id IS 'Main section containing this menu entry';

COMMENT ON COLUMN portal.mainmenu.mme_name IS 'Menu name';

COMMENT ON COLUMN portal.mainmenu.mme_order IS 'Menu order in the section';

COMMENT ON COLUMN portal.mainsection.mse_id IS 'Unique identifier';

COMMENT ON COLUMN portal.mainsection.por_id IS 'Portal containing this section';

COMMENT ON COLUMN portal.mainsection.mse_name IS 'Section name';

COMMENT ON COLUMN portal.mainsection.mse_order IS 'Order of the section in the portal';

COMMENT ON COLUMN portal.mainview.mme_id 
IS 'Main menu to which the page is attached. At most one page can be attached to a menu';

COMMENT ON COLUMN portal.mainview.mvi_title IS 'Page title';

COMMENT ON COLUMN portal.mainview.mvi_icon IS 'Icon associated with the page';

COMMENT ON COLUMN portal.mainview.mve_id IS 'Main view element attached to this view';

COMMENT ON COLUMN portal.mainview.pme_id_associated IS 'Person view associated with this main view';

COMMENT ON COLUMN portal.mainview_element.mve_id IS 'Unique identifier';

COMMENT ON COLUMN portal.mainview_element.mve_type IS 'Type of main view element';

COMMENT ON COLUMN portal.mainview_element.mve_name IS 'Name of main view element';

COMMENT ON COLUMN portal.personmenu.pme_id IS 'Unique identifier';

COMMENT ON COLUMN portal.personmenu.pse_id IS 'Section containing this menu entry';

COMMENT ON COLUMN portal.personmenu.pme_name IS 'Menu name';

COMMENT ON COLUMN portal.personmenu.pme_order IS 'Menu order in the section';

COMMENT ON COLUMN portal.personsection.pse_id IS 'Unique identifier';

COMMENT ON COLUMN portal.personsection.por_id IS 'Portal containing this section';

COMMENT ON COLUMN portal.personsection.pse_entity IS 'Entity type for this view';

COMMENT ON COLUMN portal.personsection.pse_name IS 'Section name';

COMMENT ON COLUMN portal.personsection.pse_order IS 'Order of the section in the portal for the entity type';

COMMENT ON COLUMN portal.personview.pme_id 
IS 'Person menu to which the page is attached. At most one page can be attached to a menu';

COMMENT ON COLUMN portal.personview.pvi_title IS 'Page title';

COMMENT ON COLUMN portal.personview.pvi_icon IS 'Icon associated with the page';

COMMENT ON COLUMN portal.personview.pve_id IS 'Person view element attached to this view';

COMMENT ON COLUMN portal.personview_element.pve_id IS 'Unique identifier';

COMMENT ON COLUMN portal.personview_element.pve_type IS 'Type of person view element';

COMMENT ON COLUMN portal.personview_element.pve_name IS 'Name of person view element';

COMMENT ON COLUMN portal.portal.por_id IS 'Unique identifier';

COMMENT ON COLUMN portal.portal.por_name IS 'Portal name';

COMMENT ON TYPE portal.entity IS 'The different types of persons';

COMMENT ON TYPE portal.mainview_element_type 
IS 'Different types of main views. Modules can add values to this type';

COMMENT ON TYPE portal.param 
IS 'The portal parameters. The name is composed of two elements, separated by a dot (.): 
the name and the type (from portal.param_type). Modules can add values to this type';

COMMENT ON TYPE portal.param_type 
IS 'The different types of portal parameters. Modules *cannot* add values to this type';

COMMENT ON TYPE portal.personview_element_type 
IS 'Different types of person views. Modules can add values to this type';

