<?php
// view_complaint.php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$complaint_id = sanitize($_GET['id']);
$student_id = $_SESSION['user_id'];

// جلب بيانات البلاغ
$stmt = $conn->prepare("SELECT * FROM complaints 
                       WHERE complaint_id = ? AND student_id = ?");
$stmt->execute([$complaint_id, $student_id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    header("Location: dashboard.php?error=complaint_not_found");
    exit();
}

// جلب تحديثات البلاغ
$stmt = $conn->prepare("SELECT * FROM complaint_updates 
                       WHERE complaint_id = ? 
                       ORDER BY updated_at DESC");
$stmt->execute([$complaint_id]);
$updates = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل البلاغ #<?php echo $complaint_id; ?></title>
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
        .complaint-img { max-width: 100%; cursor: pointer; }
        .update-card { border-left: 4px solid; margin-bottom: 15px; }
        .update-card.pending { border-left-color: #ffc107; }
        .update-card.in_progress { border-left-color: #0d6efd; }
        .update-card.resolved { border-left-color: #198754; }
        .update-card.rejected { border-left-color: #dc3545; }
        .rating input { display: none; }
        .rating label { cursor: pointer; font-size: 1.5rem; color: #ddd; }
        .rating input:checked ~ label { color: #ffc107; }
        .rating label:hover, 
        .rating label:hover ~ label { color: #ffc107; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">نظام البلاغات</a>
            <div class="d-flex">
                <a href="dashboard.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-arrow-right me-1"></i>العودة للوحة التحكم
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-file-alt me-2"></i>تفاصيل البلاغ #<?php echo $complaint_id; ?>
                        </h4>
                        <span class="badge bg-<?php 
                            switch($complaint['status']) {
                                case 'pending': echo 'warning'; break;
                                case 'in_progress': echo 'info'; break;
                                case 'resolved': echo 'success'; break;
                                case 'rejected': echo 'danger'; break;
                            }
                        ?>">
                            <?php 
                            $statuses = [
                                'pending' => 'قيد الانتظار',
                                'in_progress' => 'قيد المعالجة',
                                'resolved' => 'تم الحل',
                                'rejected' => 'مرفوض'
                            ];
                            echo $statuses[$complaint['status']]; 
                            ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h5><i class="fas fa-tag me-2"></i>نوع المشكلة</h5>
                                    <p class="ps-4"><?php echo $complaint['complaint_type']; ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <h5><i class="fas fa-map-marker-alt me-2"></i>الموقع</h5>
                                    <p class="ps-4"><?php echo $complaint['location']; ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <h5><i class="far fa-calendar-alt me-2"></i>تاريخ الإرسال</h5>
                                    <p class="ps-4"><?php echo $complaint['created_at']; ?></p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h5><i class="fas fa-align-left me-2"></i>وصف المشكلة</h5>
                                    <p class="ps-4"><?php echo nl2br($complaint['description']); ?></p>
                                </div>
                                
                                <?php if ($complaint['image_path']): ?>
                                <div class="mb-3">
                                    <h5><i class="fas fa-image me-2"></i>الصورة المرفقة</h5>
                                    <img src="<?php echo $complaint['image_path']; ?>" 
                                         alt="صورة البلاغ" 
                                         class="complaint-img img-thumbnail mt-2"
                                         data-bs-toggle="modal" 
                                         data-bs-target="#imageModal"
                                         data-img-src="<?php echo $complaint['image_path']; ?>">
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="fas fa-history me-2"></i>تحديثات البلاغ</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($updates)): ?>
                            <div class="alert alert-info">لا توجد تحديثات حتى الآن</div>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach ($updates as $update): ?>
                                <div class="update-card card mb-3 <?php echo $update['status']; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <h5 class="card-title status-<?php echo $update['status']; ?>">
                                                <?php echo $statuses[$update['status']]; ?>
                                            </h5>
                                            <small class="text-muted"><?php echo $update['updated_at']; ?></small>
                                        </div>
                                        <p class="card-text"><?php echo nl2br($update['notes']); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($complaint['status'] === 'resolved'): ?>
                <div class="card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="fas fa-star me-2"></i>تقييم الخدمة</h4>
                    </div>
                    <div class="card-body">
                        <form action="includes/feedback.php" method="POST">
                            <input type="hidden" name="complaint_id" value="<?php echo $complaint_id; ?>">
                            
                            <div class="mb-4">
                                <label class="form-label"><i class="fas fa-tachometer-alt me-2"></i>كيف تقيم سرعة الحل؟</label>
                                <div class="rating">
                                    <input type="radio" id="speed5" name="speed" value="5">
                                    <label for="speed5" title="سريع جدا"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="speed4" name="speed" value="4">
                                    <label for="speed4" title="سريع"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="speed3" name="speed" value="3">
                                    <label for="speed3" title="عادي"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="speed2" name="speed" value="2">
                                    <label for="speed2" title="بطيء"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="speed1" name="speed" value="1">
                                    <label for="speed1" title="بطيء جدا"><i class="fas fa-star"></i></label>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label"><i class="fas fa-check-circle me-2"></i>كيف تقيم جودة الحل؟</label>
                                <div class="rating">
                                    <input type="radio" id="quality5" name="quality" value="5">
                                    <label for="quality5" title="ممتاز جدا"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="quality4" name="quality" value="4">
                                    <label for="quality4" title="ممتاز"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="quality3" name="quality" value="3">
                                    <label for="quality3" title="عادي"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="quality2" name="quality" value="2">
                                    <label for="quality2" title="سيء"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="quality1" name="quality" value="1">
                                    <label for="quality1" title="سيء جدا"><i class="fas fa-star"></i></label>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="comments" class="form-label"><i class="fas fa-comment me-2"></i>تعليقات إضافية</label>
                                <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>إرسال التقييم
                            </button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>يمكنك تقييم الخدمة بعد حل البلاغ.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal for Image Preview -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">صورة البلاغ</h5>
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