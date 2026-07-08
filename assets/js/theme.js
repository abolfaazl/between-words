(function () {
  var waveformCache = new Map();
  var playerStates = [];
  var playerStateMap = new WeakMap();
  var waveformObserver = null;
  var searchOverlay = null;
  var searchInput = null;
  var drawer = null;
  var drawerToggle = null;
  var drawerLinks = [];

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
  }

  function isMobilePlayer() {
    return window.matchMedia("(max-width: 768px)").matches;
  }

  function clearStickyPlayers() {
    playerStates.forEach(function (state) {
      state.player.classList.remove("is-mobile-sticky");
    });
    document.body.classList.remove("bw-audio-sticky-active");
  }

  function setStickyPlayer(state) {
    if (!isMobilePlayer()) {
      return;
    }

    clearStickyPlayers();
    state.player.classList.add("is-mobile-sticky");
    document.body.classList.add("bw-audio-sticky-active");
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

  function applyModes() {
    var storedTheme = localStorage.getItem("betweenWordsTheme");
    var theme = storedTheme || (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
    var reader = localStorage.getItem("betweenWordsReader") === "1";
    var focus = localStorage.getItem("betweenWordsFocus") === "1";
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
      setProgress(state, state.audio && state.audio.duration ? state.audio.currentTime / state.audio.duration : 0);
    });
  }

  function initWaveformObserver() {
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
      if (state.canvas) {
        waveformObserver.observe(state.canvas);
      }
    });
  }

  function initPlayer(player) {
    var state = {
      player: player,
      audio: player.querySelector(".podcast-audio"),
      toggle: player.querySelector("[data-audio-toggle]"),
      progress: player.querySelector("[data-audio-progress]"),
      fill: player.querySelector("[data-audio-progress-fill]"),
      timeNode: player.querySelector("[data-audio-time]"),
      canvas: player.querySelector("[data-audio-waveform]"),
      initialTime: player.getAttribute("data-initial-time") || "00:00",
      ratio: 0,
      drawQueued: false,
      waveformReady: false,
      timeFrame: 0
    };

    playerStates.push(state);
    playerStateMap.set(player, state);

    if (state.toggle) {
      state.toggle.classList.toggle("is-disabled", !state.audio);
    }

    if (!state.audio) {
      if (state.timeNode) {
        state.timeNode.textContent = state.initialTime;
      }
      setProgress(state, 0);
      return;
    }

    setProgress(state, 0);
    setTime(state, 0, NaN);

    if (state.toggle) {
      state.toggle.addEventListener("click", function () {
        if (!state.audio.getAttribute("src")) {
          return;
        }

        if (state.audio.paused) {
          pauseOtherPlayers(state.audio);
          state.audio.play().catch(function () {});
        } else {
          state.audio.pause();
        }
      });
    }

    state.audio.addEventListener("loadedmetadata", function () {
      setTime(state, state.audio.currentTime, state.audio.duration);
      setProgress(state, state.audio.duration ? state.audio.currentTime / state.audio.duration : 0);
    });

    state.audio.addEventListener("play", function () {
      pauseOtherPlayers(state.audio);
      state.player.classList.add("is-playing");
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
    });

    state.audio.addEventListener("timeupdate", function () {
      if (state.timeFrame) {
        return;
      }

      state.timeFrame = window.requestAnimationFrame(function () {
        state.timeFrame = 0;
        setTime(state, state.audio.currentTime, state.audio.duration);
        setProgress(state, state.audio.duration ? state.audio.currentTime / state.audio.duration : 0);
      });
    });

    state.audio.addEventListener("ended", function () {
      setProgress(state, 0);
      setTime(state, 0, state.audio.duration);
      clearStickyPlayers();
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
        }

        if (event.key === "ArrowRight") {
          event.preventDefault();
          state.audio.currentTime = Math.min(state.audio.duration, state.audio.currentTime + step);
        }
      });
    }
  }

  function initPlayers() {
    document.querySelectorAll("[data-audio-player]").forEach(initPlayer);
    initWaveformObserver();
  }

  function onResize() {
    if (!isMobilePlayer()) {
      clearStickyPlayers();
    }

    playerStates.forEach(function (state) {
      if (state.audio && state.audio.duration) {
        setProgress(state, state.audio.currentTime / state.audio.duration);
      } else {
        queueWaveformDraw(state);
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    var scrubbingState = null;

    searchOverlay = document.querySelector("[data-search-overlay]");
    searchInput = document.querySelector("[data-search-input]");
    drawer = document.querySelector("#site-sidebar");
    drawerToggle = document.querySelector("[data-drawer-open]");
    drawerLinks = Array.prototype.slice.call(document.querySelectorAll("#site-sidebar a"));

    applyModes();

    document.querySelectorAll("[data-search-open]").forEach(function (button) {
      button.addEventListener("click", openSearch);
    });
    document.querySelectorAll("[data-search-close]").forEach(function (button) {
      button.addEventListener("click", closeSearch);
    });

    if (searchOverlay) {
      searchOverlay.addEventListener("click", function (event) {
        if (!event.target.closest("[data-search-panel]")) {
          closeSearch();
        }
      });
    }

    document.querySelectorAll("[data-drawer-open]").forEach(function (button) {
      button.addEventListener("click", openDrawer);
    });
    document.querySelectorAll("[data-drawer-close], [data-drawer-backdrop]").forEach(function (button) {
      button.addEventListener("click", closeDrawer);
    });
    drawerLinks.forEach(function (link) {
      link.addEventListener("click", closeDrawer);
    });

    document.querySelectorAll("[data-theme-mode]").forEach(function (button) {
      button.addEventListener("click", function () {
        localStorage.setItem("betweenWordsTheme", button.getAttribute("data-theme-mode") || "light");
        localStorage.setItem("betweenWordsReader", "0");
        applyModes();
      });
    });
    document.querySelectorAll("[data-theme-toggle]").forEach(function (button) {
      button.addEventListener("click", function () {
        var key = button.getAttribute("data-theme-toggle") === "focus" ? "betweenWordsFocus" : "betweenWordsReader";
        localStorage.setItem(key, localStorage.getItem(key) === "1" ? "0" : "1");
        applyModes();
        closeDrawer();
      });
    });
    document.querySelectorAll("[data-theme-cycle]").forEach(function (button) {
      button.addEventListener("click", function () {
        var reader = localStorage.getItem("betweenWordsReader") === "1";
        var theme = localStorage.getItem("betweenWordsTheme") || "light";
        var current = reader ? "reader" : theme;
        var next = current === "light" ? "dark" : current === "dark" ? "reader" : "light";

        if (next === "reader") {
          localStorage.setItem("betweenWordsTheme", "light");
          localStorage.setItem("betweenWordsReader", "1");
        } else {
          localStorage.setItem("betweenWordsTheme", next);
          localStorage.setItem("betweenWordsReader", "0");
        }

        applyModes();
      });
    });
    document.querySelectorAll("[data-focus-toggle]").forEach(function (button) {
      button.addEventListener("click", function () {
        localStorage.setItem("betweenWordsFocus", localStorage.getItem("betweenWordsFocus") === "1" ? "0" : "1");
        applyModes();
      });
    });

    document.querySelectorAll("[data-share-button]").forEach(function (button) {
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

    initPlayers();

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

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeSearch();
        closeDrawer();
      }
    });

    window.addEventListener("resize", onResize, { passive: true });
  });
})();
