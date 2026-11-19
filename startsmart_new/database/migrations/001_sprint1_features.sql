-- Run once on databases created from the original schema.sql.
ALTER TABLE academic_tasks
    ADD COLUMN taskType VARCHAR(30) NOT NULL DEFAULT 'General' AFTER description;

ALTER TABLE task_obstacles
    ADD COLUMN answer1 VARCHAR(255) DEFAULT NULL AFTER description,
    ADD COLUMN answer2 VARCHAR(255) DEFAULT NULL AFTER answer1,
    ADD COLUMN answer3 VARCHAR(255) DEFAULT NULL AFTER answer2,
    ADD COLUMN isConfirmed BOOLEAN NOT NULL DEFAULT FALSE AFTER answer3;

CREATE TABLE IF NOT EXISTS task_hint_requests (
    hintRequestId INT AUTO_INCREMENT,
    studentId INT NOT NULL,
    taskId INT NOT NULL,
    requestContext TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_task_hint_requests PRIMARY KEY (hintRequestId),
    CONSTRAINT fk_hint_requests_students FOREIGN KEY (studentId)
        REFERENCES students(studentId) ON DELETE CASCADE,
    CONSTRAINT fk_hint_requests_tasks FOREIGN KEY (taskId)
        REFERENCES academic_tasks(taskId) ON DELETE CASCADE
);
