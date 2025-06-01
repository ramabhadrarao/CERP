-- Enhanced Complete Database Schema for Educational Management System
-- This includes all necessary tables for a comprehensive educational system

-- =============================================
-- FOUNDATIONAL TABLES
-- =============================================

-- College/Institution table
CREATE TABLE college (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100) DEFAULT 'India',
    pincode VARCHAR(10),
    phone VARCHAR(20),
    email VARCHAR(100),
    website VARCHAR(255),
    established_date DATE,
    logo VARCHAR(255),
    status VARCHAR(20) DEFAULT 'active',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Academic Years table
CREATE TABLE academic_years (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'active',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- LOOKUP/REFERENCE TABLES
-- =============================================

-- Gender table
CREATE TABLE gender (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(20) NOT NULL UNIQUE,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Blood Groups table
CREATE TABLE blood_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(10) NOT NULL UNIQUE,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Nationality table
CREATE TABLE nationality (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    country_code VARCHAR(5),
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Religion table
CREATE TABLE religion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Caste table
CREATE TABLE caste (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(20), -- General, OBC, SC, ST, etc.
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sub Caste table
CREATE TABLE sub_caste (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    caste_id INT,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caste_id) REFERENCES caste(id)
);

-- States table
CREATE TABLE states (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    state_code VARCHAR(5),
    country VARCHAR(100) DEFAULT 'India',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Districts table
CREATE TABLE districts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    state_id INT,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (state_id) REFERENCES states(id)
);

-- =============================================
-- CORE SYSTEM TABLES (Enhanced from existing)
-- =============================================

-- Roles table (Enhanced)
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    permissions JSON,
    is_system_role BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users table (Enhanced)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_uuid VARCHAR(36) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    profile_photo VARCHAR(255),
    email_verified BOOLEAN DEFAULT FALSE,
    email_verified_at TIMESTAMP NULL,
    password_reset_token VARCHAR(255),
    password_reset_expires TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- Sessions table (Enhanced)
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    device_info TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_expires (expires_at)
);

-- =============================================
-- ORGANIZATIONAL STRUCTURE
-- =============================================

-- Departments table (Enhanced)
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    college_id INT NOT NULL,
    hod_id INT NULL,
    logo VARCHAR(255),
    description TEXT,
    email VARCHAR(100),
    phone VARCHAR(20),
    established_date DATE,
    vision TEXT,
    mission TEXT,
    address TEXT,
    status VARCHAR(20) DEFAULT 'active',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES college(id) ON DELETE CASCADE,
    FOREIGN KEY (hod_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Programs table
CREATE TABLE programs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    department_id INT,
    coordinator_id INT NULL,
    duration VARCHAR(50),
    degree_type VARCHAR(50),
    description TEXT,
    eligibility_criteria TEXT,
    total_credits INT,
    total_semesters INT,
    status VARCHAR(20) DEFAULT 'active',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (coordinator_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Branches table
CREATE TABLE branches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    program_id INT,
    coordinator_id INT NULL,
    description TEXT,
    specialization TEXT,
    intake_capacity INT,
    status VARCHAR(20) DEFAULT 'active',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    FOREIGN KEY (coordinator_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Regulations table
CREATE TABLE regulations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    program_id INT,
    branch_id INT,
    effective_from_year YEAR,
    effective_to_year YEAR,
    description TEXT,
    document_url VARCHAR(255),
    status VARCHAR(20) DEFAULT 'active',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

-- Semesters table
CREATE TABLE semesters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    semester_number INT NOT NULL,
    academic_year_id INT NOT NULL,
    regulation_id INT,
    start_date DATE,
    end_date DATE,
    is_current BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'upcoming',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (regulation_id) REFERENCES regulations(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
);

-- Batches table
CREATE TABLE batches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    program_id INT,
    branch_id INT,
    regulation_id INT,
    start_year YEAR NOT NULL,
    end_year YEAR NOT NULL,
    mentor_id INT NULL,
    class_advisor_id INT NULL,
    intake_capacity INT,
    current_strength INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (regulation_id) REFERENCES regulations(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (class_advisor_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- STUDENT MANAGEMENT
-- =============================================

-- Student Types table
CREATE TABLE student_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    fee_category VARCHAR(50),
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Students table (Enhanced)
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    student_uuid VARCHAR(36) UNIQUE NOT NULL,
    admission_no VARCHAR(50) NOT NULL UNIQUE,
    roll_no VARCHAR(50),
    regd_no VARCHAR(50) UNIQUE,
    batch_id INT NOT NULL,
    program_id INT NOT NULL,
    branch_id INT NOT NULL,
    regulation_id INT NOT NULL,
    current_semester_id INT,
    student_type_id INT NOT NULL,
    admission_date DATE,
    gender_id INT,
    dob DATE,
    father_name VARCHAR(255),
    mother_name VARCHAR(255),
    guardian_name VARCHAR(255),
    father_mobile VARCHAR(15),
    mother_mobile VARCHAR(15),
    guardian_mobile VARCHAR(15),
    father_occupation VARCHAR(100),
    mother_occupation VARCHAR(100),
    guardian_occupation VARCHAR(100),
    emergency_contact VARCHAR(15),
    permanent_address TEXT,
    correspondence_address TEXT,
    nationality_id INT NOT NULL,
    religion_id INT NOT NULL,
    caste_id INT,
    sub_caste_id INT,
    blood_group_id INT,
    aadhar_no VARCHAR(20),
    pan_no VARCHAR(20),
    passport_no VARCHAR(20),
    medical_conditions TEXT,
    photo_url VARCHAR(255),
    aadhar_attachment_url VARCHAR(255),
    birth_certificate_url VARCHAR(255),
    tc_attachment_url VARCHAR(255),
    migration_certificate_url VARCHAR(255),
    status VARCHAR(20) DEFAULT 'active',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key references
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (regulation_id) REFERENCES regulations(id) ON DELETE CASCADE,
    FOREIGN KEY (current_semester_id) REFERENCES semesters(id) ON DELETE SET NULL,
    FOREIGN KEY (blood_group_id) REFERENCES blood_groups(id),
    FOREIGN KEY (gender_id) REFERENCES gender(id),
    FOREIGN KEY (student_type_id) REFERENCES student_types(id),
    FOREIGN KEY (nationality_id) REFERENCES nationality(id),
    FOREIGN KEY (religion_id) REFERENCES religion(id),
    FOREIGN KEY (caste_id) REFERENCES caste(id),
    FOREIGN KEY (sub_caste_id) REFERENCES sub_caste(id),
    
    INDEX idx_admission_no (admission_no),
    INDEX idx_roll_no (roll_no),
    INDEX idx_regd_no (regd_no),
    INDEX idx_batch (batch_id),
    INDEX idx_status (status)
);

-- Student Educational Details Table
CREATE TABLE student_educational_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    education_level VARCHAR(50) NOT NULL, -- 10th, 12th, Diploma, Graduation, etc.
    course_name VARCHAR(255) NOT NULL,
    year_of_passing YEAR NOT NULL,
    grade_division VARCHAR(50) NOT NULL,
    percentage_cgpa VARCHAR(50) NOT NULL,
    board_university VARCHAR(255) NOT NULL,
    school_college_name VARCHAR(255),
    district_id INT,
    state_id INT,
    subjects_offered TEXT,
    medium_of_instruction VARCHAR(50),
    certificate_attachment_url VARCHAR(255),
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE SET NULL
);

-- Student Additional Documents Table
CREATE TABLE student_additional_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    document_name VARCHAR(255) NOT NULL,
    document_type VARCHAR(100),
    document_url VARCHAR(255),
    uploaded_date DATE,
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT,
    verified_date DATE,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- FACULTY MANAGEMENT
-- =============================================

-- Faculty table (Enhanced)
CREATE TABLE faculty (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    faculty_uuid VARCHAR(36) UNIQUE NOT NULL,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    faculty_type VARCHAR(50), -- Regular, Contract, Visiting, Guest
    department_id INT,
    designation VARCHAR(100),
    qualification VARCHAR(255),
    specialization TEXT,
    join_date DATE NOT NULL,
    experience_years DECIMAL(5,2),
    salary_grade VARCHAR(20),
    gender_id INT,
    dob DATE,
    blood_group_id INT,
    aadhar_no VARCHAR(20),
    pan_no VARCHAR(20),
    passport_no VARCHAR(20),
    emergency_contact VARCHAR(15),
    permanent_address TEXT,
    correspondence_address TEXT,
    photo_url VARCHAR(255),
    aadhar_attachment_url VARCHAR(255),
    pan_attachment_url VARCHAR(255),
    resume_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    status VARCHAR(20) DEFAULT 'active',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (gender_id) REFERENCES gender(id),
    FOREIGN KEY (blood_group_id) REFERENCES blood_groups(id),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    
    INDEX idx_employee_id (employee_id),
    INDEX idx_department (department_id),
    INDEX idx_status (status)
);

-- Faculty Additional Details Table
CREATE TABLE faculty_additional_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    faculty_id INT NOT NULL,
    father_name VARCHAR(255),
    father_occupation VARCHAR(255),
    mother_name VARCHAR(255),
    mother_occupation VARCHAR(255),
    marital_status VARCHAR(20),
    spouse_name VARCHAR(255),
    spouse_occupation VARCHAR(255),
    spouse_phone VARCHAR(15),
    children_count INT DEFAULT 0,
    nationality_id INT,
    religion_id INT,
    caste_id INT,
    sub_caste_id INT,
    contact_no2 VARCHAR(20),
    alternate_email VARCHAR(100),
    bank_account_no VARCHAR(50),
    bank_name VARCHAR(100),
    bank_branch VARCHAR(100),
    ifsc_code VARCHAR(20),
    pf_no VARCHAR(50),
    esi_no VARCHAR(50),
    uan_no VARCHAR(50),
    scopus_author_id VARCHAR(255),
    orcid_id VARCHAR(255),
    google_scholar_id VARCHAR(255),
    research_gate_id VARCHAR(255),
    linkedin_profile VARCHAR(255),
    aicte_id VARCHAR(255),
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE,
    FOREIGN KEY (nationality_id) REFERENCES nationality(id),
    FOREIGN KEY (religion_id) REFERENCES religion(id),
    FOREIGN KEY (caste_id) REFERENCES caste(id),
    FOREIGN KEY (sub_caste_id) REFERENCES sub_caste(id)
);

-- Faculty Qualifications table
CREATE TABLE faculty_qualifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    faculty_id INT NOT NULL,
    degree VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    institution VARCHAR(200) NOT NULL,
    board_university VARCHAR(200),
    passing_year YEAR,
    percentage_cgpa VARCHAR(20),
    rank_position VARCHAR(50),
    is_highest_qualification BOOLEAN DEFAULT FALSE,
    certificate_attachment_url VARCHAR(255),
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE
);

-- Faculty Work Experience table
CREATE TABLE faculty_work_experience (
    id INT PRIMARY KEY AUTO_INCREMENT,
    faculty_id INT NOT NULL,
    organization VARCHAR(255) NOT NULL,
    designation VARCHAR(255),
    experience_type VARCHAR(20) NOT NULL, -- Teaching, Industry, Research
    from_date DATE,
    to_date DATE,
    is_current BOOLEAN DEFAULT FALSE,
    duration_years DECIMAL(5,2),
    responsibilities TEXT,
    achievements TEXT,
    salary DECIMAL(10,2),
    reason_for_leaving TEXT,
    certificate_attachment_url VARCHAR(255),
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE
);

-- =============================================
-- COURSE MANAGEMENT
-- =============================================

-- Course Types table
CREATE TABLE course_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    credit_multiplier DECIMAL(3,2) DEFAULT 1.0,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Courses table (Enhanced)
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    short_name VARCHAR(100),
    semester_number INT,
    branch_id INT,
    regulation_id INT NOT NULL,
    course_type_id INT NOT NULL,
    theory_credits DECIMAL(3,1) DEFAULT 0,
    lab_credits DECIMAL(3,1) DEFAULT 0,
    total_credits DECIMAL(3,1) NOT NULL,
    theory_hours INT DEFAULT 0,
    lab_hours INT DEFAULT 0,
    tutorial_hours INT DEFAULT 0,
    contact_hours INT DEFAULT 0,
    syllabus TEXT,
    description TEXT,
    objectives TEXT,
    outcomes TEXT,
    prerequisites TEXT,
    corequisites TEXT,
    textbooks TEXT,
    reference_books TEXT,
    is_elective BOOLEAN DEFAULT FALSE,
    is_mandatory BOOLEAN DEFAULT TRUE,
    min_attendance_required DECIMAL(5,2) DEFAULT 75.00,
    status VARCHAR(20) DEFAULT 'active',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (semester_number) REFERENCES semesters(id) ON DELETE SET NULL,
    FOREIGN KEY (regulation_id) REFERENCES regulations(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (course_type_id) REFERENCES course_types(id) ON DELETE CASCADE,
    
    INDEX idx_course_code (course_code),
    INDEX idx_branch_semester (branch_id, semester_number),
    INDEX idx_regulation (regulation_id)
);

-- Course Prerequisites table
CREATE TABLE course_prerequisites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    prerequisite_course_id INT NOT NULL,
    is_mandatory BOOLEAN DEFAULT TRUE,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (prerequisite_course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_prerequisite (course_id, prerequisite_course_id)
);

-- Faculty Course Assignments table
CREATE TABLE faculty_course_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    faculty_id INT NOT NULL,
    course_id INT NOT NULL,
    semester_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    assignment_type VARCHAR(20) NOT NULL, -- Theory, Lab, Tutorial
    is_coordinator BOOLEAN DEFAULT FALSE,
    sections VARCHAR(100), -- A, B, C or specific section codes
    workload_hours DECIMAL(5,2),
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_assignment (faculty_id, course_id, semester_id, assignment_type)
);

-- =============================================
-- ELECTIVES MANAGEMENT
-- =============================================

-- Elective Groups table
CREATE TABLE elective_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    group_code VARCHAR(20) UNIQUE NOT NULL,
    semester_number INT NOT NULL,
    branch_id INT NOT NULL,
    regulation_id INT NOT NULL,
    elective_type VARCHAR(20) NOT NULL, -- Department, Open, Program
    min_credits DECIMAL(3,1) DEFAULT 0,
    max_courses INT DEFAULT 1,
    is_mandatory BOOLEAN DEFAULT TRUE,
    description TEXT,
    selection_start_date DATE,
    selection_end_date DATE,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (regulation_id) REFERENCES regulations(id) ON DELETE CASCADE
);

-- Elective Group Courses table
CREATE TABLE elective_group_courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    elective_group_id INT NOT NULL,
    course_id INT NOT NULL,
    minimum_students INT DEFAULT 10,
    maximum_students INT DEFAULT 60,
    priority_order INT DEFAULT 0,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (elective_group_id) REFERENCES elective_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_course (elective_group_id, course_id)
);

