<?php require __DIR__ . '/admin_header.php'; 

// Mảng dữ liệu cho các mục menu ở sidebar

?>
            <header class="header">
                <h1 class="page-title"><i class="fas fa-gauge-high me-2"></i> Dashboard</h1>
            </header>

            <section class="row" id="stats-container-row"> 
                <div class="col-xl-3 col-md-6 mb-4"> 
                    <div class="stats-card"> 
                        <div class="stats-number" style="color: var(--primary);" id="totalCourses">...</div>
                        <div class="stats-label">Tổng số khóa học</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number" style="color: var(--success);" id="totalStudents">...</div> 
                        <div class="stats-label">Tổng số học viên</div>
                    </div>    
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number" style="color: var(--warning);" id="completionRate">...%</div> 
                        <div class="stats-label">Tỷ lệ hoàn thành</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number" style="color: var(--danger);" id="monthlyRevenue">...</div> 
                        <div class="stats-label">Doanh thu tháng</div>
                    </div>
                </div>
            </section>

            <section class="content-section"> 
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title mb-0"><i class="fas fa-table me-2"></i> Danh sách khóa học</h2>
                    <button class="btn btn-primary" id="addCourseBtn">
                        <i class="fas fa-plus me-1"></i> Thêm Khóa học
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tên khóa học</th>
                                <th>Giảng viên</th>
                                <th>Ngày bắt đầu</th>
                                <th>Học phí</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody id="courseTableBody">
                            <tr><td colspan="5" class="text-center">Đang tải dữ liệu...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <div class="modal-overlay" id="courseModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Thêm Khóa học</h2>
                <button class="modal-close" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="courseForm" novalidate>
                    <input type="hidden" id="courseId">
                    <div class="form-group mb-3">
                        <label for="courseName" class="form-label">Tên khóa học</label>
                        <input type="text" class="form-control" id="courseName" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="instructor" class="form-label">Giảng viên</label>
                        <input type="text" class="form-control" id="instructor" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="startDate" class="form-label">Ngày bắt đầu</label>
                        <input type="date" class="form-control" id="startDate" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="tuition" class="form-label">Học phí (VND)</label>
                        <input type="number" class="form-control" id="tuition" required min="0">
                    </div>
                    <div class="form-group">
                        <label for="courseDescription" class="form-label">Mô tả khóa học</label>
                        <textarea class="form-control" id="courseDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelBtn">Hủy</button> 
                <button class="btn btn-primary" id="saveCourseBtn">Lưu</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="dashboard.js"></script>
</body>
</html>