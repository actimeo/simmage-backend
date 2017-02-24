FILES="$PGBASE/sql/*.sql $PGBASE/plpgsql/*.sql"

FILES="$FILES $BASE/public/plpgsql/*.sql"

FILES="$FILES $BASE/portal/sql/portal.sql $BASE/portal/plpgsql/*.sql $BASE/portal/sql/comments.sql"

FILES="$FILES $BASE/organ/sql/organ.sql $BASE/organ/plpgsql/*.sql $BASE/organ/sql/comments.sql"

FILES="$FILES $BASE/login/sql/auth.sql $BASE/login/plpgsql/*.sql $BASE/login/sql/comments.sql"

FILES="$FILES $BASE/documents/sql/documenttype.sql $BASE/documents/sql/documentsviews.sql $BASE/documents/sql/document.sql"
FILES="$FILES $BASE/documents/plpgsql/*.sql $BASE/documents/sql/comments.sql"

FILES="$FILES $BASE/lists/sql/listsviews.sql $BASE/lists/plpgsql/*.sql $BASE/lists/sql/comments.sql"

FILES="$FILES $BASE/notes/sql/notesviews.sql $BASE/notes/sql/note.sql"
FILES="$FILES $BASE/notes/plpgsql/*.sql $BASE/notes/sql/comments.sql"

FILES="$FILES $BASE/resources/sql/resourcesview.sql $BASE/resources/sql/resource.sql"
FILES="$FILES $BASE/resources/plpgsql/*.sql $BASE/resources/sql/comments.sql"

FILES="$FILES $BASE/objectives/sql/objectivesviews.sql $BASE/objectives/sql/objective.sql"
FILES="$FILES $BASE/objectives/plpgsql/*.sql $BASE/objectives/sql/comments.sql"

FILES="$FILES $BASE/events/sql/eventtype.sql $BASE/events/sql/eventsviews.sql $BASE/events/sql/event.sql $BASE/events/plpgsql/*.sql $BASE/events/sql/comments.sql"

FILES="$FILES $BASE/pgdoc/sql/schema.sql $BASE/pgdoc/plpgsql/*.sql"
