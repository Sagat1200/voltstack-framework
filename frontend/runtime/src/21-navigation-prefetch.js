  function ensureNavigationViewportObserver() {
    if (
      runtime.navigationViewportObserver ||
      typeof IntersectionObserver !== "function"
    ) {
      return runtime.navigationViewportObserver;
    }

    runtime.navigationViewportObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) {
            return;
          }

          const element = entry.target;
          const url = navigationUrlForElement(element);

          if (!url) {
            runtime.navigationViewportObserver.unobserve(element);
            return;
          }

          runtime.navigationViewportObserver.unobserve(element);
          prefetchPage(url, {
            cacheControl: navigationCacheControlForElement(element),
            navigationMode: navigationModeForElement(element),
          }).catch(function () {
            return null;
          });
        });
      },
      {
        root: null,
        rootMargin: "200px 0px",
        threshold: 0.01,
      },
    );

    return runtime.navigationViewportObserver;
  }

  function cleanupRuntimeOrphans() {
    runtime.navigationPrefetchTrackedElements.forEach(function (element) {
      if (element && element.isConnected) {
        return;
      }

      releasePrefetchInterest(element);
    });

    runtime.modelSyncTrackedElements.forEach(function (element) {
      if (element && element.isConnected) {
        return;
      }

      clearModelSyncDirectiveDebounce(element);
    });

    runtime.onDirectiveTrackedElements.forEach(function (element) {
      if (element && element.isConnected) {
        return;
      }

      runtime.onDirectiveOnce.delete(element);
      runtime.onDirectiveTrackedElements.delete(element);
    });

    runtime.navigationViewportTrackedElements.forEach(function (element) {
      if (element && element.isConnected) {
        return;
      }

      if (runtime.navigationViewportObserver) {
        runtime.navigationViewportObserver.unobserve(element);
      }

      runtime.navigationViewportObserved.delete(element);
      runtime.navigationViewportTrackedElements.delete(element);
    });

    if (
      runtime.navigationViewportTrackedElements.size === 0 &&
      runtime.navigationViewportObserver
    ) {
      runtime.navigationViewportObserver.disconnect();
      runtime.navigationViewportObserver = null;
      runtime.navigationViewportObserved = new WeakSet();
    }
  }

  function registerViewportPrefetchTargets(root) {
    cleanupRuntimeOrphans();

    const observer = ensureNavigationViewportObserver();

    if (!observer || !root || typeof root.querySelectorAll !== "function") {
      return;
    }

    root
      .querySelectorAll(NAVIGATION_PREFETCH_SELECTOR)
      .forEach(function (link) {
        if (
          !navigationUrlForElement(link) ||
          !linkAllowsPrefetchSource(link, "viewport") ||
          !shouldPrefetchForNavigationMode(navigationModeForElement(link))
        ) {
          return;
        }

        if (runtime.navigationViewportObserved.has(link)) {
          return;
        }

        runtime.navigationViewportObserved.add(link);
        runtime.navigationViewportTrackedElements.add(link);
        observer.observe(link);
      });
  }

  function navigationHeuristicScore(link) {
    if (!link || typeof link.getBoundingClientRect !== "function") {
      return null;
    }

    const rect = link.getBoundingClientRect();
    const viewportHeight =
      window.innerHeight || document.documentElement.clientHeight || 0;

    if (
      (rect.width <= 0 && rect.height <= 0) ||
      rect.bottom < -1 * NAVIGATION_HEURISTIC_VIEWPORT_MARGIN ||
      rect.top > viewportHeight + NAVIGATION_HEURISTIC_VIEWPORT_MARGIN
    ) {
      return null;
    }

    const insideViewport = rect.bottom >= 0 && rect.top <= viewportHeight;

    if (insideViewport) {
      return Math.max(rect.top, 0);
    }

    return 1000 + Math.max(rect.top - viewportHeight, 0);
  }

  function findHeuristicPrefetchCandidate(root) {
    if (!root || typeof root.querySelectorAll !== "function") {
      return null;
    }

    const currentUrl = normalizeNavigationUrl(window.location.href);
    let bestCandidate = null;
    let bestScore = Number.POSITIVE_INFINITY;

    root
      .querySelectorAll(NAVIGATION_PREFETCH_SELECTOR)
      .forEach(function (link) {
        const url = navigationUrlForElement(link);

        if (
          !url ||
          !linkAllowsPrefetchSource(link, "idle") ||
          !shouldPrefetchForNavigationMode(navigationModeForElement(link)) ||
          normalizeNavigationUrl(url) === currentUrl ||
          hasNavigationCacheOrFlight(url)
        ) {
          return;
        }

        const score = navigationHeuristicScore(link);

        if (score === null || score >= bestScore) {
          return;
        }

        bestCandidate = link;
        bestScore = score;
      });

    return bestCandidate;
  }

  function cancelHeuristicPrefetch() {
    if (runtime.navigationHeuristicHandle === null) {
      return;
    }

    if (typeof cancelIdleCallback === "function") {
      cancelIdleCallback(runtime.navigationHeuristicHandle);
    } else {
      clearTimeout(runtime.navigationHeuristicHandle);
    }

    runtime.navigationHeuristicHandle = null;
  }

  function scheduleHeuristicPrefetch(root) {
    cleanupRuntimeOrphans();
    cancelHeuristicPrefetch();

    const candidateRoot =
      root && typeof root.querySelectorAll === "function" ? root : document;
    const run = function () {
      runtime.navigationHeuristicHandle = null;

      if (document.hidden) {
        return;
      }

      const candidate = findHeuristicPrefetchCandidate(candidateRoot);

      if (!candidate) {
        return;
      }

      const url = navigationUrlForElement(candidate);

      if (!url) {
        return;
      }

      prefetchPage(url, {
        cacheControl: navigationCacheControlForElement(candidate),
        navigationMode: navigationModeForElement(candidate),
      }).catch(function () {
        return null;
      });
    };

    if (typeof requestIdleCallback === "function") {
      runtime.navigationHeuristicHandle = requestIdleCallback(run, {
        timeout: NAVIGATION_HEURISTIC_DELAY,
      });
      return;
    }

    runtime.navigationHeuristicHandle = window.setTimeout(
      run,
      NAVIGATION_HEURISTIC_DELAY,
    );
  }