-- Student Electives table
CREATE TABLE student_electives (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    elective_group_id INT NOT NULL,
    course_id INT NOT NULL,
    semester_id INT NOT NULL,
    preference_order INT DEFAULT 1,
    selected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved BOOLEAN DEFAULT FALSE,
    approved_by INT,
    approved_at TIMESTAMP,
    status VARCHAR(20) DEFAULT 'pending',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (elective_group_id) REFERENCES elective_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- STUDENT COURSE REGISTRATIONS
-- =============================================

-- Student Course Registrations Table
CREATE TABLE student_course_registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    semester_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    registration_type VARCHAR(20) DEFAULT 'Regular', -- Regular, Reappear, Improvement
    section VARCHAR(10),
    registration_date DATE DEFAULT (CURRENT_DATE),
    is_elective BOOLEAN DEFAULT FALSE,
    credits_enrolled DECIMAL(3,1),
    fee_paid DECIMAL(10,2),
    late_fee DECIMAL(10,2) DEFAULT 0,
    approved BOOLEAN DEFAULT FALSE,
    approved_by INT,
    approved_at TIMESTAMP,
    status VARCHAR(20) DEFAULT 'registered',
    remarks TEXT,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_registration (student_id, course_id, semester_id, academic_year_id),
    INDEX idx_student_semester (student_id, semester_id),
    INDEX idx_course_semester (course_id, semester_id)
);

