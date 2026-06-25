  function resolveRuntimeRoot(rootOrComponent, fallbackComponent) {
    if (
      rootOrComponent &&
      typeof rootOrComponent === "object" &&
      rootOrComponent.isConnected
    ) {
      return rootOrComponent;
    }

    if (typeof rootOrComponent === "string" && rootOrComponent !== "") {
      return findRootByComponent(rootOrComponent);
    }

    if (typeof fallbackComponent === "string" && fallbackComponent !== "") {
      return findRootByComponent(fallbackComponent);
    }

    return null;
  }

  function isAbortError(error) {
    return !!(
      error &&
      typeof error === "object" &&
      (error.name === "AbortError" || error.code === 20)
    );
  }

  function triggerDescriptor(trigger) {
    if (!trigger || typeof trigger.getAttribute !== "function") {
      return null;
    }

    return {
      tag: trigger.tagName ? String(trigger.tagName).toLowerCase() : null,
      target: trigger.getAttribute("data-volt-target"),
      action: directiveValue(trigger, [
        "volt-click",
        "volt:click",
        "volt-submit",
        "volt:submit",
      ]),
    };
  }

  function requestHookDetail(kind, meta, extra) {
    return Object.assign(
      {
        type: kind,
        component: meta && meta.component ? meta.component : null,
        action: meta && meta.action ? meta.action : null,
        requestId: meta && meta.requestId ? meta.requestId : null,
        trigger: meta && meta.trigger ? meta.trigger : null,
      },
      extra || {},
    );
  }

  function responseErrorDetail(response, payload, meta) {
    const payloadError =
      payload && payload.error && typeof payload.error === "object"
        ? payload.error
        : {};

    return requestHookDetail("action", meta, {
      status: response.status,
      ok: false,
      message:
        payloadError.message ||
        "Request failed with status " + response.status + ".",
      error: payloadError,
      outcome: "error",
    });
  }

  function exceptionErrorDetail(error, meta) {
    return requestHookDetail("action", meta, {
      ok: false,
      message:
        error && error.message ? error.message : "Unexpected runtime error.",
      outcome: "error",
    });
  }

  function stateTargetValue(detail) {
    if (!detail || typeof detail !== "object") {
      return null;
    }

    if (detail.target) {
      return detail.target;
    }

    if (detail.trigger && detail.trigger.target) {
      return detail.trigger.target;
    }

    return null;
  }

  function fieldStateTarget(element) {
    if (!element || typeof element.getAttribute !== "function") {
      return null;
    }

    return (
      directiveValue(element, ["volt-model", "volt:model"]) ||
      element.getAttribute("data-volt-target") ||
      element.getAttribute("name") ||
      element.id ||
      null
    );
  }

  function syncRequestStatus(root) {
    if (!root) {
      return;
    }

    if (root.getAttribute("data-volt-loading") === "true") {
      root.setAttribute("data-volt-request-status", "loading");
      root.setAttribute("aria-busy", "true");
      return;
    }

    if (root.getAttribute("data-volt-error") === "true") {
      root.setAttribute("data-volt-request-status", "error");
      root.setAttribute("aria-busy", "false");
      return;
    }

    if (root.getAttribute("data-volt-success") === "true") {
      root.setAttribute("data-volt-request-status", "success");
      root.setAttribute("aria-busy", "false");
      return;
    }

    if (root.getAttribute("data-volt-dirty") === "true") {
      root.setAttribute("data-volt-request-status", "dirty");
      root.setAttribute("aria-busy", "false");
      return;
    }

    root.setAttribute("data-volt-request-status", "idle");
    root.setAttribute("aria-busy", "false");
  }

  function setLoadingState(rootOrComponent, active, trigger, meta) {
    const detail = meta && typeof meta === "object" ? meta : {};
    const root = resolveRuntimeRoot(rootOrComponent, detail.component);

    if (root) {
      const previous = root.getAttribute("data-volt-loading") === "true";
      const context = active
        ? {
            action: detail.action || null,
            target: stateTargetValue(detail),
          }
        : runtimeStateContext(root, "loading");
      const minDuration = previous
        ? resolveStateDirectiveMinDuration(root, "loading", context)
        : null;
      const activatedAt = runtime.loadingActivatedAt.get(root) || null;
      const elapsed = activatedAt === null ? null : Date.now() - activatedAt;
      const remainingMinDuration =
        minDuration !== null && elapsed !== null
          ? Math.max(0, minDuration - elapsed)
          : null;

      if (
        !active &&
        previous &&
        remainingMinDuration !== null &&
        remainingMinDuration > 0 &&
        detail.reason !== "min-duration"
      ) {
        scheduleLoadingMinDurationClear(
          root,
          trigger,
          detail,
          remainingMinDuration,
        );
        return;
      }

      clearLoadingMinDuration(root);

      if (active) {
        runtime.loadingActivatedAt.set(root, Date.now());
      } else {
        runtime.loadingActivatedAt.delete(root);
      }

      root.setAttribute("data-volt-loading", active ? "true" : "false");

      if (active && detail.action) {
        root.setAttribute("data-volt-loading-action", detail.action);
      } else {
        root.removeAttribute("data-volt-loading-action");
      }

      if (active && detail.trigger && detail.trigger.target) {
        root.setAttribute("data-volt-loading-target", detail.trigger.target);
      } else {
        root.removeAttribute("data-volt-loading-target");
      }

      if (active && detail.requestId) {
        root.setAttribute("data-volt-request-id", String(detail.requestId));
      } else {
        root.removeAttribute("data-volt-request-id");
      }

      syncRequestStatus(root);
      syncRuntimeStateDirectives(root);
    }

    if (
      trigger &&
      "disabled" in trigger &&
      (!meta || meta.action !== MODEL_SYNC_INTERNAL_ACTION)
    ) {
      trigger.disabled = active;
    }
  }

  function setErrorState(rootOrComponent, active, meta) {
    const detail = meta && typeof meta === "object" ? meta : {};
    const root = resolveRuntimeRoot(rootOrComponent, detail.component);

    if (!root) {
      return;
    }

    const previous = root.getAttribute("data-volt-error") === "true";
    clearErrorTimeout(root);
    root.setAttribute("data-volt-error", active ? "true" : "false");

    if (active) {
      if (detail.action) {
        root.setAttribute("data-volt-error-action", detail.action);
      } else {
        root.removeAttribute("data-volt-error-action");
      }

      if (detail.trigger && detail.trigger.target) {
        root.setAttribute("data-volt-error-target", detail.trigger.target);
      } else {
        root.removeAttribute("data-volt-error-target");
      }

      if (detail.message) {
        root.setAttribute("data-volt-error-message", String(detail.message));
      } else {
        root.removeAttribute("data-volt-error-message");
      }

      syncRequestStatus(root);
      syncRuntimeStateDirectives(root);
      scheduleErrorTimeout(root, detail);
      return;
    }

    root.removeAttribute("data-volt-error-message");
    root.removeAttribute("data-volt-error-action");
    root.removeAttribute("data-volt-error-target");

    syncRequestStatus(root);
    syncRuntimeStateDirectives(root);

    if (previous) {
      emitRuntimeHook(
        "volt:error-cleared",
        requestHookDetail("error", detail, {
          target: stateTargetValue(detail),
          active: false,
          reason: detail.reason || null,
        }),
        root,
      );
    }
  }

  function setDirtyState(rootOrComponent, active, meta) {
    const detail = meta && typeof meta === "object" ? meta : {};
    const root = resolveRuntimeRoot(rootOrComponent, detail.component);

    if (!root) {
      return;
    }

    if (!active) {
      clearDirtyDebounce(root);
    }

    const previous = root.getAttribute("data-volt-dirty") === "true";
    root.setAttribute("data-volt-dirty", active ? "true" : "false");

    if (active) {
      const target = stateTargetValue(detail);

      if (target) {
        root.setAttribute("data-volt-dirty-target", target);
      } else {
        root.removeAttribute("data-volt-dirty-target");
      }
    } else {
      root.removeAttribute("data-volt-dirty-target");
    }

    syncRequestStatus(root);
    syncRuntimeStateDirectives(root);

    if (previous !== active) {
      emitRuntimeHook(
        active ? "volt:dirty" : "volt:clean",
        requestHookDetail("dirty", detail, {
          target: stateTargetValue(detail),
          active: active,
          reason: detail.reason || null,
          debounce: detail.debounce || null,
        }),
        root,
      );
    }
  }

  function setSuccessState(rootOrComponent, active, meta) {
    const detail = meta && typeof meta === "object" ? meta : {};
    const root = resolveRuntimeRoot(rootOrComponent, detail.component);

    if (!root) {
      return;
    }

    const previous = root.getAttribute("data-volt-success") === "true";
    const context = active
      ? {
          action: detail.action || null,
          target: stateTargetValue(detail),
        }
      : runtimeStateContext(root, "success");
    const minDuration = previous
      ? resolveStateDirectiveMinDuration(root, "success", context)
      : null;
    const activatedAt = runtime.successActivatedAt.get(root) || null;
    const elapsed = activatedAt === null ? null : Date.now() - activatedAt;
    const remainingMinDuration =
      minDuration !== null && elapsed !== null
        ? Math.max(0, minDuration - elapsed)
        : null;

    if (
      !active &&
      previous &&
      remainingMinDuration !== null &&
      remainingMinDuration > 0 &&
      detail.reason !== "min-duration"
    ) {
      scheduleSuccessMinDurationClear(root, detail, remainingMinDuration);
      return;
    }

    clearSuccessTimeout(root);
    clearSuccessMinDuration(root);

    if (active) {
      runtime.successActivatedAt.set(root, Date.now());
    } else {
      runtime.successActivatedAt.delete(root);
    }

    root.setAttribute("data-volt-success", active ? "true" : "false");

    if (active) {
      if (detail.action) {
        root.setAttribute("data-volt-success-action", detail.action);
      } else {
        root.removeAttribute("data-volt-success-action");
      }

      const target = stateTargetValue(detail);

      if (target) {
        root.setAttribute("data-volt-success-target", target);
      } else {
        root.removeAttribute("data-volt-success-target");
      }
    } else {
      root.removeAttribute("data-volt-success-action");
      root.removeAttribute("data-volt-success-target");
    }

    syncRequestStatus(root);
    syncRuntimeStateDirectives(root);

    const timeout = active
      ? resolveStateDirectiveTimeout(root, "success")
      : null;

    if (active) {
      scheduleSuccessTimeout(root, detail);
    }

    if (previous !== active) {
      emitRuntimeHook(
        active ? "volt:success" : "volt:success-cleared",
        requestHookDetail("success", detail, {
          target: stateTargetValue(detail),
          active: active,
          timeout: timeout,
          minDuration: minDuration,
          reason: detail.reason || null,
        }),
        root,
      );
    }
  }

