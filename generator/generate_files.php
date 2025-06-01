<?php
require_once 'EnhancedCRUDGenerator.php';

$studentConfig = [
    'tableName' => 'students',
    'primaryKey' => 'id',
    'columns' => [
        'id', 'user_id', 'student_uuid', 'admission_no', 'roll_no', 
        'regd_no', 'batch_id', 'program_id', 'branch_id', 'regulation_id',
        'current_semester_id', 'student_type_id', 'gender_id', 'dob',
        'father_name', 'mother_name', 'phone', 'address', 'nationality_id',
        'religion_id', 'caste_id', 'blood_group_id', 'photo_url', 'status',
        'created_at', 'updated_at'
    ],
    'foreignKeys' => [
        'batch_id' => ['table' => 'batches', 'key' => 'id', 'field' => 'name'],
        'program_id' => ['table' => 'programs', 'key' => 'id', 'field' => 'name'],
        'branch_id' => ['table' => 'branches', 'key' => 'id', 'field' => 'name'],
        'regulation_id' => ['table' => 'regulations', 'key' => 'id', 'field' => 'name'],
        'current_semester_id' => ['table' => 'semesters', 'key' => 'id', 'field' => 'name'],
        'student_type_id' => ['table' => 'student_types', 'key' => 'id', 'field' => 'name'],
        'gender_id' => ['table' => 'gender', 'key' => 'id', 'field' => 'name'],
        'nationality_id' => ['table' => 'nationality', 'key' => 'id', 'field' => 'name'],
        'religion_id' => ['table' => 'religion', 'key' => 'id', 'field' => 'name'],
        'caste_id' => ['table' => 'caste', 'key' => 'id', 'field' => 'name'],
        'blood_group_id' => ['table' => 'blood_groups', 'key' => 'id', 'field' => 'name']
    ],
    'uniqueKeys' => ['admission_no', 'roll_no', 'regd_no'],
    'fieldTypes' => [
        'dob' => 'date',
        'phone' => 'phone',
        'address' => 'textarea',
        'photo_url' => 'image',
        'status' => 'select'
    ],
    'displayNames' => [
        'admission_no' => 'Admission Number',
        'roll_no' => 'Roll Number',
        'regd_no' => 'Registration Number',
        'father_name' => 'Father Name',
        'mother_name' => 'Mother Name',
        'dob' => 'Date of Birth',
        'batch_id' => 'Batch',
        'program_id' => 'Program',
        'branch_id' => 'Branch',
        'current_semester_id' => 'Current Semester',
        'student_type_id' => 'Student Type',
        'gender_id' => 'Gender',
        'nationality_id' => 'Nationality',
        'religion_id' => 'Religion',
        'caste_id' => 'Caste',
        'blood_group_id' => 'Blood Group'
    ],
    'requiredFields' => [
        'admission_no', 'batch_id', 'program_id', 'branch_id', 
        'regulation_id', 'student_type_id', 'father_name', 'gender_id'
    ],
    'validationRules' => [
        'phone' => ['type' => 'phone', 'min-length' => '10'],
        'admission_no' => ['pattern' => '^[A-Z0-9]+$']
    ],
    'permissions' => [
        'read' => 'read_manage_students',
        'create' => 'create_manage_students',
        'update' => 'update_manage_students',
        'delete' => 'delete_manage_students'
    ],
    'searchableColumns' => ['admission_no', 'roll_no', 'father_name', 'mother_name'],
    'fileUploadColumns' => ['photo_url'],
    'statusColumn' => 'status',
    'auditEnabled' => true
];

$generator = new EnhancedCRUDGenerator($studentConfig);
$generator->generateFiles();

echo "Student management files generated successfully!";
?>