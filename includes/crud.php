<?php
// includes/crud.php - Generic CRUD functions with prepared statements

require_once 'config/database.php';

// Generic create function
function create_record($table, $data, $user_id = null) {
    $pdo = get_database_connection();
    
    // Prepare columns and placeholders
    $columns = array_keys($data);
    $placeholders = ':' . implode(', :', $columns);
    
    $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})";
    
    try {
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($data);
        
        if ($result) {
            $record_id = $pdo->lastInsertId();
            
            // Log the action
            if ($user_id) {
                log_audit($user_id, 'create', $table, $record_id, null, $data);
            }
            
            return ['success' => true, 'id' => $record_id];
        } else {
            return ['success' => false, 'message' => 'Failed to create record'];
        }
    } catch (PDOException $e) {
        error_log("Create record error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

// Generic read function with pagination and search
function read_records($table, $options = []) {
    $pdo = get_database_connection();
    
    // Default options
    $defaults = [
        'select' => '*',
        'where' => [],
        'joins' => [],
        'order_by' => 'id ASC',
        'limit' => null,
        'offset' => null,
        'search' => null,
        'search_columns' => []
    ];
    
    $options = array_merge($defaults, $options);
    
    // Build the query
    $sql = "SELECT {$options['select']} FROM {$table}";
    
    // Add joins
    if (!empty($options['joins'])) {
        foreach ($options['joins'] as $join) {
            $sql .= " {$join}";
        }
    }
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    
    // Add search functionality
    if ($options['search'] && !empty($options['search_columns'])) {
        $search_conditions = [];
        foreach ($options['search_columns'] as $column) {
            $search_conditions[] = "{$column} LIKE :search";
        }
        if (!empty($search_conditions)) {
            $where_conditions[] = '(' . implode(' OR ', $search_conditions) . ')';
            $params['search'] = '%' . $options['search'] . '%';
        }
    }
    
    // Add custom WHERE conditions
    if (!empty($options['where'])) {
        foreach ($options['where'] as $condition => $value) {
            if (is_numeric($condition)) {
                // Raw condition
                $where_conditions[] = $value;
            } else {
                // Parameterized condition
                $where_conditions[] = "{$condition} = :{$condition}";
                $params[$condition] = $value;
            }
        }
    }
    
    if (!empty($where_conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Add ORDER BY
    $sql .= " ORDER BY {$options['order_by']}";
    
    // Add LIMIT and OFFSET
    if ($options['limit']) {
        $sql .= " LIMIT {$options['limit']}";
        if ($options['offset']) {
            $sql .= " OFFSET {$options['offset']}";
        }
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Read records error: " . $e->getMessage());
        return [];
    }
}

// Get single record
function get_record($table, $id, $id_column = 'id') {
    $pdo = get_database_connection();
    
    $sql = "SELECT * FROM {$table} WHERE {$id_column} = :id LIMIT 1";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get record error: " . $e->getMessage());
        return false;
    }
}

// Generic update function
function update_record($table, $id, $data, $user_id = null, $id_column = 'id') {
    $pdo = get_database_connection();
    
    // Get old values for audit
    $old_record = get_record($table, $id, $id_column);
    
    // Prepare SET clause
    $set_clauses = [];
    foreach (array_keys($data) as $column) {
        $set_clauses[] = "{$column} = :{$column}";
    }
    $data[$id_column] = $id;
    
    $sql = "UPDATE {$table} SET " . implode(', ', $set_clauses) . " WHERE {$id_column} = :{$id_column}";
    
    try {
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($data);
        
        if ($result && $stmt->rowCount() > 0) {
            // Log the action
            if ($user_id && $old_record) {
                log_audit($user_id, 'update', $table, $id, $old_record, array_diff_assoc($data, [$id_column => $id]));
            }
            
            return ['success' => true, 'affected_rows' => $stmt->rowCount()];
        } else {
            return ['success' => false, 'message' => 'No records updated'];
        }
    } catch (PDOException $e) {
        error_log("Update record error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

// Generic delete function
function delete_record($table, $id, $user_id = null, $id_column = 'id') {
    $pdo = get_database_connection();
    
    // Get record for audit before deletion
    $old_record = get_record($table, $id, $id_column);
    
    $sql = "DELETE FROM {$table} WHERE {$id_column} = :id";
    
    try {
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute(['id' => $id]);
        
        if ($result && $stmt->rowCount() > 0) {
            // Log the action
            if ($user_id && $old_record) {
                log_audit($user_id, 'delete', $table, $id, $old_record, null);
            }
            
            return ['success' => true, 'affected_rows' => $stmt->rowCount()];
        } else {
            return ['success' => false, 'message' => 'No records deleted'];
        }
    } catch (PDOException $e) {
        error_log("Delete record error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

// Count records
function count_records($table, $options = []) {
    $pdo = get_database_connection();
    
    $sql = "SELECT COUNT(*) as total FROM {$table}";
    
    // Add joins if specified
    if (!empty($options['joins'])) {
        foreach ($options['joins'] as $join) {
            $sql .= " {$join}";
        }
    }
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    
    if (!empty($options['where'])) {
        foreach ($options['where'] as $condition => $value) {
            if (is_numeric($condition)) {
                $where_conditions[] = $value;
            } else {
                $where_conditions[] = "{$condition} = :{$condition}";
                $params[$condition] = $value;
            }
        }
    }
    
    if (!empty($where_conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $where_conditions);
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    } catch (PDOException $e) {
        error_log("Count records error: " . $e->getMessage());
        return 0;
    }
}

// Student-specific functions
function create_student($data, $user_id) {
    $pdo = get_database_connection();
    
    try {
        $pdo->beginTransaction();
        
        // Create user record first
        $user_data = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role_id' => 5, // Student role
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null
        ];
        
        $user_result = create_record('users', $user_data, $user_id);
        if (!$user_result['success']) {
            throw new Exception($user_result['message']);
        }
        
        // Create student record
        $student_data = [
            'user_id' => $user_result['id'],
            'student_id' => $data['student_id'],
            'department_id' => $data['department_id'],
            'semester' => $data['semester'],
            'year_of_admission' => $data['year_of_admission'],
            'parent_id' => $data['parent_id'] ?? null
        ];
        
        $student_result = create_record('students', $student_data, $user_id);
        if (!$student_result['success']) {
            throw new Exception($student_result['message']);
        }
        
        $pdo->commit();
        return ['success' => true, 'user_id' => $user_result['id'], 'student_id' => $student_result['id']];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Create student error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Faculty-specific functions
function create_faculty($data, $user_id) {
    $pdo = get_database_connection();
    
    try {
        $pdo->beginTransaction();
        
        // Create user record first
        $user_data = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role_id' => 3, // Faculty role
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null
        ];
        
        $user_result = create_record('users', $user_data, $user_id);
        if (!$user_result['success']) {
            throw new Exception($user_result['message']);
        }
        
        // Create faculty record
        $faculty_data = [
            'user_id' => $user_result['id'],
            'employee_id' => $data['employee_id'],
            'department_id' => $data['department_id'],
            'designation' => $data['designation'],
            'qualification' => $data['qualification'] ?? null
        ];
        
        $faculty_result = create_record('faculty', $faculty_data, $user_id);
        if (!$faculty_result['success']) {
            throw new Exception($faculty_result['message']);
        }
        
        $pdo->commit();
        return ['success' => true, 'user_id' => $user_result['id'], 'faculty_id' => $faculty_result['id']];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Create faculty error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Get students with pagination and search
function get_students($options = []) {
    $defaults = [
        'select' => 's.*, u.first_name, u.last_name, u.email, u.phone, u.status, d.name as department_name',
        'joins' => [
            'JOIN users u ON s.user_id = u.id',
            'LEFT JOIN departments d ON s.department_id = d.id'
        ],
        'search_columns' => ['u.first_name', 'u.last_name', 'u.email', 's.student_id', 'd.name'],
        'order_by' => 'u.first_name ASC, u.last_name ASC'
    ];
    
    $options = array_merge($defaults, $options);
    return read_records('students s', $options);
}

// Get faculty with pagination and search
function get_faculty($options = []) {
    $defaults = [
        'select' => 'f.*, u.first_name, u.last_name, u.email, u.phone, u.status, d.name as department_name',
        'joins' => [
            'JOIN users u ON f.user_id = u.id',
            'LEFT JOIN departments d ON f.department_id = d.id'
        ],
        'search_columns' => ['u.first_name', 'u.last_name', 'u.email', 'f.employee_id', 'd.name'],
        'order_by' => 'u.first_name ASC, u.last_name ASC'
    ];
    
    $options = array_merge($defaults, $options);
    return read_records('faculty f', $options);
}

// Get courses with pagination and search
function get_courses($options = []) {
    $defaults = [
        'select' => 'c.*, d.name as department_name, CONCAT(u.first_name, " ", u.last_name) as faculty_name',
        'joins' => [
            'LEFT JOIN departments d ON c.department_id = d.id',
            'LEFT JOIN faculty f ON c.faculty_id = f.id',
            'LEFT JOIN users u ON f.user_id = u.id'
        ],
        'search_columns' => ['c.name', 'c.code', 'd.name'],
        'order_by' => 'c.name ASC'
    ];
    
    $options = array_merge($defaults, $options);
    return read_records('courses c', $options);
}

// Validation functions
function validate_student_data($data, $is_update = false) {
    $errors = [];
    
    if (!$is_update || isset($data['username'])) {
        if (empty($data['username']) || strlen($data['username']) < 3) {
            $errors[] = "Username must be at least 3 characters long.";
        }
    }
    
    if (!$is_update || isset($data['email'])) {
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email address is required.";
        }
    }
    
    if (!$is_update || isset($data['password'])) {
        if (!$is_update && empty($data['password'])) {
            $errors[] = "Password is required.";
        } elseif (!empty($data['password'])) {
            $password_errors = validate_password($data['password']);
            $errors = array_merge($errors, $password_errors);
        }
    }
    
    if (empty($data['first_name']) || empty($data['last_name'])) {
        $errors[] = "First name and last name are required.";
    }
    
    if (!$is_update || isset($data['student_id'])) {
        if (empty($data['student_id'])) {
            $errors[] = "Student ID is required.";
        }
    }
    
    return $errors;
}

function validate_faculty_data($data, $is_update = false) {
    $errors = [];
    
    if (!$is_update || isset($data['username'])) {
        if (empty($data['username']) || strlen($data['username']) < 3) {
            $errors[] = "Username must be at least 3 characters long.";
        }
    }
    
    if (!$is_update || isset($data['email'])) {
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email address is required.";
        }
    }
    
    if (!$is_update || isset($data['password'])) {
        if (!$is_update && empty($data['password'])) {
            $errors[] = "Password is required.";
        } elseif (!empty($data['password'])) {
            $password_errors = validate_password($data['password']);
            $errors = array_merge($errors, $password_errors);
        }
    }
    
    if (empty($data['first_name']) || empty($data['last_name'])) {
        $errors[] = "First name and last name are required.";
    }
    
    if (!$is_update || isset($data['employee_id'])) {
        if (empty($data['employee_id'])) {
            $errors[] = "Employee ID is required.";
        }
    }
    
    return $errors;
}
?>