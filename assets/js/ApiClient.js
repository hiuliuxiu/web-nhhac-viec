/**
 * DANH MỤC ĐIỂM CUỐI CỦA MÁY CHỦ
 *
 * Một lớp mỏng trên HttpClient, đặt tên cho từng thao tác nghiệp vụ. Mỗi đường dẫn
 * có dạng controller/action, ví dụ 'task/create' ứng với TaskController::create()
 * bên PHP. Đổi đường dẫn bên PHP thì chỉ sửa đúng tệp này.
 */
class ApiClient {

    constructor(http) {
        this.http = http;
    }

    // --- F11 Xác thực -------------------------------------------------------
    me()      { return this.http.get('auth/me'); }
    logout()  { return this.http.post('auth/logout'); }

    // --- F10 Dự án ----------------------------------------------------------
    projects()                 { return this.http.get('project/list'); }
    project(projectId)         { return this.http.get('project/get', { project_id: projectId }); }
    createProject(data)        { return this.http.post('project/create', data); }
    updateProject(data)        { return this.http.post('project/update', data); }
    deleteProject(projectId)   { return this.http.post('project/delete', { project_id: projectId }); }
    saveSettings(data)         { return this.http.post('project/settings', data); }
    testWebhook(data)          { return this.http.post('project/testWebhook', data); }
    clearLog(projectId)        { return this.http.post('project/clearLog', { project_id: projectId }); }

    // --- F04 / F05 Công việc ------------------------------------------------
    tasks(params = {})         { return this.http.get('task/list', params); }
    createTask(data)           { return this.http.post('task/create', data); }
    updateTask(data)           { return this.http.post('task/update', data); }
    moveTask(data)             { return this.http.post('task/move', data); }
    deleteTask(taskId)         { return this.http.post('task/delete', { task_id: taskId }); }

    // --- F06 Thành viên -----------------------------------------------------
    members(projectId)         { return this.http.get('member/list', { project_id: projectId }); }
    searchUsers(projectId, q)  { return this.http.get('member/search', { project_id: projectId, q }); }
    inviteMember(data)         { return this.http.post('member/invite', data); }
    removeMember(data)         { return this.http.post('member/remove', data); }

    // --- F01 / F02 Bảng điều khiển · F07 Nhật ký ----------------------------
    dashboard(projectId)       { return this.http.get('dashboard', projectId ? { project_id: projectId } : {}); }
    notifications(params = {}) { return this.http.get('reminder/list', params); }

    // --- F09 Nhịp quét ngầm -------------------------------------------------
    cronTick()  { return this.http.get('reminder/scan'); }
    cronForce() { return this.http.post('reminder/scan', { force: true }, { force: true }); }
}
