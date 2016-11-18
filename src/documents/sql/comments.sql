COMMENT ON SCHEMA documents IS '';

COMMENT ON TYPE documents.document_type_list IS '';

COMMENT ON TYPE documents.documentsview_get IS '';

COMMENT ON TYPE documents.documentsview_list IS '';

COMMENT ON TABLE documents.document_type IS '';
COMMENT ON COLUMN documents.document_type.dty_id IS '';
COMMENT ON COLUMN documents.document_type.dty_name IS '';
COMMENT ON COLUMN documents.document_type.dty_individual_name IS '';

COMMENT ON TABLE documents.document_type_organization IS '';
COMMENT ON COLUMN documents.document_type_organization.dty_id IS '';
COMMENT ON COLUMN documents.document_type_organization.org_id IS '';

COMMENT ON TABLE documents.document_type_topic IS '';
COMMENT ON COLUMN documents.document_type_topic.dty_id IS '';
COMMENT ON COLUMN documents.document_type_topic.top_id IS '';

COMMENT ON TABLE documents.documentsview IS '';
COMMENT ON COLUMN documents.documentsview.dov_id IS '';
COMMENT ON COLUMN documents.documentsview.dov_name IS '';
COMMENT ON COLUMN documents.documentsview.dty_id IS '';

COMMENT ON TABLE documents.documentsview_topic IS '';
COMMENT ON COLUMN documents.documentsview_topic.dov_id IS '';
COMMENT ON COLUMN documents.documentsview_topic.top_id IS '';
