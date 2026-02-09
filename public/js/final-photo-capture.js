/**
 * Final photo capture: same UI/behavior as first proctoring capture.
 * Camera does NOT auto-open: user must tap "Start camera", then tap "Capture photo".
 * No fullscreen auto-launch without controls.
 * Capture -> POST post-face -> POST finalize -> redirect to result.
 */
(function () {
    const video = document.getElementById('camera-video');
    const canvas = document.getElementById('capture-canvas');
    const captureBtn = document.getElementById('capture-btn');
    const captureBtnText = document.getElementById('capture-btn-text');
    const errorEl = document.getElementById('capture-error');
    const errorTextEl = document.getElementById('capture-error-text');
    const cameraLoading = document.getElementById('camera-loading');
    const cameraOffPlaceholder = document.getElementById('camera-off-placeholder');
    const faceConfirmCheckbox = document.getElementById('face-confirm-checkbox');
    const config = window.QuizSnapFinalPhoto || {};
    const csrf = config.csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';
    let stream = null;
    let cameraStarted = false;

    function canCapture() {
        return cameraStarted && stream && faceConfirmCheckbox && faceConfirmCheckbox.checked;
    }

    function updateCaptureButtonState() {
        if (!captureBtn) return;
        if (!cameraStarted) {
            captureBtn.disabled = false;
            setButtonLabel('Start camera');
            return;
        }
        captureBtn.disabled = !canCapture();
        setButtonLabel(canCapture() ? 'Capture photo' : 'Confirm face visible above, then capture');
    }

    function showError(msg) {
        if (errorTextEl) errorTextEl.textContent = msg || '';
        if (errorEl) {
            errorEl.classList.remove('hidden');
            errorEl.style.display = 'block';
        }
    }

    function hideError() {
        if (errorEl) {
            errorEl.classList.add('hidden');
            errorEl.style.display = 'none';
        }
    }

    function setButtonLabel(text) {
        if (captureBtnText) captureBtnText.textContent = text;
    }

    function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError('Camera not supported in this browser.');
            return;
        }
        if (captureBtn) captureBtn.disabled = true;
        setButtonLabel('Starting camera...');
        if (cameraLoading) cameraLoading.style.display = 'flex';
        hideError();
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 640, height: 480 } })
            .then(function (s) {
                stream = s;
                if (video) video.srcObject = s;
                cameraStarted = true;
                if (cameraLoading) cameraLoading.style.display = 'none';
                if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'none';
                if (captureBtn) updateCaptureButtonState();
            })
            .catch(function (err) {
                showError('Camera access denied or error: ' + (err.message || 'Unknown'));
                setButtonLabel('Start camera');
                if (captureBtn) captureBtn.disabled = false;
                if (cameraLoading) cameraLoading.style.display = 'none';
                if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'flex';
            });
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (video) video.srcObject = null;
        cameraStarted = false;
    }

    function setBusy(busy) {
        if (captureBtn) captureBtn.disabled = busy;
        if (busy && captureBtnText) captureBtnText.textContent = 'Please wait...';
        else updateCaptureButtonState();
    }

    function captureAndSubmit() {
        if (!cameraStarted || !stream) {
            startCamera();
            return;
        }
        if (!canCapture()) {
            showError('Please confirm your face is visible in the frame, then capture.');
            return;
        }
        if (!video || !canvas || !stream) {
            showError('Camera not ready.');
            return;
        }
        setBusy(true);
        hideError();
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        stopCamera();
        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

        fetch(config.postFaceUrl || '/quiz/post-face', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ face_image: dataUrl }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    showError(data.message || 'Failed to save photo.');
                    setButtonLabel('Start camera');
                    setBusy(false);
                    if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'flex';
                    return;
                }
                return fetch(config.finalizeUrl || '/quiz/finalize', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                });
            })
            .then(function (r) { return r ? r.json() : null; })
            .then(function (data) {
                if (data && data.redirect) {
                    window.location.href = data.redirect;
                } else if (data && !data.success && data.message) {
                    showError(data.message || 'Failed to submit.');
                    setButtonLabel('Start camera');
                    setBusy(false);
                    if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'flex';
                } else if (data && data.success) {
                    window.location.href = config.resultUrl || '/quiz/result';
                }
            })
            .catch(function () {
                showError('Network error. Please try again.');
                setButtonLabel('Start camera');
                setBusy(false);
                if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'flex';
            });
    }

    if (captureBtn) {
        captureBtn.addEventListener('click', captureAndSubmit);
    }
    if (faceConfirmCheckbox) {
        faceConfirmCheckbox.addEventListener('change', updateCaptureButtonState);
    }
    setButtonLabel('Start camera');
    updateCaptureButtonState();
    if (cameraLoading) cameraLoading.style.display = 'none';
    window.addEventListener('beforeunload', stopCamera);
})();
