/**
 * ProctoringCapture: WebRTC face capture, then POST to backend.
 */
(function () {
    const video = document.getElementById('camera-video');
    const canvas = document.getElementById('capture-canvas');
    const captureBtn = document.getElementById('capture-btn');
    const errorEl = document.getElementById('capture-error');
    const config = window.QuizSnapProctoring || {};
    let stream = null;

    function showError(msg) {
        if (errorEl) {
            errorEl.textContent = msg;
            errorEl.style.display = 'block';
        }
    }

    function hideError() {
        if (errorEl) {
            errorEl.style.display = 'none';
        }
    }

    function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError('Camera not supported in this browser.');
            return;
        }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 640, height: 480 } })
            .then(function (s) {
                stream = s;
                if (video) video.srcObject = s;
                hideError();
                var loadingEl = document.getElementById('camera-loading');
                if (loadingEl) loadingEl.style.display = 'none';
            })
            .catch(function (err) {
                showError('Camera access denied or error: ' + (err.message || 'Unknown'));
            });
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (video) video.srcObject = null;
    }

    function captureAndSubmit() {
        if (!video || !canvas || !stream) {
            showError('Camera not ready.');
            return;
        }
        captureBtn.disabled = true;
        captureBtn.textContent = 'Please wait...';
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        stopCamera();
        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        fetch(config.storeUrl || '/student/proctoring/capture', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                quiz_id: config.quizId,
                index_number: config.indexNumber,
                face_image: dataUrl,
            }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    showError(data.message || 'Failed to start quiz.');
                    captureBtn.disabled = false;
                    captureBtn.textContent = 'Capture photo';
                }
            })
            .catch(function () {
                showError('Network error.');
                captureBtn.disabled = false;
                captureBtn.textContent = 'Capture photo';
            });
    }

    if (captureBtn) {
        captureBtn.addEventListener('click', captureAndSubmit);
    }
    startCamera();
    window.addEventListener('beforeunload', stopCamera);
})();
