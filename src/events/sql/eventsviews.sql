SET search_path = events;

CREATE TABLE events.eventsview (
  evv_id serial PRIMARY KEY,
  evv_name text NOT NULL,
  evv_categories events.event_category[],
  ety_id integer REFERENCES events.event_type
);

CREATE TABLE events.eventsview_topic (
  evv_id integer NOT NULL REFERENCES events.eventsview,
  top_id integer NOT NULL REFERENCES organ.topic,
  CONSTRAINT eventsview_topic_pkey PRIMARY KEY(evv_id, top_id)
);
