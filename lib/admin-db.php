<?php
/**
 * ADHD Dashboard - Admin Database Helper Functions
 * User management, task delegation, CRUD template management
 */

class AdminDB {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // =========================================
    // USER MANAGEMENT
    // =========================================

    /**
     * Get all users with pagination
     */
    public function getAllUsers($page = 1, $perPage = 50, $status = null) {
        try {
            $offset = ($page - 1) * $perPage;
            $query = "SELECT id, email, CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name, username, role, CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END as status, last_login, created_at 
                     FROM users WHERE 1=1";
            $params = [];

            // Filter by status if provided
            if ($status && in_array($status, ['active', 'inactive', 'pending'])) {
                if ($status === 'active') {
                    $query .= " AND is_active = 1";
                } elseif ($status === 'inactive') {
                    $query .= " AND is_active = 0";
                } elseif ($status === 'pending') {
                    $query .= " AND is_active = 0"; // Treat pending as inactive
                }
            }

            // Add pagination (use intval to safely embed limit/offset in query)
            $query .= " ORDER BY CASE WHEN status = 'pending' THEN 0 ELSE 1 END, last_login DESC LIMIT " . intval($perPage) . " OFFSET " . intval($offset);

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AdminDB::getAllUsers error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total user count by status
     */
    public function getUserCount($status = null) {
        try {
            $query = "SELECT COUNT(*) as count FROM users WHERE 1=1";
            $params = [];

            if ($status && in_array($status, ['active', 'inactive', 'pending'])) {
                if ($status === 'active') {
                    $query .= " AND is_active = 1";
                } elseif ($status === 'inactive') {
                    $query .= " AND is_active = 0";
                } elseif ($status === 'pending') {
                    $query .= " AND is_active = 0"; // Treat pending as inactive
                }
            }

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("AdminDB::getUserCount error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get user by ID with full details
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AdminDB::getUserById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AdminDB::getUserByEmail error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update user information (Admin can edit name, email, timezone, carrier, phone, theme, status)
     */
    public function updateUser($userId, $data) {
        try {
            $allowedFields = ['first_name', 'last_name', 'email', 'timezone', 'mobile_carrier', 'phone_number', 'theme_preference'];
            $updates = [];
            $params = [];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = ?";
                    $params[] = $value;
                }
            }

            if (empty($updates)) return false;

            $updates[] = "updated_at = NOW()";
            $params[] = $userId;

            $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("AdminDB::updateUser error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deactivate user (set status to inactive)
     */
    public function deactivateUser($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ? AND role != 'developer'");
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("AdminDB::deactivateUser error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reactivate user (set status to active)
     */
    public function reactivateUser($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("AdminDB::reactivateUser error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete user account (prevents developer account deletion)
     */
    public function deleteUser($userId) {
        try {
            // Prevent deletion of developer accounts
            $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false;
            }
            
            if ($user['role'] === 'developer') {
                error_log("AdminDB::deleteUser - Attempted to delete developer account: $userId");
                return false;
            }
            
            // Delete the user (cascade will delete related records)
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'developer'");
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("AdminDB::deleteUser error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create password reset token for user
     */
    public function createPasswordReset($userId) {
        try {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Delete existing token if present, then create new one
            $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Insert new reset token
            $stmt = $this->pdo->prepare(
                "INSERT INTO password_resets (user_id, token, expires_at) 
                 VALUES (?, ?, ?)"
            );
            
            if ($stmt->execute([$userId, hash('sha256', $token), $expiresAt])) {
                return $token;
            }
            return null;
        } catch (Exception $e) {
            error_log("AdminDB::createPasswordReset error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create user invitation (Admin sends invite link to email)
     */
    public function createInvitation($email, $fullName = null, $invitedById = null) {
        try {
            // Generate unique invitation token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

            // Try to insert, if duplicate email exists (constraint violation), update instead
            try {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO invitations (email, full_name, token, invited_by, expires_at) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                $result = $stmt->execute([$email, $fullName, $token, $invitedById, $expiresAt]);
                return $result ? $token : null;
            } catch (Exception $insertEx) {
                // If constraint violation (duplicate email), update the existing invitation with new token
                if (strpos($insertEx->getMessage(), '1062') !== false || strpos($insertEx->getMessage(), 'Duplicate') !== false) {
                    $stmt = $this->pdo->prepare(
                        "UPDATE invitations SET token = ?, invited_by = ?, expires_at = ?, accepted_at = NULL 
                         WHERE email = ?"
                    );
                    $result = $stmt->execute([$token, $invitedById, $expiresAt, $email]);
                    return $result ? $token : null;
                }
                throw $insertEx; // Re-throw if different error
            }
        } catch (Exception $e) {
            error_log("AdminDB::createInvitation error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get pending invitations
     */
    public function getPendingInvitations($limit = 50) {
        try {
            // Cast LIMIT to integer to avoid SQL syntax errors with parameter binding
            $stmt = $this->pdo->prepare(
                "SELECT * FROM invitations WHERE expires_at > NOW() AND accepted_at IS NULL 
                 ORDER BY created_at DESC LIMIT " . intval($limit)
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AdminDB::getPendingInvitations error: " . $e->getMessage());
            return [];
        }
    }

    // =========================================
    // DELEGATED TASKS
    // =========================================

    /**
     * Get tasks delegated by current admin, with status
     */
    public function getDelegatedTasks($adminId, $filters = []) {
        try {
            $query = "SELECT 
                        t.id,
                        t.title,
                        t.description,
                        t.priority,
                        t.status,
                        t.due_date,
                        t.assigned_to,
                        t.assigned_by,
                        t.assignment_date,
                        CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as assignee_name,
                        u.email as assignee_email
                     FROM tasks t
                     JOIN users u ON t.assigned_to = u.id
                     WHERE t.assigned_by = ? AND t.assigned_to IS NOT NULL";
            $params = [$adminId];

            // Filter by assignee
            if (!empty($filters['assigned_to'])) {
                $query .= " AND t.assigned_to = ?";
                $params[] = $filters['assigned_to'];
            }

            // Filter by status
            if (!empty($filters['status']) && in_array($filters['status'], ['not_started', 'in_progress', 'completed', 'active', 'inbox', 'scheduled'])) {
                $query .= " AND t.status = ?";
                $params[] = $filters['status'];
            }

            // Filter by due date range
            if (!empty($filters['due_from'])) {
                $query .= " AND t.due_date >= ?";
                $params[] = $filters['due_from'];
            }
            if (!empty($filters['due_to'])) {
                $query .= " AND t.due_date <= ?";
                $params[] = $filters['due_to'];
            }

            $query .= " ORDER BY t.due_date ASC, t.priority DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AdminDB::getDelegatedTasks error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get count of delegated tasks by status
     */
    public function getDelegatedTasksStats($adminId) {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status IN ('in_progress', 'active') THEN 1 ELSE 0 END) as in_progress,
                        SUM(CASE WHEN status IN ('not_started', 'inbox', 'scheduled') THEN 1 ELSE 0 END) as not_started
                     FROM tasks 
                     WHERE assigned_by = ? AND assigned_to IS NOT NULL";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$adminId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AdminDB::getDelegatedTasksStats error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update task (reassign, reschedule, complete)
     */
    public function updateTask($taskId, $data) {
        try {
            $allowedFields = ['assigned_to', 'due_date', 'status', 'priority', 'description'];
            $updates = [];
            $params = [];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = ?";
                    $params[] = $value;
                }
            }

            if (empty($updates)) return false;

            $updates[] = "updated_at = NOW()";
            $params[] = $taskId;

            $query = "UPDATE tasks SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("AdminDB::updateTask error: " . $e->getMessage());
            return false;
        }
    }

    // =========================================
    // CRUD TEMPLATE PERMISSIONS
    // =========================================

    /**
     * Get all CRUD templates (pre-built)
     */
    public function getCRUDTemplates() {
        try {
            $templates = [
                [
                    'id' => 'medications',
                    'name' => 'Medications',
                    'type' => 'pre-built',
                    'description' => 'Track medications and refill schedules',
                    'created_by' => null,
                ],
                [
                    'id' => 'bills',
                    'name' => 'Bills & Expenses',
                    'type' => 'pre-built',
                    'description' => 'Track recurring bills and payments',
                    'created_by' => null,
                ],
                [
                    'id' => 'recipes',
                    'name' => 'Recipes',
                    'type' => 'pre-built',
                    'description' => 'Store recipes and cooking instructions',
                    'created_by' => null,
                ],
                [
                    'id' => 'passwords',
                    'name' => 'Links & Passwords',
                    'type' => 'pre-built',
                    'description' => 'Secure storage of account credentials',
                    'created_by' => null,
                ],
                [
                    'id' => 'products',
                    'name' => 'Products & Warranty',
                    'type' => 'pre-built',
                    'description' => 'Track products, warranties, and documents',
                    'created_by' => null,
                ],
                [
                    'id' => 'logs',
                    'name' => 'Activity Logs',
                    'type' => 'pre-built',
                    'description' => 'Professional timeline logging for any event',
                    'created_by' => null,
                ],
                [
                    'id' => 'contacts',
                    'name' => 'Emergency Contacts',
                    'type' => 'pre-built',
                    'description' => 'Critical contact information',
                    'created_by' => null,
                ],
            ];
            return $templates;
        } catch (Exception $e) {
            error_log("AdminDB::getCRUDTemplates error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Grant CRUD template access to user
     */
    public function grantTemplateAccess($userId, $templateId) {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT IGNORE INTO user_crud_permissions (user_id, template_id, granted_at) VALUES (?, ?, NOW())"
            );
            return $stmt->execute([$userId, $templateId]);
        } catch (Exception $e) {
            error_log("AdminDB::grantTemplateAccess error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoke CRUD template access from user
     */
    public function revokeTemplateAccess($userId, $templateId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_crud_permissions WHERE user_id = ? AND template_id = ?");
            return $stmt->execute([$userId, $templateId]);
        } catch (Exception $e) {
            error_log("AdminDB::revokeTemplateAccess error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's template permissions
     */
    public function getUserTemplatePermissions($userId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT template_id FROM user_crud_permissions WHERE user_id = ?"
            );
            $stmt->execute([$userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_column($results, 'template_id');
        } catch (Exception $e) {
            error_log("AdminDB::getUserTemplatePermissions error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all users with template permissions
     */
    public function getTemplateUsers($templateId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT u.id, u.full_name, u.email FROM users u
                 JOIN user_crud_permissions p ON u.id = p.user_id
                 WHERE p.template_id = ? ORDER BY u.full_name"
            );
            $stmt->execute([$templateId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AdminDB::getTemplateUsers error: " . $e->getMessage());
            return [];
        }
    }

    // =========================================
    // SYSTEM STATISTICS
    // =========================================

    /**
     * Get admin dashboard statistics
     */
    public function getAdminStats() {
        try {
            $stats = [
                'active_users' => $this->getUserCount('active'),
                'inactive_users' => $this->getUserCount('inactive'),
                'pending_invites' => count($this->getPendingInvitations()),
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'in_progress' => 0,
            ];

            // Try to get task stats if tasks table exists
            try {
                $taskQuery = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed FROM tasks LIMIT 1";
                $stmt = $this->pdo->query($taskQuery);
                $taskStats = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($taskStats) {
                    $stats['total_tasks'] = (int)$taskStats['total'];
                    $stats['completed_tasks'] = (int)($taskStats['completed'] ?? 0);
                }
            } catch (Exception $e) {
                // Tasks table may not exist yet - that's okay
            }

            return $stats;
        } catch (Exception $e) {
            error_log("AdminDB::getAdminStats error: " . $e->getMessage());
            return [];
        }
    }
}
