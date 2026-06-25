  function navigationCacheAliases(entryOrUrl, finalUrl) {
    const aliases = [];

    function pushAlias(value) {
      const normalized = normalizeNavigationUrl(value);

      if (!normalized || aliases.indexOf(normalized) !== -1) {
        return;
      }

      aliases.push(normalized);
    }

    if (entryOrUrl && typeof entryOrUrl === "object") {
      if (Array.isArray(entryOrUrl.aliases)) {
        entryOrUrl.aliases.forEach(pushAlias);
      }

      pushAlias(entryOrUrl.url);
      pushAlias(entryOrUrl.finalUrl);
      return aliases;
    }

    pushAlias(entryOrUrl);
    pushAlias(finalUrl);
    return aliases;
  }

  function uniqueNavigationCacheEntries() {
    const entries = [];
    const seen = new Set();

    runtime.navigationCache.forEach(function (entry) {
      if (!entry || !entry.cacheKey || seen.has(entry.cacheKey)) {
        return;
      }

      seen.add(entry.cacheKey);
      entries.push(entry);
    });

    return entries;
  }

  function deleteNavigationCacheEntry(entry) {
    if (!entry) {
      return 0;
    }

    let removed = 0;

    navigationCacheAliases(entry).forEach(function (alias) {
      if (runtime.navigationCache.get(alias) === entry) {
        runtime.navigationCache.delete(alias);
        removed += 1;
      }
    });

    return removed;
  }

  function emitNavigationCacheEvent(name, detail) {
    emitRuntimeHook(
      name,
      Object.assign(
        {
          cacheEntries: uniqueNavigationCacheEntries().length,
        },
        detail || {},
      ),
      document,
    );
  }

  function invalidateNavigationCache(url, reason, extra) {
    if (typeof url !== "string" || url === "") {
      return 0;
    }

    const normalizedUrl = normalizeNavigationUrl(url);
    const details = extra && typeof extra === "object" ? extra : {};
    const entry = runtime.navigationCache.get(normalizedUrl);
    const aliases = entry ? navigationCacheAliases(entry) : [normalizedUrl];
    const removed = entry
      ? deleteNavigationCacheEntry(entry)
      : runtime.navigationCache.delete(normalizedUrl)
        ? 1
        : 0;

    if (removed > 0 && details.silent !== true) {
      emitNavigationCacheEvent(
        "volt:cache-invalidate",
        Object.assign(
          {
            url: normalizedUrl,
            aliases: aliases,
            reason: reason || "manual",
            removed: removed,
          },
          details,
        ),
      );
    }

    return removed;
  }

  function clearNavigationCache(reason, extra) {
    const removed = uniqueNavigationCacheEntries().length;

    if (removed === 0) {
      return 0;
    }

    runtime.navigationCache.clear();
    emitNavigationCacheEvent(
      "volt:cache-clear",
      Object.assign(
        {
          reason: reason || "manual",
          removed: removed,
        },
        extra || {},
      ),
    );

    return removed;
  }

  function cloneNavigationPayload(entry) {
    if (!entry || typeof entry !== "object" || typeof entry.html !== "string") {
      return null;
    }

    const documentPayload = parseNavigationDocument(entry.html);
    const cacheControl =
      entry.cacheControl || navigationCacheControlForDocument(documentPayload);
    const navigationMode =
      entry.navigationMode || navigationModeForDocument(documentPayload);
    const pageTransition =
      entry.pageTransition ||
      pageTransitionForPayload({
        html: entry.html,
        document: documentPayload,
      });

    return {
      url: entry.url,
      finalUrl: entry.finalUrl,
      html: entry.html,
      document: documentPayload,
      fetchedAt: entry.fetchedAt,
      lastAccessedAt: entry.lastAccessedAt,
      expiresAt: entry.expiresAt,
      source: entry.source || "cache",
      cacheKey: entry.cacheKey || null,
      aliases: navigationCacheAliases(entry),
      cacheControl: cacheControl,
      navigationMode: navigationMode,
      pageTransition: pageTransition,
    };
  }

  function pruneNavigationCache() {
    const now = Date.now();

    uniqueNavigationCacheEntries().forEach(function (entry) {
      if (
        !entry ||
        typeof entry.expiresAt !== "number" ||
        entry.expiresAt <= now
      ) {
        deleteNavigationCacheEntry(entry);
      }
    });

    const entries = uniqueNavigationCacheEntries().sort(function (left, right) {
      const leftStamp =
        typeof left.lastAccessedAt === "number"
          ? left.lastAccessedAt
          : left.fetchedAt;
      const rightStamp =
        typeof right.lastAccessedAt === "number"
          ? right.lastAccessedAt
          : right.fetchedAt;
      return leftStamp - rightStamp;
    });

    while (entries.length > NAVIGATION_CACHE_MAX_ENTRIES) {
      const oldestEntry = entries.shift();

      if (!oldestEntry) {
        break;
      }

      deleteNavigationCacheEntry(oldestEntry);
    }
  }

  function getCachedNavigation(url) {
    const normalizedUrl = normalizeNavigationUrl(url);
    const entry = runtime.navigationCache.get(normalizedUrl);

    if (!entry) {
      return null;
    }

    if (typeof entry.expiresAt !== "number" || entry.expiresAt <= Date.now()) {
      deleteNavigationCacheEntry(entry);
      emitNavigationCacheEvent("volt:cache-invalidate", {
        url: normalizedUrl,
        aliases: navigationCacheAliases(entry),
        reason: "ttl",
      });
      return null;
    }

    entry.lastAccessedAt = Date.now();

    return cloneNavigationPayload(entry);
  }

  function setCachedNavigation(url, payload, source, control) {
    if (!payload || typeof payload.html !== "string" || payload.html === "") {
      return null;
    }

    const normalizedUrl = normalizeNavigationUrl(url);
    const finalUrl = normalizeNavigationUrl(payload.finalUrl || normalizedUrl);
    const cacheControl = mergeNavigationCacheControl(
      control,
      payload.cacheControl && typeof payload.cacheControl === "object"
        ? payload.cacheControl
        : null,
    );
    const ttl = navigationCacheTtlForControl(cacheControl);

    if (!shouldStoreNavigationCache(cacheControl) || ttl <= 0) {
      invalidateNavigationCache(normalizedUrl, "no-store", {
        finalUrl: finalUrl,
      });

      if (finalUrl !== normalizedUrl) {
        invalidateNavigationCache(finalUrl, "no-store", {
          requestedUrl: normalizedUrl,
        });
      }

      return null;
    }

    const now = Date.now();
    const aliases = navigationCacheAliases(normalizedUrl, finalUrl);

    aliases.forEach(function (alias) {
      invalidateNavigationCache(alias, "replace", {
        requestedUrl: normalizedUrl,
        finalUrl: finalUrl,
        silent: true,
      });
    });

    const entry = {
      cacheKey: aliases.join("::"),
      aliases: aliases,
      url: normalizedUrl,
      finalUrl: finalUrl,
      html: payload.html,
      fetchedAt: now,
      lastAccessedAt: now,
      expiresAt: now + ttl,
      source: source || "prefetch",
      cacheControl: cacheControl,
      navigationMode:
        payload.navigationMode && typeof payload.navigationMode === "object"
          ? payload.navigationMode
          : navigationModeForDocument(parseNavigationDocument(payload.html)),
      pageTransition:
        payload.pageTransition && typeof payload.pageTransition === "object"
          ? payload.pageTransition
          : pageTransitionForPayload({
              html: payload.html,
              document: parseNavigationDocument(payload.html),
            }),
    };

    aliases.forEach(function (alias) {
      runtime.navigationCache.set(alias, entry);
    });
    pruneNavigationCache();
    emitNavigationCacheEvent("volt:cache-store", {
      url: normalizedUrl,
      finalUrl: finalUrl,
      aliases: aliases,
      source: entry.source,
      ttl: ttl,
      mode: cacheControl.mode,
    });

    return cloneNavigationPayload(entry);
  }

  function preloadDescriptorFromHeadNode(node) {
    if (!node || node.nodeType !== 1) {
      return null;
    }

    const tag = node.tagName.toLowerCase();

    if (tag === "link") {
      const rel = (node.getAttribute("rel") || "").toLowerCase();
      const href = normalizeHeadAssetUrl(node.getAttribute("href") || "");

      if (!href) {
        return null;
      }

      if (rel === "stylesheet") {
        return null;
      }

      if (rel === "modulepreload") {
        return {
          key: "module:" + href,
          rel: "modulepreload",
          href: href,
          crossOrigin: node.getAttribute("crossorigin") || null,
        };
      }

      return null;
    }

    if (tag === "script") {
      const src = normalizeHeadAssetUrl(node.getAttribute("src") || "");
      const type = (node.getAttribute("type") || "").toLowerCase();

      if (!src || type !== "module") {
        return null;
      }

      return {
        key: "module:" + src,
        rel: "modulepreload",
        href: src,
        crossOrigin: node.getAttribute("crossorigin") || null,
      };
    }

    return null;
  }

  function documentAlreadyHasHeadAsset(descriptor) {
    if (!descriptor || !descriptor.href || !document.head) {
      return false;
    }

    const href = descriptor.href;

    if (descriptor.as === "style") {
      return (
        !!document.head.querySelector(
          'link[rel="stylesheet"][href="' + cssEscape(href) + '"]',
        ) ||
        !!document.head.querySelector(
          'link[rel="preload"][as="style"][href="' + cssEscape(href) + '"]',
        )
      );
    }

    if (descriptor.rel === "modulepreload") {
      return (
        !!document.head.querySelector(
          'link[rel="modulepreload"][href="' + cssEscape(href) + '"]',
        ) ||
        !!document.head.querySelector(
          'script[type="module"][src="' + cssEscape(href) + '"]',
        )
      );
    }

    if (descriptor.rel === "preload") {
      return !!document.head.querySelector(
        'link[rel="preload"][href="' +
          cssEscape(href) +
          '"][as="' +
          cssEscape(descriptor.as || "") +
          '"]',
      );
    }

    return false;
  }

  function ensurePreloadHint(descriptor) {
    if (!descriptor || !descriptor.key || !descriptor.href || !document.head) {
      return;
    }

    if (
      runtime.navigationPreloadHints.has(descriptor.key) ||
      documentAlreadyHasHeadAsset(descriptor)
    ) {
      return;
    }

    const link = document.createElement("link");
    link.setAttribute("href", descriptor.href);
    link.setAttribute("rel", descriptor.rel);
    link.setAttribute("data-volt-prefetch-preload", descriptor.key);

    if (descriptor.as) {
      link.setAttribute("as", descriptor.as);
    }

    if (descriptor.crossOrigin) {
      link.setAttribute("crossorigin", descriptor.crossOrigin);
    }

    document.head.appendChild(link);
    runtime.navigationPreloadHints.add(descriptor.key);
  }

  function trackPrefetchInterest(element, url) {
    if (!element) {
      return normalizeNavigationUrl(url);
    }

    const normalizedUrl = normalizeNavigationUrl(url);
    const previousUrl = runtime.navigationPrefetchElements.get(element);

    if (previousUrl === normalizedUrl) {
      return normalizedUrl;
    }

    if (previousUrl) {
      releasePrefetchInterest(element);
    }

    runtime.navigationPrefetchElements.set(element, normalizedUrl);
    runtime.navigationPrefetchTrackedElements.add(element);
    runtime.navigationPrefetchInterest.set(
      normalizedUrl,
      (runtime.navigationPrefetchInterest.get(normalizedUrl) || 0) + 1,
    );

    return normalizedUrl;
  }

  function cancelPrefetch(url) {
    const normalizedUrl = normalizeNavigationUrl(url);
    const entry = runtime.navigationInFlight.get(normalizedUrl);

    if (
      !entry ||
      entry.source !== "prefetch" ||
      entry.retained ||
      !entry.controller
    ) {
      return false;
    }

    entry.controller.abort();
    return true;
  }

  function releasePrefetchInterest(element) {
    if (!element) {
      return;
    }

    const url = runtime.navigationPrefetchElements.get(element);

    if (!url) {
      return;
    }

    runtime.navigationPrefetchElements.delete(element);
    runtime.navigationPrefetchTrackedElements.delete(element);

    const nextCount = (runtime.navigationPrefetchInterest.get(url) || 0) - 1;

    if (nextCount > 0) {
      runtime.navigationPrefetchInterest.set(url, nextCount);
      return;
    }

    runtime.navigationPrefetchInterest.delete(url);
    cancelPrefetch(url);
  }

  function preloadCriticalHeadAssets(nextHead) {
    if (!nextHead || !nextHead.children) {
      return;
    }

    Array.from(nextHead.children).forEach(function (node) {
      const descriptor = preloadDescriptorFromHeadNode(node);

      if (descriptor) {
        ensurePreloadHint(descriptor);
      }
    });
  }

  function requestNavigationPayload(url, signal, source, options) {
    const normalizedUrl = normalizeNavigationUrl(url);
    const settings = options && typeof options === "object" ? options : {};
    const requestedControl =
      settings.cacheControl && typeof settings.cacheControl === "object"
        ? settings.cacheControl
        : parseNavigationCacheControl("", "default");
    const requestedMode =
      settings.navigationMode && typeof settings.navigationMode === "object"
        ? settings.navigationMode
        : parseNavigationMode("", "default");

    if (runtime.navigationInFlight.has(normalizedUrl)) {
      const existing = runtime.navigationInFlight.get(normalizedUrl);

      if (
        existing &&
        existing.source === "prefetch" &&
        existing.controller &&
        (requestedControl.mode === "reload" ||
          requestedControl.mode === "invalidate" ||
          requestedControl.mode === "no-store")
      ) {
        existing.controller.abort();
        runtime.navigationInFlight.delete(normalizedUrl);
      } else {
        if (source !== "prefetch") {
          existing.retained = true;
          existing.source = "navigate";
        }

        return existing.promise.then(function (payload) {
          if (!shouldStoreNavigationCache(requestedControl)) {
            invalidateNavigationCache(normalizedUrl, "no-store", {
              finalUrl:
                payload && payload.finalUrl ? payload.finalUrl : normalizedUrl,
            });
          }

          return payload;
        });
      }
    }

    const prefetchController =
      source === "prefetch" && typeof AbortController === "function"
        ? new AbortController()
        : null;
    const requestSignal = prefetchController
      ? prefetchController.signal
      : signal;
    const entry = {
      promise: null,
      controller: prefetchController,
      source: source || "navigate",
      retained: source !== "prefetch",
    };
    const promise = requestPage(normalizedUrl, requestSignal)
      .then(function (payload) {
        const responseControl = navigationCacheControlForDocument(
          payload.document,
        );
        const responseMode = navigationModeForDocument(payload.document);
        const responsePageTransition = pageTransitionForDocument(
          payload.document,
        );
        const effectiveControl = mergeNavigationCacheControl(
          requestedControl,
          responseControl,
        );
        const enrichedPayload = Object.assign({}, payload, {
          cacheControl: effectiveControl,
          navigationMode:
            responseMode.mode !== "auto" ? responseMode : requestedMode,
          pageTransition: responsePageTransition,
        });

        if (
          source === "prefetch" &&
          enrichedPayload.document &&
          enrichedPayload.document.head
        ) {
          preloadCriticalHeadAssets(enrichedPayload.document.head);
        }

        const cached = setCachedNavigation(
          normalizedUrl,
          enrichedPayload,
          source || "navigate",
          effectiveControl,
        );
        return cached || enrichedPayload;
      })
      .finally(function () {
        runtime.navigationInFlight.delete(normalizedUrl);
      });

    entry.promise = promise;
    runtime.navigationInFlight.set(normalizedUrl, entry);

    return entry.promise;
  }

  function prefetchPage(url, options) {
    const normalizedUrl = normalizeNavigationUrl(url);
    const settings = options && typeof options === "object" ? options : {};
    const cacheControl =
      settings.cacheControl && typeof settings.cacheControl === "object"
        ? settings.cacheControl
        : parseNavigationCacheControl("", "default");
    const navigationMode =
      settings.navigationMode && typeof settings.navigationMode === "object"
        ? settings.navigationMode
        : parseNavigationMode("", "default");

    if (
      !shouldPrefetchNavigation(cacheControl) ||
      !shouldPrefetchForNavigationMode(navigationMode)
    ) {
      return Promise.resolve(null);
    }

    if (cacheControl.mode === "reload" || cacheControl.mode === "invalidate") {
      invalidateNavigationCache(normalizedUrl, cacheControl.mode, {
        source: "prefetch",
      });
    }

    if (shouldReadNavigationCache(cacheControl)) {
      const cached = getCachedNavigation(normalizedUrl);

      if (cached) {
        emitNavigationCacheEvent("volt:cache-hit", {
          url: normalizedUrl,
          finalUrl: cached.finalUrl,
          source: "prefetch",
          mode: cacheControl.mode,
        });

        return Promise.resolve(cached);
      }
    }

    emitNavigationCacheEvent("volt:cache-miss", {
      url: normalizedUrl,
      source: "prefetch",
      mode: cacheControl.mode,
    });

    return requestNavigationPayload(normalizedUrl, undefined, "prefetch", {
      cacheControl: cacheControl,
      navigationMode: navigationMode,
    });
  }

  function hasNavigationCacheOrFlight(url) {
    const normalizedUrl = normalizeNavigationUrl(url);
    return (
      !!getCachedNavigation(normalizedUrl) ||
      runtime.navigationInFlight.has(normalizedUrl)
    );
  }

  function navigationVisitCacheControl(options) {
    const settings = options && typeof options === "object" ? options : {};
    const optionControl =
      settings.cacheControl && typeof settings.cacheControl === "object"
        ? settings.cacheControl
        : null;
    const triggerControl = navigationCacheControlForElement(
      settings.trigger || null,
    );

    return mergeNavigationCacheControl(triggerControl, optionControl);
  }