-- =============================================
-- ASSESSMENT AND MARKS
-- =============================================

-- Mark Types table
CREATE TABLE mark_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    weightage DECIMAL(5,2) DEFAULT 0, -- Percentage weightage
    is_internal BOOLEAN DEFAULT TRUE,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Assessment Components table
CREATE TABLE assessment_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    semester_id INT NOT NULL,
    component_name VARCHAR(100) NOT NULL,
    mark_type_id INT NOT NULL,
    max_marks DECIMAL(6,2) NOT NULL,
    weightage DECIMAL(5,2) NOT NULL,
    assessment_date DATE,
    is_mandatory BOOLEAN DEFAULT TRUE,
    description TEXT,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (mark_type_id) REFERENCES mark_types(id) ON DELETE CASCADE
);

-- Student Marks table
CREATE TABLE student_marks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_registration_id INT NOT NULL,
    assessment_component_id INT NOT NULL,
    marks_obtained DECIMAL(6,2),
    grade VARCHAR(5),
    is_absent BOOLEAN DEFAULT FALSE,
    is_malpractice BOOLEAN DEFAULT FALSE,
    entered_by INT NOT NULL,
    entered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_by INT,
    verified_at TIMESTAMP,
    is_locked BOOLEAN DEFAULT FALSE,
    remarks TEXT,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_registration_id) REFERENCES student_course_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (assessment_component_id) REFERENCES assessment_components(id) ON DELETE CASCADE,
    FOREIGN KEY (entered_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_student_assessment (student_id, course_registration_id, assessment_component_id)
);

