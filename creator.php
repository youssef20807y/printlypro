<?php
// صفحة صانع الموقع
include 'includes/header.php';
?>

<style>
.creator-container {
    margin-top: 110px;
    margin-bottom: 60px;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 70vh;
}
.creator-card {
    max-width: 480px;
    width: 100%;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 6px 32px rgba(25, 118, 210, 0.10);
    padding: 44px 32px 36px 32px;
    text-align: center;
    transition: box-shadow 0.2s;
}
.creator-card:hover {
    box-shadow: 0 8px 40px rgba(25, 118, 210, 0.16);
}
.creator-logo {
    width: 120px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(25, 118, 210, 0.10);
    margin-bottom: 18px;
    display: block;
    margin-left: auto;
    margin-right: auto;
}
.creator-title {
    margin-bottom: 18px;
    color: #1976d2;
    font-weight: bold;
    font-size: 2rem;
    letter-spacing: 1px;
}
.creator-name {
    color: #1a237e;
    font-size: 1.25rem;
    font-weight: bold;
}
.creator-contacts {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    margin-bottom: 22px;
}
.creator-link-btn {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 220px;
    padding: 12px 22px;
    border-radius: 16px;
    background: #fff;
    border: 2px solid #e3eafc;
    box-shadow: 0 1px 4px rgba(25, 118, 210, 0.04);
    color: #1976d2;
    font-size: 1.13rem;
    font-weight: 500;
    transition: border-color 0.2s, background 0.2s, color 0.2s, box-shadow 0.2s;
    text-decoration: none;
    position: relative;
    cursor: pointer;
}
.creator-link-btn i {
    font-size: 1.35rem;
    margin-left: 2px;
    transition: color 0.2s;
}
.creator-link-btn.email {
    border-color: #1976d2;
    color: #1976d2;
}
.creator-link-btn.phone {
    border-color: #43a047;
    color: #43a047;
}
.creator-link-btn.whatsapp {
    border-color: #25d366;
    color: #25d366;
}
.creator-link-btn.email i {
    color: #1976d2;
}
.creator-link-btn.phone i {
    color: #43a047;
}
.creator-link-btn.whatsapp i {
    color: #25d366;
}
.creator-link-btn:hover {
    background: #f5f7fa;
    border-width: 2.5px;
    box-shadow: 0 2px 10px rgba(25, 118, 210, 0.07);
}
.creator-link-btn.email:hover {
    border-color: #1565c0;
    color: #1565c0;
}
.creator-link-btn.phone:hover {
    border-color: #388e3c;
    color: #388e3c;
}
.creator-link-btn.whatsapp:hover {
    border-color: #128c7e;
    color: #128c7e;
}
.creator-link-btn:hover i {
    color: inherit;
}
.creator-link-text {
    color: inherit;
    font-size: 1.08rem;
    font-weight: 500;
    letter-spacing: 0.2px;
}
.creator-files {
    background: #f5f7fa;
    border-radius: 8px;
    padding: 14px 0;
    margin-bottom: 22px;
    font-size: 1.08rem;
    color: #222;
    box-shadow: 0 1px 4px rgba(25, 118, 210, 0.04);
}
.btn-secondary {
    background: #1976d2;
    color: #fff !important;
    border: none;
    border-radius: 6px;
    padding: 10px 28px;
    font-size: 1rem;
    font-weight: 500;
    transition: background 0.2s;
    text-decoration: none;
    display: inline-block;
}
.btn-secondary:hover {
    background: #125ea7;
}
</style>

<div class="creator-container">
    <div class="creator-card">
        <img src="assets/images/image-proxy.php" alt="keep.import" class="creator-logo">
        <div class="creator-title">صانع الموقع</div>
        <p style="font-size: 1.15rem; color: #333; margin-bottom: 18px;">تم تطوير هذا الموقع بواسطة<br><span class="creator-name">يوسف احمد محمد جمعة</span></p>
        <div class="creator-contacts">
            <a href="mailto:yjmt46999@gmail.com" class="creator-link-btn email" title="البريد الإلكتروني">
                <i class="fas fa-envelope"></i>
                <span class="creator-link-text">yjmt46999@gmail.com</span>
            </a>
            <a href="tel:01024485693" class="creator-link-btn phone" title="اتصال هاتفي">
                <i class="fas fa-phone"></i>
                <span class="creator-link-text">01024485693</span>
            </a>
            <a href="https://wa.me/201024485693" target="_blank" class="creator-link-btn whatsapp" title="واتساب">
                <i class="fab fa-whatsapp"></i>
                <span class="creator-link-text">تواصل واتساب</span>
            </a>
        </div>
        <div class="creator-files">
            <span>عدد الملفات البرمجية وملفات البيانات والوسائط في هذا الموقع: <strong style="color: #1976d2;">97 ملف</strong></span>
        </div>
        <a href="index.php" class="btn btn-secondary">العودة للرئيسية</a>
    </div>
</div>

<?php
include 'includes/footer.php';
?> 