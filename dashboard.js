// DOM Elements
const addCourseBtn = document.getElementById('addCourseBtn');
const courseModal = document.getElementById('courseModal');
const closeModal = document.getElementById('closeModal');
const cancelBtn = document.getElementById('cancelBtn');
const saveCourseBtn = document.getElementById('saveCourseBtn');
const modalTitle = document.getElementById('modalTitle');
const courseForm = document.getElementById('courseForm');
const courseTableBody = document.getElementById('courseTableBody');

// Cập nhật các DOM Elements cho thẻ thống kê
const totalCoursesElement = document.getElementById('totalCourses');
const totalStudentsElement = document.getElementById('totalStudents');
const completionRateElement = document.getElementById('completionRate');
const monthlyRevenueElement = document.getElementById('monthlyRevenue');


// Biến lưu trữ dữ liệu khóa học
let allCourses = [];

// Hàm để định dạng ngày thành dd/mm/yyyy
function formatDate(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

// Hàm để định dạng tiền tệ
function formatCurrency(amount) {
    const cleanAmount = (amount === null || amount === undefined || amount === '') ? 0 : amount;
    const numericAmount = parseFloat(cleanAmount);
    
    if (isNaN(numericAmount)) {
        return 'N/A';
    }
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(numericAmount);
}

// Hàm để định dạng doanh thu sang kiểu "Xtr" (triệu) hoặc "Xđ"
function formatRevenue(amount) {
    const numericAmount = parseFloat(amount) || 0;
    if (numericAmount >= 1000000) {
        const millions = Math.round(numericAmount / 1000000);
        return `${millions}tr`;
    }
    // Nếu dưới 1 triệu, hiển thị dạng tiền tệ VND
    return formatCurrency(numericAmount);
}


// Render dữ liệu từ API vào bảng
function renderCourses(courses) {
    courseTableBody.innerHTML = '';
    if (!courses || courses.length === 0) {
        courseTableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Chưa có khóa học nào.</td></tr>`;
        return;
    }
    courses.forEach((course, index) => {
        const newRow = courseTableBody.insertRow();
        newRow.innerHTML = `
            <td>${course.title}</td>
            <td>${course.teacher_name}</td>
            <td>${formatDate(course.created_at)}</td>
            <td>${formatCurrency(course.price)}</td>
            <td>
                <button class="btn btn-info btn-sm me-2 edit-btn" data-id="${course.id}"><i class="fas fa-edit"></i> Sửa</button>
                <button class="btn btn-danger btn-sm delete-btn" data-id="${course.id}"><i class="fas fa-trash"></i> Xóa</button>
            </td>
        `;
    });
}

// Tải dữ liệu khóa học từ API
async function fetchCourses() {
    try {
        const response = await fetch('api/admin/courses.php');
        if (!response.ok) {
            throw new Error('Lỗi khi tải dữ liệu khóa học.');
        }
        const data = await response.json();
        allCourses = data.items || data.data; 
        renderCourses(allCourses);
        updateStats();
    } catch (error) {
        console.error('Lỗi: ' + error.message);
    }
}

// Hàm tải dữ liệu thống kê từ API
async function fetchStats() {
    try {
        const response = await fetch('api/admin/stats.php');
        if (!response.ok) {
            throw new Error('Lỗi khi tải dữ liệu thống kê.');
        }
        const statsData = await response.json();
        
        if (totalStudentsElement) totalStudentsElement.textContent = statsData.totalStudents;
        if (completionRateElement) completionRateElement.textContent = statsData.completionRate + '%';
        if (monthlyRevenueElement) monthlyRevenueElement.textContent = formatRevenue(statsData.monthlyRevenue);

    } catch (error) {
        console.error('Lỗi tải thống kê: ' + error.message);
        if (totalStudentsElement) totalStudentsElement.textContent = '0';
        if (completionRateElement) completionRateElement.textContent = '0%';
        if (monthlyRevenueElement) monthlyRevenueElement.textContent = '0đ';
    }
}

// === CÁC HÀM XỬ LÝ MODAL ĐÃ SỬA LỖI ===
// Mở modal thêm khóa học
function openAddModal() {
    modalTitle.textContent = 'Thêm Khóa học';
    courseForm.reset();
    document.getElementById('courseId').value = '';
    courseModal.classList.add('is-visible'); // Dùng class để hiện
}

// Đóng modal
function closeCourseModal() {
    courseModal.classList.remove('is-visible'); // Dùng class để ẩn
}

// Mở modal sửa khóa học
function openEditModal(courseId) {
    const course = allCourses.find(c => c.id == courseId);
    if (!course) {
        console.error("Không tìm thấy khóa học với ID:", courseId);
        return;
    }

    modalTitle.textContent = 'Sửa Khóa học';
    document.getElementById('courseId').value = course.id;
    document.getElementById('courseName').value = course.title;
    document.getElementById('instructor').value = course.teacher_name;
    const datePart = course.created_at.split(' ')[0];
    document.getElementById('startDate').value = datePart.match(/^\d{4}-\d{2}-\d{2}$/) ? datePart : '';
    document.getElementById('tuition').value = course.price; 
    document.getElementById('courseDescription').value = course.description;
    
    courseModal.classList.add('is-visible'); // Dùng class để hiện
}
// === KẾT THÚC CÁC HÀM XỬ LÝ MODAL ===


// Xóa khóa học
async function deleteCourse(courseId) {
    if (!confirm('Bạn có chắc chắn muốn xóa khóa học này?')) {
        return;
    }

    try {
        const response = await fetch(`api/admin/courses.php?id=${courseId}`, {
            method: 'DELETE',
        });
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message);
        }
        alert('Đã xóa khóa học thành công!');
        closeCourseModal();
        fetchCourses(); // Tải lại danh sách
    } catch (error) {
        console.error('Lỗi: ' + error.message);
        alert('Lỗi: ' + error.message);
    }
}

// Lưu khóa học (thêm hoặc sửa)
async function saveCourse() {
    const courseId = document.getElementById('courseId').value;
    const courseData = {
        title: document.getElementById('courseName').value,
        teacher_name: document.getElementById('instructor').value,
        created_at: document.getElementById('startDate').value,
        price: document.getElementById('tuition').value, 
        description: document.getElementById('courseDescription').value,
    };
    
    if (!courseData.title || !courseData.teacher_name || !courseData.created_at || !courseData.price) {
        alert('Vui lòng điền đầy đủ thông tin!');
        return;
    }

    try {
        const method = courseId ? 'PUT' : 'POST';
        const url = courseId ? `api/admin/courses.php?id=${courseId}` : 'api/admin/courses.php';
        
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(courseData),
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message);
        }
        
        alert(`Đã ${courseId ? 'cập nhật' : 'thêm'} khóa học thành công!`);
        closeCourseModal();
        fetchCourses(); // Tải lại danh sách
    } catch (error) {
        console.error('Lỗi: ' + error.message);
        alert('Lỗi: ' + error.message);
    }
}

// Hàm này giờ chỉ cập nhật số khóa học
function updateStats() {
    if (totalCoursesElement) totalCoursesElement.textContent = allCourses.length;
}

// Thêm event listener cho các nút Sửa và Xóa sử dụng event delegation
courseTableBody.addEventListener('click', function(event) {
    if (event.target.closest('.edit-btn')) {
        const courseId = event.target.closest('.edit-btn').dataset.id;
        openEditModal(courseId);
    }
    if (event.target.closest('.delete-btn')) {
        const courseId = event.target.closest('.delete-btn').dataset.id;
        deleteCourse(courseId);
    }
});

// Sự kiện cho các nút
addCourseBtn.addEventListener('click', openAddModal);
closeModal.addEventListener('click', closeCourseModal);
cancelBtn.addEventListener('click', closeCourseModal);
saveCourseBtn.addEventListener('click', saveCourse);

window.addEventListener('click', function(event) {
    if (event.target === courseModal) {
        closeCourseModal();
    }
});

// Tải cả dữ liệu khóa học và dữ liệu thống kê khi trang được tải
window.onload = function() {
    fetchCourses();
    fetchStats();
};