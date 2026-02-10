/**
 * Final photo capture: same UI/behavior as first proctoring capture.
 * Camera does NOT auto-open: user must tap "Start camera", then tap "Capture photo".
 * No fullscreen auto-launch without controls.
 * Capture -> POST post-face -> POST finalize -> redirect to quiz complete (log in to see results).
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

    function isVideoReady() {
        return video && video.videoWidth > 0 && video.videoHeight > 0 && stream;
    }

    function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError('Camera not supported in this browser.');
            return;
        }
        if (captureBtn) captureBtn.disabled = true;
        setButtonLabel('Starting camera...');
        if (cameraLoading) cameraLoading.style.display = 'flex';
        if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'none';
        hideError();
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } })
            .then(function (s) {
                stream = s;
                if (video) {
                    video.srcObject = s;
                    function onReady() {
                        cameraStarted = true;
                        if (cameraLoading) cameraLoading.style.display = 'none';
                        if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'none';
                        if (captureBtn) updateCaptureButtonState();
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
                    cameraStarted = true;
                    if (cameraLoading) cameraLoading.style.display = 'none';
                    if (captureBtn) updateCaptureButtonState();
                }
            })
            .catch(function (err) {
                showError('Camera access denied or not available. Allow camera access and try again, or use a different browser.');
                setButtonLabel('Try again');
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
        if (!video || !canvas || !stream || video.videoWidth <= 0 || video.videoHeight <= 0) {
            showError('Camera is still starting. Please wait a moment, then try again.');
            return;
        }
        if (!navigator.onLine) {
            showError('You\'re offline. Connect to the internet and try again.');
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
            .then(function (r) {
                if (!r.ok && r.headers.get('content-type') && r.headers.get('content-type').indexOf('json') === -1) {
                    throw new Error('Server error. Please try again.');
                }
                return r.json();
            })
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
                } else if (data && data.success) {
                    window.location.href = config.resultUrl || '/quiz/complete';
                } else {
                    showError((data && data.message) ? data.message : 'Could not complete. Please try again.');
                    setButtonLabel('Start camera');
                    setBusy(false);
                    if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'flex';
                }
            })
            .catch(function () {
                showError(navigator.onLine ? 'Network error. Please try again.' : 'You\'re offline. Connect and try again.');
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
