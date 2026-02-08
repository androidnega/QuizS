// QuizSnap – global app script (e.g. CSRF for fetch)
document.addEventListener('DOMContentLoaded', function () {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
        window.QuizSnapCSRF = meta.getAttribute('content');
    }
});
