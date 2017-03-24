COMMENT ON SCHEMA documents IS '';

COMMENT ON TYPE documents.document_type_list IS '';

COMMENT ON TYPE documents.documentsview_get IS '';

COMMENT ON TYPE documents.documentsview_list IS '';

COMMENT ON TABLE documents.document IS '';
COMMENT ON COLUMN documents.document.doc_id IS '';
COMMENT ON COLUMN documents.document.par_id_responsible IS '';
COMMENT ON COLUMN documents.document.dty_id IS '';
COMMENT ON COLUMN documents.document.doc_title IS '';
COMMENT ON COLUMN documents.document.doc_description IS '';
COMMENT ON COLUMN documents.document.doc_status IS '';
COMMENT ON COLUMN documents.document.doc_deadline IS '';
COMMENT ON COLUMN documents.document.doc_execution_date IS '';
COMMENT ON COLUMN documents.document.doc_validity_date IS '';
COMMENT ON COLUMN documents.document.doc_file IS '';

COMMENT ON TABLE documents.document_dossier IS '';
COMMENT ON COLUMN documents.document_dossier.doc_id IS '';
COMMENT ON COLUMN documents.document_dossier.dos_id IS '';

COMMENT ON TABLE documents.document_topic IS '';
COMMENT ON COLUMN documents.document_topic.doc_id IS '';
COMMENT ON COLUMN documents.document_topic.top_id IS '';

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
