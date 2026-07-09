(function () {
  var AUDIO_PROGRESS_KEY = "betweenWordsAudioProgress";
  var FONT_SCALE_KEY = "betweenWordsFontScale";
  var FONT_SCALE_MIN = 0.9;
  var FONT_SCALE_MAX = 1.2;
  var FONT_SCALE_STEP = 0.05;
  var FONT_SCALE_DEFAULT = 1;
  var runtimeBodyClasses = ["bw-drawer-open", "bw-overlay-open", "bw-audio-sticky-active"];
  var waveformCache = new Map();
  var playerStates = [];
  var playerStateMap = new WeakMap();
  var waveformObserver = null;
  var searchOverlay = null;
  var searchInput = null;
  var drawer = null;
  var drawerToggle = null;
  var drawerLinks = [];
  var activePlayerState = null;
  var persistentShell = null;
  var isNavigating = false;
  var resumeStoreCache = null;

  function formatTime(seconds) {
    if (!Number.isFinite(seconds) || seconds < 0) {
      return "00:00";
    }

    var totalSeconds = Math.floor(seconds);
    var hours = Math.floor(totalSeconds / 3600);
    var minutes = Math.floor((totalSeconds % 3600) / 60);
    var remainingSeconds = totalSeconds % 60;

    if (hours > 0) {
      return String(hours).padStart(2, "0") + ":" + String(minutes).padStart(2, "0") + ":" + String(remainingSeconds).padStart(2, "0");
    }

    return String(minutes).padStart(2, "0") + ":" + String(remainingSeconds).padStart(2, "0");
  }

  function hashString(value) {
    var hash = 2166136261;
    var index;

    for (index = 0; index < value.length; index += 1) {
      hash ^= value.charCodeAt(index);
      hash += (hash << 1) + (hash << 4) + (hash << 7) + (hash << 8) + (hash << 24);
    }

    return hash >>> 0;
  }

  function seededWaveform(seed, count) {
    var values = [];
    var state = seed || 1;
    var index;

    for (index = 0; index < count; index += 1) {
      state = (state * 1664525 + 1013904223) >>> 0;
      var noise = state / 4294967295;
      var envelope = 0.45 + Math.sin((index / Math.max(1, count - 1)) * Math.PI) * 0.4;
      var ripple = Math.abs(Math.sin((index + (seed % 17)) * 0.37)) * 0.28;
      values.push(Math.max(0.14, Math.min(1, envelope * (0.42 + noise * 0.42 + ripple))));
    }

    return values;
  }

  function safeLocalStorageGet(key) {
    try {
      return window.localStorage.getItem(key);
    } catch (error) {
      return null;
    }
  }

  function safeLocalStorageSet(key, value) {
    try {
      window.localStorage.setItem(key, value);
    } catch (error) {
      return false;
    }

    return true;
  }

  function safeLocalStorageRemove(key) {
    try {
      window.localStorage.removeItem(key);
    } catch (error) {
      return false;
    }

    return true;
  }

  function getResumeStore() {
    if (resumeStoreCache !== null) {
      return resumeStoreCache;
    }

    var raw = safeLocalStorageGet(AUDIO_PROGRESS_KEY);
    if (!raw) {
      resumeStoreCache = {};
      return resumeStoreCache;
    }

    try {
      resumeStoreCache = JSON.parse(raw) || {};
    } catch (error) {
      resumeStoreCache = {};
    }

    return resumeStoreCache;
  }

  function saveResumeStore() {
    safeLocalStorageSet(AUDIO_PROGRESS_KEY, JSON.stringify(getResumeStore()));
  }

  function getResumeKey(state) {
    if (!state || !state.audio) {
      return "";
    }

    return state.audio.currentSrc || state.audio.src || state.player.getAttribute("data-post-id") || "";
  }

  function getResumeInfo(state) {
    var key = getResumeKey(state);
    if (!key) {
      return null;
    }

    return getResumeStore()[key] || null;
  }

  function clearResumeInfo(state) {
    var key = getResumeKey(state);
    var store = getResumeStore();

    if (!key || !store[key]) {
      return;
    }

    delete store[key];
    saveResumeStore();
  }

  function persistResume(state, forceCompleted) {
    if (!state || !state.audio) {
      return;
    }

    var duration = state.audio.duration;
    var currentTime = state.audio.currentTime || 0;
    var key = getResumeKey(state);

    if (!key || !Number.isFinite(currentTime)) {
      return;
    }

    if (forceCompleted || (Number.isFinite(duration) && duration > 0 && currentTime / duration >= 0.95)) {
      var completeStore = getResumeStore();
      completeStore[key] = {
        src: key,
        title: state.player.getAttribute("data-audio-title") || document.title,
        currentTime: 0,
        duration: Number.isFinite(duration) ? duration : 0,
        updatedAt: Date.now(),
        completed: true
      };
      saveResumeStore();
      state.resumeInfo = completeStore[key];
      updateResumeUI(state);
      return;
    }

    if (currentTime < 5) {
      clearResumeInfo(state);
      state.resumeInfo = null;
      updateResumeUI(state);
      return;
    }

    var store = getResumeStore();
    store[key] = {
      src: key,
      title: state.player.getAttribute("data-audio-title") || document.title,
      currentTime: currentTime,
      duration: Number.isFinite(duration) ? duration : 0,
      updatedAt: Date.now(),
      completed: false
    };
    saveResumeStore();
    state.resumeInfo = store[key];
    updateResumeUI(state);
  }

  function maybePersistResume(state) {
    if (!state) {
      return;
    }

    var now = Date.now();
    if (state.lastPersistAt && now - state.lastPersistAt < 5000) {
      return;
    }

    state.lastPersistAt = now;
    persistResume(state, false);
  }

  function getWaveformValues(state) {
    var canvas = state.canvas;
    var audio = state.audio;
    var src = audio ? audio.currentSrc || audio.src || "" : "";
    var rect = state.progress ? state.progress.getBoundingClientRect() : canvas.getBoundingClientRect();
    var count = Math.max(48, Math.min(140, Math.floor(rect.width / 4)));
    var key = src + ":" + count;

    if (!waveformCache.has(key)) {
      waveformCache.set(key, seededWaveform(hashString(key), count));
    }

    return waveformCache.get(key);
  }

  function queueWaveformDraw(state) {
    if (!state || !state.waveformReady || state.drawQueued) {
      return;
    }

    state.drawQueued = true;
    window.requestAnimationFrame(function () {
      state.drawQueued = false;
      drawWaveform(state);
    });
  }

  function drawWaveform(state) {
    if (!state || !state.canvas || !state.canvas.isConnected) {
      return;
    }

    var canvas = state.canvas;
    var player = state.player;
    var rect = canvas.getBoundingClientRect();
    var width = Math.max(1, Math.floor(rect.width));
    var height = Math.max(1, Math.floor(rect.height));
    var dpr = window.devicePixelRatio || 1;
    var pixelWidth = Math.floor(width * dpr);
    var pixelHeight = Math.floor(height * dpr);

    if (canvas.width !== pixelWidth || canvas.height !== pixelHeight) {
      canvas.width = pixelWidth;
      canvas.height = pixelHeight;
    }

    if (!state.ctx) {
      state.ctx = canvas.getContext("2d");
    }

    var ctx = state.ctx;
    var values = getWaveformValues(state);
    var ratio = Math.max(0, Math.min(1, state.ratio || 0));
    var barGap = 2 * dpr;
    var barWidth = Math.max(1.25 * dpr, Math.floor((canvas.width - (values.length - 1) * barGap) / values.length));
    var center = canvas.height / 2;
    var maxHeight = canvas.height * (player.classList.contains("podcast-player--single") ? 0.76 : 0.9);
    var activeLimit = ratio * values.length;
    var activeColor = document.documentElement.classList.contains("bw-theme-dark") ? "#f3f3f3" : "#111111";
    var inactiveColor = document.documentElement.classList.contains("bw-theme-dark") ? "#686868" : "#9a9a9a";

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    values.forEach(function (value, index) {
      var x = index * (barWidth + barGap);
      var barHeight = Math.max(3 * dpr, value * maxHeight);
      var y = center - barHeight / 2;
      var radius = Math.min(barWidth / 2, 2 * dpr);

      ctx.fillStyle = index <= activeLimit ? activeColor : inactiveColor;
      ctx.beginPath();
      ctx.moveTo(x + radius, y);
      ctx.lineTo(x + barWidth - radius, y);
      ctx.quadraticCurveTo(x + barWidth, y, x + barWidth, y + radius);
      ctx.lineTo(x + barWidth, y + barHeight - radius);
      ctx.quadraticCurveTo(x + barWidth, y + barHeight, x + barWidth - radius, y + barHeight);
      ctx.lineTo(x + radius, y + barHeight);
      ctx.quadraticCurveTo(x, y + barHeight, x, y + barHeight - radius);
      ctx.lineTo(x, y + radius);
      ctx.quadraticCurveTo(x, y, x + radius, y);
      ctx.fill();
    });
  }

  function setTime(state, currentTime, duration) {
    if (!state.timeNode) {
      return;
    }

    var hasDuration = Number.isFinite(duration) && duration > 0;
    var current = formatTime(currentTime || 0);

    if (hasDuration) {
      state.timeNode.textContent = current + " / " + formatTime(duration);
      return;
    }

    state.timeNode.textContent = currentTime > 0 ? current : state.initialTime;
  }

  function setProgress(state, ratio) {
    var safeRatio = Math.max(0, Math.min(1, ratio || 0));
    var percent = safeRatio * 100;

    state.ratio = safeRatio;

    if (state.progress) {
      state.progress.setAttribute("aria-valuenow", String(Math.round(percent)));
    }

    if (state.fill) {
      state.fill.style.width = percent + "%";
    }

    queueWaveformDraw(state);
  }

  function updateResumeUI(state) {
    if (!state || !state.resumeNode) {
      return;
    }

    var info = state.resumeInfo || getResumeInfo(state);
    var hasResume = info && !info.completed && Number.isFinite(info.currentTime) && info.currentTime >= 5;

    if (!hasResume) {
      state.resumeNode.hidden = true;
      state.resumeNode.textContent = "";
      return;
    }

    var template = state.player.getAttribute("data-resume-template") || "Resume from %s";
    state.resumeNode.hidden = false;
    state.resumeNode.textContent = template.replace("%s", formatTime(info.currentTime));
  }

  function pauseOtherPlayers(currentAudio) {
    playerStates.forEach(function (state) {
      if (state.audio && state.audio !== currentAudio) {
        state.audio.pause();
      }
    });
  }

  function seekFromPointer(state, clientX) {
    if (!state.audio || !state.progress || !Number.isFinite(state.audio.duration) || state.audio.duration <= 0) {
      return;
    }

    var rect = state.progress.getBoundingClientRect();
    if (!rect.width) {
      return;
    }

    var safeRatio = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
    state.audio.currentTime = state.audio.duration * safeRatio;
    setProgress(state, safeRatio);
    setTime(state, state.audio.currentTime, state.audio.duration);
    maybePersistResume(state);
  }

  function isMobilePlayer() {
    return window.matchMedia("(max-width: 768px)").matches;
  }

  function clearStickyPlayers() {
    playerStates.forEach(function (state) {
      if (state.player && state.player.isConnected) {
        state.player.classList.remove("is-mobile-sticky");
      }
    });
    document.body.classList.remove("bw-audio-sticky-active");
  }

  function setStickyPlayer(state) {
    if (!isMobilePlayer() || !state || !state.player || !state.player.isConnected) {
      return;
    }

    clearStickyPlayers();
    state.player.classList.add("is-mobile-sticky");
    document.body.classList.add("bw-audio-sticky-active");
  }

  function ensurePersistentShell() {
    if (persistentShell) {
      return persistentShell;
    }

    persistentShell = document.createElement("div");
    persistentShell.className = "bw-persistent-audio-shell";
    persistentShell.hidden = true;
    document.body.appendChild(persistentShell);

    return persistentShell;
  }

  function hidePersistentShell() {
    if (!persistentShell) {
      return;
    }

    persistentShell.hidden = true;
    persistentShell.classList.remove("is-visible");

    if (activePlayerState && activePlayerState.player) {
      activePlayerState.player.classList.remove("is-persistent");
    }
  }

  function moveActivePlayerToShell() {
    if (!activePlayerState || !activePlayerState.audio || activePlayerState.audio.paused) {
      hidePersistentShell();
      return;
    }

    var shell = ensurePersistentShell();
    if (!activePlayerState.player || !activePlayerState.player.isConnected) {
      return;
    }

    clearStickyPlayers();
    shell.appendChild(activePlayerState.player);
    activePlayerState.player.classList.add("is-persistent");
    shell.hidden = false;
    shell.classList.add("is-visible");
    queueWaveformDraw(activePlayerState);
  }

  function openSearch() {
    if (!searchOverlay) {
      return;
    }

    searchOverlay.classList.add("is-open");
    searchOverlay.setAttribute("aria-hidden", "false");
    document.body.classList.add("bw-overlay-open");

    if (searchInput) {
      window.setTimeout(function () {
        searchInput.focus();
      }, 40);
    }
  }

  function closeSearch() {
    if (!searchOverlay) {
      return;
    }

    searchOverlay.classList.remove("is-open");
    searchOverlay.setAttribute("aria-hidden", "true");
    document.body.classList.remove("bw-overlay-open");
  }

  function openDrawer() {
    if (!drawer) {
      return;
    }

    drawer.classList.add("is-open");
    document.body.classList.add("bw-drawer-open");

    if (drawerToggle) {
      drawerToggle.setAttribute("aria-expanded", "true");
    }
  }

  function closeDrawer() {
    if (!drawer) {
      return;
    }

    drawer.classList.remove("is-open");
    document.body.classList.remove("bw-drawer-open");

    if (drawerToggle) {
      drawerToggle.setAttribute("aria-expanded", "false");
    }
  }

  function clampFontScale(value) {
    return Math.min(FONT_SCALE_MAX, Math.max(FONT_SCALE_MIN, Math.round(value * 100) / 100));
  }

  function getStoredFontScale() {
    var raw = safeLocalStorageGet(FONT_SCALE_KEY);
    var value = raw ? parseFloat(raw) : FONT_SCALE_DEFAULT;

    if (!Number.isFinite(value)) {
      return FONT_SCALE_DEFAULT;
    }

    return clampFontScale(value);
  }

  function applyFontScale(scale) {
    var safeScale = clampFontScale(scale);
    document.documentElement.style.setProperty("--bw-user-font-scale", String(safeScale));
    document.documentElement.classList.toggle("bw-font-scale-custom", safeScale !== FONT_SCALE_DEFAULT);

    document.querySelectorAll("[data-font-scale='decrease']").forEach(function (button) {
      button.disabled = safeScale <= FONT_SCALE_MIN;
    });
    document.querySelectorAll("[data-font-scale='increase']").forEach(function (button) {
      button.disabled = safeScale >= FONT_SCALE_MAX;
    });
    document.querySelectorAll("[data-font-scale='reset']").forEach(function (button) {
      button.classList.toggle("is-active", safeScale === FONT_SCALE_DEFAULT);
    });
  }

  function updateFontScale(action) {
    var current = getStoredFontScale();
    var next = current;

    if (action === "increase") {
      next = clampFontScale(current + FONT_SCALE_STEP);
    } else if (action === "decrease") {
      next = clampFontScale(current - FONT_SCALE_STEP);
    } else {
      next = FONT_SCALE_DEFAULT;
    }

    safeLocalStorageSet(FONT_SCALE_KEY, String(next));
    applyFontScale(next);
  }

  function applyModes() {
    var storedTheme = safeLocalStorageGet("betweenWordsTheme");
    var theme = storedTheme || (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
    var reader = safeLocalStorageGet("betweenWordsReader") === "1";
    var focus = safeLocalStorageGet("betweenWordsFocus") === "1";
    var root = document.documentElement;
    var displayMode = reader ? "reader" : theme;

    root.classList.toggle("bw-theme-dark", theme === "dark");
    root.classList.toggle("bw-theme-light", theme !== "dark");
    root.classList.toggle("bw-reader-mode", reader);
    root.classList.toggle("bw-focus-mode", focus);
    root.setAttribute("data-bw-display-mode", displayMode);

    document.querySelectorAll("[data-theme-mode]").forEach(function (button) {
      button.classList.toggle("is-active", !reader && button.getAttribute("data-theme-mode") === theme);
    });
    document.querySelectorAll("[data-theme-toggle='reader']").forEach(function (button) {
      button.classList.toggle("is-active", reader);
    });
    document.querySelectorAll("[data-theme-toggle='focus']").forEach(function (button) {
      button.classList.toggle("is-active", focus);
    });
    document.querySelectorAll("[data-focus-toggle]").forEach(function (button) {
      button.classList.toggle("is-active", focus);
      button.setAttribute("aria-label", focus ? (button.getAttribute("data-exit-label") || "Exit focus mode") : (button.getAttribute("data-focus-label") || "Focus mode"));
    });
    document.querySelectorAll("[data-theme-cycle]").forEach(function (button) {
      button.setAttribute("data-current-mode", displayMode);
    });

    playerStates.forEach(function (state) {
      setProgress(state, state.audio && state.audio.duration ? state.audio.currentTime / state.audio.duration : state.ratio || 0);
    });
  }

  function initWaveformObserver() {
    if (waveformObserver) {
      waveformObserver.disconnect();
    }

    if (!("IntersectionObserver" in window)) {
      playerStates.forEach(function (state) {
        state.waveformReady = true;
        queueWaveformDraw(state);
      });
      return;
    }

    waveformObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }

        var player = entry.target.closest("[data-audio-player]");
        var state = player ? playerStateMap.get(player) : null;
        if (!state) {
          return;
        }

        state.waveformReady = true;
        queueWaveformDraw(state);
        waveformObserver.unobserve(entry.target);
      });
    }, {
      rootMargin: "240px 0px"
    });

    playerStates.forEach(function (state) {
      if (state.canvas && state.canvas.isConnected) {
        waveformObserver.observe(state.canvas);
      }
    });
  }

  function setActivePlayerState(state) {
    if (activePlayerState === state) {
      return;
    }

    if (activePlayerState && activePlayerState.player && activePlayerState.player !== state.player) {
      activePlayerState.player.classList.remove("bw-active-audio-player");
    }

    activePlayerState = state;

    if (activePlayerState && activePlayerState.player) {
      activePlayerState.player.classList.add("bw-active-audio-player");
    }
  }

  function syncStateFromAudio(state) {
    if (!state || !state.audio) {
      return;
    }

    state.resumeInfo = getResumeInfo(state);

    if (state.resumeInfo && !state.resumeInfo.completed && Number.isFinite(state.resumeInfo.currentTime) && state.resumeInfo.currentTime >= 5) {
      if (Number.isFinite(state.audio.duration) && state.audio.duration > 0) {
        state.audio.currentTime = Math.min(state.resumeInfo.currentTime, Math.max(0, state.audio.duration - 1));
      } else {
        state.pendingResumeTime = state.resumeInfo.currentTime;
      }

      setProgress(state, state.resumeInfo.duration ? state.resumeInfo.currentTime / state.resumeInfo.duration : 0);
      setTime(state, state.resumeInfo.currentTime, state.resumeInfo.duration);
    }

    updateResumeUI(state);
  }

  function bindPlayerEvents(state) {
    if (!state.audio) {
      if (state.timeNode) {
        state.timeNode.textContent = state.initialTime;
      }
      setProgress(state, 0);
      return;
    }

    if (state.toggle) {
      state.toggle.classList.toggle("is-disabled", false);
      state.toggle.addEventListener("click", function () {
        if (!state.audio.getAttribute("src")) {
          return;
        }

        if (state.audio.paused) {
          if (state.resumeInfo && !state.resumeInfo.completed && Number.isFinite(state.resumeInfo.currentTime) && state.resumeInfo.currentTime >= 5 && state.audio.currentTime < 1) {
            state.audio.currentTime = state.resumeInfo.currentTime;
          }
          pauseOtherPlayers(state.audio);
          state.audio.play().catch(function () {});
        } else {
          state.audio.pause();
        }
      });
    }

    state.audio.addEventListener("loadedmetadata", function () {
      if (Number.isFinite(state.pendingResumeTime) && state.pendingResumeTime >= 5) {
        state.audio.currentTime = Math.min(state.pendingResumeTime, Math.max(0, state.audio.duration - 1));
        state.pendingResumeTime = null;
      }
      setTime(state, state.audio.currentTime, state.audio.duration);
      setProgress(state, state.audio.duration ? state.audio.currentTime / state.audio.duration : state.ratio || 0);
      updateResumeUI(state);
    });

    state.audio.addEventListener("play", function () {
      pauseOtherPlayers(state.audio);
      setActivePlayerState(state);
      state.player.classList.add("is-playing");
      if (persistentShell && persistentShell.contains(state.player)) {
        persistentShell.hidden = false;
        persistentShell.classList.add("is-visible");
      } else {
        hidePersistentShell();
      }
      setStickyPlayer(state);
      if (state.toggle) {
        state.toggle.classList.add("is-playing");
      }
    });

    state.audio.addEventListener("pause", function () {
      state.player.classList.remove("is-playing");
      if (state.toggle) {
        state.toggle.classList.remove("is-playing");
      }
      if (state.player.classList.contains("is-mobile-sticky")) {
        clearStickyPlayers();
      }
      if (persistentShell && persistentShell.contains(state.player)) {
        hidePersistentShell();
      }
      persistResume(state, false);
    });

    state.audio.addEventListener("timeupdate", function () {
      if (state.timeFrame) {
        return;
      }

      state.timeFrame = window.requestAnimationFrame(function () {
        state.timeFrame = 0;
        setTime(state, state.audio.currentTime, state.audio.duration);
        setProgress(state, state.audio.duration ? state.audio.currentTime / state.audio.duration : 0);
        maybePersistResume(state);
      });
    });

    state.audio.addEventListener("ended", function () {
      setProgress(state, 0);
      setTime(state, 0, state.audio.duration);
      clearStickyPlayers();
      persistResume(state, true);
      if (persistentShell && persistentShell.contains(state.player)) {
        hidePersistentShell();
      }
    });

    if (state.progress) {
      state.progress.addEventListener("click", function (event) {
        seekFromPointer(state, event.clientX);
      });

      state.progress.addEventListener("keydown", function (event) {
        if (!Number.isFinite(state.audio.duration) || state.audio.duration <= 0) {
          return;
        }

        var step = Math.max(5, state.audio.duration * 0.02);
        if (event.key === "ArrowLeft") {
          event.preventDefault();
          state.audio.currentTime = Math.max(0, state.audio.currentTime - step);
          persistResume(state, false);
        }

        if (event.key === "ArrowRight") {
          event.preventDefault();
          state.audio.currentTime = Math.min(state.audio.duration, state.audio.currentTime + step);
          persistResume(state, false);
        }
      });
    }

    syncStateFromAudio(state);
  }

  function initPlayer(player) {
    if (!player || player.dataset.bwPlayerBound === "1") {
      return;
    }

    var state = {
      player: player,
      audio: player.querySelector(".podcast-audio"),
      toggle: player.querySelector("[data-audio-toggle]"),
      progress: player.querySelector("[data-audio-progress]"),
      fill: player.querySelector("[data-audio-progress-fill]"),
      timeNode: player.querySelector("[data-audio-time]"),
      resumeNode: player.querySelector("[data-audio-resume]"),
      canvas: player.querySelector("[data-audio-waveform]"),
      initialTime: player.getAttribute("data-initial-time") || "00:00",
      ratio: 0,
      drawQueued: false,
      waveformReady: false,
      timeFrame: 0,
      lastPersistAt: 0,
      pendingResumeTime: null,
      resumeInfo: null
    };

    player.dataset.bwPlayerBound = "1";
    playerStates.push(state);
    playerStateMap.set(player, state);

    if (state.toggle) {
      state.toggle.classList.toggle("is-disabled", !state.audio);
    }

    setProgress(state, 0);
    setTime(state, 0, NaN);
    bindPlayerEvents(state);
  }

  function initPlayers(scope) {
    (scope || document).querySelectorAll("[data-audio-player]").forEach(initPlayer);
    initWaveformObserver();
  }

  function onResize() {
    if (!isMobilePlayer()) {
      clearStickyPlayers();
    } else if (activePlayerState && activePlayerState.audio && !activePlayerState.audio.paused && activePlayerState.player && activePlayerState.player.isConnected) {
      setStickyPlayer(activePlayerState);
    }

    playerStates.forEach(function (state) {
      if (state.audio && state.audio.duration) {
        setProgress(state, state.audio.currentTime / state.audio.duration);
      } else {
        queueWaveformDraw(state);
      }
    });
  }

  function bindSearchControls() {
    document.querySelectorAll("[data-search-open]").forEach(function (button) {
      if (button.dataset.bwBound === "1") {
        return;
      }

      button.dataset.bwBound = "1";
      button.addEventListener("click", openSearch);
    });

    document.querySelectorAll("[data-search-close]").forEach(function (button) {
      if (button.dataset.bwBound === "1") {
        return;
      }

      button.dataset.bwBound = "1";
      button.addEventListener("click", closeSearch);
    });

    if (searchOverlay && searchOverlay.dataset.bwBound !== "1") {
      searchOverlay.dataset.bwBound = "1";
      searchOverlay.addEventListener("click", function (event) {
        if (!event.target.closest("[data-search-panel]")) {
          closeSearch();
        }
      });
    }
  }

  function bindDrawerControls() {
    document.querySelectorAll("[data-drawer-open]").forEach(function (button) {
      if (button.dataset.bwBound === "1") {
        return;
      }

      button.dataset.bwBound = "1";
      button.addEventListener("click", openDrawer);
    });

    document.querySelectorAll("[data-drawer-close], [data-drawer-backdrop]").forEach(function (button) {
      if (button.dataset.bwBound === "1") {
        return;
      }

      button.dataset.bwBound = "1";
      button.addEventListener("click", closeDrawer);
    });

    drawerLinks.forEach(function (link) {
      if (link.dataset.bwBound === "1") {
        return;
      }

      link.dataset.bwBound = "1";
      link.addEventListener("click", closeDrawer);
    });
  }

  function bindModeControls() {
    document.querySelectorAll("[data-theme-mode]").forEach(function (button) {
      if (button.dataset.bwBound === "1") {
        return;
      }

      button.dataset.bwBound = "1";
      button.addEventListener("click", function () {
        safeLocalStorageSet("betweenWordsTheme", button.getAttribute("data-theme-mode") || "light");
        safeLocalStorageSet("betweenWordsReader", "0");
        applyModes();
      });
    });

    document.querySelectorAll("[data-theme-toggle]").forEach(function (button) {
      if (button.dataset.bwBound === "1") {
        return;
      }

      button.dataset.bwBound = "1";
      button.addEventListener("click", function () {
        var key = button.getAttribute("data-theme-toggle") === "focus" ? "betweenWordsFocus" : "betweenWordsReader";
        safeLocalStorageSet(key, safeLocalStorageGet(key) === "1" ? "0" : "1");
        applyModes();
        closeDrawer();
      });
    });

    document.querySelectorAll("[data-theme-cycle]").forEach(function (button) {
      if (button.dataset.bwBound === "1") {
        return;
      }

      button.dataset.bwBound = "1";
      button.addEventListener("click", function () {
        var reader = safeLocalStorageGet("betweenWordsReader") === "1";
        var theme = safeLocalStorageGet("betweenWordsTheme") || "light";
        var current = reader ? "reader" : theme;
        var next = current === "light" ? "dark" : current === "dark" ? "reader" : "light";

        if (next === "reader") {
          safeLocalStorageSet("betweenWordsTheme", "light");
          safeLocalStorageSet("betweenWordsReader", "1");
        } else {
          safeLocalStorageSet("betweenWordsTheme", next);
          safeLocalStorageSet("betweenWordsReader", "0");
        }

        applyModes();
      });
    });

    document.querySelectorAll("[data-focus-toggle]").forEach(function (button) {
      if (button.dataset.bwBound === "1") {
        return;
      }

      button.dataset.bwBound = "1";
      button.addEventListener("click", function () {
        safeLocalStorageSet("betweenWordsFocus", safeLocalStorageGet("betweenWordsFocus") === "1" ? "0" : "1");
        applyModes();
      });
    });
  }

  function bindFontControls() {
    document.querySelectorAll("[data-font-scale]").forEach(function (button) {
      if (button.dataset.bwBound === "1") {
        return;
      }

      button.dataset.bwBound = "1";
      button.addEventListener("click", function () {
        updateFontScale(button.getAttribute("data-font-scale"));
      });
    });
  }

  function bindShareButtons(scope) {
    (scope || document).querySelectorAll("[data-share-button]").forEach(function (button) {
      if (button.dataset.bwBound === "1") {
        return;
      }

      button.dataset.bwBound = "1";
      button.addEventListener("click", function () {
        var title = button.getAttribute("data-share-title") || document.title;
        var url = button.getAttribute("data-share-url") || window.location.href;
        var feedback = button.parentElement ? button.parentElement.querySelector("[data-share-feedback]") : null;
        var showFeedback = function () {
          if (!feedback) {
            return;
          }
          feedback.classList.add("is-visible");
          window.setTimeout(function () {
            feedback.classList.remove("is-visible");
          }, 1600);
        };

        if (navigator.share) {
          navigator.share({ title: title, url: url }).catch(function () {});
          return;
        }

        if (navigator.clipboard) {
          navigator.clipboard.writeText(url).then(showFeedback).catch(showFeedback);
        } else {
          showFeedback();
        }
      });
    });
  }

  function bindGlobalUI() {
    searchOverlay = document.querySelector("[data-search-overlay]");
    searchInput = document.querySelector("[data-search-input]");
    drawer = document.querySelector("#site-sidebar");
    drawerToggle = document.querySelector("[data-drawer-open]");
    drawerLinks = Array.prototype.slice.call(document.querySelectorAll("#site-sidebar a"));

    bindSearchControls();
    bindDrawerControls();
    bindModeControls();
    bindFontControls();
    bindShareButtons(document);
  }

  function shouldInterceptLink(link, event) {
    if (!link || !activePlayerState || !activePlayerState.audio || activePlayerState.audio.paused) {
      return false;
    }

    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return false;
    }

    if (link.hasAttribute("download") || link.getAttribute("target") === "_blank" || link.hasAttribute("data-no-pjax")) {
      return false;
    }

    if (link.closest("#wpadminbar, .customize-partial-edit-shortcut, .comment-reply-link")) {
      return false;
    }

    var href = link.getAttribute("href");
    if (!href || href.charAt(0) === "#") {
      return false;
    }

    var url;

    try {
      url = new URL(link.href, window.location.href);
    } catch (error) {
      return false;
    }

    if (url.origin !== window.location.origin) {
      return false;
    }

    if (url.protocol !== "http:" && url.protocol !== "https:") {
      return false;
    }

    if (url.hash && url.pathname === window.location.pathname && url.search === window.location.search) {
      return false;
    }

    if (/\.(?:pdf|zip|rar|7z|mp3|m4a|ogg|wav|jpg|jpeg|png|gif|webp|svg)$/i.test(url.pathname)) {
      return false;
    }

    if (/\/(?:wp-admin|wp-login\.php)/i.test(url.pathname) || /customize\.php/i.test(url.pathname) || /logout/i.test(url.pathname)) {
      return false;
    }

    return true;
  }

  function replaceBodyClasses(nextBody) {
    if (!nextBody) {
      return;
    }

    var nextClasses = (nextBody.getAttribute("class") || "").split(/\s+/).filter(Boolean);
    runtimeBodyClasses.forEach(function (runtimeClass) {
      if (document.body.classList.contains(runtimeClass) && nextClasses.indexOf(runtimeClass) === -1) {
        nextClasses.push(runtimeClass);
      }
    });
    document.body.className = nextClasses.join(" ");
  }

  function reinitializeAfterNavigation(parsedDocument) {
    replaceBodyClasses(parsedDocument.body);
    bindGlobalUI();
    initPlayers(document);
    applyModes();
    applyFontScale(getStoredFontScale());

    if (activePlayerState && activePlayerState.audio && !activePlayerState.audio.paused) {
      moveActivePlayerToShell();
    } else {
      hidePersistentShell();
    }
  }

  function focusMainContent() {
    var main = document.querySelector("#main-content");
    if (!main) {
      return;
    }

    main.setAttribute("tabindex", "-1");
    main.focus({ preventScroll: true });
  }

  function navigateWithAudio(url, options) {
    if (isNavigating) {
      return;
    }

    var settings = options || {};
    isNavigating = true;
    moveActivePlayerToShell();

    window.fetch(url, {
      credentials: "same-origin",
      headers: {
        "X-Requested-With": "between-words-pjax"
      }
    }).then(function (response) {
      if (!response.ok) {
        throw new Error("Navigation failed");
      }

      return response.text();
    }).then(function (html) {
      var parser = new window.DOMParser();
      var nextDocument = parser.parseFromString(html, "text/html");
      var nextMain = nextDocument.querySelector("#main-content");
      var currentMain = document.querySelector("#main-content");

      if (!nextMain || !currentMain) {
        throw new Error("Main content missing");
      }

      currentMain.replaceWith(nextMain);
      document.title = nextDocument.title || document.title;

      if (settings.pushState !== false) {
        window.history.pushState({ url: url }, "", url);
      }

      closeDrawer();
      closeSearch();
      reinitializeAfterNavigation(nextDocument);

      if (settings.hash) {
        var target = document.getElementById(settings.hash);
        if (target) {
          target.scrollIntoView();
        }
      } else {
        window.scrollTo(0, 0);
      }

      focusMainContent();
    }).catch(function () {
      window.location.href = url;
    }).finally(function () {
      isNavigating = false;
    });
  }

  function bindNavigationPersistence() {
    document.addEventListener("click", function (event) {
      var link = event.target.closest("a");
      if (!shouldInterceptLink(link, event)) {
        return;
      }

      event.preventDefault();

      var destination = new URL(link.href, window.location.href);
      var hash = destination.hash ? destination.hash.replace(/^#/, "") : "";
      destination.hash = "";
      navigateWithAudio(destination.toString(), { hash: hash });
    });

    window.addEventListener("popstate", function () {
      if (!activePlayerState || !activePlayerState.audio || activePlayerState.audio.paused) {
        return;
      }

      navigateWithAudio(window.location.href, { pushState: false });
    });
  }

  function bindScrubbing() {
    var scrubbingState = null;

    document.addEventListener("pointerdown", function (event) {
      var progress = event.target.closest("[data-audio-progress]");
      if (!progress) {
        return;
      }

      var player = progress.closest("[data-audio-player]");
      var state = player ? playerStateMap.get(player) : null;
      if (!state || !state.audio) {
        return;
      }

      event.preventDefault();
      scrubbingState = state;
      seekFromPointer(state, event.clientX);

      var onMove = function (moveEvent) {
        if (scrubbingState) {
          seekFromPointer(scrubbingState, moveEvent.clientX);
        }
      };

      var onUp = function () {
        document.removeEventListener("pointermove", onMove);
        document.removeEventListener("pointerup", onUp);
        document.removeEventListener("pointercancel", onUp);
        scrubbingState = null;
      };

      document.addEventListener("pointermove", onMove, { passive: true });
      document.addEventListener("pointerup", onUp);
      document.addEventListener("pointercancel", onUp);
    });
  }

  function persistAllPlayers(forceCompleted) {
    playerStates.forEach(function (state) {
      if (state.audio) {
        persistResume(state, !!forceCompleted && !state.audio.paused && state.audio.ended);
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    bindGlobalUI();
    initPlayers(document);
    applyModes();
    applyFontScale(getStoredFontScale());
    bindNavigationPersistence();
    bindScrubbing();

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeSearch();
        closeDrawer();
      }
    });

    window.addEventListener("resize", onResize, { passive: true });
    window.addEventListener("pagehide", function () {
      persistAllPlayers(false);
    });
    window.addEventListener("beforeunload", function () {
      persistAllPlayers(false);
    });
  });
})();