-- =============================================
-- ATTENDANCE MANAGEMENT
-- =============================================

-- Class Schedule table
CREATE TABLE class_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    faculty_id INT NOT NULL,
    semester_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    section VARCHAR(10),
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room_number VARCHAR(50),
    class_type VARCHAR(20) DEFAULT 'Theory', -- Theory, Lab, Tutorial
    is_active BOOLEAN DEFAULT TRUE,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
);

-- Class Sessions table
CREATE TABLE class_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_id INT NOT NULL,
    session_date DATE NOT NULL,
    actual_start_time TIME,
    actual_end_time TIME,
    topic_covered TEXT,
    faculty_id INT NOT NULL,
    substitute_faculty_id INT,
    room_number VARCHAR(50),
    is_cancelled BOOLEAN DEFAULT FALSE,
    cancellation_reason TEXT,
    session_status VARCHAR(20) DEFAULT 'scheduled', -- scheduled, completed, cancelled
    remarks TEXT,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (schedule_id) REFERENCES class_schedule(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE,
    FOREIGN KEY (substitute_faculty_id) REFERENCES faculty(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_session (schedule_id, session_date)
);

-- Student Attendance table
CREATE TABLE student_attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    class_session_id INT NOT NULL,
    attendance_status ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL,
    marked_by INT NOT NULL,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    remarks TEXT,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_session_id) REFERENCES class_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_student_session (student_id, class_session_id),
    INDEX idx_student_attendance (student_id, attendance_status),
    INDEX idx_session_date (class_session_id)
);

