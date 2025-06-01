<?php
/**
 * seeder.php - Database Seeder for Swarnandhra
 * 
 * This file populates the database with default data for testing and development.
 * Run this after setting up the database schema.
 * 
 * Usage: php seeder.php
 */

require_once 'config/database.php';

echo "🌱 Starting Database Seeder...\n";
echo "================================\n\n";

try {
    $pdo = get_database_connection();
    $pdo->beginTransaction();

    // Clear existing data (in correct order to avoid foreign key constraints)
    echo "🧹 Clearing existing data...\n";
    $tables = [
        'audit_log',
        'user_sessions', 
        'courses',
        'students',
        'faculty',
        'users',
        'departments',
        'roles'
    ];
    
    foreach ($tables as $table) {
        $pdo->exec("DELETE FROM {$table}");
        echo "   ✓ Cleared {$table}\n";
    }
    
    // Reset auto-increment
    foreach ($tables as $table) {
        $pdo->exec("ALTER TABLE {$table} AUTO_INCREMENT = 1");
    }
    
    echo "\n";

    // 1. Seed Roles
    echo "👥 Seeding Roles...\n";
    $roles_data = [
        [
            'name' => 'super_admin',
            'description' => 'Super Administrator with full system access',
            'permissions' => json_encode(['all'])
        ],
        [
            'name' => 'head_of_department',
            'description' => 'Head of Department with departmental management access',
            'permissions' => json_encode([
                'manage_faculty', 'manage_students', 'view_reports', 
                'manage_courses', 'view_faculty', 'view_students', 'view_courses'
            ])
        ],
        [
            'name' => 'faculty',
            'description' => 'Faculty Member with teaching and student management access',
            'permissions' => json_encode([
                'manage_students', 'view_courses', 'grade_students', 
                'view_students', 'view_faculty'
            ])
        ],
        [
            'name' => 'parent',
            'description' => 'Parent/Guardian with child progress viewing access',
            'permissions' => json_encode([
                'view_student_progress', 'view_announcements'
            ])
        ],
        [
            'name' => 'student',
            'description' => 'Student with course and grade viewing access',
            'permissions' => json_encode([
                'view_courses', 'view_grades', 'view_profile'
            ])
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO roles (name, description, permissions) VALUES (?, ?, ?)");
    foreach ($roles_data as $role) {
        $stmt->execute([$role['name'], $role['description'], $role['permissions']]);
        echo "   ✓ Created role: {$role['name']}\n";
    }

    // 2. Seed Departments
    echo "\n🏢 Seeding Departments...\n";
    $departments_data = [
        ['name' => 'Computer Science', 'code' => 'CS'],
        ['name' => 'Information Technology', 'code' => 'IT'],
        ['name' => 'Electronics Engineering', 'code' => 'ECE'],
        ['name' => 'Mechanical Engineering', 'code' => 'ME'],
        ['name' => 'Civil Engineering', 'code' => 'CE'],
        ['name' => 'Business Administration', 'code' => 'MBA'],
        ['name' => 'Mathematics', 'code' => 'MATH'],
        ['name' => 'Physics', 'code' => 'PHY'],
        ['name' => 'Chemistry', 'code' => 'CHEM'],
        ['name' => 'English Literature', 'code' => 'ENG']
    ];

    $stmt = $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
    foreach ($departments_data as $dept) {
        $stmt->execute([$dept['name'], $dept['code']]);
        echo "   ✓ Created department: {$dept['name']} ({$dept['code']})\n";
    }

    // 3. Seed Users (Admin, HODs, Faculty, Students, Parents)
    echo "\n👤 Seeding Users...\n";

    // Super Admin
    $admin_data = [
        'username' => 'admin',
        'email' => 'admin@school.edu',
        'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        'role_id' => 1,
        'first_name' => 'Super',
        'last_name' => 'Administrator',
        'phone' => '+91-9876543210',
        'address' => 'School Administrative Office'
    ];
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role_id, first_name, last_name, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(array_values($admin_data));
    echo "   ✓ Created Super Admin: admin / admin123\n";

    // Heads of Department
    $hod_data = [
        ['john_hod', 'john.hod@school.edu', 'John', 'Smith', '+91-9876543211', 1], // CS
        ['sarah_hod', 'sarah.hod@school.edu', 'Sarah', 'Johnson', '+91-9876543212', 2], // IT
        ['mike_hod', 'mike.hod@school.edu', 'Mike', 'Wilson', '+91-9876543213', 3], // ECE
    ];

    foreach ($hod_data as $index => $hod) {
        $stmt->execute([
            $hod[0], $hod[1], password_hash('hod123', PASSWORD_DEFAULT), 2,
            $hod[2], $hod[3], $hod[4], 'Department Office'
        ]);
        $hod_user_id = $pdo->lastInsertId();
        
        // Update department head
        $pdo->prepare("UPDATE departments SET head_id = ? WHERE id = ?")->execute([$hod_user_id, $hod[5]]);
        echo "   ✓ Created HOD: {$hod[0]} / hod123 (Head of {$departments_data[$hod[5]-1]['name']})\n";
    }

    // Faculty Members
    $faculty_data = [
        ['dr_anderson', 'anderson@school.edu', 'Robert', 'Anderson', '+91-9876543220', 1, 'F001', 'Professor', 'PhD in Computer Science'],
        ['prof_davis', 'davis@school.edu', 'Emily', 'Davis', '+91-9876543221', 1, 'F002', 'Associate Professor', 'MSc in Software Engineering'],
        ['dr_brown', 'brown@school.edu', 'Michael', 'Brown', '+91-9876543222', 2, 'F003', 'Professor', 'PhD in Information Systems'],
        ['prof_wilson', 'wilson@school.edu', 'Lisa', 'Wilson', '+91-9876543223', 2, 'F004', 'Assistant Professor', 'MSc in Data Science'],
        ['dr_taylor', 'taylor@school.edu', 'David', 'Taylor', '+91-9876543224', 3, 'F005', 'Professor', 'PhD in Electronics'],
        ['prof_miller', 'miller@school.edu', 'Jennifer', 'Miller', '+91-9876543225', 4, 'F006', 'Associate Professor', 'MSc in Mechanical Engineering'],
        ['dr_garcia', 'garcia@school.edu', 'Carlos', 'Garcia', '+91-9876543226', 5, 'F007', 'Professor', 'PhD in Civil Engineering'],
        ['prof_martinez', 'martinez@school.edu', 'Maria', 'Martinez', '+91-9876543227', 6, 'F008', 'Assistant Professor', 'MBA, MSc in Management'],
    ];

    foreach ($faculty_data as $faculty) {
        // Create user
        $stmt->execute([
            $faculty[0], $faculty[1], password_hash('faculty123', PASSWORD_DEFAULT), 3,
            $faculty[2], $faculty[3], $faculty[4], 'Faculty Housing'
        ]);
        $faculty_user_id = $pdo->lastInsertId();
        
        // Create faculty record
        $faculty_stmt = $pdo->prepare("INSERT INTO faculty (user_id, employee_id, department_id, designation, qualification) VALUES (?, ?, ?, ?, ?)");
        $faculty_stmt->execute([$faculty_user_id, $faculty[6], $faculty[5], $faculty[7], $faculty[8]]);
        
        echo "   ✓ Created Faculty: {$faculty[0]} / faculty123 ({$faculty[2]} {$faculty[3]})\n";
    }

    // Parents
    $parent_data = [
        ['parent1', 'parent1@email.com', 'Rajesh', 'Kumar', '+91-9876543230'],
        ['parent2', 'parent2@email.com', 'Priya', 'Sharma', '+91-9876543231'],
        ['parent3', 'parent3@email.com', 'Amit', 'Patel', '+91-9876543232'],
        ['parent4', 'parent4@email.com', 'Sunita', 'Singh', '+91-9876543233'],
        ['parent5', 'parent5@email.com', 'Vikram', 'Gupta', '+91-9876543234'],
    ];

    $parent_ids = [];
    foreach ($parent_data as $parent) {
        $stmt->execute([
            $parent[0], $parent[1], password_hash('parent123', PASSWORD_DEFAULT), 4,
            $parent[2], $parent[3], $parent[4], 'City Residence'
        ]);
        $parent_ids[] = $pdo->lastInsertId();
        echo "   ✓ Created Parent: {$parent[0]} / parent123 ({$parent[2]} {$parent[3]})\n";
    }

    // Students
    echo "\n🎓 Seeding Students...\n";
    $student_data = [
        ['arjun_s', 'arjun@student.edu', 'Arjun', 'Kumar', '+91-9876543240', 'STU001', 1, 3, 2023, 0],
        ['priya_s', 'priya@student.edu', 'Priya', 'Sharma', '+91-9876543241', 'STU002', 1, 2, 2024, 1],
        ['rohit_s', 'rohit@student.edu', 'Rohit', 'Patel', '+91-9876543242', 'STU003', 2, 4, 2022, 2],
        ['sneha_s', 'sneha@student.edu', 'Sneha', 'Singh', '+91-9876543243', 'STU004', 2, 1, 2025, 3],
        ['amit_s', 'amit@student.edu', 'Amit', 'Gupta', '+91-9876543244', 'STU005', 3, 2, 2024, 4],
        ['kavya_s', 'kavya@student.edu', 'Kavya', 'Reddy', '+91-9876543245', 'STU006', 1, 1, 2025, 0],
        ['ravi_s', 'ravi@student.edu', 'Ravi', 'Nair', '+91-9876543246', 'STU007', 2, 3, 2023, 1],
        ['anita_s', 'anita@student.edu', 'Anita', 'Joshi', '+91-9876543247', 'STU008', 3, 4, 2022, 2],
        ['suresh_s', 'suresh@student.edu', 'Suresh', 'Yadav', '+91-9876543248', 'STU009', 1, 2, 2024, 3],
        ['meera_s', 'meera@student.edu', 'Meera', 'Agarwal', '+91-9876543249', 'STU010', 2, 1, 2025, 4],
    ];

    foreach ($student_data as $student) {
        // Create user
        $stmt->execute([
            $student[0], $student[1], password_hash('student123', PASSWORD_DEFAULT), 5,
            $student[2], $student[3], $student[4], 'Student Hostel'
        ]);
        $student_user_id = $pdo->lastInsertId();
        
        // Create student record
        $student_stmt = $pdo->prepare("INSERT INTO students (user_id, student_id, department_id, semester, year_of_admission, parent_id) VALUES (?, ?, ?, ?, ?, ?)");
        $parent_id = $student[9] > 0 ? $parent_ids[$student[9] - 1] : null;
        $student_stmt->execute([$student_user_id, $student[5], $student[6], $student[7], $student[8], $parent_id]);
        
        $dept_name = $departments_data[$student[6] - 1]['name'];
        echo "   ✓ Created Student: {$student[0]} / student123 ({$student[2]} {$student[3]}, {$dept_name})\n";
    }

    // 4. Seed Courses
    echo "\n📚 Seeding Courses...\n";
    $courses_data = [
        // Computer Science
        ['Programming Fundamentals', 'CS101', 4, 1, 1, 1],
        ['Data Structures', 'CS201', 4, 1, 2, 1],
        ['Database Systems', 'CS301', 3, 1, 3, 1],
        ['Software Engineering', 'CS401', 4, 1, 4, 2],
        
        // Information Technology  
        ['Web Development', 'IT101', 3, 2, 1, 3],
        ['Network Security', 'IT201', 4, 2, 2, 3],
        ['System Administration', 'IT301', 3, 2, 3, 4],
        ['Mobile App Development', 'IT401', 4, 2, 4, 4],
        
        // Electronics
        ['Digital Electronics', 'ECE101', 4, 3, 1, 5],
        ['Microprocessors', 'ECE201', 4, 3, 2, 5],
        ['VLSI Design', 'ECE301', 3, 3, 3, 5],
        
        // Mechanical
        ['Thermodynamics', 'ME101', 4, 4, 1, 6],
        ['Fluid Mechanics', 'ME201', 4, 4, 2, 6],
        
        // Civil
        ['Structural Analysis', 'CE101', 4, 5, 1, 7],
        ['Construction Management', 'CE201', 3, 5, 2, 7],
    ];

    $course_stmt = $pdo->prepare("INSERT INTO courses (name, code, credits, department_id, semester, faculty_id) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($courses_data as $course) {
        $course_stmt->execute($course);
        echo "   ✓ Created Course: {$course[1]} - {$course[0]}\n";
    }

    // 5. Add some audit log entries
    echo "\n📋 Seeding Audit Logs...\n";
    $audit_data = [
        [1, 'login', null, null, null, null],
        [2, 'create', 'students', 1, null, '{"action": "created_student"}'],
        [1, 'update', 'users', 2, '{"status": "inactive"}', '{"status": "active"}'],
        [3, 'login', null, null, null, null],
        [1, 'create', 'courses', 1, null, '{"action": "created_course"}'],
    ];

    $audit_stmt = $pdo->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, '127.0.0.1', 'Seeder Script')");
    foreach ($audit_data as $audit) {
        $audit_stmt->execute($audit);
    }
    echo "   ✓ Added sample audit log entries\n";

    $pdo->commit();
    
    echo "\n✅ Database seeding completed successfully!\n";
    echo "================================\n";
    echo "🔑 Default Login Credentials:\n";
    echo "--------------------------------\n";
    echo "Super Admin:     admin / admin123\n";
    echo "HOD:            john_hod / hod123\n";
    echo "Faculty:        dr_anderson / faculty123\n";
    echo "Student:        arjun_s / student123\n";
    echo "Parent:         parent1 / parent123\n";
    echo "--------------------------------\n";
    echo "📊 Data Summary:\n";
    echo "• 5 Roles created\n";
    echo "• 10 Departments created\n";
    echo "• " . (1 + 3 + 8 + 5 + 10) . " Users created\n";
    echo "• 8 Faculty members created\n";
    echo "• 10 Students created\n";
    echo "• 15 Courses created\n";
    echo "• 5 Sample audit logs added\n";
    echo "\n🚀 You can now login to the system!\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Error during seeding: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>