COMMENT ON SCHEMA notes IS '';

COMMENT ON TYPE notes.notesview_get IS '';

COMMENT ON TYPE notes.notesview_list IS '';

COMMENT ON TABLE notes.note IS '';
COMMENT ON COLUMN notes.note.not_id IS '';
COMMENT ON COLUMN notes.note.not_text IS '';
COMMENT ON COLUMN notes.note.not_creation_date IS '';
COMMENT ON COLUMN notes.note.not_event_date IS '';
COMMENT ON COLUMN notes.note.not_object IS '';

COMMENT ON TABLE notes.note_dossier IS '';
COMMENT ON COLUMN notes.note_dossier.not_id IS '';
COMMENT ON COLUMN notes.note_dossier.dos_id IS '';

COMMENT ON TABLE notes.note_topic IS '';
COMMENT ON COLUMN notes.note_topic.not_id IS '';
COMMENT ON COLUMN notes.note_topic.top_id IS '';

COMMENT ON TABLE notes.notesview IS '';
COMMENT ON COLUMN notes.notesview.nov_id IS '';
COMMENT ON COLUMN notes.notesview.nov_name IS '';

COMMENT ON TABLE notes.notesview_topic IS '';
COMMENT ON COLUMN notes.notesview_topic.nov_id IS '';
COMMENT ON COLUMN notes.notesview_topic.top_id IS '';