-- =============================================
-- RESEARCH AND PUBLICATIONS (Faculty)
-- =============================================

-- Publication Types table
CREATE TABLE publication_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    impact_factor_applicable BOOLEAN DEFAULT FALSE,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Research Publications table
CREATE TABLE research_publications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    faculty_id INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    journal_name VARCHAR(200),
    publication_type_id INT,
    publication_date DATE,
    volume VARCHAR(20),
    issue VARCHAR(20),
    page_numbers VARCHAR(50),
    doi VARCHAR(100),
    isbn VARCHAR(20),
    issn VARCHAR(20),
    impact_factor DECIMAL(5,3),
    citations_count INT DEFAULT 0,
    scopus_indexed BOOLEAN DEFAULT FALSE,
    web_of_science_indexed BOOLEAN DEFAULT FALSE,
    ugc_approved BOOLEAN DEFAULT FALSE,
    peer_reviewed BOOLEAN DEFAULT FALSE,
    abstract TEXT,
    keywords TEXT,
    co_authors TEXT,
    publication_url VARCHAR(500),
    attachment_url VARCHAR(255),
    status VARCHAR(20) DEFAULT 'published',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE,
    FOREIGN KEY (publication_type_id) REFERENCES publication_types(id)
);

-- =============================================
-- SYSTEM AUDIT AND LOGS
-- =============================================

