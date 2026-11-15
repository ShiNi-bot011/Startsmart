-- =====================================================================
-- SECD2523: Database - Master Relational Schema DDL
-- Derived directly from StartSmart AI Domain Model Diagram
-- Following Topic 6 Logical DB Design (Methodology to Derive Relations)
-- Constraints syntax strictly adheres to SECD2523 "SQL 1 DDL" Slide Deck
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Entity: STUDENTS (Strong Entity)
-- Logical Model: STUDENTS (studentId, name, email, password, createdAt)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    studentId INT AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_students PRIMARY KEY (studentId),
    CONSTRAINT uq_students_email UNIQUE (email)
);

-- ---------------------------------------------------------------------
-- 2. Entity: SUBJECTS (1:M Relationship: Student manages Subject)
-- Logical Model: SUBJECTS (subjectId, subjectName, subjectCode, sortOrder, createdAt, studentId)
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- 3. Entity: ACADEMIC_TASKS (1:M Relationship: Student creates AcademicTask; belongs to Subject)
-- Logical Model: ACADEMIC_TASKS (taskId, title, description, dueDate, status, progress, createdAt, studentId, subjectId)
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- 4. Entity: TASK_OBSTACLES (1:M Relationship: Subject contains TaskObstacle)
-- Logical Model: TASK_OBSTACLES (obstacleId, obstacleType, description, createdAt, subjectId, taskId)
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- 5. Entity: CHECKLISTS (1:1 Composition: AcademicTask has 1 Checklist)
-- Logical Model: CHECKLISTS (checklistId, createdAt, taskId)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS checklists (
    checklistId INT AUTO_INCREMENT,
    taskId INT NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_checklists PRIMARY KEY (checklistId),
    CONSTRAINT uq_checklists_task UNIQUE (taskId),
    CONSTRAINT fk_checklists_tasks FOREIGN KEY (taskId) 
        REFERENCES academic_tasks(taskId) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- 6. Entity: CHECKLIST_ITEMS (1:M Composition: Checklist contains ChecklistItem)
-- Logical Model: CHECKLIST_ITEMS (itemId, description, isCompleted, sortOrder, checklistId)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS checklist_items (
    itemId INT AUTO_INCREMENT,
    checklistId INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    isCompleted BOOLEAN NOT NULL DEFAULT FALSE,
    sortOrder INT NOT NULL DEFAULT 0,
    CONSTRAINT pk_checklist_items PRIMARY KEY (itemId),
    CONSTRAINT fk_items_checklists FOREIGN KEY (checklistId) 
        REFERENCES checklists(checklistId) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- 7. Entity: FOCUS_SESSIONS (1:M Relationship: AcademicTask has FocusSession)
-- Logical Model: FOCUS_SESSIONS (sessionId, startTime, endTime, duration, createdAt, taskId)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS focus_sessions (
    sessionId INT AUTO_INCREMENT,
    taskId INT NOT NULL,
    startTime DATETIME NOT NULL,
    endTime DATETIME DEFAULT NULL,
    duration INT NOT NULL DEFAULT 0,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_focus_sessions PRIMARY KEY (sessionId),
    CONSTRAINT fk_sessions_tasks FOREIGN KEY (taskId) 
        REFERENCES academic_tasks(taskId) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- 8. Entity: SELF_CHECKINS (1:1 Relationship: FocusSession includes SelfCheckIn)
-- Logical Model: SELF_CHECKINS (checkinId, mood, difficultyLevel, notes, createdAt, sessionId)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS self_checkins (
    checkinId INT AUTO_INCREMENT,
    sessionId INT NOT NULL,
    mood VARCHAR(50) NOT NULL,
    difficultyLevel VARCHAR(50) NOT NULL,
    notes TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_self_checkins PRIMARY KEY (checkinId),
    CONSTRAINT uq_checkins_session UNIQUE (sessionId),
    CONSTRAINT fk_checkins_sessions FOREIGN KEY (sessionId) 
        REFERENCES focus_sessions(sessionId) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- 9. Entity: SESSION_WRAPUPS (1:1 Relationship: FocusSession has SessionWrapUp)
-- Logical Model: SESSION_WRAPUPS (wrapUpId, reflection, completedAt, sessionId)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS session_wrapups (
    wrapUpId INT AUTO_INCREMENT,
    sessionId INT NOT NULL,
    reflection TEXT,
    completedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_session_wrapups PRIMARY KEY (wrapUpId),
    CONSTRAINT uq_wrapups_session UNIQUE (sessionId),
    CONSTRAINT fk_wrapups_sessions FOREIGN KEY (sessionId) 
        REFERENCES focus_sessions(sessionId) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- 10. Entity: AI_CONVERSATIONS (1:M Relationship: AcademicTask has AIConversation)
-- Logical Model: AI_CONVERSATIONS (conversationId, subject, createdAt, taskId)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ai_conversations (
    conversationId INT AUTO_INCREMENT,
    taskId INT NOT NULL,
    subject VARCHAR(150) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_ai_conversations PRIMARY KEY (conversationId),
    CONSTRAINT fk_conversations_tasks FOREIGN KEY (taskId) 
        REFERENCES academic_tasks(taskId) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- 11. Entity: AI_MESSAGES (1:M Relationship: AIConversation contains AIMessage)
-- Logical Model: AI_MESSAGES (messageId, content, sender, sentAt, conversationId)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ai_messages (
    messageId INT AUTO_INCREMENT,
    conversationId INT NOT NULL,
    content TEXT NOT NULL,
    sender VARCHAR(50) NOT NULL,
    sentAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_ai_messages PRIMARY KEY (messageId),
    CONSTRAINT fk_messages_conversations FOREIGN KEY (conversationId) 
        REFERENCES ai_conversations(conversationId) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- 12. Entity: ACHIEVEMENTS (Strong Entity)
-- Logical Model: ACHIEVEMENTS (achievementId, title, description, xpPoints, studentId)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS achievements (
    achievementId INT AUTO_INCREMENT,
    studentId INT DEFAULT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    xpPoints INT NOT NULL DEFAULT 0,
    CONSTRAINT pk_achievements PRIMARY KEY (achievementId),
    CONSTRAINT fk_achievements_students FOREIGN KEY (studentId) 
        REFERENCES students(studentId) ON DELETE SET NULL
);

-- ---------------------------------------------------------------------
-- 13. Entity: STUDENT_ACHIEVEMENTS (M:N Relationship transformed via Topic 6 Slide 18)
-- Logical Model: STUDENT_ACHIEVEMENTS (studentId, achievementId, earnedAt)
-- Composite Primary Key: (studentId, achievementId)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS student_achievements (
    studentId INT NOT NULL,
    achievementId INT NOT NULL,
    earnedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_student_achievements PRIMARY KEY (studentId, achievementId),
    CONSTRAINT fk_sa_students FOREIGN KEY (studentId) 
        REFERENCES students(studentId) ON DELETE CASCADE,
    CONSTRAINT fk_sa_achievements FOREIGN KEY (achievementId) 
        REFERENCES achievements(achievementId) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- 14. Entity: REVISION_SCHEDULES (1:M Relationship: Student has RevisionSchedule)
-- Logical Model: REVISION_SCHEDULES (scheduleId, examDate, createdAt, studentId)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS revision_schedules (
    scheduleId INT AUTO_INCREMENT,
    studentId INT NOT NULL,
    examDate DATE NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_revision_schedules PRIMARY KEY (scheduleId),
    CONSTRAINT fk_schedules_students FOREIGN KEY (studentId) 
        REFERENCES students(studentId) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- 15. Entity: REVISION_ITEMS (1:M Composition: RevisionSchedule contains RevisionItem)
-- Logical Model: REVISION_ITEMS (revisionItemId, topic, scheduledDate, status, scheduleId)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS revision_items (
    revisionItemId INT AUTO_INCREMENT,
    scheduleId INT NOT NULL,
    topic VARCHAR(150) NOT NULL,
    scheduledDate DATE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    CONSTRAINT pk_revision_items PRIMARY KEY (revisionItemId),
    CONSTRAINT fk_items_schedules FOREIGN KEY (scheduleId) 
        REFERENCES revision_schedules(scheduleId) ON DELETE CASCADE
);
