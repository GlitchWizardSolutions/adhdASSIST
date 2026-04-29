/**
 * Dashboard API - Consolidated API calls for dashboard
 * Replaces scattered apiCall patterns in dashboard.js
 */

const DashboardAPI = (function() {
    'use strict';

    /**
     * Create a new task
     */
    async function createTask(title, priority = 'medium', status = 'inbox') {
        return APIHelper.post('tasks/create.php', {
            title,
            priority,
            status
        });
    }

    /**
     * Get all tasks
     */
    async function getTasks() {
        return APIHelper.get('tasks/read.php');
    }

    /**
     * Update task
     */
    async function updateTask(taskId, data) {
        return APIHelper.put('tasks/update.php', {
            task_id: taskId,
            ...data
        });
    }

    /**
     * Delete task
     */
    async function deleteTask(taskId) {
        return APIHelper.delete('tasks/delete.php', {
            task_id: taskId
        });
    }

    /**
     * Get daily habits
     */
    async function getDailyHabits(date = null) {
        // Use provided date or default to today (YYYY-MM-DD format)
        if (!date) {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            date = `${year}-${month}-${day}`;
        }
        return APIHelper.get(`habits/read.php?date=${date}`);
    }

    /**
     * Update habit completion (toggle)
     * Toggles habit completion for today
     */
    async function updateHabit(habitId, completed, date = null) {
        // Use provided date or default to today (YYYY-MM-DD format)
        if (!date) {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            date = `${year}-${month}-${day}`;
        }
        // Use toggle endpoint which properly handles completion in habit_completions table
        return APIHelper.post('habits/toggle.php', {
            habit_id: habitId,
            date
        });
    }

    /**
     * Refresh habits (reset daily)
     */
    async function refreshHabits() {
        return APIHelper.post('habits/refresh.php');
    }

    /**
     * Send habits via SMS
     */
    async function sendHabitsSMS() {
        return APIHelper.post('habits/send-sms-on-demand.php', {});
    }

    /**
     * Send tasks via SMS
     */
    async function sendTasksSMS() {
        return APIHelper.post('tasks/send-sms-on-demand.php', {});
    }

    /**
     * Midnight reset
     */
    async function midnightReset() {
        return APIHelper.post('tasks/midnight-reset.php');
    }

    /**
     * Get focus timer settings
     */
    async function getFocusSettings() {
        return APIHelper.get('focus/settings.php');
    }

    /**
     * Update focus timer session
     */
    async function updateFocusSession(data) {
        return APIHelper.post('focus/update.php', data);
    }

    /**
     * Get energy level data
     */
    async function getEnergyData() {
        return APIHelper.get('energy/read.php');
    }

    /**
     * Update energy level
     */
    async function updateEnergyLevel(level, note = '') {
        return APIHelper.post('energy/update.php', {
            level,
            note
        });
    }

    /**
     * Get user preferences
     */
    async function getUserPreferences() {
        return APIHelper.get('user/preferences.php');
    }

    /**
     * Update user preferences
     */
    async function updateUserPreferences(prefs) {
        return APIHelper.put('user/preferences.php', prefs);
    }

    /**
     * Batch API calls
     */
    async function batchLoad() {
        return APIHelper.batch([
            { endpoint: 'tasks/read.php' },
            { endpoint: 'habits/read.php' },
            { endpoint: 'energy/read.php' }
        ]);
    }

    // Public API
    return {
        createTask,
        getTasks,
        updateTask,
        deleteTask,
        getDailyHabits,
        updateHabit,
        refreshHabits,
        sendHabitsSMS,
        sendTasksSMS,
        midnightReset,
        getFocusSettings,
        updateFocusSession,
        getEnergyData,
        updateEnergyLevel,
        getUserPreferences,
        updateUserPreferences,
        batchLoad
    };
})();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DashboardAPI;
}