-- Enhanced Audit Log table
CREATE TABLE audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    session_id VARCHAR(255),
    request_method VARCHAR(10),
    request_url TEXT,
    severity VARCHAR(20) DEFAULT 'INFO', -- DEBUG, INFO, WARNING, ERROR, CRITICAL
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at),
    INDEX idx_severity (severity)
);

-- System Notifications table
CREATE TABLE system_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type VARCHAR(50) DEFAULT 'info', -- info, warning, success, error
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    action_url VARCHAR(500),
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created_at (created_at)
);

-- =============================================
-- INSERT DEFAULT DATA
-- =============================================

-- Insert default gender values
INSERT INTO gender (name) VALUES 
('Male'), ('Female'), ('Other');

-- Insert default blood groups
INSERT INTO blood_groups (name) VALUES 
('A+'), ('A-'), ('B+'), ('B-'), ('AB+'), ('AB-'), ('O+'), ('O-');

-- Insert default nationality
INSERT INTO nationality (name, country_code) VALUES 
('Indian', 'IN'), ('American', 'US'), ('British', 'GB'), ('Canadian', 'CA');

-- Insert default religions
INSERT INTO religion (name) VALUES 
('Hindu'), ('Muslim'), ('Christian'), ('Sikh'), ('Buddhist'), ('Jain'), ('Other');

-- Insert default castes
INSERT INTO caste (name, category) VALUES 
('General', 'General'), ('OBC', 'OBC'), ('SC', 'SC'), ('ST', 'ST');

-- Insert enhanced default roles
INSERT INTO roles (name, description, permissions, is_system_role) VALUES 
('super_admin', 'Super Administrator with full access', '["all"]', TRUE),
('admin', 'System Administrator', '["manage_users", "manage_roles", "view_reports", "system_settings"]', TRUE),
('principal', 'Principal/Director', '["manage_faculty", "manage_students", "view_reports", "approve_policies"]', TRUE),
('hod', 'Head of Department', '["manage_department_faculty", "manage_department_students", "view_department_reports", "manage_courses"]', TRUE),
('faculty', 'Faculty Member', '["manage_assigned_courses", "view_students", "grade_students", "mark_attendance", "view_course_materials"]', TRUE),
('student', 'Student', '["view_courses", "view_grades", "view_attendance", "view_profile", "course_registration"]', TRUE),
('parent', 'Parent/Guardian', '["view_ward_progress", "view_ward_attendance", "view_announcements"]', TRUE),
('staff', 'Administrative Staff', '["manage_records", "generate_reports", "data_entry"]', TRUE),
('guest', 'Guest User', '["view_basic_info"]', TRUE);

-- Insert default course types
INSERT INTO course_types (name, description, credit_multiplier) VALUES 
('Theory', 'Theory-based courses', 1.0),
('Practical', 'Laboratory/Practical courses', 0.5),
('Project', 'Project-based courses', 1.0),
('Seminar', 'Seminar-based courses', 0.5),
('Core', 'Core/Mandatory courses', 1.0),
('Elective', 'Elective courses', 1.0),
('Open Elective', 'Open Elective courses across departments', 1.0),
('Audit', 'Audit courses (non-credit)', 0.0);

-- Insert default mark types
INSERT INTO mark_types (name, description, weightage, is_internal) VALUES 
('Internal Assessment', 'Continuous internal assessment', 40.00, TRUE),
('Mid Term', 'Mid-term examination', 20.00, TRUE),
('Assignment', 'Assignment marks', 10.00, TRUE),
('Quiz', 'Quiz marks', 10.00, TRUE),
('External Exam', 'End-semester external examination', 60.00, FALSE),
('Practical', 'Practical/Lab examination', 100.00, FALSE),
('Viva', 'Viva voce examination', 100.00, FALSE),
('Project', 'Project evaluation', 100.00, FALSE);

-- Insert default student types
INSERT INTO student_types (name, description, fee_category) VALUES 
('Regular', 'Regular course students', 'Regular'),
('Lateral Entry', 'Students admitted through lateral entry', 'Regular'),
('Management Quota', 'Management quota students', 'Management'),
('NRI', 'Non-Resident Indian students', 'NRI'),
('International', 'International students', 'International'),
('Sponsored', 'Industry sponsored students', 'Sponsored');

