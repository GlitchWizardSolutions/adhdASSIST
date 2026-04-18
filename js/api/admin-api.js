/**
 * Admin API - Consolidated API calls for admin dashboard
 * Replaces scattered fetch patterns in admin.js
 */

const AdminAPI = (function() {
    'use strict';

    /**
     * Get list of active users
     */
    async function getActiveUsers() {
        return APIHelper.get('admin/users-list.php?status=active');
    }

    /**
     * Get list of inactive users
     */
    async function getInactiveUsers() {
        return APIHelper.get('admin/users-list.php?status=inactive');
    }

    /**
     * Get pending user invitations
     */
    async function getPendingInvitations() {
        return APIHelper.get('admin/users-invitations.php?status=pending');
    }

    /**
     * Invite user
     */
    async function inviteUser(email, role = 'user') {
        return APIHelper.post('admin/users-invite.php', {
            email,
            role
        });
    }

    /**
     * Reset user password
     */
    async function resetUserPassword(userId) {
        return APIHelper.post('admin/users-reset-password.php', {
            user_id: userId
        });
    }

    /**
     * Deactivate user
     */
    async function deactivateUser(userId) {
        return APIHelper.post('admin/users-deactivate.php', {
            user_id: userId
        });
    }

    /**
     * Reactivate user
     */
    async function reactivateUser(userId) {
        return APIHelper.post('admin/users-reactivate.php', {
            user_id: userId
        });
    }

    /**
     * Delete user
     */
    async function deleteUser(userId) {
        return APIHelper.delete('admin/users-delete.php', {
            user_id: userId
        });
    }

    /**
     * Get delegated tasks
     */
    async function getDelegatedTasks() {
        return APIHelper.get('admin/delegated-tasks-list.php');
    }

    /**
     * Get completed delegated tasks
     */
    async function getCompletedTasks() {
        return APIHelper.get('admin/delegated-tasks-completed.php');
    }

    /**
     * Delegate task to user
     */
    async function delegateTask(taskId, userId, dueDate = null) {
        return APIHelper.post('admin/delegated-tasks-create.php', {
            task_id: taskId,
            user_id: userId,
            due_date: dueDate
        });
    }

    /**
     * Get CRUD templates
     */
    async function getCRUDTemplates() {
        return APIHelper.get('admin/crud-list.php');
    }

    /**
     * Get CRUD template permissions
     */
    async function getCRUDPermissions(templateId) {
        return APIHelper.get(`admin/crud-permissions.php?id=${templateId}`);
    }

    /**
     * Update CRUD permissions
     */
    async function updateCRUDPermissions(templateId, permissions) {
        return APIHelper.put('admin/crud-permissions.php', {
            template_id: templateId,
            permissions
        });
    }

    /**
     * Get dashboard statistics
     */
    async function getDashboardStats() {
        return APIHelper.get('admin/dashboard-stats.php');
    }

    /**
     * Get pending notifications
     */
    async function getPendingNotifications() {
        return APIHelper.get('admin/notifications-pending.php');
    }

    /**
     * Get system configuration
     */
    async function getConfiguration() {
        return APIHelper.get('admin/configuration.php');
    }

    /**
     * Update system configuration
     */
    async function updateConfiguration(config) {
        return APIHelper.put('admin/configuration.php', config);
    }

    /**
     * Get audit log
     */
    async function getAuditLog(limit = 100) {
        return APIHelper.get(`admin/audit-log.php?limit=${limit}`);
    }

    /**
     * Batch load admin data
     */
    async function batchLoadAdminData() {
        return APIHelper.batch([
            { endpoint: 'admin/users-list.php?status=active' },
            { endpoint: 'admin/users-invitations.php?status=pending' },
            { endpoint: 'admin/delegated-tasks-completed.php' },
            { endpoint: 'admin/dashboard-stats.php' }
        ]);
    }

    // Public API
    return {
        getActiveUsers,
        getInactiveUsers,
        getPendingInvitations,
        inviteUser,
        resetUserPassword,
        deactivateUser,
        reactivateUser,
        deleteUser,
        getDelegatedTasks,
        getCompletedTasks,
        delegateTask,
        getCRUDTemplates,
        getCRUDPermissions,
        updateCRUDPermissions,
        getDashboardStats,
        getPendingNotifications,
        getConfiguration,
        updateConfiguration,
        getAuditLog,
        batchLoadAdminData
    };
})();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminAPI;
}
