  async function requestPage(url, signal) {
    const response = await fetch(url, {
      method: "GET",
      headers: {
        "X-Requested-With": "VoltStack",
        "X-Volt-Navigate": "true",
      },
      credentials: "same-origin",
      signal: signal,
    });

    if (!response.ok) {
      throw createRuntimeRequestError(
        "http-error",
        "Navigation request failed with status " + response.status + ".",
        {
          status: response.status,
          ok: false,
          url: url,
        },
      );
    }

    const html = await response.text();

    return {
      html: html,
      document: parseNavigationDocument(html),
      finalUrl: response.url || url,
    };
  }

  async function visit(url, options) {
    const settings = options || {};
    const normalizedUrl = normalizeNavigationUrl(url);
    const cacheControl = navigationVisitCacheControl(settings);
    const requestedNavigationMode =
      settings.navigationMode && typeof settings.navigationMode === "object"
        ? settings.navigationMode
        : parseNavigationMode("", "default");
    const requestId = runtime.navigationRequestId + 1;
    runtime.navigationRequestId = requestId;
    const previousController = runtime.navigationController;
    const controller =
      typeof AbortController === "function" ? new AbortController() : null;
    const requestMeta = {
      requestId: requestId,
      trigger: triggerDescriptor(settings.trigger || null),
    };
    const requestStartedAt = runtimeNow();
    let finalUrl = normalizedUrl;
    let responsePayloadBytes = 0;
    let htmlBytes = 0;
    let patchDurationMs = null;
    let networkDurationMs = null;
    let cacheHit = false;
    let resolvedNavigationMode = requestedNavigationMode.mode;
    let resolvedDocumentContract = null;
    let resolvedPageTransition = null;
    let preservedFragments = 0;
    let discardedFragments = 0;
    let persistedFragments = 0;
    let persistentFragmentRegistrySize = 0;
    const timeoutMs = resolveRequestTimeoutMs("navigation", settings, [
      settings.trigger || null,
      document.body,
      document.documentElement,
    ]);
    const retryPolicy = resolveRequestRetryPolicy("navigation", settings, [
      settings.trigger || null,
      document.body,
      document.documentElement,
    ]);
    let retryCount = 0;
    let errorKind = null;
    let errorMessage = null;
    let responseStatus = null;
    runtime.navigationController = controller;

    if (previousController) {
      abortControllerWithMeta(previousController, {
        kind: "aborted",
        message: "Navigation request was superseded by a newer visit.",
      });
    }

    setNavigationState(true, settings.trigger || null);
    emitRuntimeHook(
      "volt:request-start",
      requestHookDetail("navigation", requestMeta, {
        url: normalizedUrl,
        historyMode: settings.historyMode || "push",
        cacheMode: cacheControl.mode,
        navigationMode: requestedNavigationMode.mode,
        timeoutMs: timeoutMs,
        retryAttempts: retryPolicy.attempts,
        retryDelayMs: retryPolicy.delayMs,
      }),
      document,
    );

    let outcome = "success";
    let fallbackReason = null;

    try {
      if (
        cacheControl.mode === "reload" ||
        cacheControl.mode === "invalidate"
      ) {
        invalidateNavigationCache(normalizedUrl, cacheControl.mode, {
          source: "navigate",
        });
      }

      const cachedPayload = shouldReadNavigationCache(cacheControl)
        ? getCachedNavigation(normalizedUrl)
        : null;

      if (cachedPayload) {
        cacheHit = true;
        emitNavigationCacheEvent("volt:cache-hit", {
          url: normalizedUrl,
          finalUrl: cachedPayload.finalUrl,
          source: "navigate",
          mode: cacheControl.mode,
        });
      } else {
        emitNavigationCacheEvent("volt:cache-miss", {
          url: normalizedUrl,
          source: "navigate",
          mode: cacheControl.mode,
        });
      }

      const payload =
        cachedPayload ||
        (await (async function () {
          let attempt = 0;

          while (true) {
            try {
              return await withRequestTimeout(
                requestNavigationPayload(
                  normalizedUrl,
                  controller ? controller.signal : undefined,
                  "navigate",
                  {
                    cacheControl: cacheControl,
                    navigationMode: requestedNavigationMode,
                  },
                ),
                controller,
                timeoutMs,
                {
                  message:
                    "Navigation request timed out after " + timeoutMs + "ms.",
                },
              );
            } catch (error) {
              if (isAbortError(error)) {
                throw error;
              }

              const retryDetail = exceptionErrorDetail(
                "navigation",
                error,
                requestMeta,
                {
                  url: normalizedUrl,
                  finalUrl: finalUrl,
                  status:
                    error && typeof error.status === "number"
                      ? error.status
                      : null,
                  retryAttempt: attempt + 1,
                },
              );

              if (
                !shouldRetryNavigationRequest(
                  retryDetail,
                  retryPolicy,
                  attempt,
                )
              ) {
                throw error;
              }

              retryCount = attempt + 1;
              emitRuntimeHook(
                "volt:request-retry",
                requestHookDetail("navigation", requestMeta, {
                  url: normalizedUrl,
                  finalUrl: finalUrl,
                  retryAttempt: retryCount,
                  retryAttempts: retryPolicy.attempts,
                  retryDelayMs: retryPolicy.delayMs,
                  errorKind: retryDetail.errorKind,
                  message: retryDetail.message,
                  status:
                    typeof retryDetail.status === "number"
                      ? retryDetail.status
                      : null,
                }),
                document,
              );

              await waitForRetryDelay(
                retryPolicy.delayMs,
                controller ? controller.signal : null,
              );
              attempt += 1;
            }
          }
        })());

      finalUrl = payload && payload.finalUrl ? payload.finalUrl : normalizedUrl;
      htmlBytes = serializedPayloadBytes(payload && payload.html ? payload.html : "");
      responsePayloadBytes = htmlBytes;
      networkDurationMs = cacheHit
        ? 0
        : roundedMetricValue(runtimeNow() - requestStartedAt);

      if (runtime.navigationRequestId !== requestId) {
        outcome = "stale";
        errorKind = "stale";
        emitRuntimeHook(
          "volt:request-stale",
          requestHookDetail("navigation", requestMeta, {
            url: normalizedUrl,
            finalUrl: finalUrl,
            outcome: outcome,
          }),
          document,
        );
        return;
      }

      const resolvedPayloadNavigationMode = payload.document
        ? navigationModeForDocument(payload.document)
        : payload.navigationMode && typeof payload.navigationMode === "object"
          ? payload.navigationMode
          : requestedNavigationMode;
      const payloadNavigationMode =
        resolvedPayloadNavigationMode.mode !== "auto"
          ? resolvedPayloadNavigationMode
          : payload.navigationMode && typeof payload.navigationMode === "object"
            ? payload.navigationMode
            : requestedNavigationMode;
      const payloadDocumentContract = payload.document
        ? documentContractForDocument(payload.document)
        : parseDocumentContract("", "default");
      const payloadPageTransition =
        payload.document || (payload && typeof payload.html === "string")
          ? pageTransitionForPayload(payload)
          : payload.pageTransition && typeof payload.pageTransition === "object"
            ? payload.pageTransition
            : parsePageTransition("", "default");
      resolvedNavigationMode =
        payloadNavigationMode && payloadNavigationMode.mode
          ? payloadNavigationMode.mode
          : requestedNavigationMode.mode;
      resolvedDocumentContract = payloadDocumentContract.mode;

      if (shouldFallbackForLayoutChange(payload.document)) {
        outcome = "layout-fallback";
        fallbackReason = "layout-mismatch";

        if (settings.fallback !== false) {
          window.location.assign(payload.finalUrl);
          return;
        }
      }

      if (payloadDocumentContract.mode === "reload") {
        outcome = "document-fallback";
        fallbackReason = "document-reload-only";

        if (settings.fallback !== false) {
          window.location.assign(payload.finalUrl);
          return;
        }
      }

      if (payloadNavigationMode && payloadNavigationMode.mode === "reload") {
        outcome = "policy-reload";
        fallbackReason = "document-policy-reload";

        if (settings.fallback !== false) {
          window.location.assign(payload.finalUrl);
          return;
        }
      }

      const pageTransition = resolveNavigationPageTransition(
        settings.pageTransition,
        payloadPageTransition,
      );
      resolvedPageTransition = pageTransition.name;

      emitRuntimeHook(
        "volt:before-navigate",
        {
          url: normalizedUrl,
          finalUrl: finalUrl,
          navigationMode: resolvedNavigationMode,
          pageTransition: pageTransition.name,
          pageTransitionSource: pageTransition.source || "default",
          pageTransitionMode: pageTransition.mode || "out-in",
          pageTransitionDuration:
            typeof pageTransition.duration === "number"
              ? pageTransition.duration
              : null,
          pageTransitionProfile: pageTransition.profile || null,
        },
        document,
      );

      if (pageTransition.mode === "out-in") {
        await runPageTransitionPhase(document.body, "leave", pageTransition);
      }

      const patchStartedAt = runtimeNow();
      const navigationMutation = await withPreservedUiState(
        document.body,
        async function () {
          return applyDocumentPayload(payload.document, {
            source: "navigate",
            url: normalizedUrl,
            finalUrl: finalUrl,
            cacheControl: payload.cacheControl,
            pageTransition: pageTransition,
          });
        },
        {
          type: "navigation",
          url: normalizedUrl,
          finalUrl: finalUrl,
        },
      );
      patchDurationMs = roundedMetricValue(runtimeNow() - patchStartedAt);
      preservedFragments =
        navigationMutation &&
        typeof navigationMutation.preservedCount === "number"
          ? navigationMutation.preservedCount
          : 0;
      discardedFragments =
        navigationMutation &&
        typeof navigationMutation.discardedCount === "number"
          ? navigationMutation.discardedCount
          : 0;
      persistedFragments =
        navigationMutation &&
        typeof navigationMutation.persistedCount === "number"
          ? navigationMutation.persistedCount
          : 0;
      persistentFragmentRegistrySize =
        navigationMutation &&
        typeof navigationMutation.persistentRegistrySize === "number"
          ? navigationMutation.persistentRegistrySize
          : 0;

      if (settings.historyMode === "replace") {
        window.history.replaceState({}, "", finalUrl);
      } else if (settings.updateHistory !== false) {
        window.history.pushState({}, "", finalUrl);
      }

      if (settings.preserveScroll !== true) {
        window.scrollTo(0, 0);
      }

      transitionClientStateScope(finalUrl, "navigation");

      emitRuntimeHook(
        "volt:navigated",
        {
          url: normalizedUrl,
          finalUrl: finalUrl,
          historyMode: settings.historyMode || "push",
          navigationMode: resolvedNavigationMode,
          pageTransition: pageTransition.name,
          pageTransitionSource: pageTransition.source || "default",
          pageTransitionMode: pageTransition.mode || "out-in",
          pageTransitionDuration:
            typeof pageTransition.duration === "number"
              ? pageTransition.duration
              : null,
          pageTransitionProfile: pageTransition.profile || null,
          preservedFragments: preservedFragments,
          discardedFragments: discardedFragments,
          persistedFragments: persistedFragments,
          persistentFragmentRegistrySize: persistentFragmentRegistrySize,
        },
        document,
      );
    } catch (error) {
      if (isAbortError(error)) {
        const abortDetail = requestAbortDetail(
          "navigation",
          requestMeta,
          controller ? controller.signal : null,
          {
            url: normalizedUrl,
            finalUrl: finalUrl,
          },
        );

        if (abortDetail.errorKind === "timeout") {
          const errorDetail = timeoutErrorDetail(
            "navigation",
            requestMeta,
            controller ? controller.signal : null,
            {
              url: normalizedUrl,
              finalUrl: finalUrl,
            },
          );
          outcome = "timeout";
          errorKind = errorDetail.errorKind;
          errorMessage = errorDetail.message;
          fallbackReason = settings.fallback !== false ? "request-timeout" : null;
          emitRuntimeHook("volt:request-error", errorDetail, document);

          if (settings.fallback !== false) {
            window.location.assign(normalizedUrl);
            return;
          }

          throw error;
        }

        outcome = "aborted";
        errorKind = abortDetail.errorKind;
        errorMessage = abortDetail.message;
        emitRuntimeHook("volt:request-abort", abortDetail, document);
        return;
      }

      const errorDetail = exceptionErrorDetail("navigation", error, requestMeta, {
        url: normalizedUrl,
        finalUrl: finalUrl,
        status:
          error && typeof error.status === "number" ? error.status : null,
      });
      outcome = errorDetail.errorKind;
      errorKind = errorDetail.errorKind;
      errorMessage = errorDetail.message;
      responseStatus =
        errorDetail && typeof errorDetail.status === "number"
          ? errorDetail.status
          : null;
      fallbackReason = settings.fallback !== false ? "request-error" : null;
      emitRuntimeHook(
        "volt:request-error",
        errorDetail,
        document,
      );

      if (settings.fallback !== false) {
        window.location.assign(normalizedUrl);
        return;
      }

      throw error;
    } finally {
      if (runtime.navigationController === controller) {
        runtime.navigationController = null;
      }

      if (runtime.navigationRequestId === requestId) {
        setNavigationState(false, settings.trigger || null);
      }

      const finishDetail = requestHookDetail("navigation", requestMeta, {
        url: normalizedUrl,
        finalUrl: finalUrl,
        outcome: outcome,
        fallbackReason: fallbackReason,
        cacheMode: cacheControl.mode,
        cacheHit: cacheHit,
        navigationMode: resolvedNavigationMode,
        documentContract: resolvedDocumentContract,
        pageTransition: resolvedPageTransition,
        timeoutMs: timeoutMs,
        retryCount: retryCount,
        retryAttempts: retryPolicy.attempts,
        retryDelayMs: retryPolicy.delayMs,
        networkDurationMs: networkDurationMs,
        patchDurationMs: patchDurationMs,
        totalDurationMs: roundedMetricValue(runtimeNow() - requestStartedAt),
        status: responseStatus,
        errorKind: errorKind,
        message: errorMessage,
        requestPayloadBytes: 0,
        responsePayloadBytes: responsePayloadBytes,
        htmlBytes: htmlBytes,
        preservedFragments: preservedFragments,
        discardedFragments: discardedFragments,
        persistedFragments: persistedFragments,
        persistentFragmentRegistrySize: persistentFragmentRegistrySize,
      });
      const telemetryEntry = recordRuntimeTelemetry("navigation", finishDetail);

      emitRuntimeHook(
        "volt:request-finish",
        Object.assign({}, finishDetail, {
          telemetrySequence: telemetryEntry.sequence,
        }),
        document,
      );
    }
  }

