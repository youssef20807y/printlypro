<?php
/**
 * ملف تذييل لوحة تحكم المسؤول
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
if (!defined('PRINTLY')) {
    exit('ممنوع الوصول المباشر');
}
?>

        <footer class="main-footer">
            <strong>جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?> <a href="../index.php">مطبعة برنتلي</a>.</strong>
            <div class="float-left d-none d-sm-inline-block">
                <b>الإصدار</b> 1.0.0
            </div>
        </footer>
    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Summernote -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- سكريبت مخصص -->
    <script>
        $(document).ready(function() {
            // تهيئة DataTables
            $('.datatable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json"
                },
                "responsive": true,
                "autoWidth": false
            });
            
            // تهيئة Select2
            $('.select2').select2({
                dir: "rtl",
                language: "ar"
            });
            
            // تهيئة Summernote
            $('.summernote').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                callbacks: {
                    onImageUpload: function(files) {
                        // رفع الصور
                        for (let i = 0; i < files.length; i++) {
                            uploadSummernoteImage(files[i], $(this));
                        }
                    }
                }
            });
            
            // وظيفة رفع الصور لـ Summernote
            function uploadSummernoteImage(file, editor) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('action', 'upload_image');
                
                $.ajax({
                    url: 'ajax/upload.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        const response = JSON.parse(data);
                        if (response.success) {
                            editor.summernote('insertImage', response.url);
                        } else {
                            alert('فشل رفع الصورة: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('حدث خطأ أثناء رفع الصورة');
                    }
                });
            }
            
            // معاينة الصورة قبل الرفع
            $('.custom-file-input').on('change', function() {
                const fileName = $(this).val().split('\\').pop();
                $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
                
                const fileInput = this;
                const imgPreview = $(this).closest('.form-group').find('.img-preview');
                
                if (fileInput.files && fileInput.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        imgPreview.attr('src', e.target.result);
                        imgPreview.parent().show();
                    }
                    
                    reader.readAsDataURL(fileInput.files[0]);
                }
            });
            
            // تأكيد الحذف
            $('.btn-delete').on('click', function(e) {
                if (!confirm('هل أنت متأكد من رغبتك في الحذف؟')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
