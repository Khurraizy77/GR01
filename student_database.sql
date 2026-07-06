CREATE TABLE IF NOT EXISTS student_accounts (
    matric_no VARCHAR(15) NOT NULL PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL DEFAULT '123456',
    account_status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_accounts_participant
        FOREIGN KEY (matric_no)
        REFERENCES participants(matric_no)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

ALTER TABLE student_accounts
    ADD COLUMN IF NOT EXISTS email VARCHAR(100) NULL AFTER matric_no;

INSERT INTO student_accounts (matric_no, email, password)
SELECT matric_no, CONCAT(LOWER(matric_no), '@student.ccms.local'), password
FROM participants
WHERE matric_no NOT IN (SELECT matric_no FROM student_accounts);

UPDATE student_accounts
SET email = CONCAT(LOWER(matric_no), '@student.ccms.local')
WHERE email IS NULL OR email = '';

ALTER TABLE student_accounts
    MODIFY email VARCHAR(100) NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS idx_student_accounts_email
    ON student_accounts(email);
