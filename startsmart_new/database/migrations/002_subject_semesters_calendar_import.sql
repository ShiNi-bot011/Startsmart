-- Run once after 001_sprint1_features.sql on an existing database.
ALTER TABLE subjects
    ADD COLUMN semesterNumber TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER subjectCode,
    DROP INDEX uq_subjects_student_name,
    ADD CONSTRAINT uq_subjects_student_semester_name
        UNIQUE (studentId, semesterNumber, subjectName);

ALTER TABLE academic_tasks
    ADD COLUMN externalEventUid VARCHAR(255) DEFAULT NULL AFTER dueDate,
    ADD UNIQUE KEY uq_tasks_student_external_event (studentId, externalEventUid);
