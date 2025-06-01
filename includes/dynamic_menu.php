<?php
/**
 * Dynamic Menu Management Functions
 */

/**
 * Get menu items based on user permissions and roles
 */
function get_dynamic_menu_items($menu_type = 'sidebar', $user_permissions = [], $user_role = '') {
    $pdo = get_database_connection();
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM menu_items 
            WHERE menu_type = ? AND is_active = TRUE 
            ORDER BY parent_id ASC, sort_order ASC
        ");
        $stmt->execute([$menu_type]);
        $all_items = $stmt->fetchAll();
        
        $filtered_items = [];
        
        foreach ($all_items as $item) {
            if (user_can_access_menu($item, $user_permissions, $user_role)) {
                $filtered_items[] = $item;
            }
        }
        
        return build_menu_tree($filtered_items);
        
    } catch (Exception $e) {
        error_log("Get menu items error: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user can access a menu item
 */
function user_can_access_menu($menu_item, $user_permissions, $user_role) {
    $required_permissions = json_decode($menu_item['required_permissions'], true) ?: [];
    $required_roles = json_decode($menu_item['required_roles'], true) ?: [];
    
    if (empty($required_permissions) && empty($required_roles)) {
        return true;
    }
    
    if (!empty($required_permissions)) {
        if (in_array('all', $user_permissions)) {
            return true;
        }
        if (!empty(array_intersect($user_permissions, $required_permissions))) {
            return true;
        }
    }
    
    if (!empty($required_roles)) {
        if (in_array($user_role, $required_roles)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Build hierarchical menu tree from flat array
 */
function build_menu_tree($items, $parent_id = null) {
    $tree = [];
    
    foreach ($items as $item) {
        if ($item['parent_id'] == $parent_id) {
            $children = build_menu_tree($items, $item['id']);
            if (!empty($children)) {
                $item['children'] = $children;
            }
            $tree[] = $item;
        }
    }
    
    return $tree;
}

/**
 * Render menu HTML
 */
function render_dynamic_menu($menu_items, $current_page = '') {
    $html = '';
    
    foreach ($menu_items as $item) {
        $has_children = isset($item['children']) && !empty($item['children']);
        $is_active = ($current_page === $item['url'] || 
                      ($has_children && menu_has_active_child($item['children'], $current_page)));
        
        if ($has_children) {
            $html .= render_dropdown_menu($item, $current_page, $is_active);
        } else {
            $html .= render_single_menu($item, $is_active);
        }
    }
    
    return $html;
}

/**
 * Render dropdown menu item
 */
function render_dropdown_menu($item, $current_page, $is_active) {
    $active_class = $is_active ? 'active' : '';
    $show_class = $is_active ? 'show' : '';
    
    $html = '
    <li class="nav-item dropdown ' . $active_class . '">
        <a class="nav-link dropdown-toggle" href="#navbar-' . strtolower(str_replace(' ', '-', $item['name'])) . '" 
           data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="' . ($is_active ? 'true' : 'false') . '">
            <span class="nav-link-icon d-md-none d-lg-inline-block">' . get_icon_svg($item['icon']) . '</span>
            <span class="nav-link-title">' . htmlspecialchars($item['name']) . '</span>
        </a>
        <div class="dropdown-menu ' . $show_class . '">
            <div class="dropdown-menu-columns">
                <div class="dropdown-menu-column">';
    
    foreach ($item['children'] as $child) {
        $child_active = ($current_page === $child['url']) ? 'active' : '';
        $html .= '
                    <a class="dropdown-item ' . $child_active . '" href="' . htmlspecialchars($child['url']) . '">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">' . get_icon_svg($child['icon']) . '</span>
                        ' . htmlspecialchars($child['name']) . '
                    </a>';
    }
    
    $html .= '
                </div>
            </div>
        </div>
    </li>';
    
    return $html;
}

/**
 * Render single menu item
 */
function render_single_menu($item, $is_active) {
    $active_class = $is_active ? 'active' : '';
    $link_class = (!empty($item['css_class'])) ? $item['css_class'] : '';
    
    return '
    <li class="nav-item ' . $active_class . '">
        <a class="nav-link ' . $link_class . '" href="' . htmlspecialchars($item['url']) . '" target="' . htmlspecialchars($item['target']) . '">
            <span class="nav-link-icon d-md-none d-lg-inline-block">' . get_icon_svg($item['icon']) . '</span>
            <span class="nav-link-title">' . htmlspecialchars($item['name']) . '</span>
        </a>
    </li>';
}

/**
 * Check if menu has active child
 */
function menu_has_active_child($children, $current_page) {
    foreach ($children as $child) {
        if ($current_page === $child['url']) {
            return true;
        }
        if (isset($child['children']) && menu_has_active_child($child['children'], $current_page)) {
            return true;
        }
    }
    return false;
}

/**
 * Get SVG icon based on icon name
 */
function get_icon_svg($icon_name) {
    $icons = [
        'home' => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
        'user' => '<circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>',
        'users' => '<circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3"/>',
        'book-open' => '<path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/><path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/><line x1="3" y1="6" x2="3" y2="19"/><line x1="12" y1="6" x2="12" y2="19"/><line x1="21" y1="6" x2="21" y2="19"/>',
        'settings' => '<path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/><circle cx="12" cy="12" r="3"/>',
        'bar-chart' => '<rect x="3" y="12" width="6" height="8" rx="1"/><rect x="9" y="8" width="6" height="12" rx="1"/><rect x="15" y="4" width="6" height="16" rx="1"/>',
        'shield' => '<path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"/>',
        'database' => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6a8 3 0 0 0 16 0v-6"/><path d="M4 12v6a8 3 0 0 0 16 0v-6"/>',
        'log-out' => '<path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/><path d="M7 12h14l-3 -3m0 6l3 -3"/>',
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'list' => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',
        'user-plus' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>',
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
        'user-check' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17,11 19,13 23,9"/>',
        'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'menu' => '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>'
    ];
    
    if (!isset($icons[$icon_name])) {
        return '<circle cx="12" cy="12" r="1"/>';
    }
    
    return '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/>' . $icons[$icon_name] . '</svg>';
}

// Menu Management CRUD Functions
function add_menu_item($data) {
    $pdo = get_database_connection();
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO menu_items (name, icon, url, parent_id, sort_order, required_permissions, 
                                   required_roles, menu_type, css_class, target, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['name'],
            $data['icon'],
            $data['url'],
            $data['parent_id'] ?: null,
            $data['sort_order'],
            json_encode($data['required_permissions']),
            json_encode($data['required_roles']),
            $data['menu_type'],
            $data['css_class'],
            $data['target'],
            $data['description']
        ]);
        
    } catch (Exception $e) {
        error_log("Add menu item error: " . $e->getMessage());
        return false;
    }
}

function update_menu_item($id, $data) {
    $pdo = get_database_connection();
    
    try {
        $stmt = $pdo->prepare("
            UPDATE menu_items 
            SET name = ?, icon = ?, url = ?, parent_id = ?, sort_order = ?, 
                required_permissions = ?, required_roles = ?, menu_type = ?, 
                css_class = ?, target = ?, description = ?, is_active = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['name'],
            $data['icon'],
            $data['url'],
            $data['parent_id'] ?: null,
            $data['sort_order'],
            json_encode($data['required_permissions']),
            json_encode($data['required_roles']),
            $data['menu_type'],
            $data['css_class'],
            $data['target'],
            $data['description'],
            $data['is_active'],
            $id
        ]);
        
    } catch (Exception $e) {
        error_log("Update menu item error: " . $e->getMessage());
        return false;
    }
}

function delete_menu_item($id) {
    $pdo = get_database_connection();
    
    try {
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        return $stmt->execute([$id]);
        
    } catch (Exception $e) {
        error_log("Delete menu item error: " . $e->getMessage());
        return false;
    }
}

function get_all_menu_items() {
    $pdo = get_database_connection();
    
    try {
        $stmt = $pdo->query("
            SELECT m.*, 
                   p.name as parent_name,
                   (SELECT COUNT(*) FROM menu_items WHERE parent_id = m.id) as children_count
            FROM menu_items m 
            LEFT JOIN menu_items p ON m.parent_id = p.id 
            ORDER BY m.parent_id ASC, m.sort_order ASC
        ");
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Get all menu items error: " . $e->getMessage());
        return [];
    }
}
?>