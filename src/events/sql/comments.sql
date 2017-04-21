COMMENT ON SCHEMA events IS 'This moduls is about events, events types and events views.

Events
------
Events are classified into categories and can have an optional type.

Events Types
------------
Events types are defined for each category and in relation with one or more topics. Each organization can 
choose to use an event type.';

COMMENT ON TYPE events.event_type_list IS '';

COMMENT ON TYPE events.eventsview_get IS '';

COMMENT ON TYPE events.eventsview_list IS '';

COMMENT ON TABLE events.event IS '';
COMMENT ON COLUMN events.event.eve_id IS '';
COMMENT ON COLUMN events.event.eve_title IS '';
COMMENT ON COLUMN events.event.ety_id IS '';
COMMENT ON COLUMN events.event.eve_duration IS '';
COMMENT ON COLUMN events.event.eve_status IS '';
COMMENT ON COLUMN events.event.eve_start_time IS '';
COMMENT ON COLUMN events.event.eve_end_time IS '';
COMMENT ON COLUMN events.event.eve_mod_date IS '';
COMMENT ON COLUMN events.event.eve_del_date IS '';
COMMENT ON COLUMN events.event.eve_place IS '';
COMMENT ON COLUMN events.event.eve_cost IS '';
COMMENT ON COLUMN events.event.eve_description IS '';
COMMENT ON COLUMN events.event.eve_sumup IS '';

COMMENT ON TABLE events.event_dossier IS '';
COMMENT ON COLUMN events.event_dossier.eve_id IS '';
COMMENT ON COLUMN events.event_dossier.dos_id IS '';

COMMENT ON TABLE events.event_topic IS '';
COMMENT ON COLUMN events.event_topic.eve_id IS '';
COMMENT ON COLUMN events.event_topic.top_id IS '';

COMMENT ON TABLE events.event_type IS 'Events types';
COMMENT ON COLUMN events.event_type.ety_id IS 'Unique identifier';
COMMENT ON COLUMN events.event_type.ety_category IS 'Event category';
COMMENT ON COLUMN events.event_type.ety_name IS 'Type name';
COMMENT ON COLUMN events.event_type.ety_individual_name IS 'Is the event name should be replaced?';

COMMENT ON TABLE events.event_type_organization IS 'Organizations interested in events types';
COMMENT ON COLUMN events.event_type_organization.ety_id IS 'Event type identifier';
COMMENT ON COLUMN events.event_type_organization.org_id IS 'Organizarion identifier';

COMMENT ON TABLE events.event_type_topic IS 'Topics related to events types';
COMMENT ON COLUMN events.event_type_topic.ety_id IS 'Event type identifier';
COMMENT ON COLUMN events.event_type_topic.top_id IS 'Topic identifier';

COMMENT ON TABLE events.eventsview IS '';
COMMENT ON COLUMN events.eventsview.evv_id IS '';
COMMENT ON COLUMN events.eventsview.evv_name IS '';
COMMENT ON COLUMN events.eventsview.evv_categories IS '';
COMMENT ON COLUMN events.eventsview.ety_id IS '';

COMMENT ON TABLE events.eventsview_topic IS '';
COMMENT ON COLUMN events.eventsview_topic.evv_id IS '';
COMMENT ON COLUMN events.eventsview_topic.top_id IS '';
