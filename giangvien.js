document.addEventListener('DOMContentLoaded', function() {
    let allTeachers = [];
    let currentPage = 1;
    
    const API_URL = 'api/admin/teachers.php';
    const addModal = new bootstrap.Modal(document.getElementById('addTeacherModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editTeacherModal'));

    async function loadTeachers(page = 1, search = '') {
        const tableBody = document.getElementById('teacher-table-body');
        tableBody.innerHTML = `<tr><td colspan="5" class="text-center">Đang tải...</td></tr>`;

        try {
            const params = new URLSearchParams({ page, search });
            const response = await fetch(`${API_URL}?${params.toString()}`);
            const data = await response.json();

            if (!response.ok) throw new Error(data.message || 'Lỗi tải dữ liệu.');
            
            allTeachers = data.items;
            renderTeachers(allTeachers);
            setupPagination(data.total_pages, page);
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${error.message}</td></tr>`;
        }
    }
    
    function renderTeachers(teachersToRender) {
        const tableBody = document.getElementById('teacher-table-body');
        tableBody.innerHTML = '';
        if (!teachersToRender || teachersToRender.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center">Không tìm thấy giảng viên nào.</td></tr>`;
            return;
        }

        teachersToRender.forEach(teacher => {
            const initials = teacher.name.split(' ').map(n => n[0]).join('').toUpperCase();
            const row = `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="student-avatar" style="background-color: #0d6efd;">${initials}</div>
                            <div>
                                <div class="fw-bold">${teacher.name}</div>
                                <small class="text-muted">ID: ${teacher.id}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>${teacher.email}</div>
                        <small class="text-muted">${teacher.phone || 'Chưa có SĐT'}</small>
                    </td>
                    <td>${new Date(teacher.created_at).toLocaleDateString('vi-VN')}</td>
                    <td><span class="badge bg-info">${teacher.course_count}</span></td>
                    <td>
                        <button class="btn btn-sm btn-info edit-btn" data-id="${teacher.id}"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${teacher.id}"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
            tableBody.innerHTML += row;
        });
    }
    
    function setupPagination(totalPages, currentPageFromApi) {
        const paginationContainer = document.querySelector('.pagination');
        paginationContainer.innerHTML = '';
        currentPage = currentPageFromApi;

        for (let i = 1; i <= totalPages; i++) {
            const pageItem = `<li class="page-item ${currentPage === i ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            paginationContainer.innerHTML += pageItem;
        }
    }

    // --- Event Listeners ---
    document.querySelector('.pagination').addEventListener('click', (e) => {
        e.preventDefault();
        if (e.target.matches('.page-link')) {
            loadTeachers(parseInt(e.target.dataset.page), document.getElementById('searchInput').value);
        }
    });

    document.getElementById('searchInput').addEventListener('input', (e) => {
        loadTeachers(1, e.target.value);
    });

    document.getElementById('resetFiltersBtn').addEventListener('click', () => {
        document.getElementById('searchInput').value = '';
        loadTeachers();
    });

    document.getElementById('saveTeacherBtn').addEventListener('click', async () => {
        const data = {
            name: document.getElementById('teacherName').value,
            email: document.getElementById('teacherEmail').value,
            phone: document.getElementById('teacherPhone').value,
            password: document.getElementById('teacherPassword').value
        };
        try {
            const response = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            if (!response.ok) throw new Error((await response.json()).message);
            addModal.hide();
            loadTeachers();
            alert('Thêm giảng viên thành công!');
        } catch (error) { alert(`Lỗi: ${error.message}`); }
    });

    document.getElementById('teacher-table-body').addEventListener('click', (e) => {
        const editButton = e.target.closest('.edit-btn');
        if (editButton) {
            const teacher = allTeachers.find(t => t.id == editButton.dataset.id);
            document.getElementById('editTeacherId').value = teacher.id;
            document.getElementById('editTeacherName').value = teacher.name;
            document.getElementById('editTeacherEmail').value = teacher.email;
            document.getElementById('editTeacherPhone').value = teacher.phone || '';
            editModal.show();
        }
        const deleteButton = e.target.closest('.delete-btn');
        if (deleteButton) {
            if (confirm(`Bạn có chắc muốn xóa giảng viên ID: ${deleteButton.dataset.id}?`)) {
                deleteTeacher(deleteButton.dataset.id);
            }
        }
    });
    
    document.getElementById('updateTeacherBtn').addEventListener('click', async () => {
        const id = document.getElementById('editTeacherId').value;
        const data = {
            name: document.getElementById('editTeacherName').value,
            email: document.getElementById('editTeacherEmail').value,
            phone: document.getElementById('editTeacherPhone').value,
        };
        try {
            const response = await fetch(`${API_URL}?id=${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            if (!response.ok) throw new Error((await response.json()).message);
            editModal.hide();
            loadTeachers(currentPage, document.getElementById('searchInput').value);
            alert('Cập nhật thành công!');
        } catch (error) { alert(`Lỗi: ${error.message}`); }
    });

    async function deleteTeacher(id) {
        try {
            const response = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
            if (!response.ok) throw new Error((await response.json()).message);
            loadTeachers(currentPage, document.getElementById('searchInput').value);
            alert('Xóa thành công!');
        } catch (error) { alert(`Lỗi: ${error.message}`); }
    }
    
    loadTeachers();
});