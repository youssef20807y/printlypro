// التحقق من دعم الإشعارات
function checkNotificationSupport() {
    if (!('Notification' in window)) {
        console.log('هذا المتصفح لا يدعم الإشعارات');
        return false;
    }
    if (!('serviceWorker' in navigator)) {
        console.log('هذا المتصفح لا يدعم Service Workers');
        return false;
    }
    return true;
}

// تسجيل Service Worker
async function registerServiceWorker() {
    try {
        const registration = await navigator.serviceWorker.register('js/service-worker.js');
        return registration;
    } catch (error) {
        console.error('فشل في تسجيل Service Worker:', error);
        return null;
    }
}

// طلب إذن الإشعارات
async function requestNotificationPermission() {
    try {
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            updateSubscriptionOnServer();
        } else {
            console.log('تم رفض الإذن للإشعارات');
        }
    } catch (error) {
        console.error('حدث خطأ أثناء طلب الإذن:', error);
    }
}

// تحديث حالة الاشتراك في الإشعارات
async function updateSubscriptionOnServer() {
    if (!checkNotificationSupport()) return;

    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array('YOUR_PUBLIC_VAPID_KEY') // يجب تغيير هذا المفتاح
        });

        // إرسال معلومات الاشتراك للخادم
        await fetch('api/save-subscription.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(subscription)
        });

        updateNotificationButton(true);
    } catch (error) {
        console.error('فشل في الاشتراك في الإشعارات:', error);
        updateNotificationButton(false);
    }
}

// تحويل المفتاح العام إلى تنسيق مناسب
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

// تحديث حالة زر الإشعارات
function updateNotificationButton(isSubscribed) {
    const button = document.getElementById('notificationBtn');
    if (button) {
        button.innerHTML = isSubscribed ? 
            '<i class="fas fa-bell"></i>' :
            '<i class="far fa-bell"></i>';
        button.title = isSubscribed ? 'تم تفعيل الإشعارات' : 'تفعيل الإشعارات';
    }
}

// التحقق من حالة الاشتراك عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', async () => {
    if (!checkNotificationSupport()) return;

    try {
        const registration = await registerServiceWorker();
        if (registration) {
            const subscription = await registration.pushManager.getSubscription();
            updateNotificationButton(!!subscription);
        }
    } catch (error) {
        console.error('حدث خطأ أثناء التحقق من حالة الاشتراك:', error);
    }
});

// إضافة مستمع الحدث لزر الإشعارات
document.getElementById('notificationBtn').addEventListener('click', requestNotificationPermission);
