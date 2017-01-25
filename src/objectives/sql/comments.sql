COMMENT ON SCHEMA objectives IS '';

COMMENT ON TYPE objectives.objectivesview_get IS '';

COMMENT ON TYPE objectives.objectivesview_list IS '';

COMMENT ON TABLE objectives.objective IS '';
COMMENT ON COLUMN objectives.objective.obj_id IS '';
COMMENT ON COLUMN objectives.objective.obj_name IS '';
COMMENT ON COLUMN objectives.objective.obj_open IS '';
COMMENT ON COLUMN objectives.objective.obj_deadline IS '';

COMMENT ON TABLE objectives.objective_dossier IS '';
COMMENT ON COLUMN objectives.objective_dossier.obj_id IS '';
COMMENT ON COLUMN objectives.objective_dossier.dos_id IS '';

COMMENT ON TABLE objectives.objective_topic IS '';
COMMENT ON COLUMN objectives.objective_topic.obj_id IS '';
COMMENT ON COLUMN objectives.objective_topic.top_id IS '';

COMMENT ON TABLE objectives.objectivesview IS '';
COMMENT ON COLUMN objectives.objectivesview.obv_id IS '';
COMMENT ON COLUMN objectives.objectivesview.obv_name IS '';

COMMENT ON TABLE objectives.objectivesview_topic IS '';
COMMENT ON COLUMN objectives.objectivesview_topic.obv_id IS '';
COMMENT ON COLUMN objectives.objectivesview_topic.top_id IS '';
