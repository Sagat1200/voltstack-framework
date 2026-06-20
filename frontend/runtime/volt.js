(function () {
  const runtime = {
    navigationRequestId: 0,
    navigationController: null,
    navigationCache: new Map(),
    navigationInFlight: new Map(),
    navigationPreloadHints: new Set(),
    navigationPrefetchInterest: new Map(),
    navigationPrefetchElements: new WeakMap(),
    navigationViewportObserver: null,
    navigationViewportObserved: new WeakSet(),
    navigationHeuristicHandle: null,
    componentRequestStates: new Map(),
    statePolicies: new Map(),
    loadingDelays: new Map(),
    loadingActivatedAt: new Map(),
    loadingMinClearDelays: new Map(),
    successTimeouts: new Map(),
    successActivatedAt: new Map(),
    successMinClearDelays: new Map(),
    errorTimeouts: new Map(),
    dirtyDebounces: new Map(),
    clientStateScope: null,
    clientStateValues: new Map(),
    sharedStateValues: new Map(),
    clientStateSubscribers: new Map(),
    sharedStateSubscribers: new Map(),
    clientStateGlobalSubscribers: new Set(),
    sharedStateGlobalSubscribers: new Set(),
    directiveSequence: 0,
    onDirectiveOnce: new WeakMap(),
  };

  const NAVIGATION_CACHE_TTL = 5000;
  const NAVIGATION_CACHE_MAX_ENTRIES = 10;
  const NAVIGATION_HEURISTIC_DELAY = 180;
  const NAVIGATION_HEURISTIC_VIEWPORT_MARGIN = 240;
  const NAVIGATION_PREFETCH_SELECTOR =
    "a[volt-navigate], a[volt\\:navigate], a[volt-prefetch], a[volt\\:prefetch]";
  const NAVIGATION_CACHE_CONTROL_META_NAMES = [
    "volt-cache-control",
    "volt:navigation-cache",
  ];
  const NAVIGATION_MODE_META_NAMES = [
    "volt-navigation-mode",
    "volt:navigation-mode",
  ];
  const NAVIGATION_PAGE_TRANSITION_META_NAMES = [
    "volt-page-transition",
    "volt:page-transition",
  ];
  const NAVIGATION_PAGE_TRANSITION_PROFILE_META_NAMES = [
    "volt-page-transition-profile",
    "volt:page-transition-profile",
  ];
  const NAVIGATION_PAGE_TRANSITION_DURATION_META_NAMES = [
    "volt-page-transition-duration",
    "volt:page-transition-duration",
  ];
  const NAVIGATION_PAGE_TRANSITION_MODE_META_NAMES = [
    "volt-page-transition-mode",
    "volt:page-transition-mode",
  ];
  const NAVIGATION_FRAGMENT_CONTROL_META_NAMES = [
    "volt-fragment-control",
    "volt:fragment-cache",
  ];
  const NAVIGATION_FRAGMENT_SELECTOR =
    "[data-volt-preserve], [volt-preserve], [volt\\:preserve]";
  const PAGE_TRANSITION_PROFILES = Object.freeze({
    soft: Object.freeze({
      name: "fade",
      duration: 220,
      mode: "out-in",
    }),
    gentle: Object.freeze({
      name: "fade",
      duration: 320,
      mode: "out-in",
    }),
    crisp: Object.freeze({
      name: "fade",
      duration: 160,
      mode: "out-in",
    }),
    classic: Object.freeze({
      name: "default",
      duration: 180,
      mode: "out-in",
    }),
  });

  function componentRequestState(component) {
    if (!component) {
      return null;
    }

    if (!runtime.componentRequestStates.has(component)) {
      runtime.componentRequestStates.set(component, {
        requestId: 0,
        controller: null,
      });
    }

    return runtime.componentRequestStates.get(component);
  }

  function findRoot(element) {
    return element.closest('[data-volt-root="true"]');
  }

  function findRootByComponent(componentName) {
    const roots = document.querySelectorAll('[data-volt-root="true"]');

    for (let index = 0; index < roots.length; index += 1) {
      if (roots[index].getAttribute("data-volt-component") === componentName) {
        return roots[index];
      }
    }

    return null;
  }

  function readSnapshot(root) {
    const snapshot = root.getAttribute("data-volt-snapshot");

    return snapshot ? JSON.parse(snapshot) : null;
  }

  function collectModelUpdates(root) {
    const updates = {};

    root
      .querySelectorAll("[volt-model], [volt\\:model]")
      .forEach(function (element) {
        const key = directiveValue(element, ["volt-model", "volt:model"]);

        if (!key) {
          return;
        }

        if (element.type === "checkbox") {
          updates[key] = !!element.checked;
          return;
        }

        updates[key] = element.value;
      });

    return updates;
  }

  function collectFormData(form) {
    const data = {};
    const formData = new FormData(form);

    formData.forEach(function (value, key) {
      if (typeof value === "string") {
        data[key] = value;
      }
    });

    return data;
  }

  function cssEscape(value) {
    if (window.CSS && typeof window.CSS.escape === "function") {
      return window.CSS.escape(value);
    }

    return String(value).replace(/[^a-zA-Z0-9\-_]/g, "\\$&");
  }

  function directiveValue(element, names) {
    for (let index = 0; index < names.length; index += 1) {
      const value = element.getAttribute(names[index]);

      if (value !== null && value !== "") {
        return value;
      }
    }

    return null;
  }

  function closestFromEventTarget(event, selector) {
    return event && event.target instanceof Element
      ? event.target.closest(selector)
      : null;
  }

  function directiveAttribute(element, names) {
    for (let index = 0; index < names.length; index += 1) {
      if (element.hasAttribute(names[index])) {
        return {
          name: names[index],
          value: element.getAttribute(names[index]) || "",
        };
      }
    }

    return null;
  }

  function dispatchDirectiveNames() {
    return ["volt-dispatch", "volt:dispatch"];
  }

  function onDirectiveNames() {
    return ["volt-on", "volt:on"];
  }

  function parseDispatchDirectiveEvents(value) {
    if (typeof value !== "string") {
      return [];
    }

    return value
      .split("|")
      .map(function (entry) {
        return entry.trim();
      })
      .filter(function (entry) {
        return /^[A-Za-z][A-Za-z0-9:._-]*$/.test(entry);
      });
  }

  function onDirectiveSelector() {
    return "[volt-on], [volt\\:on]";
  }

  function normalizeOnDirectiveKeyFilter(key) {
    const normalized = String(key || "").trim().toLowerCase();

    if (normalized === "esc") {
      return "escape";
    }

    if (normalized === "spacebar" || normalized === " ") {
      return "space";
    }

    return normalized;
  }

  function onDirectiveOnceStore(element) {
    if (!runtime.onDirectiveOnce.has(element)) {
      runtime.onDirectiveOnce.set(element, new Set());
    }

    return runtime.onDirectiveOnce.get(element);
  }

  function onDirectiveActionValue(rawValue, event) {
    const trimmed = typeof rawValue === "string" ? rawValue.trim() : "";

    if (trimmed === "") {
      return {
        valid: false,
        value: null,
      };
    }

    if (trimmed === "$event.target.value") {
      return {
        valid: true,
        value:
          event && event.target && "value" in event.target
            ? event.target.value
            : undefined,
      };
    }

    if (trimmed === "$event.target.checked") {
      return {
        valid: true,
        value:
          event && event.target && "checked" in event.target
            ? !!event.target.checked
            : undefined,
      };
    }

    const stringLiteral = parseDirectiveStringLiteral(trimmed);

    if (stringLiteral !== null) {
      return {
        valid: true,
        value: stringLiteral,
      };
    }

    if (/^(true|false)$/i.test(trimmed)) {
      return {
        valid: true,
        value: trimmed.toLowerCase() === "true",
      };
    }

    if (/^null$/i.test(trimmed)) {
      return {
        valid: true,
        value: null,
      };
    }

    if (/^-?\d+(?:\.\d+)?$/.test(trimmed)) {
      return {
        valid: true,
        value: Number(trimmed),
      };
    }

    return {
      valid: false,
      value: null,
    };
  }

  function parseOnDirectiveEventSpec(rawValue) {
    if (typeof rawValue !== "string" || rawValue.trim() === "") {
      return null;
    }

    const segments = rawValue
      .trim()
      .split(".")
      .map(function (segment) {
        return segment.trim().toLowerCase();
      })
      .filter(function (segment) {
        return segment !== "";
      });

    if (segments.length === 0) {
      return null;
    }

    const eventName = segments[0];
    const supportedEvents = [
      "click",
      "input",
      "change",
      "submit",
      "focus",
      "blur",
      "keydown",
      "keyup",
    ];

    if (supportedEvents.indexOf(eventName) === -1) {
      return null;
    }

    const modifiers = {
      prevent: false,
      stop: false,
      once: false,
      self: false,
    };
    let keyFilter = null;

    for (let index = 1; index < segments.length; index += 1) {
      const segment = segments[index];

      if (Object.prototype.hasOwnProperty.call(modifiers, segment)) {
        modifiers[segment] = true;
        continue;
      }

      if ((eventName === "keydown" || eventName === "keyup") && !keyFilter) {
        keyFilter = normalizeOnDirectiveKeyFilter(segment);
        continue;
      }

      return null;
    }

    return {
      name: eventName,
      modifiers: modifiers,
      keyFilter: keyFilter,
      raw: rawValue.trim(),
    };
  }

  function parseOnDirectiveAction(rawValue) {
    if (typeof rawValue !== "string" || rawValue.trim() === "") {
      return null;
    }

    const trimmed = rawValue.trim();
    const dispatchMatches = trimmed.match(/^dispatch:([A-Za-z][A-Za-z0-9:._-]*)$/);

    if (dispatchMatches) {
      return {
        type: "dispatch",
        name: dispatchMatches[1],
        raw: trimmed,
      };
    }

    const toggleMatches = trimmed.match(
      /^state:toggle\s+(client|shared):([A-Za-z0-9_.-]+)$/i,
    );

    if (toggleMatches) {
      return {
        type: "toggle",
        scope: normalizeRuntimeStateScope(toggleMatches[1]),
        path: toggleMatches[2],
        raw: trimmed,
      };
    }

    const deleteMatches = trimmed.match(
      /^state:delete\s+(client|shared):([A-Za-z0-9_.-]+)$/i,
    );

    if (deleteMatches) {
      return {
        type: "delete",
        scope: normalizeRuntimeStateScope(deleteMatches[1]),
        path: deleteMatches[2],
        raw: trimmed,
      };
    }

    const setMatches = trimmed.match(
      /^state:set\s+(client|shared):([A-Za-z0-9_.-]+)\s*=\s*(.+)$/i,
    );

    if (setMatches) {
      return {
        type: "set",
        scope: normalizeRuntimeStateScope(setMatches[1]),
        path: setMatches[2],
        valueExpression: setMatches[3].trim(),
        raw: trimmed,
      };
    }

    return null;
  }

  function parseOnDirectiveRule(value, index) {
    if (typeof value !== "string" || value.trim() === "") {
      return null;
    }

    const separator = findTopLevelArrow(value);

    if (separator === -1) {
      return null;
    }

    const eventSpec = parseOnDirectiveEventSpec(value.slice(0, separator).trim());
    const action = parseOnDirectiveAction(value.slice(separator + 2).trim());

    if (!eventSpec || !action) {
      return null;
    }

    return {
      key: String(index) + ":" + value.trim(),
      event: eventSpec,
      action: action,
      raw: value.trim(),
    };
  }

  function parseOnDirectiveRules(value) {
    return splitTopLevelDirectiveEntries(value, "|")
      .map(function (entry, index) {
        return parseOnDirectiveRule(entry, index);
      })
      .filter(function (entry) {
        return !!entry;
      });
  }

  function dispatchDirectiveDisabled(element) {
    if (!element || typeof element.getAttribute !== "function") {
      return true;
    }

    if ("disabled" in element && !!element.disabled) {
      return true;
    }

    return (
      (element.getAttribute("aria-disabled") || "").toLowerCase() === "true"
    );
  }

  function dispatchDirectiveScopeId(root) {
    if (!root || typeof root.getAttribute !== "function") {
      return currentClientStateScope();
    }

    return (
      root.getAttribute("data-volt-component") ||
      root.getAttribute("data-volt-scope-id") ||
      currentClientStateScope()
    );
  }

  function dispatchDirectiveDetail(root, trigger, directive, originalEvent) {
    return {
      sourceElement: trigger,
      directive: directive,
      scopeId: dispatchDirectiveScopeId(root),
      clientScope: currentClientStateScope(),
      sharedScope: "shared",
      component:
        root && typeof root.getAttribute === "function"
          ? root.getAttribute("data-volt-component") || null
          : null,
      originalEvent: originalEvent,
    };
  }

  function emitNamedDispatchDirectiveEvent(
    trigger,
    root,
    name,
    directive,
    originalEvent,
  ) {
    if (!trigger || !name || dispatchDirectiveDisabled(trigger)) {
      return false;
    }

    trigger.dispatchEvent(
      new CustomEvent(name, {
        detail: dispatchDirectiveDetail(
          root,
          trigger,
          directive || "dispatch:" + name,
          originalEvent,
        ),
        bubbles: true,
        cancelable: true,
      }),
    );

    return true;
  }

  function emitDispatchDirective(trigger, root, directiveAttributeValue, event) {
    const events = parseDispatchDirectiveEvents(directiveAttributeValue);

    if (!trigger || events.length === 0 || dispatchDirectiveDisabled(trigger)) {
      return false;
    }

    const detail = dispatchDirectiveDetail(
      root,
      trigger,
      directiveAttributeValue,
      event,
    );

    events.forEach(function (name) {
      trigger.dispatchEvent(
        new CustomEvent(name, {
          detail: detail,
          bubbles: true,
          cancelable: true,
        }),
      );
    });

    return true;
  }

  function matchesOnDirectiveEvent(rule, eventName, event) {
    if (!rule || !rule.event || rule.event.name !== eventName) {
      return false;
    }

    if (
      (eventName === "keydown" || eventName === "keyup") &&
      rule.event.keyFilter
    ) {
      return (
        normalizeOnDirectiveKeyFilter(event && event.key) ===
        rule.event.keyFilter
      );
    }

    return true;
  }

  function runOnDirectiveAction(rule, trigger, root, event) {
    if (!rule || !rule.action) {
      return false;
    }

    if (rule.action.type === "dispatch") {
      return emitNamedDispatchDirectiveEvent(
        trigger,
        root,
        rule.action.name,
        "volt:on=" + rule.raw,
        event,
      );
    }

    if (rule.action.type === "set") {
      const resolved = onDirectiveActionValue(rule.action.valueExpression, event);

      if (!resolved.valid) {
        return false;
      }

      setRuntimeStateValue(rule.action.path, resolved.value, {
        scope: rule.action.scope,
        action: "directive:on:set",
      });
      return true;
    }

    if (rule.action.type === "toggle") {
      const current = getRuntimeStateValue(rule.action.path, {
        scope: rule.action.scope,
        fallback: false,
      });
      setRuntimeStateValue(rule.action.path, !current, {
        scope: rule.action.scope,
        action: "directive:on:toggle",
      });
      return true;
    }

    if (rule.action.type === "delete") {
      return deleteRuntimeStateValue(rule.action.path, {
        scope: rule.action.scope,
      });
    }

    return false;
  }

  function handleOnDirectiveEvent(eventName, event) {
    const trigger = closestFromEventTarget(event, onDirectiveSelector());

    if (!trigger) {
      return false;
    }

    const rules = parseOnDirectiveRules(directiveValue(trigger, onDirectiveNames()));

    if (rules.length === 0) {
      return false;
    }

    const root = findRoot(trigger);
    let handled = false;

    rules.forEach(function (rule) {
      if (!matchesOnDirectiveEvent(rule, eventName, event)) {
        return;
      }

      if (rule.event.modifiers.self && event.target !== trigger) {
        return;
      }

      const onceStore = onDirectiveOnceStore(trigger);

      if (rule.event.modifiers.once && onceStore.has(rule.key)) {
        return;
      }

      if (rule.event.modifiers.prevent && event.cancelable) {
        event.preventDefault();
      }

      if (rule.event.modifiers.stop) {
        event.stopPropagation();
      }

      if (runOnDirectiveAction(rule, trigger, root, event)) {
        handled = true;

        if (rule.event.modifiers.once) {
          onceStore.add(rule.key);
        }
      }
    });

    return handled;
  }

  function normalizeNavigationUrl(url) {
    try {
      return new URL(url, window.location.href).toString();
    } catch (error) {
      return String(url || "");
    }
  }

  function cloneStateValue(value) {
    if (typeof structuredClone === "function") {
      try {
        return structuredClone(value);
      } catch (error) {}
    }

    if (value === null || typeof value !== "object") {
      return value;
    }

    try {
      return JSON.parse(JSON.stringify(value));
    } catch (error) {
      return value;
    }
  }

  function normalizeRuntimeStateScope(scope) {
    return scope === "shared" ? "shared" : "client";
  }

  function normalizeRuntimeStateKey(key) {
    if (typeof key !== "string") {
      return null;
    }

    const normalized = key.trim();
    return normalized !== "" ? normalized : null;
  }

  function currentClientStateScope() {
    if (!runtime.clientStateScope) {
      runtime.clientStateScope = normalizeNavigationUrl(window.location.href);
    }

    return runtime.clientStateScope;
  }

  function runtimeStateStore(scope) {
    return normalizeRuntimeStateScope(scope) === "shared"
      ? runtime.sharedStateValues
      : runtime.clientStateValues;
  }

  function runtimeStateSubscriberStore(scope) {
    return normalizeRuntimeStateScope(scope) === "shared"
      ? runtime.sharedStateSubscribers
      : runtime.clientStateSubscribers;
  }

  function runtimeStateGlobalSubscribers(scope) {
    return normalizeRuntimeStateScope(scope) === "shared"
      ? runtime.sharedStateGlobalSubscribers
      : runtime.clientStateGlobalSubscribers;
  }

  function runtimeStateSnapshot(scope) {
    const snapshot = {};

    runtimeStateStore(scope).forEach(function (value, key) {
      snapshot[key] = cloneStateValue(value);
    });

    return snapshot;
  }

  function hasRuntimeStateValue(key, options) {
    const normalizedKey = normalizeRuntimeStateKey(key);

    if (!normalizedKey) {
      return false;
    }

    return runtimeStateStore(options && options.scope).has(normalizedKey);
  }

  function normalizeStatePathSegments(path) {
    if (typeof path !== "string" || path.trim() === "") {
      return [];
    }

    return path
      .split(".")
      .map(function (segment) {
        return segment.trim();
      })
      .filter(function (segment) {
        return segment !== "";
      });
  }

  function resolveValueBySegments(value, segments) {
    let current = value;

    for (let index = 0; index < segments.length; index += 1) {
      const segment = segments[index];

      if (Array.isArray(current)) {
        const numericIndex = Number(segment);

        if (
          !Number.isInteger(numericIndex) ||
          numericIndex < 0 ||
          numericIndex >= current.length
        ) {
          return {
            found: false,
            value: null,
          };
        }

        current = current[numericIndex];
        continue;
      }

      if (
        current === null ||
        typeof current !== "object" ||
        !Object.prototype.hasOwnProperty.call(current, segment)
      ) {
        return {
          found: false,
          value: null,
        };
      }

      current = current[segment];
    }

    return {
      found: true,
      value: current,
    };
  }

  function runtimeStateValueByPath(scope, path) {
    const segments = normalizeStatePathSegments(path);

    if (segments.length === 0) {
      return {
        found: false,
        value: null,
      };
    }

    for (let length = segments.length; length >= 1; length -= 1) {
      const stateKey = segments.slice(0, length).join(".");

      if (
        !hasRuntimeStateValue(stateKey, {
          scope: scope,
        })
      ) {
        continue;
      }

      const value = getRuntimeStateValue(stateKey, {
        scope: scope,
      });
      const nested = resolveValueBySegments(value, segments.slice(length));

      return {
        found: nested.found,
        value: nested.found ? cloneStateValue(nested.value) : null,
      };
    }

    return {
      found: false,
      value: null,
    };
  }

  function notifyRuntimeStateSubscribers(detail) {
    const scope = normalizeRuntimeStateScope(detail && detail.scope);
    const key = detail && detail.key ? detail.key : null;
    const subscriberStore = runtimeStateSubscriberStore(scope);
    const globalSubscribers = runtimeStateGlobalSubscribers(scope);

    if (key && subscriberStore.has(key)) {
      subscriberStore.get(key).forEach(function (listener) {
        try {
          listener(detail);
        } catch (error) {
          console.error("VoltStack state subscriber error:", error);
        }
      });
    }

    globalSubscribers.forEach(function (listener) {
      try {
        listener(detail);
      } catch (error) {
        console.error("VoltStack state subscriber error:", error);
      }
    });
  }

  function emitRuntimeStateChanged(
    scope,
    key,
    value,
    previousValue,
    action,
    extra,
  ) {
    const detail = Object.assign(
      {
        scope: normalizeRuntimeStateScope(scope),
        scopeId:
          normalizeRuntimeStateScope(scope) === "client"
            ? currentClientStateScope()
            : "shared",
        key: key,
        value: cloneStateValue(value),
        previousValue: cloneStateValue(previousValue),
        action: action || "set",
        snapshot: runtimeStateSnapshot(scope),
      },
      extra || {},
    );

    emitRuntimeHook("volt:state-changed", detail, document);
    notifyRuntimeStateSubscribers(detail);
  }

  function emitRuntimeStateCleared(scope, keys, reason, extra) {
    const detail = Object.assign(
      {
        scope: normalizeRuntimeStateScope(scope),
        scopeId:
          normalizeRuntimeStateScope(scope) === "client"
            ? currentClientStateScope()
            : "shared",
        keys: Array.isArray(keys) ? keys.slice() : [],
        reason: reason || "manual",
        snapshot: runtimeStateSnapshot(scope),
      },
      extra || {},
    );

    emitRuntimeHook("volt:state-cleared", detail, document);
    notifyRuntimeStateSubscribers(detail);
  }

  function getRuntimeStateValue(key, options) {
    const normalizedKey = normalizeRuntimeStateKey(key);

    if (!normalizedKey) {
      return null;
    }

    const settings = options && typeof options === "object" ? options : {};
    const store = runtimeStateStore(settings.scope);
    const fallback = Object.prototype.hasOwnProperty.call(settings, "fallback")
      ? settings.fallback
      : null;

    return store.has(normalizedKey)
      ? cloneStateValue(store.get(normalizedKey))
      : fallback;
  }

  function setRuntimeStateValue(key, value, options) {
    const normalizedKey = normalizeRuntimeStateKey(key);

    if (!normalizedKey) {
      return null;
    }

    const settings = options && typeof options === "object" ? options : {};
    const scope = normalizeRuntimeStateScope(settings.scope);
    const store = runtimeStateStore(scope);
    const previousValue = store.has(normalizedKey)
      ? store.get(normalizedKey)
      : null;

    store.set(normalizedKey, value);
    syncAllRuntimeStateDirectives();
    emitRuntimeStateChanged(
      scope,
      normalizedKey,
      value,
      previousValue,
      settings.action || "set",
    );
    return cloneStateValue(value);
  }

  function mergeRuntimeStateValue(key, value, options) {
    const current = getRuntimeStateValue(key, options);
    const nextValue = Object.assign(
      {},
      current && typeof current === "object" && !Array.isArray(current)
        ? current
        : {},
      value && typeof value === "object" && !Array.isArray(value) ? value : {},
    );

    return setRuntimeStateValue(
      key,
      nextValue,
      Object.assign({}, options || {}, {
        action: "merge",
      }),
    );
  }

  function updateRuntimeStateValue(key, updater, options) {
    const current = getRuntimeStateValue(key, options);
    const nextValue =
      typeof updater === "function" ? updater(current) : updater;

    return setRuntimeStateValue(
      key,
      nextValue,
      Object.assign({}, options || {}, {
        action: "update",
      }),
    );
  }

  function deleteRuntimeStateValue(key, options) {
    const normalizedKey = normalizeRuntimeStateKey(key);

    if (!normalizedKey) {
      return false;
    }

    const settings = options && typeof options === "object" ? options : {};
    const scope = normalizeRuntimeStateScope(settings.scope);
    const store = runtimeStateStore(scope);

    if (!store.has(normalizedKey)) {
      return false;
    }

    const previousValue = store.get(normalizedKey);
    store.delete(normalizedKey);
    syncAllRuntimeStateDirectives();
    emitRuntimeStateChanged(
      scope,
      normalizedKey,
      null,
      previousValue,
      "delete",
    );
    return true;
  }

  function clearRuntimeState(scope, reason, extra) {
    const normalizedScope = normalizeRuntimeStateScope(scope);
    const store = runtimeStateStore(normalizedScope);
    const keys = Array.from(store.keys());

    if (keys.length === 0) {
      return false;
    }

    store.clear();
    syncAllRuntimeStateDirectives();
    emitRuntimeStateCleared(normalizedScope, keys, reason, extra);
    return true;
  }

  function transitionClientStateScope(nextUrl, reason) {
    const nextScope = normalizeNavigationUrl(nextUrl || window.location.href);
    const previousScope = currentClientStateScope();

    if (previousScope === nextScope) {
      return false;
    }

    const hadValues = clearRuntimeState("client", reason || "navigation", {
      previousScopeId: previousScope,
      nextScopeId: nextScope,
    });

    runtime.clientStateScope = nextScope;

    emitRuntimeHook(
      "volt:state-scope-changed",
      {
        scope: "client",
        previousScopeId: previousScope,
        nextScopeId: nextScope,
        cleared: hadValues,
        reason: reason || "navigation",
      },
      document,
    );

    return true;
  }

  function subscribeRuntimeState(key, listener, options) {
    if (typeof listener !== "function") {
      return function () {
        return false;
      };
    }

    const settings = options && typeof options === "object" ? options : {};
    const scope = normalizeRuntimeStateScope(settings.scope);
    const normalizedKey = normalizeRuntimeStateKey(key);

    if (!normalizedKey) {
      const listeners = runtimeStateGlobalSubscribers(scope);
      listeners.add(listener);

      return function () {
        return listeners.delete(listener);
      };
    }

    const subscriberStore = runtimeStateSubscriberStore(scope);

    if (!subscriberStore.has(normalizedKey)) {
      subscriberStore.set(normalizedKey, new Set());
    }

    subscriberStore.get(normalizedKey).add(listener);

    return function () {
      const listeners = subscriberStore.get(normalizedKey);

      if (!listeners) {
        return false;
      }

      const deleted = listeners.delete(listener);

      if (listeners.size === 0) {
        subscriberStore.delete(normalizedKey);
      }

      return deleted;
    };
  }

  function createPublicStateApi() {
    return {
      get: function (key, options) {
        return getRuntimeStateValue(key, options);
      },
      set: function (key, value, options) {
        return setRuntimeStateValue(key, value, options);
      },
      merge: function (key, value, options) {
        return mergeRuntimeStateValue(key, value, options);
      },
      update: function (key, updater, options) {
        return updateRuntimeStateValue(key, updater, options);
      },
      delete: function (key, options) {
        return deleteRuntimeStateValue(key, options);
      },
      clear: function (options) {
        const settings = options && typeof options === "object" ? options : {};
        return clearRuntimeState(settings.scope, settings.reason || "manual");
      },
      snapshot: function (options) {
        const settings = options && typeof options === "object" ? options : {};
        return runtimeStateSnapshot(settings.scope);
      },
      subscribe: function (key, listener, options) {
        return subscribeRuntimeState(key, listener, options);
      },
      currentScope: function () {
        return currentClientStateScope();
      },
    };
  }

  function storeDirectiveNames(state, suffix) {
    const parts = [state];

    if (suffix) {
      parts.push(suffix);
    }

    const dashed = "volt-" + parts.join("-");
    const colon = "volt:" + parts.join(".");

    return [dashed, colon];
  }

  function showDirectiveNames(suffix) {
    return storeDirectiveNames("show", suffix);
  }

  function classDirectiveNames(suffix) {
    return storeDirectiveNames("class", suffix);
  }

  function attrDirectiveNames(suffix) {
    return storeDirectiveNames("attr", suffix);
  }

  function styleDirectiveNames(suffix) {
    return storeDirectiveNames("style", suffix);
  }

  function ifDirectiveNames(suffix) {
    return storeDirectiveNames("if", suffix);
  }

  function forDirectiveNames(suffix) {
    return storeDirectiveNames("for", suffix);
  }

  function textDirectiveNames(suffix) {
    return storeDirectiveNames("text", suffix);
  }

  function parseStoreDirectiveExpression(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return null;
    }

    const matches = value
      .trim()
      .match(/^(!)?\s*(client|shared):([A-Za-z0-9_.-]+)$/i);

    if (!matches) {
      return null;
    }

    return {
      negate: matches[1] === "!",
      scope: normalizeRuntimeStateScope(matches[2]),
      path: matches[3],
      raw: value.trim(),
    };
  }

  function tokenizeStoreConditionExpression(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return null;
    }

    const tokens = [];
    let index = 0;

    while (index < value.length) {
      const character = value[index];

      if (/\s/.test(character)) {
        index += 1;
        continue;
      }

      if (value.slice(index, index + 2) === "&&") {
        tokens.push({
          type: "and",
          value: "&&",
        });
        index += 2;
        continue;
      }

      if (value.slice(index, index + 2) === "||") {
        tokens.push({
          type: "or",
          value: "||",
        });
        index += 2;
        continue;
      }

      if (value.slice(index, index + 3) === "===") {
        tokens.push({
          type: "comparison",
          operator: "===",
          value: "===",
        });
        index += 3;
        continue;
      }

      if (value.slice(index, index + 3) === "!==") {
        tokens.push({
          type: "comparison",
          operator: "!==",
          value: "!==",
        });
        index += 3;
        continue;
      }

      if (value.slice(index, index + 2) === "==") {
        tokens.push({
          type: "comparison",
          operator: "==",
          value: "==",
        });
        index += 2;
        continue;
      }

      if (value.slice(index, index + 2) === "!=") {
        tokens.push({
          type: "comparison",
          operator: "!=",
          value: "!=",
        });
        index += 2;
        continue;
      }

      if (value.slice(index, index + 2) === ">=") {
        tokens.push({
          type: "comparison",
          operator: ">=",
          value: ">=",
        });
        index += 2;
        continue;
      }

      if (value.slice(index, index + 2) === "<=") {
        tokens.push({
          type: "comparison",
          operator: "<=",
          value: "<=",
        });
        index += 2;
        continue;
      }

      if (character === "!") {
        tokens.push({
          type: "not",
          value: "!",
        });
        index += 1;
        continue;
      }

      if (character === "(") {
        tokens.push({
          type: "lparen",
          value: "(",
        });
        index += 1;
        continue;
      }

      if (character === ")") {
        tokens.push({
          type: "rparen",
          value: ")",
        });
        index += 1;
        continue;
      }

      if (character === ">") {
        tokens.push({
          type: "comparison",
          operator: ">",
          value: ">",
        });
        index += 1;
        continue;
      }

      if (character === "<") {
        tokens.push({
          type: "comparison",
          operator: "<",
          value: "<",
        });
        index += 1;
        continue;
      }

      if (character === "'" || character === '"') {
        let endIndex = index + 1;
        let escaping = false;

        while (endIndex < value.length) {
          const currentCharacter = value[endIndex];

          if (escaping) {
            escaping = false;
            endIndex += 1;
            continue;
          }

          if (currentCharacter === "\\") {
            escaping = true;
            endIndex += 1;
            continue;
          }

          if (currentCharacter === character) {
            break;
          }

          endIndex += 1;
        }

        if (endIndex >= value.length || value[endIndex] !== character) {
          return null;
        }

        const rawLiteral = value.slice(index, endIndex + 1);
        const parsedLiteral = parseDirectiveStringLiteral(rawLiteral);

        if (parsedLiteral === null) {
          return null;
        }

        tokens.push({
          type: "literal",
          value: parsedLiteral,
          raw: rawLiteral,
        });
        index = endIndex + 1;
        continue;
      }

      const referenceMatches = value
        .slice(index)
        .match(/^(client|shared):([A-Za-z0-9_.-]+)/i);

      if (referenceMatches) {
        tokens.push({
          type: "ref",
          value: referenceMatches[0],
          scope: normalizeRuntimeStateScope(referenceMatches[1]),
          path: referenceMatches[2],
        });
        index += referenceMatches[0].length;
        continue;
      }

      const literalMatches = value.slice(index).match(/^(true|false)\b/i);

      if (literalMatches) {
        tokens.push({
          type: "literal",
          value: literalMatches[0].toLowerCase() === "true",
          raw: literalMatches[0],
        });
        index += literalMatches[0].length;
        continue;
      }

      const nullMatches = value.slice(index).match(/^null\b/i);

      if (nullMatches) {
        tokens.push({
          type: "literal",
          value: null,
          raw: nullMatches[0],
        });
        index += nullMatches[0].length;
        continue;
      }

      const numberMatches = value.slice(index).match(/^-?\d+(?:\.\d+)?\b/);

      if (numberMatches) {
        tokens.push({
          type: "literal",
          value: Number(numberMatches[0]),
          raw: numberMatches[0],
        });
        index += numberMatches[0].length;
        continue;
      }

      return null;
    }

    return tokens;
  }

  function parseStoreConditionPrimary(tokens, state) {
    const token = tokens[state.index];

    if (!token) {
      return null;
    }

    if (token.type === "literal") {
      state.index += 1;
      return {
        type: "literal",
        value: token.value,
      };
    }

    if (token.type === "ref") {
      state.index += 1;
      return {
        type: "ref",
        scope: token.scope,
        path: token.path,
      };
    }

    if (token.type === "lparen") {
      state.index += 1;
      const expression = parseStoreConditionOr(tokens, state);

      if (
        !expression ||
        !tokens[state.index] ||
        tokens[state.index].type !== "rparen"
      ) {
        return null;
      }

      state.index += 1;
      return expression;
    }

    return null;
  }

  function parseStoreConditionUnary(tokens, state) {
    const token = tokens[state.index];

    if (token && token.type === "not") {
      state.index += 1;
      const argument = parseStoreConditionUnary(tokens, state);

      if (!argument) {
        return null;
      }

      return {
        type: "not",
        argument: argument,
      };
    }

    return parseStoreConditionPrimary(tokens, state);
  }

  function parseStoreConditionComparison(tokens, state) {
    let left = parseStoreConditionUnary(tokens, state);

    if (!left) {
      return null;
    }

    while (
      tokens[state.index] &&
      tokens[state.index].type === "comparison"
    ) {
      const operator = tokens[state.index].operator;
      state.index += 1;
      const right = parseStoreConditionUnary(tokens, state);

      if (!right) {
        return null;
      }

      left = {
        type: "comparison",
        operator: operator,
        left: left,
        right: right,
      };
    }

    return left;
  }

  function parseStoreConditionAnd(tokens, state) {
    let left = parseStoreConditionComparison(tokens, state);

    if (!left) {
      return null;
    }

    while (tokens[state.index] && tokens[state.index].type === "and") {
      state.index += 1;
      const right = parseStoreConditionComparison(tokens, state);

      if (!right) {
        return null;
      }

      left = {
        type: "and",
        left: left,
        right: right,
      };
    }

    return left;
  }

  function parseStoreConditionOr(tokens, state) {
    let left = parseStoreConditionAnd(tokens, state);

    if (!left) {
      return null;
    }

    while (tokens[state.index] && tokens[state.index].type === "or") {
      state.index += 1;
      const right = parseStoreConditionAnd(tokens, state);

      if (!right) {
        return null;
      }

      left = {
        type: "or",
        left: left,
        right: right,
      };
    }

    return left;
  }

  function parseStoreConditionExpression(value) {
    const tokens = tokenizeStoreConditionExpression(value);

    if (!tokens || tokens.length === 0) {
      return null;
    }

    const state = {
      index: 0,
    };
    const ast = parseStoreConditionOr(tokens, state);

    if (!ast || state.index !== tokens.length) {
      return null;
    }

    return {
      ast: ast,
      raw: value.trim(),
    };
  }

  function resolveStoreConditionNodeValue(node) {
    if (!node) {
      return undefined;
    }

    if (node.type === "literal") {
      return node.value;
    }

    if (node.type === "ref") {
      const result = runtimeStateValueByPath(node.scope, node.path);
      return result.found ? result.value : undefined;
    }

    if (node.type === "comparison") {
      return evaluateStoreConditionComparison(
        node.operator,
        resolveStoreConditionNodeValue(node.left),
        resolveStoreConditionNodeValue(node.right),
      );
    }

    return evaluateStoreConditionNode(node);
  }

  function evaluateStoreConditionComparison(operator, leftValue, rightValue) {
    switch (operator) {
      case "===":
        return leftValue === rightValue;
      case "!==":
        return leftValue !== rightValue;
      case "==":
        return leftValue == rightValue;
      case "!=":
        return leftValue != rightValue;
      case ">":
        return leftValue > rightValue;
      case "<":
        return leftValue < rightValue;
      case ">=":
        return leftValue >= rightValue;
      case "<=":
        return leftValue <= rightValue;
      default:
        return false;
    }
  }

  function evaluateStoreConditionNode(node) {
    if (!node) {
      return false;
    }

    if (node.type === "literal") {
      return !!resolveStoreConditionNodeValue(node);
    }

    if (node.type === "ref") {
      return !!resolveStoreConditionNodeValue(node);
    }

    if (node.type === "comparison") {
      return !!resolveStoreConditionNodeValue(node);
    }

    if (node.type === "not") {
      return !evaluateStoreConditionNode(node.argument);
    }

    if (node.type === "and") {
      return (
        evaluateStoreConditionNode(node.left) &&
        evaluateStoreConditionNode(node.right)
      );
    }

    if (node.type === "or") {
      return (
        evaluateStoreConditionNode(node.left) ||
        evaluateStoreConditionNode(node.right)
      );
    }

    return false;
  }

  function parseDirectiveStringLiteral(value) {
    if (typeof value !== "string" || value.length < 2) {
      return null;
    }

    const quote = value[0];

    if ((quote !== "'" && quote !== '"') || value[value.length - 1] !== quote) {
      return null;
    }

    let result = "";
    let escaping = false;

    for (let index = 1; index < value.length - 1; index += 1) {
      const character = value[index];

      if (escaping) {
        switch (character) {
          case "n":
            result += "\n";
            break;
          case "r":
            result += "\r";
            break;
          case "t":
            result += "\t";
            break;
          case "\\":
          case "'":
          case '"':
            result += character;
            break;
          default:
            result += character;
            break;
        }

        escaping = false;
        continue;
      }

      if (character === "\\") {
        escaping = true;
        continue;
      }

      result += character;
    }

    if (escaping) {
      result += "\\";
    }

    return result;
  }

  function matchesTopLevelSplitOperator(value, index, operator) {
    if (operator === "??") {
      return value.slice(index, index + 2) === "??";
    }

    if (operator === "|") {
      return (
        value[index] === "|" &&
        value[index - 1] !== "|" &&
        value[index + 1] !== "|"
      );
    }

    return false;
  }

  function splitTopLevelDirectiveEntries(value, operator) {
    if (typeof value !== "string" || value.trim() === "") {
      return [];
    }

    const entries = [];
    let current = "";
    let depth = 0;
    let quote = null;
    let escaping = false;

    for (let index = 0; index < value.length; index += 1) {
      const character = value[index];

      if (quote !== null) {
        current += character;

        if (escaping) {
          escaping = false;
          continue;
        }

        if (character === "\\") {
          escaping = true;
          continue;
        }

        if (character === quote) {
          quote = null;
        }

        continue;
      }

      if (character === "'" || character === '"') {
        quote = character;
        current += character;
        continue;
      }

      if (character === "(") {
        depth += 1;
        current += character;
        continue;
      }

      if (character === ")") {
        depth = Math.max(0, depth - 1);
        current += character;
        continue;
      }

      if (depth === 0 && matchesTopLevelSplitOperator(value, index, operator)) {
        if (current.trim() !== "") {
          entries.push(current.trim());
        }

        current = "";
        index += operator.length - 1;
        continue;
      }

      current += character;
    }

    if (current.trim() !== "") {
      entries.push(current.trim());
    }

    return entries;
  }

  function findTopLevelArrow(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return -1;
    }

    let depth = 0;
    let quote = null;
    let escaping = false;

    for (let index = 0; index < value.length; index += 1) {
      const character = value[index];

      if (quote !== null) {
        if (escaping) {
          escaping = false;
          continue;
        }

        if (character === "\\") {
          escaping = true;
          continue;
        }

        if (character === quote) {
          quote = null;
        }

        continue;
      }

      if (character === "'" || character === '"') {
        quote = character;
        continue;
      }

      if (character === "(") {
        depth += 1;
        continue;
      }

      if (character === ")") {
        depth = Math.max(0, depth - 1);
        continue;
      }

      if (depth === 0 && character === "-" && value[index + 1] === ">") {
        return index;
      }
    }

    return -1;
  }

  function parseStoreTextDirectiveSegment(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return null;
    }

    const trimmed = value.trim();
    const literal = parseDirectiveStringLiteral(trimmed);

    if (literal !== null) {
      return {
        type: "literal",
        value: literal,
        raw: trimmed,
      };
    }

    if (/^(true|false)$/i.test(trimmed)) {
      return {
        type: "literal",
        value: trimmed.toLowerCase() === "true",
        raw: trimmed,
      };
    }

    if (/^null$/i.test(trimmed)) {
      return {
        type: "literal",
        value: null,
        raw: trimmed,
      };
    }

    if (/^-?\d+(?:\.\d+)?$/.test(trimmed)) {
      return {
        type: "literal",
        value: Number(trimmed),
        raw: trimmed,
      };
    }

    const expression = parseStoreDirectiveExpression(trimmed);

    if (!expression) {
      return null;
    }

    return {
      type: "ref",
      expression: expression,
      raw: trimmed,
    };
  }

  function parseStoreTextDirectiveExpression(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return null;
    }

    const entries = splitTopLevelDirectiveEntries(value, "??");

    if (entries.length === 0) {
      return null;
    }

    const segments = entries.map(function (entry) {
      return parseStoreTextDirectiveSegment(entry);
    });

    if (
      segments.some(function (segment) {
        return !segment;
      })
    ) {
      return null;
    }

    return {
      segments: segments,
      raw: value.trim(),
    };
  }

  function resolveStoreDirectiveActive(value) {
    const expression = parseStoreConditionExpression(value);

    if (!expression) {
      return false;
    }

    return evaluateStoreConditionNode(expression.ast);
  }

  function resolveStoreDirectiveValue(value) {
    const expression = parseStoreTextDirectiveExpression(value);

    if (!expression) {
      return {
        found: false,
        value: null,
      };
    }

    if (
      expression.segments.length === 1 &&
      expression.segments[0] &&
      expression.segments[0].type === "ref"
    ) {
      return runtimeStateValueByPath(
        expression.segments[0].expression.scope,
        expression.segments[0].expression.path,
      );
    }

    for (let index = 0; index < expression.segments.length; index += 1) {
      const segment = expression.segments[index];

      if (segment.type === "literal") {
        return {
          found: true,
          value: segment.value,
        };
      }

      if (segment.type === "ref") {
        const result = runtimeStateValueByPath(
          segment.expression.scope,
          segment.expression.path,
        );

        if (
          result.found &&
          result.value !== null &&
          typeof result.value !== "undefined"
        ) {
          return result;
        }
      }
    }

    return {
      found: false,
      value: null,
    };
  }

  function formatStoreDirectiveTextValue(value) {
    if (value === null || typeof value === "undefined") {
      return "";
    }

    if (typeof value === "object") {
      try {
        return JSON.stringify(value);
      } catch (error) {
        return "";
      }
    }

    return String(value);
  }

  function parseForDirectiveExpression(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return null;
    }

    const matches = value
      .trim()
      .match(
        /^([A-Za-z_$][A-Za-z0-9_$]*)(?:\s*,\s*([A-Za-z_$][A-Za-z0-9_$]*))?\s+in\s+(client|shared):([A-Za-z0-9_.-]+)$/i,
      );

    if (!matches) {
      return null;
    }

    return {
      itemAlias: matches[1],
      indexAlias: matches[2] || "index",
      scope: normalizeRuntimeStateScope(matches[3]),
      path: matches[4],
      raw: value.trim(),
    };
  }

  function parseStoreClassDirectiveRule(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return null;
    }

    const separator = findTopLevelArrow(value);

    if (separator === -1) {
      return null;
    }

    const expression = parseStoreConditionExpression(
      value.slice(0, separator).trim(),
    );
    const classValue = value.slice(separator + 2).trim();

    if (!expression || classValue === "") {
      return null;
    }

    return {
      expression: expression,
      classValue: classValue,
      raw: value.trim(),
    };
  }

  function parseStoreClassDirectiveRules(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return [];
    }

    return splitTopLevelDirectiveEntries(value, "|")
      .map(function (entry) {
        return parseStoreClassDirectiveRule(entry);
      })
      .filter(function (entry) {
        return !!entry;
      });
  }

  function parseStoreAttrDirectiveRule(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return null;
    }

    const separator = findTopLevelArrow(value);

    if (separator === -1) {
      return null;
    }

    const expression = parseStoreConditionExpression(
      value.slice(0, separator).trim(),
    );
    const attributes = parseDirectiveAttributes(
      value.slice(separator + 2).trim(),
    );

    if (!expression || attributes.length === 0) {
      return null;
    }

    return {
      expression: expression,
      attributes: attributes,
      raw: value.trim(),
    };
  }

  function parseStoreAttrDirectiveRules(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return [];
    }

    return splitTopLevelDirectiveEntries(value, "|")
      .map(function (entry) {
        return parseStoreAttrDirectiveRule(entry);
      })
      .filter(function (entry) {
        return !!entry;
      });
  }

  function parseStoreStyleDirectiveRule(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return null;
    }

    const separator = findTopLevelArrow(value);

    if (separator === -1) {
      return null;
    }

    const expression = parseStoreConditionExpression(
      value.slice(0, separator).trim(),
    );
    const styles = parseDirectiveStyles(value.slice(separator + 2).trim());

    if (!expression || styles.length === 0) {
      return null;
    }

    return {
      expression: expression,
      styles: styles,
      raw: value.trim(),
    };
  }

  function parseStoreStyleDirectiveRules(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return [];
    }

    return splitTopLevelDirectiveEntries(value, "|")
      .map(function (entry) {
        return parseStoreStyleDirectiveRule(entry);
      })
      .filter(function (entry) {
        return !!entry;
      });
  }

  function createDirectivePlaceholder(type) {
    const placeholder = document.createElement("template");
    const id = "volt-" + type + "-" + String(runtime.directiveSequence + 1);

    runtime.directiveSequence += 1;
    placeholder.setAttribute("data-volt-" + type + "-placeholder", id);

    return placeholder;
  }

  function createIfDirectivePlaceholder() {
    return createDirectivePlaceholder("if");
  }

  function createForDirectivePlaceholder() {
    return createDirectivePlaceholder("for");
  }

  function removeDirectiveAttributes(element, names) {
    if (
      !element ||
      element.nodeType !== Node.ELEMENT_NODE ||
      !Array.isArray(names)
    ) {
      return;
    }

    names.forEach(function (name) {
      element.removeAttribute(name);
    });
  }

  function resolveForInterpolationValue(expression, context) {
    if (typeof expression !== "string" || expression.trim() === "") {
      return "";
    }

    const normalized = expression.trim();
    const aliases = [context.itemAlias, context.indexAlias];

    for (let index = 0; index < aliases.length; index += 1) {
      const alias = aliases[index];

      if (!alias) {
        continue;
      }

      if (normalized === alias) {
        return context[alias];
      }

      if (normalized.indexOf(alias + ".") === 0) {
        const nested = resolveValueBySegments(
          context[alias],
          normalizeStatePathSegments(normalized.slice(alias.length + 1)),
        );
        return nested.found ? nested.value : "";
      }
    }

    return "";
  }

  function interpolateForTemplateString(template, context) {
    if (typeof template !== "string" || template.indexOf("{{") === -1) {
      return template;
    }

    return template.replace(/\{\{\s*([^}]+)\s*\}\}/g, function (_, expression) {
      const value = resolveForInterpolationValue(expression, context);

      if (value === null || typeof value === "undefined") {
        return "";
      }

      if (typeof value === "object") {
        try {
          return JSON.stringify(value);
        } catch (error) {
          return "";
        }
      }

      return String(value);
    });
  }

  function applyForTemplateInterpolation(node, context) {
    if (!node || node.nodeType !== Node.ELEMENT_NODE) {
      return;
    }

    const elements = [node].concat(
      Array.prototype.slice.call(node.querySelectorAll("*")),
    );

    elements.forEach(function (element) {
      element.getAttributeNames().forEach(function (name) {
        element.setAttribute(
          name,
          interpolateForTemplateString(
            element.getAttribute(name) || "",
            context,
          ),
        );
      });
    });

    const walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT);
    let textNode = walker.nextNode();

    while (textNode) {
      textNode.textContent = interpolateForTemplateString(
        textNode.textContent || "",
        context,
      );
      textNode = walker.nextNode();
    }
  }

  function ensureIfDirectiveBinding(element) {
    if (!element || element.nodeType !== Node.ELEMENT_NODE) {
      return null;
    }

    if (element.__voltIfBinding) {
      return element.__voltIfBinding;
    }

    const placeholder = createIfDirectivePlaceholder();
    const binding = {
      id: placeholder.getAttribute("data-volt-if-placeholder"),
      placeholder: placeholder,
      templateNode: element.cloneNode(true),
      currentNode: element,
    };

    placeholder.__voltIfBinding = binding;
    element.__voltIfBinding = binding;
    element.parentNode.insertBefore(placeholder, element);
    return binding;
  }

  function ensureForDirectiveBinding(element) {
    if (!element || element.nodeType !== Node.ELEMENT_NODE) {
      return null;
    }

    if (element.__voltForBinding) {
      return element.__voltForBinding;
    }

    const expression = parseForDirectiveExpression(
      directiveValue(element, forDirectiveNames()),
    );

    if (!expression || !element.parentNode) {
      return null;
    }

    const placeholder = createForDirectivePlaceholder();
    const templateNode = element.cloneNode(true);
    removeDirectiveAttributes(templateNode, forDirectiveNames());

    const binding = {
      id: placeholder.getAttribute("data-volt-for-placeholder"),
      expression: expression,
      placeholder: placeholder,
      templateNode: templateNode,
      currentNodes: [],
    };

    placeholder.__voltForBinding = binding;
    element.__voltForBinding = binding;
    element.parentNode.insertBefore(placeholder, element);
    element.remove();

    return binding;
  }

  function syncIfBinding(binding) {
    if (!binding || !binding.placeholder || !binding.templateNode) {
      return false;
    }

    const active = resolveStoreDirectiveActive(
      directiveValue(binding.templateNode, ifDirectiveNames()),
    );
    const currentNode =
      binding.currentNode && binding.currentNode.isConnected
        ? binding.currentNode
        : null;

    if (active) {
      if (currentNode) {
        currentNode.__voltIfBinding = binding;
        binding.currentNode = currentNode;
        return false;
      }

      const nextNode = binding.templateNode.cloneNode(true);
      nextNode.__voltIfBinding = binding;
      binding.currentNode = nextNode;
      binding.placeholder.parentNode.insertBefore(
        nextNode,
        binding.placeholder.nextSibling,
      );
      return true;
    }

    if (!currentNode) {
      binding.currentNode = null;
      return false;
    }

    currentNode.remove();
    binding.currentNode = null;
    return true;
  }

  function syncIfDirectives(root) {
    if (!root) {
      return false;
    }

    let mutated = false;

    collectElementsWithDirectiveAttributes(root, ifDirectiveNames()).forEach(
      function (element) {
        ensureIfDirectiveBinding(element);
      },
    );

    collectElementsWithDirectiveAttributes(root, [
      "data-volt-if-placeholder",
    ]).forEach(function (placeholder) {
      if (syncIfBinding(placeholder.__voltIfBinding || null)) {
        mutated = true;
      }
    });

    return mutated;
  }

  function syncForBinding(binding) {
    if (
      !binding ||
      !binding.placeholder ||
      !binding.templateNode ||
      !binding.expression
    ) {
      return;
    }

    const result = runtimeStateValueByPath(
      binding.expression.scope,
      binding.expression.path,
    );
    const items =
      result.found && Array.isArray(result.value) ? result.value : [];
    const fragment = document.createDocumentFragment();

    binding.currentNodes.forEach(function (node) {
      if (node && node.isConnected) {
        node.remove();
      }
    });

    binding.currentNodes = [];

    items.forEach(function (item, index) {
      const node = binding.templateNode.cloneNode(true);
      const context = {
        itemAlias: binding.expression.itemAlias,
        indexAlias: binding.expression.indexAlias,
      };

      context[binding.expression.itemAlias] = cloneStateValue(item);
      context[binding.expression.indexAlias] = index;

      applyForTemplateInterpolation(node, context);
      binding.currentNodes.push(node);
      fragment.appendChild(node);
    });

    binding.placeholder.parentNode.insertBefore(
      fragment,
      binding.placeholder.nextSibling,
    );
  }

  function syncForDirectives(root) {
    if (!root) {
      return;
    }

    collectElementsWithDirectiveAttributes(root, forDirectiveNames()).forEach(
      function (element) {
        ensureForDirectiveBinding(element);
      },
    );

    collectElementsWithDirectiveAttributes(root, [
      "data-volt-for-placeholder",
    ]).forEach(function (placeholder) {
      syncForBinding(placeholder.__voltForBinding || null);
    });
  }

  function syncShowDirectives(root) {
    if (!root) {
      return;
    }

    collectElementsWithDirectiveAttributes(root, showDirectiveNames()).forEach(
      function (element) {
        const directive = directiveValue(element, showDirectiveNames());
        const active = resolveStoreDirectiveActive(directive);

        applyDirectiveVisibility(
          element,
          "show",
          active,
          false,
        );
      },
    );

    collectElementsWithDirectiveAttributes(
      root,
      showDirectiveNames("hide"),
    ).forEach(function (element) {
      applyDirectiveVisibility(
        element,
        "show",
        resolveStoreDirectiveActive(
          directiveValue(element, showDirectiveNames("hide")),
        ),
        true,
      );
    });
  }

  function syncTextDirectives(root) {
    if (!root) {
      return;
    }

    collectElementsWithDirectiveAttributes(root, textDirectiveNames()).forEach(
      function (element) {
        const directive = directiveValue(element, textDirectiveNames());
        const result = resolveStoreDirectiveValue(directive);
        element.textContent = result.found
          ? formatStoreDirectiveTextValue(result.value)
          : "";
      },
    );
  }

  function syncAttrDirectives(root) {
    if (!root) {
      return;
    }

    collectElementsWithDirectiveAttributes(root, attrDirectiveNames()).forEach(
      function (element) {
        const directives = parseStoreAttrDirectiveRules(
          directiveValue(element, attrDirectiveNames()),
        );

        if (directives.length === 0) {
          return;
        }

        directives.forEach(function (directive) {
          applyDirectiveAttributes(
            element,
            "store:" + directive.expression.raw + "->" + directive.raw,
            evaluateStoreConditionNode(directive.expression.ast),
            directive.attributes,
          );
        });
      },
    );
  }

  function syncStyleDirectives(root) {
    if (!root) {
      return;
    }

    collectElementsWithDirectiveAttributes(root, styleDirectiveNames()).forEach(
      function (element) {
        const directives = parseStoreStyleDirectiveRules(
          directiveValue(element, styleDirectiveNames()),
        );

        if (directives.length === 0) {
          return;
        }

        directives.forEach(function (directive) {
          applyDirectiveStyles(
            element,
            evaluateStoreConditionNode(directive.expression.ast),
            directive.styles,
            "store:" + directive.expression.raw + "->" + directive.raw,
          );
        });
      },
    );
  }

  function syncAllStoreDirectives() {
    document
      .querySelectorAll('[data-volt-root="true"]')
      .forEach(function (root) {
        syncForDirectives(root);

        let iterations = 0;

        while (syncIfDirectives(root) && iterations < 5) {
          iterations += 1;
        }

        syncTextDirectives(root);
        syncClassDirectives(root);
        syncAttrDirectives(root);
        syncStyleDirectives(root);
        syncShowDirectives(root);
      });
  }

  function parseStateSyncRuleValue(entry) {
    if (typeof entry !== "string") {
      return null;
    }

    const matches = entry
      .trim()
      .match(
        /^(client|shared):([A-Za-z0-9_.-]+)\s*->\s*(params|updates)\.([A-Za-z_][A-Za-z0-9_]*)$/i,
      );

    if (!matches) {
      return null;
    }

    return {
      scope: normalizeRuntimeStateScope(matches[1]),
      sourcePath: matches[2],
      destination: matches[3].toLowerCase(),
      field: matches[4],
      raw: entry.trim(),
    };
  }

  function parseStateSyncRules(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return [];
    }

    return value
      .split(",")
      .map(function (entry) {
        return parseStateSyncRuleValue(entry);
      })
      .filter(function (rule) {
        return rule !== null;
      });
  }

  function stateSyncRulesForElement(element) {
    if (!element || !element.getAttribute) {
      return [];
    }

    const value = directiveValue(element, [
      "data-volt-state-sync",
      "volt-state-sync",
      "volt:state-sync",
    ]);
    return parseStateSyncRules(value || "");
  }

  function collectStateSyncRules(root, trigger) {
    const rules = [];

    stateSyncRulesForElement(root).forEach(function (rule) {
      rules.push(rule);
    });

    if (trigger && trigger !== root) {
      stateSyncRulesForElement(trigger).forEach(function (rule) {
        rules.push(rule);
      });
    }

    return rules;
  }

  function applySelectiveStateSync(
    root,
    trigger,
    params,
    updates,
    requestMeta,
  ) {
    const nextParams = Object.assign({}, params || {});
    const nextUpdates = Object.assign({}, updates || {});
    const rules = collectStateSyncRules(root, trigger);
    const applied = [];
    const skipped = [];

    rules.forEach(function (rule) {
      const result = runtimeStateValueByPath(rule.scope, rule.sourcePath);

      if (!result.found) {
        skipped.push({
          rule: rule.raw,
          scope: rule.scope,
          sourcePath: rule.sourcePath,
          destination: rule.destination,
          field: rule.field,
          reason: "missing-source",
        });
        return;
      }

      if (rule.destination === "updates") {
        nextUpdates[rule.field] = result.value;
      } else {
        nextParams[rule.field] = result.value;
      }

      applied.push({
        rule: rule.raw,
        scope: rule.scope,
        sourcePath: rule.sourcePath,
        destination: rule.destination,
        field: rule.field,
        value: cloneStateValue(result.value),
      });
    });

    if (applied.length > 0 || skipped.length > 0) {
      emitRuntimeHook(
        "volt:state-sync",
        requestHookDetail("action", requestMeta, {
          applied: applied,
          skipped: skipped,
          params: cloneStateValue(nextParams),
          updates: cloneStateValue(nextUpdates),
        }),
        resolveRuntimeRoot(root, requestMeta.component) || root || document,
      );
    }

    return {
      params: nextParams,
      updates: nextUpdates,
      applied: applied,
      skipped: skipped,
    };
  }

  function navigationUrlForElement(link) {
    if (!link || !link.getAttribute) {
      return null;
    }

    const href = link.getAttribute("href");

    if (!href || href.startsWith("#")) {
      return null;
    }

    try {
      const url = new URL(href, window.location.href);

      if (!sameOrigin(url)) {
        return null;
      }

      return url.toString();
    } catch (error) {
      return null;
    }
  }

  function prefetchModeTokensForElement(link) {
    const attribute = directiveAttribute(link, [
      "volt-prefetch",
      "volt:prefetch",
    ]);

    if (!attribute) {
      return ["auto"];
    }

    const value = (attribute.value || "").trim().toLowerCase();

    if (value === "") {
      return ["auto"];
    }

    return value.split(/[\s,|]+/).filter(function (token) {
      return token !== "";
    });
  }

  function linkAllowsPrefetchSource(link, source) {
    const tokens = prefetchModeTokensForElement(link);

    if (
      tokens.includes("none") ||
      tokens.includes("off") ||
      tokens.includes("false")
    ) {
      return false;
    }

    if (
      tokens.includes("auto") ||
      tokens.includes("all") ||
      tokens.includes("eager") ||
      tokens.includes("true")
    ) {
      return true;
    }

    if (source === "intent") {
      return (
        tokens.includes("hover") ||
        tokens.includes("focus") ||
        tokens.includes("intent")
      );
    }

    if (source === "viewport") {
      return tokens.includes("viewport") || tokens.includes("visible");
    }

    if (source === "idle") {
      return tokens.includes("idle") || tokens.includes("heuristic");
    }

    return false;
  }

  function normalizeHeadAssetUrl(url) {
    if (!url) {
      return "";
    }

    try {
      return new URL(url, window.location.href).toString();
    } catch (error) {
      return String(url);
    }
  }

  function parseNavigationDocument(html) {
    const parser = new DOMParser();
    return parser.parseFromString(html, "text/html");
  }

  function navigationCacheControlTokens(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return [];
    }

    return value
      .trim()
      .toLowerCase()
      .split(/[\s,|;]+/)
      .filter(function (token) {
        return token !== "";
      });
  }

  function mergeNavigationCacheControl(baseControl, overrideControl) {
    const base =
      baseControl && typeof baseControl === "object" ? baseControl : {};
    const override =
      overrideControl && typeof overrideControl === "object"
        ? overrideControl
        : {};

    return {
      mode:
        override.mode && override.mode !== "default"
          ? override.mode
          : base.mode || "default",
      ttl:
        override.ttl !== null && typeof override.ttl !== "undefined"
          ? override.ttl
          : typeof base.ttl === "number"
            ? base.ttl
            : null,
      raw: override.raw || base.raw || "",
      source: override.source || base.source || "default",
    };
  }

  function parseNavigationCacheControl(value, source) {
    const tokens = navigationCacheControlTokens(value);
    const control = {
      mode: "default",
      ttl: null,
      raw: typeof value === "string" ? value : "",
      source: source || "default",
    };

    tokens.forEach(function (token) {
      if (token === "no-store" || token === "store=none") {
        control.mode = "no-store";
        return;
      }

      if (
        control.mode !== "no-store" &&
        (token === "reload" ||
          token === "refresh" ||
          token === "network-only" ||
          token === "no-cache" ||
          token === "revalidate" ||
          token === "bypass")
      ) {
        control.mode = "reload";
        return;
      }

      if (
        control.mode === "default" &&
        (token === "invalidate" ||
          token === "reset" ||
          token === "refresh-cache")
      ) {
        control.mode = "invalidate";
        return;
      }

      const equalsMatch = token.match(/^(ttl|max-age)=(.+)$/);
      const colonMatch = token.match(/^(ttl|max-age):(.+)$/);
      const ttlMatch = equalsMatch || colonMatch;

      if (!ttlMatch) {
        return;
      }

      const parsedTtl = parseDirectiveTimeout(ttlMatch[2]);

      if (parsedTtl !== null) {
        control.ttl = parsedTtl;
      }
    });

    return control;
  }

  function navigationCacheControlForElement(element) {
    if (!element || typeof element.getAttribute !== "function") {
      return parseNavigationCacheControl("", "default");
    }

    const attribute = directiveAttribute(element, [
      "volt-cache",
      "volt:cache",
      "data-volt-cache",
    ]);

    if (!attribute) {
      return parseNavigationCacheControl("", "default");
    }

    return parseNavigationCacheControl(attribute.value, "element");
  }

  function navigationCacheControlForDocument(doc) {
    if (!doc || !doc.head || typeof doc.head.querySelector !== "function") {
      return parseNavigationCacheControl("", "default");
    }

    for (
      let index = 0;
      index < NAVIGATION_CACHE_CONTROL_META_NAMES.length;
      index += 1
    ) {
      const name = NAVIGATION_CACHE_CONTROL_META_NAMES[index];
      const meta = doc.head.querySelector(
        'meta[name="' + cssEscape(name) + '"]',
      );

      if (meta) {
        return parseNavigationCacheControl(
          meta.getAttribute("content") || "",
          "document",
        );
      }
    }

    return parseNavigationCacheControl("", "default");
  }

  function parseNavigationMode(value, source) {
    const normalized =
      typeof value === "string" ? value.trim().toLowerCase() : "";

    if (
      normalized === "reload" ||
      normalized === "full-reload" ||
      normalized === "hard-reload" ||
      normalized === "document"
    ) {
      return {
        mode: "reload",
        raw: normalized,
        source: source || "default",
      };
    }

    if (
      normalized === "spa" ||
      normalized === "soft" ||
      normalized === "client"
    ) {
      return {
        mode: "spa",
        raw: normalized,
        source: source || "default",
      };
    }

    return {
      mode: "auto",
      raw: normalized,
      source: source || "default",
    };
  }

  function navigationModeForElement(element) {
    if (!element || typeof element.getAttribute !== "function") {
      return parseNavigationMode("", "default");
    }

    const navigateAttribute = directiveAttribute(element, [
      "volt-navigate",
      "volt:navigate",
    ]);

    if (navigateAttribute) {
      return parseNavigationMode(
        navigateAttribute.value || "",
        navigateAttribute.name,
      );
    }

    const modeAttribute = directiveAttribute(element, [
      "data-volt-navigation-mode",
      "volt-navigation-mode",
      "volt:navigation-mode",
    ]);

    if (modeAttribute) {
      return parseNavigationMode(modeAttribute.value || "", modeAttribute.name);
    }

    return parseNavigationMode("", "default");
  }

  function navigationModeForDocument(doc) {
    if (!doc || typeof doc !== "object") {
      return parseNavigationMode("", "default");
    }

    if (doc.head && typeof doc.head.querySelector === "function") {
      for (
        let index = 0;
        index < NAVIGATION_MODE_META_NAMES.length;
        index += 1
      ) {
        const name = NAVIGATION_MODE_META_NAMES[index];
        const meta = doc.head.querySelector(
          'meta[name="' + cssEscape(name) + '"]',
        );

        if (meta) {
          return parseNavigationMode(
            meta.getAttribute("content") || "",
            "document",
          );
        }
      }
    }

    if (doc.body && typeof doc.body.getAttribute === "function") {
      const attribute = directiveAttribute(doc.body, [
        "data-volt-navigation-mode",
        "volt-navigation-mode",
        "volt:navigation-mode",
      ]);

      if (attribute) {
        return parseNavigationMode(attribute.value || "", "body");
      }
    }

    return parseNavigationMode("", "default");
  }

  function shouldPrefetchForNavigationMode(mode) {
    const navigationMode = mode && mode.mode ? mode.mode : "auto";
    return navigationMode !== "reload";
  }

  function firstAttributeValue(element, names) {
    if (
      !element ||
      typeof element.getAttribute !== "function" ||
      !Array.isArray(names)
    ) {
      return null;
    }

    for (let index = 0; index < names.length; index += 1) {
      const name = names[index];

      if (element.hasAttribute(name)) {
        return element.getAttribute(name) || "";
      }
    }

    return null;
  }

  function firstDocumentMetaValue(doc, names) {
    if (
      !doc ||
      !doc.head ||
      typeof doc.head.querySelector !== "function" ||
      !Array.isArray(names)
    ) {
      return null;
    }

    for (let index = 0; index < names.length; index += 1) {
      const name = names[index];
      const meta = doc.head.querySelector(
        'meta[name="' + cssEscape(name) + '"]',
      );

      if (meta) {
        return meta.getAttribute("content") || "";
      }
    }

    return null;
  }

  function escapeRegExp(value) {
    return String(value).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  }

  function firstHtmlMetaValue(html, names) {
    if (typeof html !== "string" || html === "" || !Array.isArray(names)) {
      return null;
    }

    for (let index = 0; index < names.length; index += 1) {
      const name = escapeRegExp(names[index]);
      const nameFirst = new RegExp(
        "<meta[^>]*name=[\"']" +
          name +
          "[\"'][^>]*content=[\"']([^\"']*)[\"'][^>]*>",
        "i",
      );
      const contentFirst = new RegExp(
        "<meta[^>]*content=[\"']([^\"']*)[\"'][^>]*name=[\"']" +
          name +
          "[\"'][^>]*>",
        "i",
      );
      const nameFirstMatch = html.match(nameFirst);

      if (nameFirstMatch && typeof nameFirstMatch[1] === "string") {
        return nameFirstMatch[1];
      }

      const contentFirstMatch = html.match(contentFirst);

      if (contentFirstMatch && typeof contentFirstMatch[1] === "string") {
        return contentFirstMatch[1];
      }
    }

    return null;
  }

  function normalizePageTransitionMode(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return "out-in";
    }

    const normalized = value.trim().toLowerCase();

    if (normalized === "in-out") {
      return "in-out";
    }

    return "out-in";
  }

  function normalizePageTransitionProfile(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return null;
    }

    const normalized = value.trim().toLowerCase();
    return Object.prototype.hasOwnProperty.call(
      PAGE_TRANSITION_PROFILES,
      normalized,
    )
      ? normalized
      : null;
  }

  function resolvePageTransitionProfile(value) {
    const profileName = normalizePageTransitionProfile(value);

    if (!profileName) {
      return null;
    }

    const profile = PAGE_TRANSITION_PROFILES[profileName];

    if (!profile) {
      return null;
    }

    return Object.assign(
      {
        profile: profileName,
      },
      profile,
    );
  }

  function parsePageTransition(value, source) {
    const raw = typeof value === "string" ? value : "";
    const normalized = raw.trim().toLowerCase();
    const transition = {
      name: null,
      duration: null,
      mode: "out-in",
      raw: raw,
      source: source || "default",
      declared: normalized !== "",
    };

    if (!normalized) {
      return transition;
    }

    if (
      normalized === "none" ||
      normalized === "off" ||
      normalized === "false" ||
      normalized === "disabled"
    ) {
      return transition;
    }

    transition.name =
      normalized === "true" || normalized === "on" ? "default" : normalized;

    return transition;
  }

  function applyPageTransitionOptions(transition, durationValue, modeValue) {
    const nextTransition = Object.assign({}, transition);
    const parsedDuration = parseDirectiveTimeout(durationValue);

    if (typeof parsedDuration === "number" && parsedDuration >= 0) {
      nextTransition.duration = parsedDuration;
    }

    if (typeof modeValue === "string" && modeValue.trim() !== "") {
      nextTransition.mode = normalizePageTransitionMode(modeValue);
    }

    return nextTransition;
  }

  function createPageTransition(
    transitionValue,
    durationValue,
    modeValue,
    source,
    profileValue,
  ) {
    const explicitTransition = parsePageTransition(
      transitionValue || "",
      source,
    );
    const profile = resolvePageTransitionProfile(profileValue);

    const nextTransition = profile
      ? {
          name: profile.name || null,
          duration:
            typeof profile.duration === "number" ? profile.duration : null,
          mode: profile.mode || "out-in",
          raw: explicitTransition.raw,
          source: source || "default",
          declared: true,
          profile: profile.profile,
        }
      : Object.assign({}, explicitTransition, {
          profile: null,
        });

    if (explicitTransition.declared) {
      nextTransition.name = explicitTransition.name;
      nextTransition.declared = true;
    }

    return applyPageTransitionOptions(nextTransition, durationValue, modeValue);
  }

  function pageTransitionForElement(element) {
    const transitionValue = firstAttributeValue(element, [
      "data-volt-page-transition",
      "volt-page-transition",
      "volt:page-transition",
    ]);
    const profileValue = firstAttributeValue(element, [
      "data-volt-page-transition-profile",
      "volt-page-transition-profile",
      "volt:page-transition-profile",
    ]);
    const durationValue = firstAttributeValue(element, [
      "data-volt-page-transition-duration",
      "volt-page-transition-duration",
      "volt:page-transition-duration",
    ]);
    const modeValue = firstAttributeValue(element, [
      "data-volt-page-transition-mode",
      "volt-page-transition-mode",
      "volt:page-transition-mode",
    ]);

    return createPageTransition(
      transitionValue,
      durationValue,
      modeValue,
      "link",
      profileValue,
    );
  }

  function pageTransitionForDocument(doc) {
    const documentTransition = firstDocumentMetaValue(
      doc,
      NAVIGATION_PAGE_TRANSITION_META_NAMES,
    );
    const documentProfile = firstDocumentMetaValue(
      doc,
      NAVIGATION_PAGE_TRANSITION_PROFILE_META_NAMES,
    );
    const bodyTransition = firstAttributeValue(
      doc && doc.body ? doc.body : null,
      [
        "data-volt-page-transition",
        "volt-page-transition",
        "volt:page-transition",
      ],
    );
    const bodyProfile = firstAttributeValue(doc && doc.body ? doc.body : null, [
      "data-volt-page-transition-profile",
      "volt-page-transition-profile",
      "volt:page-transition-profile",
    ]);
    const transitionValue =
      documentTransition !== null ? documentTransition : bodyTransition || "";
    const profileValue =
      documentProfile !== null ? documentProfile : bodyProfile || "";
    const durationValue =
      firstDocumentMetaValue(
        doc,
        NAVIGATION_PAGE_TRANSITION_DURATION_META_NAMES,
      ) ||
      firstAttributeValue(doc && doc.body ? doc.body : null, [
        "data-volt-page-transition-duration",
        "volt-page-transition-duration",
        "volt:page-transition-duration",
      ]);
    const modeValue =
      firstDocumentMetaValue(doc, NAVIGATION_PAGE_TRANSITION_MODE_META_NAMES) ||
      firstAttributeValue(doc && doc.body ? doc.body : null, [
        "data-volt-page-transition-mode",
        "volt-page-transition-mode",
        "volt:page-transition-mode",
      ]);
    const source =
      documentTransition !== null || documentProfile !== null
        ? "document"
        : bodyTransition !== null || bodyProfile !== null
          ? "body"
          : "default";

    return createPageTransition(
      transitionValue,
      durationValue,
      modeValue,
      source,
      profileValue,
    );
  }

  function pageTransitionForPayload(payload) {
    const documentTransition =
      payload && payload.document
        ? pageTransitionForDocument(payload.document)
        : parsePageTransition("", "default");

    if (documentTransition.declared) {
      return documentTransition;
    }

    const transitionValue = firstHtmlMetaValue(
      payload && typeof payload.html === "string" ? payload.html : "",
      NAVIGATION_PAGE_TRANSITION_META_NAMES,
    );
    const profileValue = firstHtmlMetaValue(
      payload && typeof payload.html === "string" ? payload.html : "",
      NAVIGATION_PAGE_TRANSITION_PROFILE_META_NAMES,
    );
    const durationValue = firstHtmlMetaValue(
      payload && typeof payload.html === "string" ? payload.html : "",
      NAVIGATION_PAGE_TRANSITION_DURATION_META_NAMES,
    );
    const modeValue = firstHtmlMetaValue(
      payload && typeof payload.html === "string" ? payload.html : "",
      NAVIGATION_PAGE_TRANSITION_MODE_META_NAMES,
    );

    return createPageTransition(
      transitionValue || "",
      durationValue,
      modeValue,
      transitionValue !== null || profileValue !== null
        ? "document"
        : "default",
      profileValue,
    );
  }

  function resolveNavigationPageTransition(
    requestedTransition,
    documentTransition,
  ) {
    if (documentTransition && documentTransition.declared) {
      return documentTransition;
    }

    if (requestedTransition && requestedTransition.declared) {
      return requestedTransition;
    }

    return (
      documentTransition ||
      requestedTransition ||
      parsePageTransition("", "default")
    );
  }

  function hasPageTransition(transition) {
    return !!(
      transition &&
      typeof transition.name === "string" &&
      transition.name !== ""
    );
  }

  function navigationPageTransitionEffect(transition) {
    if (!hasPageTransition(transition)) {
      return null;
    }

    const phaseConfig = {
      name: transition.name,
    };

    if (typeof transition.duration === "number" && transition.duration >= 0) {
      phaseConfig.duration = transition.duration;
    }

    return {
      type: "navigation-transition",
      target: "body",
      transition: {
        leave: phaseConfig,
        enter: phaseConfig,
      },
      pageTransitionSource: transition.source || "default",
      pageTransitionMode: transition.mode || "out-in",
      pageTransitionName: transition.name,
      pageTransitionProfile: transition.profile || null,
    };
  }

  async function runPageTransitionPhase(element, phase, transition) {
    if (!element || !hasPageTransition(transition)) {
      return false;
    }

    const effect = navigationPageTransitionEffect(transition);

    if (!effect) {
      return false;
    }

    return runElementTransition(element, element, phase, effect);
  }

  function fragmentControlTokens(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return [];
    }

    return value
      .trim()
      .toLowerCase()
      .split(/[\s,|;]+/)
      .filter(function (token) {
        return token !== "";
      });
  }

  function parseFragmentControl(value, source) {
    const tokens = fragmentControlTokens(value);
    const control = {
      mode: "preserve",
      raw: typeof value === "string" ? value : "",
      source: source || "default",
    };

    tokens.forEach(function (token) {
      if (
        token === "reset" ||
        token === "discard" ||
        token === "drop" ||
        token === "no-store" ||
        token === "none" ||
        token === "off" ||
        token === "false"
      ) {
        control.mode = "reset";
        return;
      }

      if (
        token === "preserve" ||
        token === "keep" ||
        token === "on" ||
        token === "true"
      ) {
        control.mode = "preserve";
      }
    });

    return control;
  }

  function fragmentControlForDocument(doc) {
    if (!doc || typeof doc !== "object") {
      return parseFragmentControl("", "default");
    }

    if (doc.head && typeof doc.head.querySelector === "function") {
      for (
        let index = 0;
        index < NAVIGATION_FRAGMENT_CONTROL_META_NAMES.length;
        index += 1
      ) {
        const name = NAVIGATION_FRAGMENT_CONTROL_META_NAMES[index];
        const meta = doc.head.querySelector(
          'meta[name="' + cssEscape(name) + '"]',
        );

        if (meta) {
          return parseFragmentControl(
            meta.getAttribute("content") || "",
            "document",
          );
        }
      }
    }

    if (doc.body && typeof doc.body.getAttribute === "function") {
      const attribute = directiveAttribute(doc.body, [
        "data-volt-fragment-control",
        "volt-fragment-control",
        "volt:fragment-control",
      ]);

      if (attribute) {
        return parseFragmentControl(attribute.value, "body");
      }
    }

    return parseFragmentControl("", "default");
  }

  function shouldReadNavigationCache(control) {
    const mode = control && control.mode ? control.mode : "default";
    return mode !== "reload" && mode !== "no-store" && mode !== "invalidate";
  }

  function shouldStoreNavigationCache(control) {
    const mode = control && control.mode ? control.mode : "default";
    return mode !== "no-store";
  }

  function shouldPrefetchNavigation(control) {
    const mode = control && control.mode ? control.mode : "default";
    return mode !== "no-store";
  }

  function navigationCacheTtlForControl(control) {
    if (control && typeof control.ttl === "number" && control.ttl >= 0) {
      return control.ttl;
    }

    return NAVIGATION_CACHE_TTL;
  }

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
        return {
          key: "style:" + href,
          rel: "preload",
          href: href,
          as: "style",
          crossOrigin: node.getAttribute("crossorigin") || null,
        };
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

  function registerViewportPrefetchTargets(root) {
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

  function directiveSelector(names) {
    return names
      .map(function (name) {
        return "[" + name.replace(/[:.]/g, "\\$&") + "]";
      })
      .join(", ");
  }

  function collectElementsWithDirectiveAttributes(root, names) {
    const elements = [];

    if (!root || !Array.isArray(names) || names.length === 0) {
      return elements;
    }

    function matchesNames(element) {
      return names.some(function (name) {
        return element.hasAttribute(name);
      });
    }

    if (typeof root.hasAttribute === "function" && matchesNames(root)) {
      elements.push(root);
    }

    if (typeof root.querySelectorAll !== "function") {
      return elements;
    }

    root.querySelectorAll("*").forEach(function (element) {
      if (matchesNames(element)) {
        elements.push(element);
      }
    });

    return elements;
  }

  function collectDirectiveElements(root, selector) {
    const elements = [];

    if (!root || typeof root.querySelectorAll !== "function") {
      return elements;
    }

    if (typeof root.matches === "function" && root.matches(selector)) {
      elements.push(root);
    }

    root.querySelectorAll(selector).forEach(function (element) {
      elements.push(element);
    });

    return elements;
  }

  function runtimeDirectiveStore(element) {
    if (!element.__voltRuntimeDirectiveStore) {
      element.__voltRuntimeDirectiveStore = {
        visibility: {},
        attributes: {},
        classes: {},
        styles: {},
      };
    }

    return element.__voltRuntimeDirectiveStore;
  }

  function componentStatePolicies(component) {
    if (!component) {
      return [];
    }

    if (!runtime.statePolicies.has(component)) {
      runtime.statePolicies.set(component, []);
    }

    return runtime.statePolicies.get(component);
  }

  function stateDirectiveNames(state, suffix) {
    const parts = Array.isArray(suffix)
      ? suffix.filter(function (value) {
          return !!value;
        })
      : suffix
        ? [suffix]
        : [];
    let dashed = "volt-" + state;
    let dotted = "volt:" + state;

    parts.forEach(function (part) {
      dashed += "-" + part;
      dotted += "." + part;
    });

    return [dashed, dotted];
  }

  function parseDirectiveList(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return [];
    }

    return value
      .split(",")
      .map(function (entry) {
        return entry.trim();
      })
      .filter(function (entry) {
        return entry !== "";
      });
  }

  function runtimeStateContext(root, state) {
    if (!root) {
      return {
        action: null,
        target: null,
      };
    }

    return {
      action: root.getAttribute("data-volt-" + state + "-action") || null,
      target: root.getAttribute("data-volt-" + state + "-target") || null,
    };
  }

  function matchesDirectiveScope(filterValues, currentValue) {
    if (!Array.isArray(filterValues) || filterValues.length === 0) {
      return true;
    }

    if (!currentValue) {
      return false;
    }

    return filterValues.indexOf(currentValue) !== -1;
  }

  function stateDirectiveScope(element, state, shorthandValue, context) {
    const actionAttribute = directiveAttribute(
      element,
      stateDirectiveNames(state, "action"),
    );
    const targetAttribute = directiveAttribute(
      element,
      stateDirectiveNames(state, "target"),
    );
    const shorthandEntries = parseDirectiveList(shorthandValue);
    const usesActionShorthand = !!(context && context.action);

    return {
      actions: actionAttribute
        ? parseDirectiveList(actionAttribute.value)
        : usesActionShorthand
          ? shorthandEntries
          : [],
      targets: targetAttribute
        ? parseDirectiveList(targetAttribute.value)
        : usesActionShorthand
          ? []
          : shorthandEntries,
    };
  }

  function stateDirectiveIsActive(
    element,
    state,
    active,
    shorthandValue,
    context,
  ) {
    if (!active) {
      return false;
    }

    const scope = stateDirectiveScope(element, state, shorthandValue, context);

    return (
      matchesDirectiveScope(scope.actions, context.action) &&
      matchesDirectiveScope(scope.targets, context.target)
    );
  }

  function applyDirectiveVisibility(element, state, active, inverse) {
    const storeKey = state + ":" + (inverse ? "hide" : "show");
    const store = runtimeDirectiveStore(element);

    if (!store.visibility[storeKey]) {
      store.visibility[storeKey] = {
        hidden: !!element.hidden,
        ariaHidden: element.getAttribute("aria-hidden"),
        display: element.style.display || "",
      };
    }

    const shouldHide = inverse ? active : !active;
    const initialState = store.visibility[storeKey];

    if (shouldHide) {
      element.hidden = true;
      element.setAttribute("aria-hidden", "true");
      element.style.setProperty("display", "none", "important");
      return;
    }

    element.hidden = initialState.hidden;

    if (initialState.display === "") {
      element.style.removeProperty("display");
    } else {
      element.style.display = initialState.display;
    }

    if (initialState.ariaHidden === null) {
      element.removeAttribute("aria-hidden");
      return;
    }

    element.setAttribute("aria-hidden", initialState.ariaHidden);
  }

  function parseDirectiveAttributes(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return [];
    }

    return value
      .split(",")
      .map(function (token) {
        const entry = token.trim();

        if (!entry) {
          return null;
        }

        const separator = entry.indexOf("=");

        if (separator === -1) {
          return {
            name: entry,
            value: "",
          };
        }

        return {
          name: entry.slice(0, separator).trim(),
          value: entry.slice(separator + 1).trim(),
        };
      })
      .filter(function (entry) {
        return entry && entry.name;
      });
  }

  function parseDirectiveStyles(value) {
    if (typeof value !== "string" || value.trim() === "") {
      return [];
    }

    return value
      .split(";")
      .map(function (token) {
        const entry = token.trim();

        if (!entry) {
          return null;
        }

        const separator = entry.indexOf(":");

        if (separator === -1) {
          return null;
        }

        return {
          name: entry.slice(0, separator).trim(),
          value: entry.slice(separator + 1).trim(),
        };
      })
      .filter(function (entry) {
        return entry && entry.name && entry.value;
      });
  }

  function applyDirectiveAttributes(element, state, active, attributes) {
    if (!Array.isArray(attributes) || attributes.length === 0) {
      return;
    }

    const storeKey = state + ":attr";
    const store = runtimeDirectiveStore(element);

    if (!store.attributes[storeKey]) {
      store.attributes[storeKey] = {};
    }

    attributes.forEach(function (entry) {
      if (!store.attributes[storeKey].hasOwnProperty(entry.name)) {
        store.attributes[storeKey][entry.name] = element.hasAttribute(
          entry.name,
        )
          ? element.getAttribute(entry.name)
          : null;
      }

      if (active) {
        element.setAttribute(entry.name, entry.value);
        return;
      }

      const initialValue = store.attributes[storeKey][entry.name];

      if (initialValue === null) {
        element.removeAttribute(entry.name);
        return;
      }

      element.setAttribute(entry.name, initialValue);
    });
  }

  function applyDirectiveStyles(element, active, styles, storeKey) {
    if (!Array.isArray(styles) || styles.length === 0) {
      return;
    }

    const store = runtimeDirectiveStore(element);
    const styleStoreKey = storeKey || "runtime:style";

    if (!store.styles[styleStoreKey]) {
      store.styles[styleStoreKey] = {};
    }

    styles.forEach(function (entry) {
      if (
        !Object.prototype.hasOwnProperty.call(
          store.styles[styleStoreKey],
          entry.name,
        )
      ) {
        store.styles[styleStoreKey][entry.name] = {
          value: element.style.getPropertyValue(entry.name),
          priority: element.style.getPropertyPriority(entry.name),
        };
      }

      if (active) {
        element.style.setProperty(entry.name, entry.value);
        return;
      }

      const initialState = store.styles[styleStoreKey][entry.name];

      if (!initialState || initialState.value === "") {
        element.style.removeProperty(entry.name);
        return;
      }

      element.style.setProperty(
        entry.name,
        initialState.value,
        initialState.priority || "",
      );
    });
  }

  function applyDirectiveClasses(element, active, value, storeKey) {
    if (typeof value !== "string" || value.trim() === "") {
      return;
    }

    const store = runtimeDirectiveStore(element);
    const classStoreKey = storeKey || "runtime:class";

    if (!store.classes[classStoreKey]) {
      store.classes[classStoreKey] = {};
    }

    value.split(/\s+/).forEach(function (className) {
      if (!className) {
        return;
      }

      if (
        !Object.prototype.hasOwnProperty.call(
          store.classes[classStoreKey],
          className,
        )
      ) {
        store.classes[classStoreKey][className] =
          element.classList.contains(className);
      }

      const initialValue = store.classes[classStoreKey][className];

      if (!active && initialValue) {
        element.classList.add(className);
        return;
      }

      element.classList.toggle(className, active);
    });
  }

  function syncClassDirectives(root) {
    if (!root) {
      return;
    }

    collectElementsWithDirectiveAttributes(root, classDirectiveNames()).forEach(
      function (element) {
        const directives = parseStoreClassDirectiveRules(
          directiveValue(element, classDirectiveNames()),
        );

        if (directives.length === 0) {
          return;
        }

        directives.forEach(function (directive) {
          applyDirectiveClasses(
            element,
            evaluateStoreConditionNode(directive.expression.ast),
            directive.classValue,
            "store:" + directive.expression.raw + "->" + directive.classValue,
          );
        });
      },
    );
  }

  function stateDirectiveShorthandValue(element, state) {
    const attribute = directiveAttribute(element, stateDirectiveNames(state));

    return attribute ? attribute.value : "";
  }

  function parseDirectiveTimeout(value) {
    if (typeof value === "number") {
      return Number.isFinite(value) && value >= 0 ? Math.round(value) : null;
    }

    if (typeof value !== "string" || value.trim() === "") {
      return null;
    }

    const normalized = value.trim().toLowerCase();
    const match = normalized.match(/^(\d+(?:\.\d+)?)(ms|s)?$/);

    if (!match) {
      return null;
    }

    const amount = Number.parseFloat(match[1]);

    if (!Number.isFinite(amount) || amount < 0) {
      return null;
    }

    const unit = match[2] || "ms";
    const multiplier = unit === "s" ? 1000 : 1;

    return Math.round(amount * multiplier);
  }

  function runtimePolicyScopeMatches(policyValue, contextValue) {
    if (typeof policyValue !== "string" || policyValue === "") {
      return true;
    }

    return policyValue === contextValue;
  }

  function runtimePolicyValueKey(suffix) {
    switch (suffix) {
      case "delay":
        return "delay";
      case "timeout":
        return "timeout";
      case "debounce":
        return "debounce";
      case "min-duration":
        return "minDuration";
      default:
        return null;
    }
  }

  function matchingRuntimePolicyDurations(root, state, suffix, context) {
    if (!root) {
      return [];
    }

    const component = root.getAttribute("data-volt-component") || null;
    const valueKey = runtimePolicyValueKey(suffix);

    if (!component || !valueKey) {
      return [];
    }

    return componentStatePolicies(component)
      .filter(function (policy) {
        return (
          policy &&
          policy.state === state &&
          runtimePolicyScopeMatches(
            policy.scopeAction,
            context && context.action,
          ) &&
          runtimePolicyScopeMatches(
            policy.scopeTarget,
            context && context.target,
          )
        );
      })
      .map(function (policy) {
        return parseDirectiveTimeout(policy[valueKey]);
      })
      .filter(function (value) {
        return value !== null;
      });
  }

  function registerRuntimePolicy(root, effect) {
    if (
      !root ||
      !effect ||
      effect.type !== "runtime.policy" ||
      typeof effect.state !== "string" ||
      effect.state === ""
    ) {
      return false;
    }

    const component = root.getAttribute("data-volt-component") || null;

    if (!component) {
      return false;
    }

    const normalized = {
      state: effect.state,
      scopeAction:
        typeof effect.scopeAction === "string" && effect.scopeAction !== ""
          ? effect.scopeAction
          : null,
      scopeTarget:
        typeof effect.scopeTarget === "string" && effect.scopeTarget !== ""
          ? effect.scopeTarget
          : null,
      delay: parseDirectiveTimeout(effect.delay),
      timeout: parseDirectiveTimeout(effect.timeout),
      debounce: parseDirectiveTimeout(effect.debounce),
      minDuration: parseDirectiveTimeout(effect.minDuration),
    };
    const hasValues = ["delay", "timeout", "debounce", "minDuration"].some(
      function (key) {
        return normalized[key] !== null;
      },
    );
    const signature = [
      normalized.state,
      normalized.scopeAction || "",
      normalized.scopeTarget || "",
    ].join("|");
    const store = componentStatePolicies(component).filter(function (policy) {
      const policySignature = [
        policy.state,
        policy.scopeAction || "",
        policy.scopeTarget || "",
      ].join("|");

      return policySignature !== signature;
    });

    if (hasValues) {
      store.push(normalized);
    }

    runtime.statePolicies.set(component, store);
    return true;
  }

  function matchingStateDirectiveElements(
    root,
    state,
    suffix,
    active,
    contextOverride,
  ) {
    const context = contextOverride || runtimeStateContext(root, state);
    const names = stateDirectiveNames(state, suffix);

    return collectElementsWithDirectiveAttributes(root, names).filter(
      function (element) {
        return stateDirectiveIsActive(
          element,
          state,
          active,
          stateDirectiveShorthandValue(element, state),
          context,
        );
      },
    );
  }

  function resolveStateDirectiveDuration(root, state, suffix, context) {
    const values = matchingStateDirectiveElements(
      root,
      state,
      suffix,
      true,
      context,
    )
      .map(function (element) {
        return parseDirectiveTimeout(
          directiveValue(element, stateDirectiveNames(state, suffix)),
        );
      })
      .filter(function (value) {
        return value !== null;
      });
    const policyValues = matchingRuntimePolicyDurations(
      root,
      state,
      suffix,
      context || runtimeStateContext(root, state),
    );
    const allValues = values.concat(policyValues);

    if (allValues.length === 0) {
      return null;
    }

    return Math.min.apply(null, allValues);
  }

  function resolveStateDirectiveTimeout(root, state) {
    return resolveStateDirectiveDuration(root, state, "timeout");
  }

  function resolveStateDirectiveDelay(root, state, context) {
    return resolveStateDirectiveDuration(root, state, "delay", context);
  }

  function resolveStateDirectiveMinDuration(root, state, context) {
    return resolveStateDirectiveDuration(root, state, "min-duration", context);
  }

  function resolveStateDirectiveDebounce(root, state, context) {
    return resolveStateDirectiveDuration(root, state, "debounce", context);
  }

  function clearLoadingDelay(root) {
    const timeoutId = runtime.loadingDelays.get(root);

    if (timeoutId !== undefined) {
      window.clearTimeout(timeoutId);
      runtime.loadingDelays.delete(root);
    }
  }

  function clearLoadingMinDuration(root) {
    const timeoutId = runtime.loadingMinClearDelays.get(root);

    if (timeoutId !== undefined) {
      window.clearTimeout(timeoutId);
      runtime.loadingMinClearDelays.delete(root);
    }
  }

  function clearSuccessTimeout(root) {
    const timeoutId = runtime.successTimeouts.get(root);

    if (timeoutId !== undefined) {
      window.clearTimeout(timeoutId);
      runtime.successTimeouts.delete(root);
    }
  }

  function clearSuccessMinDuration(root) {
    const timeoutId = runtime.successMinClearDelays.get(root);

    if (timeoutId !== undefined) {
      window.clearTimeout(timeoutId);
      runtime.successMinClearDelays.delete(root);
    }
  }

  function scheduleLoadingDelay(root, trigger, meta) {
    if (!root) {
      return;
    }

    clearLoadingDelay(root);

    const detail = meta && typeof meta === "object" ? meta : {};
    const context = {
      action: detail.action || null,
      target: stateTargetValue(detail),
    };
    const delay = resolveStateDirectiveDelay(root, "loading", context);

    if (delay === null || delay <= 0) {
      setLoadingState(root, true, trigger, detail);
      return;
    }

    const component =
      root.getAttribute("data-volt-component") || detail.component || null;
    const requestId = detail.requestId || null;
    const timeoutId = window.setTimeout(function () {
      runtime.loadingDelays.delete(root);

      const activeRoot = resolveRuntimeRoot(root, component);
      const state = componentRequestState(component);

      if (!activeRoot || !state || state.requestId !== requestId) {
        return;
      }

      setLoadingState(
        activeRoot,
        true,
        trigger,
        Object.assign({}, detail, {
          component: component,
        }),
      );
    }, delay);

    runtime.loadingDelays.set(root, timeoutId);
  }

  function scheduleLoadingMinDurationClear(root, trigger, meta, remaining) {
    if (!root) {
      return;
    }

    clearLoadingMinDuration(root);

    const component =
      root.getAttribute("data-volt-component") ||
      (meta && meta.component) ||
      null;
    const detail = Object.assign({}, meta || {}, {
      component: component,
      reason: "min-duration",
    });

    const timeoutId = window.setTimeout(function () {
      runtime.loadingMinClearDelays.delete(root);

      const activeRoot = resolveRuntimeRoot(root, component);

      if (
        !activeRoot ||
        activeRoot.getAttribute("data-volt-loading") !== "true"
      ) {
        return;
      }

      setLoadingState(activeRoot, false, trigger, detail);
    }, remaining);

    runtime.loadingMinClearDelays.set(root, timeoutId);
  }

  function clearErrorTimeout(root) {
    const timeoutId = runtime.errorTimeouts.get(root);

    if (timeoutId !== undefined) {
      window.clearTimeout(timeoutId);
      runtime.errorTimeouts.delete(root);
    }
  }

  function clearDirtyDebounce(root) {
    const timeoutId = runtime.dirtyDebounces.get(root);

    if (timeoutId !== undefined) {
      window.clearTimeout(timeoutId);
      runtime.dirtyDebounces.delete(root);
    }
  }

  function scheduleSuccessTimeout(root, meta) {
    if (!root) {
      return;
    }

    clearSuccessTimeout(root);

    const timeout = resolveStateDirectiveTimeout(root, "success");

    if (timeout === null) {
      return;
    }

    const component =
      root.getAttribute("data-volt-component") ||
      (meta && meta.component) ||
      null;
    const detail = Object.assign({}, meta || {}, {
      component: component,
      reason: "timeout",
    });

    const timeoutId = window.setTimeout(function () {
      runtime.successTimeouts.delete(root);

      const activeRoot = resolveRuntimeRoot(root, component);

      if (
        !activeRoot ||
        activeRoot.getAttribute("data-volt-success") !== "true"
      ) {
        return;
      }

      setSuccessState(activeRoot, false, detail);
    }, timeout);

    runtime.successTimeouts.set(root, timeoutId);
  }

  function scheduleSuccessMinDurationClear(root, meta, remaining) {
    if (!root) {
      return;
    }

    clearSuccessMinDuration(root);

    const component =
      root.getAttribute("data-volt-component") ||
      (meta && meta.component) ||
      null;
    const detail = Object.assign({}, meta || {}, {
      component: component,
      reason: "min-duration",
    });

    const timeoutId = window.setTimeout(function () {
      runtime.successMinClearDelays.delete(root);

      const activeRoot = resolveRuntimeRoot(root, component);

      if (
        !activeRoot ||
        activeRoot.getAttribute("data-volt-success") !== "true"
      ) {
        return;
      }

      setSuccessState(activeRoot, false, detail);
    }, remaining);

    runtime.successMinClearDelays.set(root, timeoutId);
  }

  function scheduleErrorTimeout(root, meta) {
    if (!root) {
      return;
    }

    clearErrorTimeout(root);

    const timeout = resolveStateDirectiveTimeout(root, "error");

    if (timeout === null) {
      return;
    }

    const component =
      root.getAttribute("data-volt-component") ||
      (meta && meta.component) ||
      null;
    const detail = Object.assign({}, meta || {}, {
      component: component,
      reason: "timeout",
    });

    const timeoutId = window.setTimeout(function () {
      runtime.errorTimeouts.delete(root);

      const activeRoot = resolveRuntimeRoot(root, component);

      if (
        !activeRoot ||
        activeRoot.getAttribute("data-volt-error") !== "true"
      ) {
        return;
      }

      setErrorState(activeRoot, false, detail);
    }, timeout);

    runtime.errorTimeouts.set(root, timeoutId);
  }

  function scheduleDirtyDebounce(root, meta) {
    if (!root) {
      return;
    }

    clearDirtyDebounce(root);

    const detail = meta && typeof meta === "object" ? meta : {};
    const context = {
      action: detail.action || null,
      target: stateTargetValue(detail),
    };
    const debounce = resolveStateDirectiveDebounce(root, "dirty", context);

    if (debounce === null || debounce <= 0) {
      setDirtyState(root, true, detail);
      return;
    }

    const component =
      root.getAttribute("data-volt-component") || detail.component || null;
    const timeoutId = window.setTimeout(function () {
      runtime.dirtyDebounces.delete(root);

      const activeRoot = resolveRuntimeRoot(root, component);

      if (!activeRoot) {
        return;
      }

      setDirtyState(
        activeRoot,
        true,
        Object.assign({}, detail, {
          component: component,
          reason: "debounce",
          debounce: debounce,
        }),
      );
    }, debounce);

    runtime.dirtyDebounces.set(root, timeoutId);
  }

  function syncRuntimeStateDirective(root, state, active) {
    const showNames = stateDirectiveNames(state);
    const hideNames = stateDirectiveNames(state, "hide");
    const classNames = stateDirectiveNames(state, "class");
    const attrNames = stateDirectiveNames(state, "attr");

    collectElementsWithDirectiveAttributes(root, showNames).forEach(
      function (element) {
        applyDirectiveVisibility(
          element,
          state,
          stateDirectiveIsActive(
            element,
            state,
            active,
            stateDirectiveShorthandValue(element, state),
            runtimeStateContext(root, state),
          ),
          false,
        );
      },
    );

    collectElementsWithDirectiveAttributes(root, hideNames).forEach(
      function (element) {
        applyDirectiveVisibility(
          element,
          state,
          stateDirectiveIsActive(
            element,
            state,
            active,
            stateDirectiveShorthandValue(element, state),
            runtimeStateContext(root, state),
          ),
          true,
        );
      },
    );

    collectElementsWithDirectiveAttributes(root, classNames).forEach(
      function (element) {
        applyDirectiveClasses(
          element,
          stateDirectiveIsActive(
            element,
            state,
            active,
            stateDirectiveShorthandValue(element, state),
            runtimeStateContext(root, state),
          ),
          directiveValue(element, stateDirectiveNames(state, "class")),
          "state:" + state + ":class",
        );
      },
    );

    collectElementsWithDirectiveAttributes(root, attrNames).forEach(
      function (element) {
        applyDirectiveAttributes(
          element,
          state,
          stateDirectiveIsActive(
            element,
            state,
            active,
            stateDirectiveShorthandValue(element, state),
            runtimeStateContext(root, state),
          ),
          parseDirectiveAttributes(
            directiveValue(element, stateDirectiveNames(state, "attr")),
          ),
        );
      },
    );
  }

  function syncRuntimeStateDirectives(root) {
    if (!root) {
      return;
    }

    syncForDirectives(root);

    let iterations = 0;

    while (syncIfDirectives(root) && iterations < 5) {
      iterations += 1;
    }

    syncRuntimeStateDirective(
      root,
      "loading",
      root.getAttribute("data-volt-loading") === "true",
    );
    syncRuntimeStateDirective(
      root,
      "error",
      root.getAttribute("data-volt-error") === "true",
    );
    syncRuntimeStateDirective(
      root,
      "dirty",
      root.getAttribute("data-volt-dirty") === "true",
    );
    syncRuntimeStateDirective(
      root,
      "success",
      root.getAttribute("data-volt-success") === "true",
    );
    syncTextDirectives(root);
    syncClassDirectives(root);
    syncAttrDirectives(root);
    syncStyleDirectives(root);
    syncShowDirectives(root);
  }

  function syncAllRuntimeStateDirectives() {
    const roots = document.querySelectorAll('[data-volt-root="true"]');

    roots.forEach(function (root) {
      syncRuntimeStateDirectives(root);
    });
  }

  function elementIndex(elements, target) {
    for (let index = 0; index < elements.length; index += 1) {
      if (elements[index] === target) {
        return index;
      }
    }

    return -1;
  }

  function isTextSelectableElement(element) {
    if (!element || typeof element !== "object") {
      return false;
    }

    if (element.tagName === "TEXTAREA") {
      return true;
    }

    if (element.tagName !== "INPUT") {
      return false;
    }

    const type = (element.type || "text").toLowerCase();

    return (
      ["text", "search", "url", "tel", "password", "email", "number"].indexOf(
        type,
      ) !== -1
    );
  }

  function buildFocusDescriptor(root, element) {
    if (!element) {
      return null;
    }

    if (element.id) {
      return {
        strategy: "id",
        value: element.id,
      };
    }

    const targetName = element.getAttribute("data-volt-target");

    if (targetName) {
      return {
        strategy: "target",
        value: targetName,
      };
    }

    const modelName = directiveValue(element, ["volt-model", "volt:model"]);

    if (modelName) {
      const matches = root.querySelectorAll("[volt-model], [volt\\:model]");
      const index = elementIndex(matches, element);

      if (index !== -1) {
        return {
          strategy: "model",
          value: modelName,
          index: index,
        };
      }
    }

    const fieldName = element.getAttribute("name");

    if (fieldName) {
      const matches = root.querySelectorAll(
        '[name="' + cssEscape(fieldName) + '"]',
      );
      const index = elementIndex(matches, element);

      if (index !== -1) {
        return {
          strategy: "name",
          value: fieldName,
          index: index,
        };
      }
    }

    return null;
  }

  function buildStableElementDescriptor(element) {
    if (!element) {
      return null;
    }

    if (element.id) {
      return {
        strategy: "id",
        value: element.id,
      };
    }

    const targetName = element.getAttribute("data-volt-target");

    if (targetName) {
      return {
        strategy: "target",
        value: targetName,
      };
    }

    return null;
  }

  function findByDescriptor(root, descriptor) {
    if (!descriptor || !root) {
      return null;
    }

    if (descriptor.strategy === "id" && descriptor.value) {
      return document.getElementById(descriptor.value);
    }

    if (descriptor.strategy === "target" && descriptor.value) {
      return root.querySelector(
        '[data-volt-target="' + descriptor.value + '"]',
      );
    }

    if (descriptor.strategy === "model" && descriptor.value) {
      const matches = root.querySelectorAll(
        '[volt-model="' +
          descriptor.value +
          '"], [volt\\:model="' +
          descriptor.value +
          '"]',
      );

      return matches[descriptor.index || 0] || null;
    }

    if (descriptor.strategy === "name" && descriptor.value) {
      const matches = root.querySelectorAll(
        '[name="' + cssEscape(descriptor.value) + '"]',
      );

      return matches[descriptor.index || 0] || null;
    }

    return null;
  }

  function captureSelectionState(element) {
    if (!isTextSelectableElement(element)) {
      return null;
    }

    return {
      start:
        typeof element.selectionStart === "number"
          ? element.selectionStart
          : null,
      end:
        typeof element.selectionEnd === "number" ? element.selectionEnd : null,
      direction:
        typeof element.selectionDirection === "string"
          ? element.selectionDirection
          : "none",
      scrollTop:
        typeof element.scrollTop === "number" ? element.scrollTop : null,
      scrollLeft:
        typeof element.scrollLeft === "number" ? element.scrollLeft : null,
    };
  }

  function captureFocusState(root) {
    const activeElement = document.activeElement;

    if (!root || !activeElement || !root.contains(activeElement)) {
      return null;
    }

    const descriptor = buildFocusDescriptor(root, activeElement);

    if (!descriptor) {
      return null;
    }

    return {
      descriptor: descriptor,
      selection: captureSelectionState(activeElement),
    };
  }

  function restoreSelectionState(element, selection) {
    if (!element || !selection || !isTextSelectableElement(element)) {
      return;
    }

    if (
      typeof selection.start === "number" &&
      typeof selection.end === "number" &&
      typeof element.setSelectionRange === "function"
    ) {
      try {
        element.setSelectionRange(
          selection.start,
          selection.end,
          selection.direction || "none",
        );
      } catch (error) {}
    }

    if (typeof selection.scrollTop === "number") {
      element.scrollTop = selection.scrollTop;
    }

    if (typeof selection.scrollLeft === "number") {
      element.scrollLeft = selection.scrollLeft;
    }
  }

  function restoreFocusState(root, focusState) {
    if (!root || !focusState || !focusState.descriptor) {
      return;
    }

    const nextElement = findByDescriptor(root, focusState.descriptor);

    if (!nextElement || typeof nextElement.focus !== "function") {
      return;
    }

    nextElement.focus({
      preventScroll: true,
    });
    restoreSelectionState(nextElement, focusState.selection);
  }

  function isElementScrollRestorable(element) {
    if (!element || typeof element !== "object") {
      return false;
    }

    if (
      element.hasAttribute("data-volt-preserve-scroll") ||
      element.hasAttribute("volt-preserve-scroll") ||
      element.hasAttribute("volt:preserve-scroll")
    ) {
      return true;
    }

    return !!(
      (typeof element.scrollTop === "number" && element.scrollTop !== 0) ||
      (typeof element.scrollLeft === "number" && element.scrollLeft !== 0)
    );
  }

  function captureScrollState(root) {
    if (!root) {
      return [];
    }

    const candidates = [];
    const seen = new Set();

    function addCandidate(element) {
      if (!element || seen.has(element)) {
        return;
      }

      seen.add(element);

      if (!isElementScrollRestorable(element)) {
        return;
      }

      const descriptor = buildStableElementDescriptor(element);

      if (!descriptor) {
        return;
      }

      candidates.push({
        descriptor: descriptor,
        scrollTop:
          typeof element.scrollTop === "number" ? element.scrollTop : null,
        scrollLeft:
          typeof element.scrollLeft === "number" ? element.scrollLeft : null,
      });
    }

    addCandidate(root);
    root
      .querySelectorAll(
        "[id], [data-volt-target], [data-volt-preserve-scroll], [volt-preserve-scroll], [volt\\:preserve-scroll]",
      )
      .forEach(addCandidate);

    return candidates;
  }

  function restoreScrollState(root, scrollStates) {
    if (!root || !Array.isArray(scrollStates)) {
      return;
    }

    scrollStates.forEach(function (entry) {
      if (!entry || !entry.descriptor) {
        return;
      }

      const element = findByDescriptor(root, entry.descriptor);

      if (!element) {
        return;
      }

      if (typeof entry.scrollTop === "number") {
        element.scrollTop = entry.scrollTop;
      }

      if (typeof entry.scrollLeft === "number") {
        element.scrollLeft = entry.scrollLeft;
      }
    });
  }

  function emitRuntimeHook(name, detail, target) {
    const hookDetail = detail && typeof detail === "object" ? detail : {};
    const eventTarget = target || document;

    eventTarget.dispatchEvent(
      new CustomEvent(name, {
        detail: hookDetail,
        bubbles: true,
      }),
    );
  }

  function wait(duration) {
    return new Promise(function (resolve) {
      window.setTimeout(resolve, duration);
    });
  }

  function nextFrame() {
    return new Promise(function (resolve) {
      window.requestAnimationFrame(function () {
        window.requestAnimationFrame(resolve);
      });
    });
  }

  function transitionDurationFor(element, effect, phase) {
    if (!element) {
      return 180;
    }

    const phaseDuration = transitionConfigValue(effect, phase, "duration");

    if (typeof phaseDuration === "number" && phaseDuration >= 0) {
      return phaseDuration;
    }

    if (
      effect &&
      typeof effect.transitionDuration === "number" &&
      effect.transitionDuration >= 0
    ) {
      return effect.transitionDuration;
    }

    const phaseAttribute = element.getAttribute(
      "data-volt-transition-" + phase + "-duration",
    );
    const phaseParsed = phaseAttribute ? Number(phaseAttribute) : NaN;

    if (Number.isFinite(phaseParsed) && phaseParsed >= 0) {
      return phaseParsed;
    }

    const attributeValue = element.getAttribute(
      "data-volt-transition-duration",
    );
    const parsed = attributeValue ? Number(attributeValue) : NaN;

    return Number.isFinite(parsed) && parsed >= 0 ? parsed : 180;
  }

  function transitionConfigValue(effect, phase, key) {
    if (!effect || typeof effect !== "object") {
      return null;
    }

    if (
      effect.transition &&
      typeof effect.transition === "object" &&
      effect.transition !== null
    ) {
      const phaseConfig = effect.transition[phase];

      if (
        phaseConfig &&
        typeof phaseConfig === "object" &&
        Object.prototype.hasOwnProperty.call(phaseConfig, key)
      ) {
        return phaseConfig[key];
      }

      if (key === "name" && typeof phaseConfig === "string") {
        return phaseConfig;
      }
    }

    if (
      effect.transitions &&
      typeof effect.transitions === "object" &&
      effect.transitions !== null
    ) {
      const phaseConfig = effect.transitions[phase];

      if (
        phaseConfig &&
        typeof phaseConfig === "object" &&
        Object.prototype.hasOwnProperty.call(phaseConfig, key)
      ) {
        return phaseConfig[key];
      }

      if (key === "name" && typeof phaseConfig === "string") {
        return phaseConfig;
      }
    }

    return null;
  }

  function transitionVariantFor(element, effect, phase) {
    if (effect && effect.transition === false) {
      return null;
    }

    const phaseVariant = transitionConfigValue(effect, phase, "name");

    if (typeof phaseVariant === "string" && phaseVariant !== "") {
      return phaseVariant;
    }

    if (
      effect &&
      typeof effect.transition === "string" &&
      effect.transition !== ""
    ) {
      return effect.transition;
    }

    if (effect && effect.transition === true) {
      return "default";
    }

    if (!element) {
      return null;
    }

    const phaseAttribute = element.getAttribute(
      "data-volt-transition-" + phase,
    );

    if (phaseAttribute === "") {
      return "default";
    }

    if (phaseAttribute) {
      return phaseAttribute;
    }

    const attributeValue = element.getAttribute("data-volt-transition");

    if (attributeValue === "") {
      return "default";
    }

    return attributeValue || null;
  }

  function transitionClassListFor(element, effect, phase, variant) {
    const classes = ["volt-transition", "volt-transition-" + phase];

    if (variant) {
      classes.push("volt-transition-" + variant);
    }

    const phaseClasses = [];
    const classConfig = transitionConfigValue(effect, phase, "className");

    if (typeof classConfig === "string" && classConfig !== "") {
      phaseClasses.push(classConfig);
    }

    if (element) {
      const phaseAttribute = element.getAttribute(
        "data-volt-transition-" + phase + "-class",
      );

      if (phaseAttribute) {
        phaseClasses.push(phaseAttribute);
      }

      const globalAttribute = element.getAttribute(
        "data-volt-transition-class",
      );

      if (globalAttribute) {
        phaseClasses.push(globalAttribute);
      }
    }

    phaseClasses.forEach(function (classList) {
      classList.split(/\s+/).forEach(function (className) {
        if (className) {
          classes.push(className);
        }
      });
    });

    return classes;
  }

  async function runElementTransition(root, element, phase, effect) {
    const variant = transitionVariantFor(element, effect, phase);

    if (!element || !phase || !variant) {
      return false;
    }

    const duration = transitionDurationFor(element, effect, phase);
    const activeClass = "volt-transition-" + phase + "-active";
    const classes = transitionClassListFor(element, effect, phase, variant);
    const detail = effectHookDetail(root, effect, element, {
      phase: phase,
      variant: variant,
      duration: duration,
      transitionSource:
        effect && effect.pageTransitionSource
          ? effect.pageTransitionSource
          : null,
      transitionMode:
        effect && effect.pageTransitionMode ? effect.pageTransitionMode : null,
      transitionName:
        effect && effect.pageTransitionName ? effect.pageTransitionName : null,
    });

    emitRuntimeHook("volt:before-" + phase, detail, element);
    element.style.setProperty("--volt-transition-duration", duration + "ms");
    element.classList.add.apply(element.classList, classes);
    await nextFrame();
    element.classList.add(activeClass);
    await wait(duration);
    element.classList.remove(activeClass);
    element.classList.remove.apply(element.classList, classes);
    element.style.removeProperty("--volt-transition-duration");
    emitRuntimeHook("volt:after-" + phase, detail, element);

    return true;
  }

  function fragmentFromHtml(html) {
    if (typeof html !== "string" || html === "") {
      return null;
    }

    const template = document.createElement("template");
    template.innerHTML = html.trim();

    return template.content;
  }

  function effectHookDetail(root, effect, target, extra) {
    return Object.assign(
      {
        type: effect && effect.type ? effect.type : null,
        target:
          effect && typeof effect.target === "string" ? effect.target : null,
        selector:
          effect && typeof effect.selector === "string"
            ? effect.selector
            : null,
        component:
          root && typeof root.getAttribute === "function"
            ? root.getAttribute("data-volt-component")
            : null,
        element: target || null,
      },
      extra || {},
    );
  }

  function createEffectResult(
    root,
    effect,
    target,
    handled,
    preventsHtmlFallback,
    extra,
  ) {
    emitRuntimeHook(
      "volt:after-effect",
      effectHookDetail(
        root,
        effect,
        target,
        Object.assign(
          {
            handled: handled,
            preventsHtmlFallback: preventsHtmlFallback,
          },
          extra || {},
        ),
      ),
      target || root || document,
    );

    return {
      handled: handled,
      preventsHtmlFallback: preventsHtmlFallback,
    };
  }

  async function withPreservedUiState(root, callback, meta) {
    const detail = meta && typeof meta === "object" ? meta : {};
    const focusState = captureFocusState(root);
    const scrollState = captureScrollState(root);
    emitRuntimeHook("volt:before-patch", detail, root);
    const result = await callback();
    const updatedRoot =
      root && root.isConnected
        ? root
        : root && root.getAttribute
          ? findRootByComponent(root.getAttribute("data-volt-component"))
          : null;

    if (updatedRoot) {
      restoreScrollState(updatedRoot, scrollState);
      restoreFocusState(updatedRoot, focusState);
    }

    emitRuntimeHook(
      "volt:after-patch",
      Object.assign({}, detail, {
        updatedRoot: updatedRoot || null,
      }),
      updatedRoot || root || document,
    );

    return result;
  }

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

    if (trigger && "disabled" in trigger) {
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

  function sameOrigin(url) {
    return url.origin === window.location.origin;
  }

  function shouldHandleNavigation(event, link) {
    if (!link) {
      return false;
    }

    if (
      event.defaultPrevented ||
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey
    ) {
      return false;
    }

    if (link.hasAttribute("download")) {
      return false;
    }

    const target = link.getAttribute("target");

    if (target && target !== "" && target.toLowerCase() !== "_self") {
      return false;
    }

    const href = link.getAttribute("href");

    if (!href || href.startsWith("#")) {
      return false;
    }

    const url = new URL(href, window.location.href);

    if (!sameOrigin(url)) {
      return false;
    }

    return true;
  }

  function setNavigationState(active, trigger) {
    document.documentElement.setAttribute(
      "data-volt-navigating",
      active ? "true" : "false",
    );
    document.documentElement.setAttribute(
      "aria-busy",
      active ? "true" : "false",
    );

    if (document.body) {
      document.body.setAttribute(
        "data-volt-navigating",
        active ? "true" : "false",
      );
      document.body.setAttribute("aria-busy", active ? "true" : "false");
    }

    if (trigger && "disabled" in trigger) {
      trigger.disabled = active;
    }
  }

  function replaceBodyAttributes(nextBody) {
    const currentBody = document.body;
    const attributeNames = currentBody.getAttributeNames();

    attributeNames.forEach(function (name) {
      currentBody.removeAttribute(name);
    });

    nextBody.getAttributeNames().forEach(function (name) {
      currentBody.setAttribute(name, nextBody.getAttribute(name) || "");
    });
  }

  function preservedFragmentAttribute(element) {
    return directiveAttribute(element, [
      "data-volt-preserve",
      "volt-preserve",
      "volt:preserve",
    ]);
  }

  function preservedFragmentKey(element) {
    if (!element || typeof element.getAttribute !== "function") {
      return null;
    }

    const attribute = preservedFragmentAttribute(element);

    if (!attribute) {
      return null;
    }

    const explicitKey = (attribute.value || "").trim();

    if (explicitKey !== "") {
      return explicitKey;
    }

    const id = (element.getAttribute("id") || "").trim();

    if (id !== "") {
      return id;
    }

    const target = (element.getAttribute("data-volt-target") || "").trim();

    return target !== "" ? target : null;
  }

  function preservedFragmentCandidates(root) {
    if (!root || typeof root.querySelectorAll !== "function") {
      return [];
    }

    const candidates = [];

    if (
      typeof root.matches === "function" &&
      root.matches(NAVIGATION_FRAGMENT_SELECTOR)
    ) {
      candidates.push(root);
    }

    root
      .querySelectorAll(NAVIGATION_FRAGMENT_SELECTOR)
      .forEach(function (element) {
        candidates.push(element);
      });

    return candidates.filter(function (element) {
      const parent = element.parentElement;

      return !parent || !parent.closest(NAVIGATION_FRAGMENT_SELECTOR);
    });
  }

  function fragmentNavigationDetail(meta, extra) {
    return Object.assign(
      {
        source: meta && meta.source ? meta.source : "navigate",
        url: meta && meta.url ? meta.url : window.location.href,
        finalUrl: meta && meta.finalUrl ? meta.finalUrl : null,
      },
      extra || {},
    );
  }

  function discardPreservedFragments(fragments, meta, reason, extra) {
    if (!(fragments instanceof Map) || fragments.size === 0) {
      return {
        preservedCount: 0,
        discardedCount: 0,
      };
    }

    let discardedCount = 0;

    fragments.forEach(function (fragment) {
      discardedCount += 1;
      emitRuntimeHook(
        "volt:fragment-discard",
        fragmentNavigationDetail(
          meta,
          Object.assign(
            {
              key: fragment.key,
              tagName: fragment.tagName,
              reason: reason,
            },
            extra || {},
          ),
        ),
        document,
      );
    });

    return {
      preservedCount: 0,
      discardedCount: discardedCount,
    };
  }

  function shouldRestorePreservedFragments(control, meta) {
    const fragmentMode = control && control.mode ? control.mode : "preserve";

    if (fragmentMode === "reset") {
      return false;
    }

    const cacheControl =
      meta && meta.cacheControl && typeof meta.cacheControl === "object"
        ? meta.cacheControl
        : null;
    const cacheMode =
      cacheControl && cacheControl.mode ? cacheControl.mode : "default";

    return cacheMode !== "no-store";
  }

  function capturePreservedFragments(root, meta) {
    const fragments = new Map();

    preservedFragmentCandidates(root).forEach(function (element) {
      const key = preservedFragmentKey(element);

      if (!key) {
        emitRuntimeHook(
          "volt:fragment-discard",
          fragmentNavigationDetail(meta, {
            reason: "missing-key",
            tagName: element.tagName ? element.tagName.toLowerCase() : null,
          }),
          document,
        );
        return;
      }

      if (fragments.has(key)) {
        emitRuntimeHook(
          "volt:fragment-discard",
          fragmentNavigationDetail(meta, {
            key: key,
            reason: "duplicate-source",
            tagName: element.tagName ? element.tagName.toLowerCase() : null,
          }),
          document,
        );
        return;
      }

      fragments.set(key, {
        key: key,
        tagName: element.tagName ? element.tagName.toLowerCase() : null,
        element: element,
      });
    });

    return fragments;
  }

  function preservedFragmentTargets(root, meta) {
    const targets = new Map();

    preservedFragmentCandidates(root).forEach(function (element) {
      const key = preservedFragmentKey(element);

      if (!key) {
        emitRuntimeHook(
          "volt:fragment-discard",
          fragmentNavigationDetail(meta, {
            reason: "missing-target-key",
            tagName: element.tagName ? element.tagName.toLowerCase() : null,
          }),
          document,
        );
        return;
      }

      if (targets.has(key)) {
        emitRuntimeHook(
          "volt:fragment-discard",
          fragmentNavigationDetail(meta, {
            key: key,
            reason: "duplicate-target",
            tagName: element.tagName ? element.tagName.toLowerCase() : null,
          }),
          document,
        );
        return;
      }

      targets.set(key, element);
    });

    return targets;
  }

  function restorePreservedFragments(root, fragments, meta) {
    if (!root || !(fragments instanceof Map) || fragments.size === 0) {
      return {
        preservedCount: 0,
        discardedCount: 0,
      };
    }

    const targets = preservedFragmentTargets(root, meta);
    let preservedCount = 0;
    let discardedCount = 0;

    fragments.forEach(function (fragment) {
      const target = targets.get(fragment.key);

      if (!target) {
        discardedCount += 1;
        emitRuntimeHook(
          "volt:fragment-discard",
          fragmentNavigationDetail(meta, {
            key: fragment.key,
            tagName: fragment.tagName,
            reason: "missing-target",
          }),
          document,
        );
        return;
      }

      const targetTagName = target.tagName
        ? target.tagName.toLowerCase()
        : null;

      if (targetTagName !== fragment.tagName) {
        discardedCount += 1;
        emitRuntimeHook(
          "volt:fragment-discard",
          fragmentNavigationDetail(meta, {
            key: fragment.key,
            tagName: fragment.tagName,
            targetTagName: targetTagName,
            reason: "tag-mismatch",
          }),
          document,
        );
        return;
      }

      target.replaceWith(fragment.element);
      preservedCount += 1;

      emitRuntimeHook(
        "volt:fragment-preserve",
        fragmentNavigationDetail(meta, {
          key: fragment.key,
          tagName: fragment.tagName,
        }),
        document,
      );
    });

    return {
      preservedCount: preservedCount,
      discardedCount: discardedCount,
    };
  }

  function currentLayoutIdentity() {
    if (document.body) {
      const bodyLayout = document.body.getAttribute("data-volt-layout");

      if (bodyLayout) {
        return bodyLayout;
      }
    }

    if (document.documentElement) {
      const documentLayout =
        document.documentElement.getAttribute("data-volt-layout");

      if (documentLayout) {
        return documentLayout;
      }
    }

    return null;
  }

  function documentLayoutIdentity(doc) {
    if (!doc || typeof doc !== "object") {
      return null;
    }

    if (doc.body) {
      const bodyLayout = doc.body.getAttribute("data-volt-layout");

      if (bodyLayout) {
        return bodyLayout;
      }
    }

    if (doc.documentElement) {
      const documentLayout =
        doc.documentElement.getAttribute("data-volt-layout");

      if (documentLayout) {
        return documentLayout;
      }
    }

    return null;
  }

  function shouldFallbackForLayoutChange(doc) {
    const currentLayout = currentLayoutIdentity();
    const nextLayout = documentLayoutIdentity(doc);

    if (!currentLayout && !nextLayout) {
      return false;
    }

    return currentLayout !== nextLayout;
  }

  function setElementAttributes(target, source) {
    const nextAttributes = {};

    source.getAttributeNames().forEach(function (name) {
      nextAttributes[name] = source.getAttribute(name) || "";
    });

    target.getAttributeNames().forEach(function (name) {
      if (!Object.prototype.hasOwnProperty.call(nextAttributes, name)) {
        target.removeAttribute(name);
      }
    });

    Object.keys(nextAttributes).forEach(function (name) {
      target.setAttribute(name, nextAttributes[name]);
    });
  }

  function managedHeadNodeKey(node) {
    if (!node || node.nodeType !== 1) {
      return null;
    }

    const explicitKey = node.getAttribute("data-volt-head-key");

    if (explicitKey) {
      return "explicit:" + explicitKey;
    }

    const tag = node.tagName.toLowerCase();

    if (tag === "meta") {
      if (node.hasAttribute("name")) {
        return "meta:name:" + (node.getAttribute("name") || "");
      }

      if (node.hasAttribute("property")) {
        return "meta:property:" + (node.getAttribute("property") || "");
      }

      if (node.hasAttribute("http-equiv")) {
        return "meta:http-equiv:" + (node.getAttribute("http-equiv") || "");
      }

      return null;
    }

    if (tag === "link") {
      const rel = (node.getAttribute("rel") || "").toLowerCase();
      const href = node.getAttribute("href") || "";

      if (!rel || !href) {
        return null;
      }

      return "link:" + rel + ":" + href + ":" + (node.getAttribute("as") || "");
    }

    if (tag === "script") {
      const src = node.getAttribute("src") || "";

      if (!src) {
        return null;
      }

      return "script:" + (node.getAttribute("type") || "") + ":" + src;
    }

    if (tag === "style") {
      const styleId = node.getAttribute("id") || "";

      if (styleId) {
        return "style:id:" + styleId;
      }
    }

    return null;
  }

  function managedHeadEntries(head) {
    if (!head || !head.children) {
      return [];
    }

    const entries = [];

    Array.from(head.children).forEach(function (node) {
      const key = managedHeadNodeKey(node);

      if (key) {
        entries.push({
          key: key,
          node: node,
        });
      }
    });

    return entries;
  }

  function syncManagedHeadNode(currentNode, nextNode) {
    if (!currentNode || !nextNode) {
      return;
    }

    setElementAttributes(currentNode, nextNode);

    const tag = currentNode.tagName.toLowerCase();

    if (tag === "script" || tag === "style") {
      const nextContent = nextNode.textContent || "";

      if (currentNode.textContent !== nextContent) {
        currentNode.textContent = nextContent;
      }
    }
  }

  function waitForManagedHeadNode(node) {
    if (!node || node.tagName.toLowerCase() !== "link") {
      return Promise.resolve();
    }

    const rel = (node.getAttribute("rel") || "").toLowerCase();

    if (rel !== "stylesheet") {
      return Promise.resolve();
    }

    return new Promise(function (resolve) {
      let settled = false;

      function finish() {
        if (settled) {
          return;
        }

        settled = true;
        resolve();
      }

      if (node.sheet) {
        finish();
        return;
      }

      node.addEventListener("load", finish, { once: true });
      node.addEventListener("error", finish, { once: true });
      window.setTimeout(finish, 1500);
    });
  }

  async function reconcileDocumentHead(nextHead) {
    if (!nextHead || !document.head) {
      return;
    }

    const nextEntries = managedHeadEntries(nextHead);
    const currentEntries = managedHeadEntries(document.head);
    const nextMap = new Map();
    const currentMap = new Map();
    const pendingLoads = [];
    const hasManagedHead = nextEntries.length > 0;

    nextEntries.forEach(function (entry) {
      nextMap.set(entry.key, entry.node);
    });

    currentEntries.forEach(function (entry) {
      currentMap.set(entry.key, entry.node);
    });

    if (hasManagedHead) {
      currentEntries.forEach(function (entry) {
        if (!nextMap.has(entry.key)) {
          entry.node.remove();
        }
      });
    }

    nextEntries.forEach(function (entry) {
      const existing = currentMap.get(entry.key);

      if (existing) {
        syncManagedHeadNode(existing, entry.node);
        return;
      }

      const clone = entry.node.cloneNode(true);
      document.head.appendChild(clone);
      pendingLoads.push(waitForManagedHeadNode(clone));
    });

    if (pendingLoads.length > 0) {
      await Promise.all(pendingLoads);
    }
  }

  async function applyDocumentPayload(doc, meta) {
    const payloadMeta = meta && typeof meta === "object" ? meta : {};
    const fragmentControl = fragmentControlForDocument(doc);
    const pageTransition =
      payloadMeta.pageTransition || parsePageTransition("", "default");
    const fragmentSummary = {
      preservedCount: 0,
      discardedCount: 0,
    };

    if (doc.title) {
      document.title = doc.title;
    }

    if (doc.head) {
      await reconcileDocumentHead(doc.head);
    }

    if (doc.body) {
      const fragmentMeta = Object.assign({}, payloadMeta, {
        fragmentControl: fragmentControl,
      });
      const preservedFragments = capturePreservedFragments(
        document.body,
        fragmentMeta,
      );
      replaceBodyAttributes(doc.body);
      document.body.innerHTML = doc.body.innerHTML;
      const restoredFragments = shouldRestorePreservedFragments(
        fragmentControl,
        payloadMeta,
      )
        ? restorePreservedFragments(
            document.body,
            preservedFragments,
            fragmentMeta,
          )
        : discardPreservedFragments(
            preservedFragments,
            fragmentMeta,
            fragmentControl.mode === "reset"
              ? "document-policy"
              : "navigation-policy",
            {
              policyMode: fragmentControl.mode,
              policySource: fragmentControl.source,
              cacheMode:
                payloadMeta.cacheControl && payloadMeta.cacheControl.mode
                  ? payloadMeta.cacheControl.mode
                  : "default",
            },
          );

      fragmentSummary.preservedCount = restoredFragments.preservedCount;
      fragmentSummary.discardedCount = restoredFragments.discardedCount;
      syncAllRuntimeStateDirectives();
      registerViewportPrefetchTargets(document);
      scheduleHeuristicPrefetch(document);
      await runPageTransitionPhase(document.body, "enter", pageTransition);
    }

    return fragmentSummary;
  }

  function resolveEffectTarget(root, effect) {
    if (!effect || typeof effect !== "object") {
      return null;
    }

    if (effect.target === "root" || effect.target === "self") {
      return root;
    }

    if (typeof effect.selector === "string" && effect.selector !== "") {
      return document.querySelector(effect.selector);
    }

    if (typeof effect.target !== "string" || effect.target === "") {
      return null;
    }

    const escapedTarget = cssEscape(effect.target);
    const scopedTarget = root
      ? root.querySelector("#" + escapedTarget) ||
        root.querySelector('[data-volt-target="' + effect.target + '"]')
      : null;

    if (scopedTarget) {
      return scopedTarget;
    }

    return (
      document.getElementById(effect.target) ||
      document.querySelector('[data-volt-target="' + effect.target + '"]')
    );
  }

  function dispatchRuntimeEvent(effect, target) {
    const name = effect.name || effect.event;

    if (!name) {
      return;
    }

    const eventTarget = target || document;
    eventTarget.dispatchEvent(
      new CustomEvent(name, {
        detail: effect.payload || effect.detail || {},
        bubbles: true,
      }),
    );
  }

  function applyHtmlReplace(root, target, effect) {
    if (!target) {
      return null;
    }

    const html = typeof effect.html === "string" ? effect.html : effect.value;

    if (typeof html !== "string") {
      return null;
    }

    const descriptor = buildStableElementDescriptor(target);

    if (
      effect.outer === true ||
      effect.mode === "outer" ||
      target === document.body ||
      target.hasAttribute("data-volt-root")
    ) {
      target.outerHTML = html;

      if (target === document.body) {
        return document.body;
      }

      if (target.hasAttribute("data-volt-root")) {
        const componentName = target.getAttribute("data-volt-component");

        return componentName ? findRootByComponent(componentName) : null;
      }

      return descriptor
        ? findByDescriptor(root || document.body, descriptor)
        : null;
    }

    target.innerHTML = html;
    return target;
  }

  function applyClassToggle(target, effect) {
    if (!target) {
      return;
    }

    const className = effect.class || effect.className || effect.value;

    if (typeof className !== "string" || className === "") {
      return;
    }

    if (typeof effect.force === "boolean") {
      target.classList.toggle(className, effect.force);
      return;
    }

    target.classList.toggle(className);
  }

  function applyStyleSet(target, effect) {
    if (!target) {
      return;
    }

    if (effect.styles && typeof effect.styles === "object") {
      Object.keys(effect.styles).forEach(function (property) {
        if (effect.styles[property] === null) {
          target.style.removeProperty(property);
          return;
        }

        target.style.setProperty(property, String(effect.styles[property]));
      });

      return;
    }

    if (typeof effect.property === "string") {
      if (effect.value === null) {
        target.style.removeProperty(effect.property);
        return;
      }

      if (typeof effect.value !== "undefined") {
        target.style.setProperty(effect.property, String(effect.value));
      }
    }
  }

  function resolveContainerTarget(root, effect) {
    if (!effect || typeof effect !== "object") {
      return null;
    }

    if (typeof effect.parentTarget === "string" && effect.parentTarget !== "") {
      return resolveEffectTarget(root, { target: effect.parentTarget });
    }

    return resolveEffectTarget(root, effect);
  }

  function applyDomInsert(root, effect) {
    const container = resolveContainerTarget(root, effect);
    const fragment = fragmentFromHtml(effect.html);

    if (!container || typeof effect.html !== "string" || !fragment) {
      return [];
    }

    const insertedNodes = Array.from(fragment.childNodes);
    const insertedElements = insertedNodes.filter(function (node) {
      return node.nodeType === 1;
    });

    if (
      typeof effect.beforeSelector === "string" &&
      effect.beforeSelector !== ""
    ) {
      const anchor = document.querySelector(effect.beforeSelector);

      if (anchor) {
        anchor.parentNode.insertBefore(fragment, anchor);
        return insertedElements;
      }
    }

    if (
      (effect.position || "beforeend") === "afterbegin" &&
      container.firstChild
    ) {
      container.insertBefore(fragment, container.firstChild);
      return insertedElements;
    }

    container.appendChild(fragment);
    return insertedElements;
  }

  function applyDomMove(root, target, effect) {
    const container = resolveContainerTarget(root, effect);

    if (!target || !container) {
      return false;
    }

    if (
      typeof effect.beforeSelector === "string" &&
      effect.beforeSelector !== ""
    ) {
      const anchor = document.querySelector(effect.beforeSelector);

      if (anchor && anchor.parentNode === container) {
        container.insertBefore(target, anchor);
        return true;
      }
    }

    container.appendChild(target);
    return true;
  }

  function syncAttributeProperty(target, name, value) {
    if (!target || typeof name !== "string") {
      return;
    }

    if (name === "value" && "value" in target) {
      target.value = value;
      return;
    }

    if (name === "checked" && "checked" in target) {
      target.checked = value !== null;
      return;
    }

    if (name === "selected" && "selected" in target) {
      target.selected = value !== null;
      return;
    }

    if (name === "disabled" && "disabled" in target) {
      target.disabled = value !== null;
    }
  }

  function applyScroll(target, effect) {
    const behavior = effect.behavior === "smooth" ? "smooth" : "auto";

    if (target && typeof target.scrollIntoView === "function") {
      target.scrollIntoView({
        behavior: behavior,
        block: effect.block || "start",
        inline: effect.inline || "nearest",
      });
      return;
    }

    window.scrollTo({
      top: typeof effect.top === "number" ? effect.top : 0,
      left: typeof effect.left === "number" ? effect.left : 0,
      behavior: behavior,
    });
  }

  async function applyEffect(root, effect) {
    if (!effect || typeof effect.type !== "string") {
      return {
        handled: false,
        preventsHtmlFallback: false,
      };
    }

    const target = resolveEffectTarget(root, effect);
    emitRuntimeHook(
      "volt:before-effect",
      effectHookDetail(root, effect, target),
      target || root || document,
    );

    switch (effect.type) {
      case "text.update":
        if (target && typeof effect.value !== "undefined") {
          target.textContent = String(effect.value);
          await runElementTransition(root, target, "update", effect);
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case "html.replace": {
        const replacedTarget = applyHtmlReplace(root, target, effect);

        if (replacedTarget) {
          await runElementTransition(
            root,
            replacedTarget,
            effect.outer === true || effect.mode === "outer"
              ? "enter"
              : "update",
            effect,
          );
        }

        return createEffectResult(
          root,
          effect,
          replacedTarget || target,
          !!target,
          !!target,
        );
      }

      case "dom.append":
        if (target && typeof effect.html === "string") {
          const insertedElements = applyDomInsert(
            root,
            Object.assign({}, effect, {
              beforeSelector: null,
              position: effect.position || "beforeend",
            }),
          );

          for (let index = 0; index < insertedElements.length; index += 1) {
            await runElementTransition(
              root,
              insertedElements[index],
              "enter",
              effect,
            );
          }

          return createEffectResult(root, effect, target, true, true, {
            insertedCount: insertedElements.length,
          });
        }
        break;

      case "dom.insert":
        {
          const insertedElements = applyDomInsert(root, effect);

          if (insertedElements.length > 0) {
            for (let index = 0; index < insertedElements.length; index += 1) {
              await runElementTransition(
                root,
                insertedElements[index],
                "enter",
                effect,
              );
            }

            return createEffectResult(root, effect, target, true, true, {
              insertedCount: insertedElements.length,
            });
          }
        }
        break;

      case "dom.remove":
        if (target) {
          await runElementTransition(root, target, "leave", effect);
          target.remove();
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case "dom.move":
        if (applyDomMove(root, target, effect)) {
          await runElementTransition(root, target, "move", effect);
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case "attribute.set":
        if (target && typeof effect.name === "string") {
          const attributeValue =
            typeof effect.value === "undefined" ? "" : String(effect.value);
          target.setAttribute(effect.name, attributeValue);
          syncAttributeProperty(target, effect.name, attributeValue);
          await runElementTransition(root, target, "update", effect);
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case "attribute.remove":
        if (target && typeof effect.name === "string") {
          target.removeAttribute(effect.name);
          syncAttributeProperty(target, effect.name, null);
          await runElementTransition(root, target, "update", effect);
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case "class.toggle":
        applyClassToggle(target, effect);
        if (target) {
          await runElementTransition(root, target, "update", effect);
        }
        return createEffectResult(root, effect, target, !!target, !!target);

      case "style.set":
        applyStyleSet(target, effect);
        if (target) {
          await runElementTransition(root, target, "update", effect);
        }
        return createEffectResult(root, effect, target, !!target, !!target);

      case "focus":
        if (target && typeof target.focus === "function") {
          target.focus();
          return createEffectResult(root, effect, target, true, false);
        }
        break;

      case "blur":
        if (target && typeof target.blur === "function") {
          target.blur();
          return createEffectResult(root, effect, target, true, false);
        }
        break;

      case "scroll":
        applyScroll(target, effect);
        return createEffectResult(root, effect, target, true, false);

      case "dispatch.event":
        dispatchRuntimeEvent(effect, target);
        return createEffectResult(root, effect, target, true, false);

      case "runtime.policy": {
        const component =
          root && root.getAttribute
            ? root.getAttribute("data-volt-component")
            : null;
        const activeRoot = resolveRuntimeRoot(root, component) || root;

        return createEffectResult(
          activeRoot,
          effect,
          activeRoot,
          registerRuntimePolicy(activeRoot, effect),
          false,
        );
      }

      case "state.set":
        if (typeof effect.key === "string" && effect.key !== "") {
          setRuntimeStateValue(effect.key, effect.value, {
            scope: effect.scope,
            action: "effect",
          });
          return createEffectResult(root, effect, target, true, false);
        }
        break;

      case "state.merge":
        if (typeof effect.key === "string" && effect.key !== "") {
          mergeRuntimeStateValue(effect.key, effect.value, {
            scope: effect.scope,
          });
          return createEffectResult(root, effect, target, true, false);
        }
        break;

      case "state.delete":
        if (typeof effect.key === "string" && effect.key !== "") {
          deleteRuntimeStateValue(effect.key, {
            scope: effect.scope,
          });
          return createEffectResult(root, effect, target, true, false);
        }
        break;

      case "state.clear":
        clearRuntimeState(effect.scope, effect.reason || "effect");
        return createEffectResult(root, effect, target, true, false);

      case "navigate":
        if (typeof effect.url === "string" && effect.url !== "") {
          await visit(effect.url, {
            historyMode: effect.replace ? "replace" : "push",
            preserveScroll: !!effect.preserveScroll,
          });
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      default:
        return createEffectResult(root, effect, target, false, false);
    }

    return createEffectResult(root, effect, target, false, false);
  }

  async function applyEffects(root, effects) {
    if (!Array.isArray(effects) || effects.length === 0) {
      return {
        handled: false,
        preventsHtmlFallback: false,
      };
    }

    let handled = false;
    let preventsHtmlFallback = false;

    for (let index = 0; index < effects.length; index += 1) {
      const result = await applyEffect(root, effects[index]);
      handled = result.handled || handled;
      preventsHtmlFallback =
        result.preventsHtmlFallback || preventsHtmlFallback;
    }

    return {
      handled: handled,
      preventsHtmlFallback: preventsHtmlFallback,
    };
  }

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
      throw new Error(
        "Navigation request failed with status " + response.status + ".",
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
    runtime.navigationController = controller;

    if (previousController) {
      previousController.abort();
    }

    setNavigationState(true, settings.trigger || null);
    emitRuntimeHook(
      "volt:request-start",
      requestHookDetail("navigation", requestMeta, {
        url: normalizedUrl,
        historyMode: settings.historyMode || "push",
        cacheMode: cacheControl.mode,
        navigationMode: requestedNavigationMode.mode,
      }),
      document,
    );

    let outcome = "success";

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
        (await requestNavigationPayload(
          normalizedUrl,
          controller ? controller.signal : undefined,
          "navigate",
          {
            cacheControl: cacheControl,
            navigationMode: requestedNavigationMode,
          },
        ));

      if (runtime.navigationRequestId !== requestId) {
        outcome = "stale";
        emitRuntimeHook(
          "volt:request-stale",
          requestHookDetail("navigation", requestMeta, {
            url: normalizedUrl,
            finalUrl: payload.finalUrl,
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
      const payloadPageTransition =
        payload.document || (payload && typeof payload.html === "string")
          ? pageTransitionForPayload(payload)
          : payload.pageTransition && typeof payload.pageTransition === "object"
            ? payload.pageTransition
            : parsePageTransition("", "default");

      if (shouldFallbackForLayoutChange(payload.document)) {
        outcome = "layout-fallback";

        if (settings.fallback !== false) {
          window.location.assign(payload.finalUrl);
          return;
        }
      }

      if (payloadNavigationMode && payloadNavigationMode.mode === "reload") {
        outcome = "policy-reload";

        if (settings.fallback !== false) {
          window.location.assign(payload.finalUrl);
          return;
        }
      }

      const pageTransition = resolveNavigationPageTransition(
        settings.pageTransition,
        payloadPageTransition,
      );

      emitRuntimeHook(
        "volt:before-navigate",
        {
          url: normalizedUrl,
          finalUrl: payload.finalUrl,
          navigationMode:
            payloadNavigationMode && payloadNavigationMode.mode
              ? payloadNavigationMode.mode
              : requestedNavigationMode.mode,
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

      const navigationMutation = await withPreservedUiState(
        document.body,
        async function () {
          return applyDocumentPayload(payload.document, {
            source: "navigate",
            url: normalizedUrl,
            finalUrl: payload.finalUrl,
            cacheControl: payload.cacheControl,
            pageTransition: pageTransition,
          });
        },
        {
          type: "navigation",
          url: normalizedUrl,
          finalUrl: payload.finalUrl,
        },
      );

      if (settings.historyMode === "replace") {
        window.history.replaceState({}, "", payload.finalUrl);
      } else if (settings.updateHistory !== false) {
        window.history.pushState({}, "", payload.finalUrl);
      }

      if (settings.preserveScroll !== true) {
        window.scrollTo(0, 0);
      }

      transitionClientStateScope(payload.finalUrl, "navigation");

      emitRuntimeHook(
        "volt:navigated",
        {
          url: normalizedUrl,
          finalUrl: payload.finalUrl,
          historyMode: settings.historyMode || "push",
          navigationMode:
            payloadNavigationMode && payloadNavigationMode.mode
              ? payloadNavigationMode.mode
              : requestedNavigationMode.mode,
          pageTransition: pageTransition.name,
          pageTransitionSource: pageTransition.source || "default",
          pageTransitionMode: pageTransition.mode || "out-in",
          pageTransitionDuration:
            typeof pageTransition.duration === "number"
              ? pageTransition.duration
              : null,
          pageTransitionProfile: pageTransition.profile || null,
          preservedFragments:
            navigationMutation &&
            typeof navigationMutation.preservedCount === "number"
              ? navigationMutation.preservedCount
              : 0,
          discardedFragments:
            navigationMutation &&
            typeof navigationMutation.discardedCount === "number"
              ? navigationMutation.discardedCount
              : 0,
        },
        document,
      );
    } catch (error) {
      if (isAbortError(error)) {
        outcome = "aborted";
        emitRuntimeHook(
          "volt:request-abort",
          requestHookDetail("navigation", requestMeta, {
            url: normalizedUrl,
            outcome: outcome,
          }),
          document,
        );
        return;
      }

      outcome = "error";
      emitRuntimeHook(
        "volt:request-error",
        requestHookDetail("navigation", requestMeta, {
          url: normalizedUrl,
          message:
            error && error.message ? error.message : "Navigation failed.",
          outcome: outcome,
        }),
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

      emitRuntimeHook(
        "volt:request-finish",
        requestHookDetail("navigation", requestMeta, {
          url: normalizedUrl,
          outcome: outcome,
        }),
        document,
      );
    }
  }

  async function dispatchAction(root, action, params, updates, trigger) {
    const snapshot = root.getAttribute("data-volt-snapshot");
    const component = root.getAttribute("data-volt-component");
    const endpoint = root.getAttribute("data-volt-endpoint") || "/_volt/action";
    const csrf = root.getAttribute("data-volt-csrf");

    if (!snapshot || !component || !action) {
      return;
    }

    const state = componentRequestState(component);
    const previousController =
      state && state.controller ? state.controller : null;
    const requestId = state ? state.requestId + 1 : 1;
    const controller =
      typeof AbortController === "function" ? new AbortController() : null;
    const requestMeta = {
      component: component,
      action: action,
      requestId: requestId,
      trigger: triggerDescriptor(trigger),
    };
    const syncedPayload = applySelectiveStateSync(
      root,
      trigger,
      params,
      updates,
      requestMeta,
    );

    if (state) {
      state.requestId = requestId;
      state.controller = controller;
    }

    if (previousController) {
      previousController.abort();
    }

    clearDirtyDebounce(root);
    setErrorState(component, false, requestMeta);
    setSuccessState(
      component,
      false,
      Object.assign({}, requestMeta, {
        reason: "request",
      }),
    );

    if (trigger && "disabled" in trigger) {
      trigger.disabled = true;
    }

    scheduleLoadingDelay(root, trigger, requestMeta);
    emitRuntimeHook(
      "volt:request-start",
      requestHookDetail("action", requestMeta),
      resolveRuntimeRoot(root, component) || document,
    );

    let outcome = "success";

    try {
      const response = await fetch(endpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "VoltStack",
          "X-CSRF-TOKEN": csrf || "",
        },
        credentials: "same-origin",
        signal: controller ? controller.signal : undefined,
        body: JSON.stringify({
          component: component,
          action: action,
          params: syncedPayload.params,
          updates: syncedPayload.updates,
          snapshot: JSON.parse(snapshot),
        }),
      });

      let payload = null;

      try {
        payload = await response.json();
      } catch (error) {
        payload = null;
      }

      if (state && state.requestId !== requestId) {
        outcome = "stale";
        emitRuntimeHook(
          "volt:request-stale",
          requestHookDetail("action", requestMeta, {
            status: response.status,
            outcome: outcome,
          }),
          resolveRuntimeRoot(root, component) || document,
        );
        return;
      }

      if (!response.ok) {
        outcome = "error";
        const errorDetail = responseErrorDetail(response, payload, requestMeta);
        setErrorState(component, true, errorDetail);
        emitRuntimeHook(
          "volt:request-error",
          errorDetail,
          resolveRuntimeRoot(root, component) || document,
        );
        return;
      }

      const patchMeta = {
        type: "action",
        component: component,
        action: action,
        effects: Array.isArray(payload.effects)
          ? payload.effects
              .map(function (effect) {
                return effect && effect.type ? effect.type : null;
              })
              .filter(function (value) {
                return value !== null;
              })
          : [],
        usedHtmlFallback: false,
      };

      const patchRoot = resolveRuntimeRoot(root, component) || root;

      await withPreservedUiState(
        patchRoot,
        async function () {
          const activeRoot = resolveRuntimeRoot(root, component) || root;
          const result = await applyEffects(activeRoot, payload.effects);

          if (
            !result.preventsHtmlFallback &&
            payload.html &&
            activeRoot.isConnected
          ) {
            patchMeta.usedHtmlFallback = true;
            activeRoot.outerHTML = payload.html;
          }

          const updatedRoot = resolveRuntimeRoot(activeRoot, component);

          if (payload.snapshot && updatedRoot) {
            updatedRoot.setAttribute(
              "data-volt-snapshot",
              JSON.stringify(payload.snapshot),
            );
          }

          return result;
        },
        patchMeta,
      );

      setDirtyState(component, false, requestMeta);
      setSuccessState(component, true, requestMeta);
    } catch (error) {
      if (isAbortError(error)) {
        outcome = "aborted";
        emitRuntimeHook(
          "volt:request-abort",
          requestHookDetail("action", requestMeta, {
            outcome: outcome,
          }),
          resolveRuntimeRoot(root, component) || document,
        );
        return;
      }

      outcome = "error";
      const errorDetail = exceptionErrorDetail(error, requestMeta);
      setErrorState(component, true, errorDetail);
      emitRuntimeHook(
        "volt:request-error",
        errorDetail,
        resolveRuntimeRoot(root, component) || document,
      );
      throw error;
    } finally {
      if (state && state.requestId === requestId) {
        state.controller = null;
        clearLoadingDelay(resolveRuntimeRoot(root, component) || root);
        setLoadingState(component, false, trigger, requestMeta);
      }

      emitRuntimeHook(
        "volt:request-finish",
        requestHookDetail("action", requestMeta, {
          outcome: outcome,
        }),
        resolveRuntimeRoot(root, component) || document,
      );
    }
  }

  document.addEventListener("input", function (event) {
    handleOnDirectiveEvent("input", event);

    const element = closestFromEventTarget(event, "input, textarea, select");

    if (!element) {
      return;
    }

    const root = findRoot(element);

    if (!root) {
      return;
    }

    const component = root.getAttribute("data-volt-component");
    const snapshot = readSnapshot(root);
    const key = directiveValue(element, ["volt-model", "volt:model"]);

    if (snapshot && snapshot.state && key) {
      snapshot.state[key] =
        element.type === "checkbox" ? !!element.checked : element.value;
      root.setAttribute("data-volt-snapshot", JSON.stringify(snapshot));
    }

    scheduleDirtyDebounce(root, {
      component: component,
      target: fieldStateTarget(element),
    });
    setSuccessState(root, false, {
      component: component,
      target: fieldStateTarget(element),
    });
  });

  document.addEventListener("click", function (event) {
    handleOnDirectiveEvent("click", event);

    const actionTrigger = closestFromEventTarget(
      event,
      "[volt-click], [volt\\:click]",
    );

    if (actionTrigger) {
      const root = findRoot(actionTrigger);

      if (!root) {
        return;
      }

      event.preventDefault();

      const params = directiveValue(actionTrigger, [
        "volt-params",
        "volt:params",
      ]);
      dispatchAction(
        root,
        directiveValue(actionTrigger, ["volt-click", "volt:click"]),
        params ? JSON.parse(params) : {},
        collectModelUpdates(root),
        actionTrigger,
      ).catch(function (error) {
        console.error("VoltStack runtime error:", error);
      });

      return;
    }

    const dispatchTrigger = closestFromEventTarget(
      event,
      "[volt-dispatch], [volt\\:dispatch]",
    );

    if (dispatchTrigger) {
      emitDispatchDirective(
        dispatchTrigger,
        findRoot(dispatchTrigger),
        directiveValue(dispatchTrigger, dispatchDirectiveNames()),
        event,
      );
    }

    const navigationTrigger = closestFromEventTarget(
      event,
      "a[volt-navigate], a[volt\\:navigate]",
    );

    if (!shouldHandleNavigation(event, navigationTrigger)) {
      return;
    }

    const url = new URL(navigationTrigger.href, window.location.href);
    const navigationMode = navigationModeForElement(navigationTrigger);

    if (navigationMode.mode === "reload") {
      return;
    }

    event.preventDefault();

    const preserveScroll =
      navigationTrigger.hasAttribute("volt-preserve-scroll") ||
      navigationTrigger.hasAttribute("volt:preserve-scroll");
    const replace =
      navigationTrigger.hasAttribute("volt-replace") ||
      navigationTrigger.hasAttribute("volt:replace");

    visit(url.toString(), {
      trigger: navigationTrigger,
      preserveScroll: preserveScroll,
      historyMode: replace ? "replace" : "push",
      navigationMode: navigationMode,
      pageTransition: pageTransitionForElement(navigationTrigger),
    }).catch(function (error) {
      console.error("VoltStack navigation error:", error);
    });
  });

  document.addEventListener(
    "pointerenter",
    function (event) {
      const navigationTrigger = closestFromEventTarget(
        event,
        NAVIGATION_PREFETCH_SELECTOR,
      );

      if (
        !navigationTrigger ||
        !navigationTrigger.href ||
        !linkAllowsPrefetchSource(navigationTrigger, "intent") ||
        !shouldPrefetchForNavigationMode(
          navigationModeForElement(navigationTrigger),
        )
      ) {
        return;
      }

      const url = trackPrefetchInterest(
        navigationTrigger,
        navigationTrigger.href,
      );

      prefetchPage(url, {
        cacheControl: navigationCacheControlForElement(navigationTrigger),
        navigationMode: navigationModeForElement(navigationTrigger),
      }).catch(function () {
        return null;
      });
    },
    true,
  );

  document.addEventListener(
    "pointerleave",
    function (event) {
      const navigationTrigger = closestFromEventTarget(
        event,
        NAVIGATION_PREFETCH_SELECTOR,
      );

      if (!navigationTrigger) {
        return;
      }

      releasePrefetchInterest(navigationTrigger);
    },
    true,
  );

  document.addEventListener("focusin", function (event) {
    handleOnDirectiveEvent("focus", event);

    const navigationTrigger = closestFromEventTarget(
      event,
      NAVIGATION_PREFETCH_SELECTOR,
    );

    if (
      !navigationTrigger ||
      !navigationTrigger.href ||
      !linkAllowsPrefetchSource(navigationTrigger, "intent") ||
      !shouldPrefetchForNavigationMode(
        navigationModeForElement(navigationTrigger),
      )
    ) {
      return;
    }

    const url = trackPrefetchInterest(
      navigationTrigger,
      navigationTrigger.href,
    );

    prefetchPage(url, {
      cacheControl: navigationCacheControlForElement(navigationTrigger),
      navigationMode: navigationModeForElement(navigationTrigger),
    }).catch(function () {
      return null;
    });
  });

  document.addEventListener(
    "volt:navigation-cache-invalidate",
    function (event) {
      const detail =
        event && event.detail && typeof event.detail === "object"
          ? event.detail
          : {};

      if (typeof detail.url === "string" && detail.url !== "") {
        invalidateNavigationCache(detail.url, detail.reason || "event", {
          source: detail.source || "event",
        });
        return;
      }

      clearNavigationCache(detail.reason || "event", {
        source: detail.source || "event",
      });
    },
  );

  document.addEventListener("focusout", function (event) {
    handleOnDirectiveEvent("blur", event);

    const navigationTrigger = closestFromEventTarget(
      event,
      NAVIGATION_PREFETCH_SELECTOR,
    );

    if (!navigationTrigger) {
      return;
    }

    releasePrefetchInterest(navigationTrigger);
  });

  document.addEventListener("submit", function (event) {
    handleOnDirectiveEvent("submit", event);

    const form = closestFromEventTarget(
      event,
      "form[volt-submit], form[volt\\:submit]",
    );

    if (!form) {
      return;
    }

    const root = findRoot(form);

    if (!root) {
      return;
    }

    event.preventDefault();

    dispatchAction(
      root,
      directiveValue(form, ["volt-submit", "volt:submit"]),
      collectFormData(form),
      collectModelUpdates(root),
      form,
    ).catch(function (error) {
      console.error("VoltStack runtime error:", error);
    });
  });

  document.addEventListener("change", function (event) {
    handleOnDirectiveEvent("change", event);
  });

  document.addEventListener("keydown", function (event) {
    handleOnDirectiveEvent("keydown", event);
  });

  document.addEventListener("keyup", function (event) {
    handleOnDirectiveEvent("keyup", event);
  });

  window.addEventListener("popstate", function () {
    visit(window.location.href, {
      updateHistory: false,
      historyMode: "replace",
      preserveScroll: false,
      fallback: false,
    }).catch(function (error) {
      console.error("VoltStack navigation error:", error);
      window.location.reload();
    });
  });

  runtime.clientStateScope = normalizeNavigationUrl(window.location.href);
  window.Volt =
    window.Volt && typeof window.Volt === "object" ? window.Volt : {};
  window.Volt.visit = function (url, options) {
    return visit(url, options || {});
  };
  window.Volt.prefetch = function (url, options) {
    return prefetchPage(url, options || {});
  };
  window.Volt.state = createPublicStateApi();

  function bootRuntimeDocumentFeatures() {
    syncAllRuntimeStateDirectives();
    registerViewportPrefetchTargets(document);
    scheduleHeuristicPrefetch(document);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", bootRuntimeDocumentFeatures, {
      once: true,
    });
  }

  bootRuntimeDocumentFeatures();
})();
