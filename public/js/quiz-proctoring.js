/**
 * StudentQuiz: Timer, auto-save, tab/window blur detection, optional post-quiz face.
 * ProctoringEnforcer: blur → warning/auto-submit; disable copy-paste, right-click.
 */
(function () {
    const c = window.QuizSnapQuiz || {};
    const saveAnswerUrl = c.saveAnswerUrl;
    const saveAnswersBatchUrl = c.saveAnswersBatchUrl;
    const violationUrl = c.violationUrl;
    const heartbeatUrl = c.heartbeatUrl;
    const finalPhotoUrl = c.finalPhotoUrl;
    const timeSyncUrl = c.timeSyncUrl;
    const csrfToken = c.csrfToken;
    let remainingSeconds = c.remainingSeconds || 0;
    let endTimeMs = null;
    let timerInterval = null;
    let timeSyncInterval = null;
    let blurCount = 0;
    const TIME_SYNC_INTERVAL_MS = 30000;

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
        if (endTimeMs !== null) {
            remainingSeconds = Math.max(0, Math.ceil((endTimeMs - Date.now()) / 1000));
        } else {
            remainingSeconds = Math.max(0, remainingSeconds - 1);
        }
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
    }

    function syncTimeFromServer() {
        if (!timeSyncUrl || remainingSeconds <= 0) return;
        fetch(timeSyncUrl, { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && typeof data.remaining_seconds === 'number') {
                    remainingSeconds = Math.max(0, data.remaining_seconds);
                    endTimeMs = Date.now() + remainingSeconds * 1000;
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

    function recordViolation(type, metadata) {
        var body = { type: type };
        if (metadata) body.metadata = typeof metadata === 'string' ? metadata : JSON.stringify(metadata);
        fetch(violationUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.auto_submitted && data.redirect) {
                    window.location.href = data.redirect;
                }
                if (type === 'window_resize' && data && data.next_violation_auto_submits) {
                    if (window.QuizSnapQuiz && window.QuizSnapQuiz.showResizeFinalWarning) {
                        window.QuizSnapQuiz.showResizeFinalWarning();
                    }
                }
            })
            .catch(function () {});
    }

    /** Redirect to final photo page (separate screen). Photo required before submission. */
    function goToFinalPhoto() {
        if (window.QuizSnapQuiz) window.QuizSnapQuiz.navigatingToFinalPhoto = true;
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
        endTimeMs = Date.now() + remainingSeconds * 1000;
        var text = formatTime(remainingSeconds);
        timerEl.textContent = text;
        if (timerStickyEl) timerStickyEl.textContent = text;
        applyTimerColor(remainingSeconds);
        timerInterval = setInterval(updateTimer, 1000);
        if (timeSyncUrl) {
            syncTimeFromServer();
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
        window.addEventListener('beforeunload', function (e) {
            if (window.QuizSnapQuiz && window.QuizSnapQuiz.navigatingToFinalPhoto) {
                return;
            }
            flushSavePending();
            e.preventDefault();
            e.returnValue = '';
        });
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
        sendHeartbeat();
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            onBlurOrTabSwitch();
        } else {
            sendHeartbeat();
        }
    });

    function sendHeartbeat() {
        if (!heartbeatUrl) return;
        fetch(heartbeatUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            body: JSON.stringify({}),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.show_tab_switch_warning && window.QuizSnapQuiz && window.QuizSnapQuiz.showTabSwitchWarning) {
                    window.QuizSnapQuiz.showTabSwitchWarning();
                }
            })
            .catch(function () {});
    }

    (function () {
        var NEW_TAB_ZONE_PX = 80;
        var showDelay = null;
        document.addEventListener('mousemove', function (e) {
            if (e.clientY < NEW_TAB_ZONE_PX) {
                if (showDelay) return;
                showDelay = setTimeout(function () {
                    showDelay = null;
                    if (window.QuizSnapQuiz && window.QuizSnapQuiz.showNewTabZoneWarning) {
                        window.QuizSnapQuiz.showNewTabZoneWarning();
                    }
                }, 400);
            } else {
                if (showDelay) {
                    clearTimeout(showDelay);
                    showDelay = null;
                }
                if (window.QuizSnapQuiz && window.QuizSnapQuiz.hideNewTabZoneWarning) {
                    window.QuizSnapQuiz.hideNewTabZoneWarning();
                }
            }
        });
    })();

    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
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

    // --- Window / fullscreen enforcement (resize, exit fullscreen) ---
    var resizeBlurOverlay = document.getElementById('resize-blur-overlay');
    var resizeBlurWarning = document.getElementById('resize-blur-warning');
    var resizeBlurFinalWarning = document.getElementById('resize-blur-final-warning');
    var windowResizeLimit = (window.QuizSnapQuiz && window.QuizSnapQuiz.windowResizeLimit) || 3;
    var wasFullscreenOrMaximized = (function () {
        if (document.fullscreenElement || document.webkitFullscreenElement) return true;
        var margin = 50;
        var w = window.outerWidth || window.innerWidth;
        var h = window.outerHeight || window.innerHeight;
        var availW = (window.screen && window.screen.availWidth) || w;
        var availH = (window.screen && window.screen.availHeight) || h;
        return (w >= availW - margin && h >= availH - margin);
    })();
    var resizeDebounceTimer = null;
    var RESIZE_DEBOUNCE_MS = 600;

    function isFullscreenOrMaximized() {
        if (document.fullscreenElement || document.webkitFullscreenElement) return true;
        var margin = 50;
        var w = window.outerWidth || window.innerWidth;
        var h = window.outerHeight || window.innerHeight;
        var availW = (window.screen && window.screen.availWidth) || w;
        var availH = (window.screen && window.screen.availHeight) || h;
        return (w >= availW - margin && h >= availH - margin);
    }

    function showResizeBlur(showFinalWarning) {
        if (!resizeBlurOverlay) return;
        resizeBlurOverlay.classList.remove('hidden');
        resizeBlurOverlay.setAttribute('aria-hidden', 'false');
        if (resizeBlurWarning) {
            resizeBlurWarning.classList.remove('hidden');
            resizeBlurWarning.textContent = 'Repeated violations will result in auto-submission of your quiz.';
        }
        if (resizeBlurFinalWarning) {
            if (showFinalWarning) {
                resizeBlurFinalWarning.classList.remove('hidden');
            } else {
                resizeBlurFinalWarning.classList.add('hidden');
            }
        }
    }

    function hideResizeBlur() {
        if (!resizeBlurOverlay) return;
        resizeBlurOverlay.classList.add('hidden');
        resizeBlurOverlay.setAttribute('aria-hidden', 'true');
        if (resizeBlurWarning) resizeBlurWarning.classList.add('hidden');
        if (resizeBlurFinalWarning) resizeBlurFinalWarning.classList.add('hidden');
    }

    function onWindowResizeOrExitFullscreen() {
        if (remainingSeconds <= 0) return;
        if (!wasFullscreenOrMaximized) return;
        wasFullscreenOrMaximized = false;
        showResizeBlur(false);
        var timestamp = new Date().toISOString();
        recordViolation('window_resize', { timestamp: timestamp });
        if (resizeBlurWarning) resizeBlurWarning.classList.remove('hidden');
    }

    function checkWindowState() {
        if (remainingSeconds <= 0) return;
        var nowOk = isFullscreenOrMaximized();
        if (nowOk) {
            wasFullscreenOrMaximized = true;
            hideResizeBlur();
        }
    }

    if (resizeBlurOverlay) {
        document.addEventListener('keydown', function (e) {
            if (resizeBlurOverlay && !resizeBlurOverlay.classList.contains('hidden')) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
        document.addEventListener('keypress', function (e) {
            if (resizeBlurOverlay && !resizeBlurOverlay.classList.contains('hidden')) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    }

    function handleResizeOrFullscreenChange() {
        if (remainingSeconds <= 0) return;
        if (isFullscreenOrMaximized()) {
            wasFullscreenOrMaximized = true;
            hideResizeBlur();
        } else {
            if (resizeDebounceTimer) clearTimeout(resizeDebounceTimer);
            resizeDebounceTimer = setTimeout(function () {
                resizeDebounceTimer = null;
                onWindowResizeOrExitFullscreen();
            }, RESIZE_DEBOUNCE_MS);
        }
    }

    window.addEventListener('resize', handleResizeOrFullscreenChange);
    document.addEventListener('fullscreenchange', handleResizeOrFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleResizeOrFullscreenChange);

    setInterval(checkWindowState, 800);
})();