-- Insert default publication types
INSERT INTO publication_types (name, description, impact_factor_applicable) VALUES 
('Journal Article', 'Peer-reviewed journal articles', TRUE),
('Conference Paper', 'Conference proceedings', FALSE),
('Book Chapter', 'Book chapters', FALSE),
('Book', 'Complete books', FALSE),
('Patent', 'Patent publications', FALSE),
('Technical Report', 'Technical reports', FALSE),
('Thesis', 'PhD/Masters thesis', FALSE);

-- Create default super admin user
INSERT INTO users (user_uuid, username, email, password_hash, role_id, first_name, last_name, status) VALUES 
(UUID(), 'admin', 'admin@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Super', 'Admin', 'active');

-- Create sample college
INSERT INTO college (name, code, city, state, country, established_date, status) VALUES 
('Sample Engineering College', 'SEC', 'Hyderabad', 'Telangana', 'India', '1995-01-01', 'active');

-- Create sample academic year
INSERT INTO academic_years (name, start_date, end_date, is_current) VALUES 
('2024-25', '2024-07-01', '2025-06-30', TRUE);

-- =============================================
-- INDEXES FOR PERFORMANCE
-- =============================================

-- Additional indexes for better performance
CREATE INDEX idx_students_composite ON students(batch_id, program_id, branch_id, status);
CREATE INDEX idx_faculty_department ON faculty(department_id, status);
CREATE INDEX idx_courses_regulation ON courses(regulation_id, branch_id, semester_number);
CREATE INDEX idx_registrations_composite ON student_course_registrations(semester_id, academic_year_id, status);
CREATE INDEX idx_attendance_student_date ON student_attendance(student_id, class_session_id);
CREATE INDEX idx_marks_student_course ON student_marks(student_id, course_registration_id);
CREATE INDEX idx_audit_timestamp ON audit_log(created_at, user_id);

-- =============================================
-- VIEWS FOR COMMON QUERIES
-- =============================================

-- Student details view
CREATE VIEW vw_student_details AS
SELECT 
    s.*,
    u.username, u.email, u.phone, u.address as user_address,
    b.name as batch_name,
    p.name as program_name, p.code as program_code,
    br.name as branch_name, br.code as branch_code,
    d.name as department_name, d.code as department_code,
    g.name as gender,
    bg.name as blood_group,
    st.name as student_type,
    r.name as religion,
    c.name as caste
FROM students s
JOIN users u ON s.user_id = u.id
LEFT JOIN batches b ON s.batch_id = b.id
LEFT JOIN programs p ON s.program_id = p.id
LEFT JOIN branches br ON s.branch_id = br.id
LEFT JOIN departments d ON p.department_id = d.id
LEFT JOIN gender g ON s.gender_id = g.id
LEFT JOIN blood_groups bg ON s.blood_group_id = bg.id
LEFT JOIN student_types st ON s.student_type_id = st.id
LEFT JOIN religion r ON s.religion_id = r.id
LEFT JOIN caste c ON s.caste_id = c.id;

-- Faculty details view
CREATE VIEW vw_faculty_details AS
SELECT 
    f.*,
    u.username, u.email, u.phone, u.address as user_address,
    d.name as department_name, d.code as department_code,
    g.name as gender,
    bg.name as blood_group
FROM faculty f
JOIN users u ON f.user_id = u.id
LEFT JOIN departments d ON f.department_id = d.id
LEFT JOIN gender g ON f.gender_id = g.id
LEFT JOIN blood_groups bg ON f.blood_group_id = bg.id;

-- Course enrollment view
-- Corrected Course enrollment view
CREATE VIEW vw_course_enrollments AS
SELECT 
    scr.*,
    s.admission_no, 
    CONCAT(u.first_name, ' ', u.last_name) as student_name,
    u.first_name,
    u.last_name,
    c.course_code, 
    c.name as course_name, 
    c.total_credits,
    sem.name as semester_name,
    ay.name as academic_year
FROM student_course_registrations scr
JOIN students s ON scr.student_id = s.id
JOIN users u ON s.user_id = u.id
JOIN courses c ON scr.course_id = c.id
JOIN semesters sem ON scr.semester_id = sem.id
JOIN academic_years ay ON scr.academic_year_id = ay.id;