<?php
// dashboard.php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}
// جلب شكاوى الطالب
$stmt = $conn->prepare("SELECT * FROM complaints WHERE student_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$complaints = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <!-- Bootstrap RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-rtl@5.3.0/dist/css/bootstrap-rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-in_progress { color: #0d6efd; font-weight: bold; }
        .status-resolved { color: #198754; font-weight: bold; }
        .status-rejected { color: #dc3545; font-weight: bold; }
        .complaint-img { max-width: 100px; max-height: 100px; cursor: pointer; }
        .complaint-img:hover { opacity: 0.8; }
        .nav-link.active { font-weight: bold; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">نظام البلاغات</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fas fa-home me-1"></i>الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#newComplaintModal">
                            <i class="fas fa-plus-circle me-1"></i>بلاغ جديد
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo $_SESSION['full_name']; ?>
                    </span>
                    <a href="?action=logout" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt me-1"></i>تسجيل الخروج
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="fas fa-history me-2"></i>بلاغاتي السابقة</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($complaints)): ?>
                            <div class="alert alert-info">لا توجد بلاغات سابقة</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>رقم البلاغ</th>
                                            <th>النوع</th>
                                            <th>التاريخ</th>
                                            <th>الحالة</th>
                                            <th>الصورة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($complaints as $complaint): ?>
                                        <tr>
                                            <td><?php echo $complaint['complaint_id']; ?></td>
                                            <td><?php echo $complaint['complaint_type']; ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($complaint['created_at'])); ?></td>
                                            <td class="status-<?php echo $complaint['status']; ?>">
                                                <?php 
                                                $statuses = [
                                                    'pending' => 'قيد الانتظار',
                                                    'in_progress' => 'قيد المعالجة',
                                                    'resolved' => 'تم الحل',
                                                    'rejected' => 'مرفوض'
                                                ];
                                                echo $statuses[$complaint['status']]; 
                                                ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($complaint['image_path'])): ?>
                                                    <img src="<?php echo $complaint['image_path']; ?>" 
                                                         alt="صورة البلاغ" 
                                                         class="complaint-img"
                                                         data-bs-toggle="modal" 
                                                         data-bs-target="#imageModal"
                                                         data-img-src="<?php echo $complaint['image_path']; ?>">
                                                <?php else: ?>
                                                    <span class="text-muted">لا توجد صورة</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="view_complain.php?id=<?php echo $complaint['complaint_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>عرض
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for New Complaint -->
    <div class="modal fade" id="newComplaintModal" tabindex="-1" aria-labelledby="newComplaintModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newComplaintModalLabel">تقديم بلاغ جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="complain.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="complaint_type" class="form-label">نوع المشكلة</label>
                            <select class="form-select" id="complaint_type" name="complaint_type" required>
                                <option value="">اختر نوع المشكلة</option>
                                <option value="تقنية">مشكلة تقنية</option>
                                <option value="أكاديمية">مشكلة أكاديمية</option>
                                <option value="صيانة">مشكلة صيانة</option>
                                <option value="إدارية">مشكلة إدارية</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">وصف المشكلة</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location" class="form-label">الموقع</label>
                            <input type="text" class="form-control" id="location" name="location" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">صورة (اختياري)</label>
                            <input class="form-control" type="file" id="image" name="image" accept="image/*">
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">إرسال البلاغ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Image Preview -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" alt="صورة البلاغ">
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize image modal
        var imageModal = document.getElementById('imageModal');
        imageModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var imageSrc = button.getAttribute('data-img-src');
            var modalImage = imageModal.querySelector('.modal-body img');
            modalImage.src = imageSrc;
        });
    </script>
</body>
</html>