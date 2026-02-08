/**
 * StudentQuiz: Timer, auto-save, tab/window blur detection, optional post-quiz face.
 * ProctoringEnforcer: blur → warning/auto-submit; disable copy-paste, right-click.
 */
(function () {
    const c = window.QuizSnapQuiz || {};
    const saveAnswerUrl = c.saveAnswerUrl;
    const saveAnswersBatchUrl = c.saveAnswersBatchUrl;
    const violationUrl = c.violationUrl;
    const finalPhotoUrl = c.finalPhotoUrl;
    const timeSyncUrl = c.timeSyncUrl;
    const csrfToken = c.csrfToken;
    let remainingSeconds = c.remainingSeconds || 0;
    let timerInterval = null;
    let timeSyncInterval = null;
    let blurCount = 0;
    const TIME_SYNC_INTERVAL_MS = 60000;

    const timerEl = document.getElementById('quiz-timer');
    const timerStickyEl = document.getElementById('quiz-timer-sticky');
    const quizForm = document.getElementById('quiz-form');
    const postFaceBtn = document.getElementById('post-face-btn');
    const blurWarning = document.getElementById('blur-warning');

    function csrf() {
        return csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';
    }

    function formatTime(sec) {
        sec = Math.max(0, Math.floor(sec));
        const minutes = Math.floor(sec / 60);
        const seconds = sec % 60;
        return minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
    }

    function applyTimerColor(sec) {
        var els = [timerEl, timerStickyEl].filter(Boolean);
        els.forEach(function (el) {
            if (!el) return;
            el.classList.remove('quiz-timer-green', 'quiz-timer-blue', 'quiz-timer-red');
            if (sec <= 30) {
                el.classList.add('quiz-timer-red');
            } else if (sec <= 120) {
                el.classList.add('quiz-timer-blue');
            } else {
                el.classList.add('quiz-timer-green');
            }
        });
    }

    function playTimeUpSound() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 440;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.8);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.8);
        } catch (e) {}
    }

    function updateTimer() {
        if (remainingSeconds <= 0) {
            if (timerInterval) clearInterval(timerInterval);
            if (timeSyncInterval) clearInterval(timeSyncInterval);
            playTimeUpSound();
            submitQuiz(true);
            return;
        }
        var text = formatTime(remainingSeconds);
        if (timerEl) timerEl.textContent = text;
        if (timerStickyEl) timerStickyEl.textContent = text;
        applyTimerColor(remainingSeconds);
        remainingSeconds--;
    }

    function syncTimeFromServer() {
        if (!timeSyncUrl || remainingSeconds <= 0) return;
        fetch(timeSyncUrl, { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && typeof data.remaining_seconds === 'number') {
                    remainingSeconds = Math.max(0, data.remaining_seconds);
                    var text = formatTime(remainingSeconds);
                    if (timerEl) timerEl.textContent = text;
                    if (timerStickyEl) timerStickyEl.textContent = text;
                    applyTimerColor(remainingSeconds);
                    if (remainingSeconds <= 0) {
                        if (timerInterval) clearInterval(timerInterval);
                        if (timeSyncInterval) clearInterval(timeSyncInterval);
                        submitQuiz(true);
                    }
                }
            })
            .catch(function () {});
    }

    var savePending = {};
    var saveDebounceTimer = null;
    var SAVE_DEBOUNCE_MS = 1200;

    function flushSavePending() {
        if (saveDebounceTimer) clearTimeout(saveDebounceTimer);
        saveDebounceTimer = null;
        var list = [];
        for (var id in savePending) { list.push(savePending[id]); }
        savePending = {};
        if (list.length === 0) return;
        var h = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' };
        if (saveAnswersBatchUrl && list.length > 0) {
            fetch(saveAnswersBatchUrl, {
                method: 'POST',
                headers: h,
                body: JSON.stringify({
                    answers: list.map(function (p) { return { question_id: p.questionId, answer: p.answer }; }),
                }),
            }).catch(function () {});
        } else {
            list.forEach(function (p) {
                fetch(saveAnswerUrl, {
                    method: 'POST',
                    headers: h,
                    body: JSON.stringify({ question_id: p.questionId, answer: p.answer }),
                }).catch(function () {});
            });
        }
    }

    function saveAnswer(questionId, answer) {
        savePending[questionId] = { questionId: questionId, answer: answer };
        if (saveDebounceTimer) clearTimeout(saveDebounceTimer);
        saveDebounceTimer = setTimeout(flushSavePending, SAVE_DEBOUNCE_MS);
    }

    function recordViolation(type) {
        fetch(violationUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ type: type }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.auto_submitted && data.redirect) {
                    window.location.href = data.redirect;
                }
            })
            .catch(function () {});
    }

    /** Redirect to final photo page (separate screen). Photo required before submission. */
    function goToFinalPhoto() {
        flushSavePending();
        if (finalPhotoUrl) {
            window.location.href = finalPhotoUrl;
        }
    }

    function submitQuiz(doPostFace) {
        if (doPostFace) {
            goToFinalPhoto();
        } else {
            goToFinalPhoto();
        }
    }

    if (timerEl && remainingSeconds > 0) {
        var text = formatTime(remainingSeconds);
        timerEl.textContent = text;
        if (timerStickyEl) timerStickyEl.textContent = text;
        applyTimerColor(remainingSeconds);
        timerInterval = setInterval(updateTimer, 1000);
        if (timeSyncUrl) {
            timeSyncInterval = setInterval(syncTimeFromServer, TIME_SYNC_INTERVAL_MS);
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') syncTimeFromServer();
            });
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) syncTimeFromServer();
            });
        }
    }

    if (quizForm) {
        window.addEventListener('beforeunload', function () { flushSavePending(); });
        quizForm.querySelectorAll('input[type="radio"], textarea').forEach(function (el) {
            const questionId = el.dataset.questionId || (el.name && el.name.replace('q_', ''));
            const getVal = function () {
                if (el.type === 'radio') {
                    const r = quizForm.querySelector('input[name="' + el.name + '"]:checked');
                    return r ? r.value : '';
                }
                return el.value;
            };
            el.addEventListener('change', function () {
                saveAnswer(questionId, getVal());
            });
            el.addEventListener('blur', function () {
                if (el.type !== 'radio') saveAnswer(questionId, getVal());
            });
        });
    }

    function onBlurOrTabSwitch() {
        blurCount++;
        if (blurWarning) {
            blurWarning.classList.remove('hidden');
        }
        recordViolation('blur');
    }

    window.addEventListener('blur', onBlurOrTabSwitch);
    window.addEventListener('focus', function () {
        if (blurWarning) blurWarning.classList.add('hidden');
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            onBlurOrTabSwitch();
        }
    });

    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
        recordViolation('right_click');
        if (window.QuizSnapQuiz && window.QuizSnapQuiz.showRightClickWarning) {
            window.QuizSnapQuiz.showRightClickWarning();
        } else {
            alert('Do not right-click. Stay on this tab.');
        }
    });
    document.addEventListener('copy', function (e) {
        e.preventDefault();
        recordViolation('copy_paste');
    });
    document.addEventListener('cut', function (e) {
        e.preventDefault();
        recordViolation('copy_paste');
    });
    document.addEventListener('paste', function (e) {
        e.preventDefault();
        recordViolation('copy_paste');
    });

    if (postFaceBtn) {
        postFaceBtn.addEventListener('click', function () {
            goToFinalPhoto();
        });
    }
})();
