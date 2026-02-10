/**
 * ProctoringCapture: WebRTC face capture, then POST to backend.
 * Camera must be ready (stream + video has dimensions) before capture is allowed.
 */
(function () {
    const video = document.getElementById('camera-video');
    const canvas = document.getElementById('capture-canvas');
    const captureBtn = document.getElementById('capture-btn');
    const captureBtnText = document.getElementById('capture-btn-text');
    const errorEl = document.getElementById('capture-error');
    const errorTextEl = document.getElementById('capture-error-text');
    const cameraLoading = document.getElementById('camera-loading');
    const config = window.QuizSnapProctoring || {};
    let stream = null;
    let videoReady = false;

    function setButtonText(text) {
        if (captureBtnText) captureBtnText.textContent = text;
        else if (captureBtn) captureBtn.textContent = text;
    }

    function showError(msg) {
        if (errorTextEl) errorTextEl.textContent = msg || '';
        if (errorEl) {
            errorEl.style.display = 'block';
            errorEl.classList.remove('hidden');
        }
    }

    function hideError() {
        if (errorEl) {
            errorEl.style.display = 'none';
            errorEl.classList.add('hidden');
        }
    }

    function isVideoReady() {
        return video && video.videoWidth > 0 && video.videoHeight > 0 && stream;
    }

    function updateCaptureButton() {
        if (!captureBtn) return;
        if (!stream) {
            captureBtn.disabled = false;
            setButtonText('Starting camera...');
            return;
        }
        if (!videoReady) {
            captureBtn.disabled = true;
            setButtonText('Waiting for camera...');
            return;
        }
        captureBtn.disabled = false;
        setButtonText('Capture photo');
    }

    function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError('Camera not supported in this browser.');
            setButtonText('Capture photo');
            if (captureBtn) captureBtn.disabled = true;
            return;
        }
        hideError();
        videoReady = false;
        if (captureBtn) captureBtn.disabled = true;
        setButtonText('Starting camera...');
        if (cameraLoading) cameraLoading.style.display = 'flex';

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } })
            .then(function (s) {
                stream = s;
                if (video) {
                    video.srcObject = s;
                    function onReady() {
                        videoReady = video.videoWidth > 0 && video.videoHeight > 0;
                        if (cameraLoading) cameraLoading.style.display = 'none';
                        updateCaptureButton();
                    }
                    if (video.videoWidth > 0 && video.videoHeight > 0) {
                        onReady();
                    } else {
                        video.addEventListener('loadedmetadata', onReady, { once: true });
                        video.addEventListener('loadeddata', onReady, { once: true });
                        video.addEventListener('canplay', onReady, { once: true });
                        setTimeout(onReady, 2000);
                    }
                } else {
                    if (cameraLoading) cameraLoading.style.display = 'none';
                    updateCaptureButton();
                }
            })
            .catch(function (err) {
                showError('Camera access denied or not available. Allow camera access and try again, or use a different browser.');
                setButtonText('Try again');
                if (captureBtn) captureBtn.disabled = false;
                if (cameraLoading) cameraLoading.style.display = 'none';
            });
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (video) video.srcObject = null;
        videoReady = false;
    }

    function captureAndSubmit() {
        if (!stream) {
            startCamera();
            return;
        }
        if (!videoReady || !video || video.videoWidth <= 0 || video.videoHeight <= 0) {
            showError('Camera is still starting. Please wait a moment, then try again.');
            return;
        }
        if (!canvas) {
            showError('Something went wrong. Please refresh the page.');
            return;
        }
        captureBtn.disabled = true;
        setButtonText('Please wait...');
        hideError();
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
                'X-CSRF-TOKEN': config.csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                quiz_id: config.quizId,
                index_number: config.indexNumber,
                face_image: dataUrl,
            }),
        })
            .then(function (r) {
                if (!r.ok && r.headers.get('content-type') && r.headers.get('content-type').indexOf('json') === -1) {
                    throw new Error('Server error. Please try again.');
                }
                return r.json();
            })
            .then(function (data) {
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    showError(data.message || 'Failed to start quiz. Please try again.');
                    captureBtn.disabled = false;
                    setButtonText('Capture photo');
                }
            })
            .catch(function (err) {
                showError(err && err.message ? err.message : 'Network error. Check your connection and try again.');
                captureBtn.disabled = false;
                setButtonText('Capture photo');
            });
    }

    if (captureBtn) {
        captureBtn.addEventListener('click', captureAndSubmit);
    }
    startCamera();
    window.addEventListener('beforeunload', stopCamera);
})();
