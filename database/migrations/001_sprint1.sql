-- =====================================================================
-- SECD2523: Database - Sprint 1 DDL Script
-- Derived from the Conceptual Domain Model using Topic 6 Methodology
-- Constraints follow SECD2523 "SQL 1 DDL" (Slides 6, 11, 14, 17, 23)
-- =====================================================================

-- 1. Entity: STUDENTS (Strong Entity)
-- SECD2523 Concept: Entity Integrity (PRIMARY KEY) + Required Data (NOT NULL)
CREATE TABLE IF NOT EXISTS students (
    studentId INT AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_students PRIMARY KEY (studentId),
    CONSTRAINT uq_students_email UNIQUE (email)
);

-- 2. Entity: SUBJECTS (Child of STUDENTS in 1:* Relationship)
-- SECD2523 Concept: Referential Integrity (FOREIGN KEY references STUDENTS)
CREATE TABLE IF NOT EXISTS subjects (
    subjectId INT AUTO_INCREMENT,
    studentId INT NOT NULL,
    subjectName VARCHAR(100) NOT NULL,
    subjectCode VARCHAR(20) DEFAULT NULL,
    sortOrder INT NOT NULL DEFAULT 0,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_subjects PRIMARY KEY (subjectId),
    CONSTRAINT fk_subjects_students FOREIGN KEY (studentId) 
        REFERENCES students(studentId) ON DELETE CASCADE,
    CONSTRAINT uq_subjects_student_name UNIQUE (studentId, subjectName)
);

-- 3. Entity: ACADEMIC_TASKS (Child of STUDENTS and SUBJECTS in 1:* Relationships)
-- SECD2523 Concept: Multi-parent Referential Integrity
CREATE TABLE IF NOT EXISTS academic_tasks (
    taskId INT AUTO_INCREMENT,
    studentId INT NOT NULL,
    subjectId INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    dueDate DATE DEFAULT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    progress INT NOT NULL DEFAULT 0,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_tasks PRIMARY KEY (taskId),
    CONSTRAINT fk_tasks_students FOREIGN KEY (studentId) 
        REFERENCES students(studentId) ON DELETE CASCADE,
    CONSTRAINT fk_tasks_subjects FOREIGN KEY (subjectId) 
        REFERENCES subjects(subjectId) ON DELETE CASCADE
);

-- 4. Entity: TASK_OBSTACLES (Weak/Child Entity)
CREATE TABLE IF NOT EXISTS task_obstacles (
    obstacleId INT AUTO_INCREMENT,
    subjectId INT DEFAULT NULL,
    taskId INT DEFAULT NULL,
    obstacleType VARCHAR(100) NOT NULL,
    description TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_obstacles PRIMARY KEY (obstacleId),
    CONSTRAINT fk_obstacles_subjects FOREIGN KEY (subjectId) 
        REFERENCES subjects(subjectId) ON DELETE CASCADE,
    CONSTRAINT fk_obstacles_tasks FOREIGN KEY (taskId) 
        REFERENCES academic_tasks(taskId) ON DELETE SET NULL
);
