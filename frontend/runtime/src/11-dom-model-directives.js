  function parseModelLocalDirectiveValue(value) {
    const expression = parseStoreDirectiveExpression(value);

    if (!expression || expression.negate) {
      return null;
    }

    return expression;
  }

  function isDirectiveFocusableElement(element) {
    if (
      !element ||
      typeof element.focus !== "function" ||
      !element.isConnected ||
      element.hidden === true
    ) {
      return false;
    }

    if (
      (typeof element.disabled === "boolean" && element.disabled) ||
      element.getAttribute("aria-disabled") === "true"
    ) {
      return false;
    }

    if (typeof window.getComputedStyle === "function") {
      const styles = window.getComputedStyle(element);

      if (
        !styles ||
        styles.display === "none" ||
        styles.visibility === "hidden"
      ) {
        return false;
      }
    }

    if (
      typeof element.getClientRects === "function" &&
      element.getClientRects().length === 0 &&
      document.activeElement !== element
    ) {
      return false;
    }

    return true;
  }

  function portalDirectiveState(element) {
    const store = runtimeDirectiveStore(element);

    if (!store.portal) {
      store.portal = {
        placeholder: null,
        selector: null,
      };
    }

    return store.portal;
  }

  function htmlDirectiveState(element) {
    const store = runtimeDirectiveStore(element);

    if (!store.html) {
      store.html = {
        lastApplied: null,
      };
    }

    return store.html;
  }

  function bindDirectiveState(element) {
    const store = runtimeDirectiveStore(element);

    if (!store.bindings) {
      store.bindings = {};
    }

    return store.bindings;
  }

  function modelLocalDirectiveState(element) {
    const store = runtimeDirectiveStore(element);

    if (!store.modelLocal) {
      store.modelLocal = {
        baselineCaptured: false,
        value: null,
        checked: false,
        selected: false,
      };
    }

    return store.modelLocal;
  }

  function ensureModelLocalBaseline(element) {
    const state = modelLocalDirectiveState(element);

    if (state.baselineCaptured) {
      return state;
    }

    state.baselineCaptured = true;
    state.value = "value" in element ? element.value : null;
    state.checked = "checked" in element ? !!element.checked : false;
    state.selected = "selected" in element ? !!element.selected : false;
    return state;
  }

  function modelLocalDirectiveValue(element) {
    return directiveValue(element, modelLocalDirectiveNames());
  }

  function parseModelSyncDirectiveValue(value) {
    return parseModelLocalDirectiveValue(value);
  }

  function modelSyncDirectiveValue(element) {
    return directiveValue(element, modelSyncDirectiveNames());
  }

  function modelSyncUpdateField(element) {
    const explicit = directiveValue(element, [
      "data-volt-model-sync-update",
      "volt-model-sync-update",
      "volt:model.sync.update",
    ]);

    if (typeof explicit === "string" && explicit.trim() !== "") {
      return explicit.trim();
    }

    const name = element && element.getAttribute ? element.getAttribute("name") : null;

    return typeof name === "string" && name.trim() !== "" ? name.trim() : null;
  }

  function clearModelSyncDirectiveDebounce(element) {
    if (!element || !runtime.modelSyncDebounces.has(element)) {
      return;
    }

    clearTimeout(runtime.modelSyncDebounces.get(element));
    runtime.modelSyncDebounces.delete(element);
    runtime.modelSyncTrackedElements.delete(element);
  }

  function buildModelSyncDirectivePayload(root, element) {
    if (!root || !element) {
      return {
        params: {},
        updates: {},
        shouldDispatch: false,
      };
    }

    const rules = collectStateSyncRules(root, element);

    if (rules.length > 0) {
      return {
        params: {},
        updates: {},
        shouldDispatch: true,
      };
    }

    const updateField = modelSyncUpdateField(element);
    const next = readModelLocalElementValue(element);

    if (!updateField || !next.valid) {
      return {
        params: {},
        updates: {},
        shouldDispatch: false,
      };
    }

    const updates = {};

    updates[updateField] = next.value;

    return {
      params: {},
      updates: updates,
      shouldDispatch: true,
    };
  }

  function scheduleModelSyncDirectiveDispatch(root, element) {
    if (!root || !element) {
      return false;
    }

    const payload = buildModelSyncDirectivePayload(root, element);

    if (!payload.shouldDispatch) {
      return false;
    }

    clearModelSyncDirectiveDebounce(element);

    const timeoutId = window.setTimeout(function () {
      runtime.modelSyncDebounces.delete(element);
      runtime.modelSyncTrackedElements.delete(element);

      if (!element.isConnected) {
        return;
      }

      const activeRoot = findRoot(element) || root;

      if (!activeRoot || !activeRoot.isConnected) {
        return;
      }

      const nextPayload = buildModelSyncDirectivePayload(activeRoot, element);

      if (!nextPayload.shouldDispatch) {
        return;
      }

      dispatchAction(
        activeRoot,
        MODEL_SYNC_INTERNAL_ACTION,
        nextPayload.params,
        nextPayload.updates,
        element,
      ).catch(function (error) {
        console.error("VoltStack runtime error:", error);
      });
    }, MODEL_SYNC_DEBOUNCE);

    runtime.modelSyncDebounces.set(element, timeoutId);
    runtime.modelSyncTrackedElements.add(element);
    return true;
  }

  function elementSupportsModelLocal(element) {
    if (!element || !element.tagName) {
      return false;
    }

    const tagName = element.tagName.toLowerCase();

    return tagName === "input" || tagName === "textarea" || tagName === "select";
  }

  function readModelLocalElementValue(element) {
    if (!elementSupportsModelLocal(element)) {
      return {
        valid: false,
        value: null,
      };
    }

    const tagName = element.tagName.toLowerCase();

    if (tagName === "textarea" || tagName === "select") {
      return {
        valid: true,
        value: element.value,
      };
    }

    const type = (element.type || "text").toLowerCase();

    if (type === "checkbox") {
      return {
        valid: true,
        value: !!element.checked,
      };
    }

    if (type === "radio") {
      if (!element.checked) {
        return {
          valid: false,
          value: null,
        };
      }

      return {
        valid: true,
        value: element.value,
      };
    }

    return {
      valid: true,
      value: element.value,
    };
  }

  function applyModelLocalElementValue(element, value, found) {
    if (!elementSupportsModelLocal(element)) {
      return;
    }

    const state = ensureModelLocalBaseline(element);
    const tagName = element.tagName.toLowerCase();

    if (tagName === "textarea" || tagName === "select") {
      const nextValue = found ? formatBindDirectiveValue(value) : state.value || "";

      if (element.value !== nextValue) {
        element.value = nextValue;
      }

      return;
    }

    const type = (element.type || "text").toLowerCase();

    if (type === "checkbox") {
      const nextChecked = found ? !!value : !!state.checked;

      if (!!element.checked !== nextChecked) {
        element.checked = nextChecked;
      }

      return;
    }

    if (type === "radio") {
      const nextChecked = found ? String(value) === String(element.value) : !!state.checked;

      if (!!element.checked !== nextChecked) {
        element.checked = nextChecked;
      }

      return;
    }

    const nextValue = found ? formatBindDirectiveValue(value) : state.value || "";

    if (element.value !== nextValue) {
      element.value = nextValue;
    }
  }

  function updateModelLocalDirectiveFromElement(element, sourceAction) {
    if (!elementSupportsModelLocal(element)) {
      return false;
    }

    const modelValue = modelLocalDirectiveValue(element);
    const expression = parseModelLocalDirectiveValue(modelValue);

    if (!expression) {
      return false;
    }

    const next = readModelLocalElementValue(element);

    if (!next.valid) {
      return false;
    }

    const current = getRuntimeStateValue(expression.path, {
      scope: expression.scope,
      fallback: null,
    });

    if (valuesAreSame(current, next.value)) {
      return false;
    }

    setRuntimeStateValue(expression.path, next.value, {
      scope: expression.scope,
      action: sourceAction || "directive:model.local",
    });

    return true;
  }

  function updateModelSyncDirectiveFromElement(element, root, sourceAction) {
    if (!elementSupportsModelLocal(element)) {
      return false;
    }

    const modelValue = modelSyncDirectiveValue(element);
    const expression = parseModelSyncDirectiveValue(modelValue);

    if (!expression) {
      return false;
    }

    const next = readModelLocalElementValue(element);

    if (!next.valid) {
      return false;
    }

    const current = getRuntimeStateValue(expression.path, {
      scope: expression.scope,
      fallback: null,
    });
    const changed = !valuesAreSame(current, next.value);

    if (changed) {
      setRuntimeStateValue(expression.path, next.value, {
        scope: expression.scope,
        action: sourceAction || "directive:model.sync",
      });
    }

    return scheduleModelSyncDirectiveDispatch(root, element) || changed;
  }

  function ensureBindDirectiveBaseline(element, entry) {
    const bindings = bindDirectiveState(element);
    const key = entry.attributeName;

    if (!bindings[key]) {
      const reflectAttributeName = entry.reflectAttributeName;

      bindings[key] = {
        propertyName: entry.propertyName,
        reflectAttributeName: reflectAttributeName,
        hasProperty: entry.propertyName in element,
        initialPropertyValue:
          entry.propertyName in element ? element[entry.propertyName] : null,
        hadAttribute:
          !!reflectAttributeName && element.hasAttribute(reflectAttributeName),
        initialAttributeValue:
          reflectAttributeName && element.hasAttribute(reflectAttributeName)
            ? element.getAttribute(reflectAttributeName)
            : null,
      };
    }

    return bindings[key];
  }

  function restoreBindDirectiveValue(element, binding) {
    if (!element || !binding) {
      return;
    }

    const propertyName = binding.propertyName;
    const reflectAttributeName = binding.reflectAttributeName;

    if (propertyName === "value") {
      const value =
        binding.hadAttribute && binding.initialAttributeValue !== null
          ? binding.initialAttributeValue
          : typeof binding.initialPropertyValue !== "undefined" &&
              binding.initialPropertyValue !== null
            ? String(binding.initialPropertyValue)
            : "";

      if ("value" in element) {
        element.value = value;
      }

      if (reflectAttributeName) {
        if (binding.hadAttribute && binding.initialAttributeValue !== null) {
          element.setAttribute(reflectAttributeName, binding.initialAttributeValue);
        } else {
          element.removeAttribute(reflectAttributeName);
        }
      }

      return;
    }

    if (isBooleanBindDirectiveProperty(propertyName)) {
      if (propertyName in element) {
        element[propertyName] = false;
      }

      if (reflectAttributeName) {
        element.removeAttribute(reflectAttributeName);
      }

      return;
    }

    if (binding.hasProperty) {
      if (
        binding.initialPropertyValue === null ||
        typeof binding.initialPropertyValue === "undefined"
      ) {
        element[propertyName] = "";
      } else {
        element[propertyName] = binding.initialPropertyValue;
      }
    }

    if (reflectAttributeName) {
      if (binding.hadAttribute && binding.initialAttributeValue !== null) {
        element.setAttribute(reflectAttributeName, binding.initialAttributeValue);
      } else {
        element.removeAttribute(reflectAttributeName);
      }
    }
  }

  function applyBindDirectiveValue(element, entry, value, found) {
    const binding = ensureBindDirectiveBaseline(element, entry);
    const propertyName = entry.propertyName;
    const reflectAttributeName = entry.reflectAttributeName;

    if (!found || value === null || typeof value === "undefined") {
      restoreBindDirectiveValue(element, binding);
      return;
    }

    if (isBooleanBindDirectiveProperty(propertyName)) {
      const nextValue = !!value;

      if (propertyName in element) {
        element[propertyName] = nextValue;
      }

      if (reflectAttributeName) {
        if (nextValue) {
          element.setAttribute(reflectAttributeName, reflectAttributeName);
        } else {
          element.removeAttribute(reflectAttributeName);
        }
      }

      return;
    }

    const nextValue = formatBindDirectiveValue(value);

    if (propertyName in element) {
      element[propertyName] = nextValue;
    } else if (reflectAttributeName) {
      element.setAttribute(reflectAttributeName, nextValue);
      return;
    }

    if (reflectAttributeName && propertyName !== "value") {
      element.setAttribute(reflectAttributeName, nextValue);
    }
  }

  function ensurePortalPlaceholder(element, state) {
    if (
      state &&
      state.placeholder &&
      state.placeholder.parentNode &&
      state.placeholder.isConnected
    ) {
      return state.placeholder;
    }

    if (!element || !element.parentNode) {
      return null;
    }

    const placeholder = document.createElement("span");

    placeholder.setAttribute("data-volt-portal-placeholder", "true");
    placeholder.hidden = true;
    placeholder.style.display = "none";
    element.parentNode.insertBefore(placeholder, element);

    if (state) {
      state.placeholder = placeholder;
    }

    return placeholder;
  }

  function restorePortalElement(element, state) {
    const placeholder =
      state && state.placeholder && state.placeholder.parentNode
        ? state.placeholder
        : null;

    if (!element || !placeholder || !placeholder.parentNode) {
      return false;
    }

    if (
      element.parentNode === placeholder.parentNode &&
      element.previousSibling === placeholder
    ) {
      return false;
    }

    placeholder.parentNode.insertBefore(element, placeholder.nextSibling);
    return true;
  }

  function syncPortalDirectives(root) {
    if (!root) {
      return;
    }

    collectElementsWithDirectiveAttributes(root, portalDirectiveNames()).forEach(
      function (element) {
        const selector = directiveValue(element, portalDirectiveNames());
        const state = portalDirectiveState(element);

        if (typeof selector !== "string" || selector.trim() === "") {
          restorePortalElement(element, state);
          return;
        }

        state.selector = selector.trim();

        const target = document.querySelector(state.selector);

        if (!target) {
          restorePortalElement(element, state);
          return;
        }

        ensurePortalPlaceholder(element, state);

        if (element.parentNode !== target) {
          target.appendChild(element);
        }
      },
    );
  }

  function syncHtmlDirectives(root) {
    if (!root) {
      return false;
    }

    let changed = false;

    collectElementsWithDirectiveAttributes(root, htmlDirectiveNames()).forEach(
      function (element) {
        const directive = directiveValue(element, htmlDirectiveNames());
        const result = resolveStoreDirectiveValue(directive);
        const nextHtml = result.found
          ? formatStoreDirectiveHtmlValue(result.value)
          : "";
        const state = htmlDirectiveState(element);

        if (state.lastApplied === nextHtml) {
          return;
        }

        element.innerHTML = nextHtml;
        state.lastApplied = nextHtml;
        changed = true;
      },
    );

    return changed;
  }

  function syncBindDirectives(root) {
    if (!root) {
      return;
    }

    [root]
      .concat(Array.from(root.querySelectorAll("*")))
      .forEach(function (element) {
      const entries = bindDirectiveEntries(element);

      if (entries.length === 0) {
        return;
      }

      entries.forEach(function (entry) {
        const result = resolveStoreDirectiveValue(entry.expression);

        applyBindDirectiveValue(element, entry, result.value, result.found);
      });
      });
  }

  function syncModelLocalDirectives(root) {
    if (!root) {
      return;
    }

    [root]
      .concat(Array.from(root.querySelectorAll("*")))
      .forEach(function (element) {
        const value = modelLocalDirectiveValue(element);
        const expression = parseModelLocalDirectiveValue(value);

        if (!expression) {
          return;
        }

        const result = runtimeStateValueByPath(expression.scope, expression.path);

        applyModelLocalElementValue(element, result.value, result.found);
      });
  }

  function syncModelSyncDirectives(root) {
    if (!root) {
      return;
    }

    [root]
      .concat(Array.from(root.querySelectorAll("*")))
      .forEach(function (element) {
        const value = modelSyncDirectiveValue(element);
        const expression = parseModelSyncDirectiveValue(value);

        if (!expression) {
          return;
        }

        const result = runtimeStateValueByPath(expression.scope, expression.path);

        applyModelLocalElementValue(element, result.value, result.found);
      });
  }

  function focusDirectiveState(element) {
    const store = runtimeDirectiveStore(element);

    if (!store.focus) {
      store.focus = {};
    }

    return store.focus;
  }

  function focusElementForDirective(element) {
    if (
      !isDirectiveFocusableElement(element) ||
      document.activeElement === element
    ) {
      return false;
    }

    try {
      element.focus({
        preventScroll: true,
      });
    } catch (error) {
      try {
        element.focus();
      } catch (innerError) {
        return false;
      }
    }

    return document.activeElement === element;
  }

  function syncFocusDirectives(root) {
    if (!root) {
      return;
    }

    let candidate = null;

    collectElementsWithDirectiveAttributes(root, focusDirectiveNames()).forEach(
      function (element) {
        const directive = directiveValue(element, focusDirectiveNames());
        const active = resolveStoreDirectiveActive(directive);
        const state = focusDirectiveState(element);
        const previous = state.reactive === true;

        state.reactive = active;

        if (active && !previous && isDirectiveFocusableElement(element)) {
          candidate = element;
        }
      },
    );

    collectElementsWithDirectiveAttributes(
      root,
      autofocusWhenDirectiveNames(),
    ).forEach(function (element) {
      const directive = directiveValue(element, autofocusWhenDirectiveNames());
      const active = resolveStoreDirectiveActive(directive);
      const state = focusDirectiveState(element);
      const previous = state.autofocusWhen === true;

      state.autofocusWhen = active;

      if (active && !previous && isDirectiveFocusableElement(element)) {
        candidate = element;
      }
    });

    if (candidate) {
      focusElementForDirective(candidate);
    }
  }

