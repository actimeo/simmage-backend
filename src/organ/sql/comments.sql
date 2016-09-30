COMMENT ON SCHEMA organ IS 'This module describes the structure of the organizations receiving the patients.

Topics
------


Organizations
-------------
An organization can be internal or external. It is composed of one or several groups.

Participants
------------
Participants are working for organizations. They are attached to groups.

Dossier
-------
A dossier contains information about patients or family members. They are attached to different groups, 
depending the topics.

Group
-----
A group belongs to an organization. One or several participants are attached to this group, and have access 
to one or several dossiers.

A group can be mandatory: all new dossiers will be added to this group and it won''t be possible 
to remove dossiers from this group.

A set of groups can be exclusive: it won''t be allowed to add a dossier to several groups in a same set 
of exclusive groups.

A mandatory group cannot be in an exclusive set.

Referee
-------
When a dossier is attached to a group, a referee is chosen from the participants attached to this same group.
';

COMMENT ON TYPE organ.dossier_organization_status IS 'Status of a dossier in an organization, returned by dossier_status_list';

COMMENT ON TYPE organ.group_list IS 'Group information, including organization info, returned by group_list';

COMMENT ON TABLE organ.dossier IS 'Information about patient, family';
COMMENT ON COLUMN organ.dossier.dos_id IS 'Unique identifier';
COMMENT ON COLUMN organ.dossier.dos_firstname IS 'Person firstname';
COMMENT ON COLUMN organ.dossier.dos_lastname IS 'Person lastname';
COMMENT ON COLUMN organ.dossier.dos_birthdate IS 'Person birthdate';
COMMENT ON COLUMN organ.dossier.dos_gender IS 'Person gender';
COMMENT ON COLUMN organ.dossier.dos_grouped IS 'Defines if the dossier is about a person or a whole group (family)';
COMMENT ON COLUMN organ.dossier.dos_external IS 'Defines if a person/group is a patient or external';
COMMENT ON COLUMN organ.dossier.dos_groupname IS 'If the dossier is about a whole group, the name of this group';

COMMENT ON TABLE organ.dossier_assignment IS 'Assignation of a patient to a group';
COMMENT ON COLUMN organ.dossier_assignment.doa_id IS 'Unique identifier';
COMMENT ON COLUMN organ.dossier_assignment.dos_id IS 'Dossier identifier';
COMMENT ON COLUMN organ.dossier_assignment.grp_id IS 'Group identifier';
COMMENT ON COLUMN organ.dossier_assignment.doa_visible IS 'Is the assignation visible? Set to false to archive a dossier';

COMMENT ON TABLE organ.dossier_link IS 'Relationship between dossiers';
COMMENT ON COLUMN organ.dossier_link.dol_id IS 'Unique identifier';
COMMENT ON COLUMN organ.dossier_link.dos_id IS 'Dossier identifier';
COMMENT ON COLUMN organ.dossier_link.dos_id_related IS 'Dossier in relation with the first dossier';
COMMENT ON COLUMN organ.dossier_link.dol_relationship IS 'Relationship between dossiers';

COMMENT ON TABLE organ.dossier_status IS 'Presence status of a dossier in an organization. Independant from all assignations to groups';
COMMENT ON COLUMN organ.dossier_status.dst_id IS 'Unique identifier';
COMMENT ON COLUMN organ.dossier_status.dos_id IS 'Dossier identifier';
COMMENT ON COLUMN organ.dossier_status.org_id IS 'Organization identifier';
COMMENT ON COLUMN organ.dossier_status.dst_value IS 'Status value';
COMMENT ON COLUMN organ.dossier_status.dst_start IS 'Date at which this status was set';
COMMENT ON COLUMN organ.dossier_status.dst_end IS 'Date at which this status was overridden';

COMMENT ON TABLE organ.group IS 'Group receiving patients';
COMMENT ON COLUMN organ.group.grp_id IS 'Unique identifier';
COMMENT ON COLUMN organ.group.org_id IS 'Organization to which is attached the group';
COMMENT ON COLUMN organ.group.grp_name IS 'Name of the group';
COMMENT ON COLUMN organ.group.grp_description IS 'Description of the group';
COMMENT ON COLUMN organ.group.grp_mandatory IS 'Default: false. Indicates if new dossiers are inevitably assigned 
to this group.';
COMMENT ON COLUMN organ.group.grp_orientation IS 'Default: organization. Defines if the group is oriented towards organization or participant.';

COMMENT ON TABLE organ.group_exclusive IS 'A set of exclusive groups';
COMMENT ON COLUMN organ.group_exclusive.gre_id IS 'Unique identifier';
COMMENT ON COLUMN organ.group_exclusive.gre_name IS 'Name of the set';

COMMENT ON TABLE organ.group_exclusive_group IS 'The groups contained in a set of exclusive groups.';
COMMENT ON COLUMN organ.group_exclusive_group.geg_id IS 'Unique identifier';
COMMENT ON COLUMN organ.group_exclusive_group.gre_id IS 'Set of unique groups identifier';
COMMENT ON COLUMN organ.group_exclusive_group.grp_id IS 'Group identifier';

COMMENT ON TABLE organ.group_topic IS 'The topics of interest for a group';
COMMENT ON COLUMN organ.group_topic.grt_id IS 'Unique identifier';
COMMENT ON COLUMN organ.group_topic.grp_id IS 'Group identifier';
COMMENT ON COLUMN organ.group_topic.top_id IS 'Topic identifier';

COMMENT ON TABLE organ.organization IS 'An organization receiving patients.';
COMMENT ON COLUMN organ.organization.org_id IS 'Unique identifier';
COMMENT ON COLUMN organ.organization.org_name IS 'Organization name';
COMMENT ON COLUMN organ.organization.org_description IS 'Organization description';
COMMENT ON COLUMN organ.organization.org_internal IS 'Indicates if internal or external organization';

COMMENT ON TABLE organ.participant IS 'Base information about a participant. Uniqueness of participants is done 
in firstname/lastname.';
COMMENT ON COLUMN organ.participant.par_id IS 'Unique identifier';
COMMENT ON COLUMN organ.participant.par_firstname IS 'Participant first name(s)';
COMMENT ON COLUMN organ.participant.par_lastname IS 'Participant last name';
COMMENT ON COLUMN organ.participant.par_email IS 'Participant email';

COMMENT ON TABLE organ.participant_assignment IS 'Assignation of a participant to a group';
COMMENT ON COLUMN organ.participant_assignment.paa_id IS 'Unique identifier';
COMMENT ON COLUMN organ.participant_assignment.grp_id IS 'Group identifier';
COMMENT ON COLUMN organ.participant_assignment.par_id IS 'Participant identifier';

COMMENT ON TABLE organ.referee IS 'Participant is a referee for a patient in a given group';
COMMENT ON COLUMN organ.referee.ref_id IS 'Unique identifier';
COMMENT ON COLUMN organ.referee.doa_id IS 'Dossier assignation to a group';
COMMENT ON COLUMN organ.referee.paa_id IS 'Participant assignation to a group';

COMMENT ON TABLE organ.topic IS 'A general topic';
COMMENT ON COLUMN organ.topic.top_id IS 'Unique identifier';
COMMENT ON COLUMN organ.topic.top_name IS 'Topic name';
COMMENT ON COLUMN organ.topic.top_description IS 'Topic description';
COMMENT ON COLUMN organ.topic.top_icon IS 'Icon representing the topic';
COMMENT ON COLUMN organ.topic.top_color IS 'Color representing the topic';
