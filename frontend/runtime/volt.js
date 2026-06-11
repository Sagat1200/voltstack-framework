(function () {
  const runtime = {
    navigationRequestId: 0,
    navigationController: null,
    componentRequestStates: new Map(),
    loadingDelays: new Map(),
    loadingActivatedAt: new Map(),
    loadingMinClearDelays: new Map(),
    successTimeouts: new Map(),
    successActivatedAt: new Map(),
    successMinClearDelays: new Map(),
    errorTimeouts: new Map(),
    dirtyDebounces: new Map(),
  };

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
      if (roots[index].getAttribute('data-volt-component') === componentName) {
        return roots[index];
      }
    }

    return null;
  }

  function readSnapshot(root) {
    const snapshot = root.getAttribute('data-volt-snapshot');

    return snapshot ? JSON.parse(snapshot) : null;
  }

  function collectModelUpdates(root) {
    const updates = {};

    root.querySelectorAll('[volt-model], [volt\\:model]').forEach(function (element) {
      const key = directiveValue(element, ['volt-model', 'volt:model']);

      if (!key) {
        return;
      }

      if (element.type === 'checkbox') {
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
      if (typeof value === 'string') {
        data[key] = value;
      }
    });

    return data;
  }

  function cssEscape(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(value);
    }

    return String(value).replace(/[^a-zA-Z0-9\-_]/g, '\\$&');
  }

  function directiveValue(element, names) {
    for (let index = 0; index < names.length; index += 1) {
      const value = element.getAttribute(names[index]);

      if (value !== null && value !== '') {
        return value;
      }
    }

    return null;
  }

  function directiveAttribute(element, names) {
    for (let index = 0; index < names.length; index += 1) {
      if (element.hasAttribute(names[index])) {
        return {
          name: names[index],
          value: element.getAttribute(names[index]) || '',
        };
      }
    }

    return null;
  }

  function directiveSelector(names) {
    return names.map(function (name) {
      return '[' + name.replace(/[:.]/g, '\\$&') + ']';
    }).join(', ');
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

    if (typeof root.hasAttribute === 'function' && matchesNames(root)) {
      elements.push(root);
    }

    if (typeof root.querySelectorAll !== 'function') {
      return elements;
    }

    root.querySelectorAll('*').forEach(function (element) {
      if (matchesNames(element)) {
        elements.push(element);
      }
    });

    return elements;
  }

  function collectDirectiveElements(root, selector) {
    const elements = [];

    if (!root || typeof root.querySelectorAll !== 'function') {
      return elements;
    }

    if (typeof root.matches === 'function' && root.matches(selector)) {
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
      };
    }

    return element.__voltRuntimeDirectiveStore;
  }

  function stateDirectiveNames(state, suffix) {
    const parts = Array.isArray(suffix)
      ? suffix.filter(function (value) {
          return !!value;
        })
      : suffix
        ? [suffix]
        : [];
    let dashed = 'volt-' + state;
    let dotted = 'volt:' + state;

    parts.forEach(function (part) {
      dashed += '-' + part;
      dotted += '.' + part;
    });

    return [dashed, dotted];
  }

  function parseDirectiveList(value) {
    if (typeof value !== 'string' || value.trim() === '') {
      return [];
    }

    return value.split(',').map(function (entry) {
      return entry.trim();
    }).filter(function (entry) {
      return entry !== '';
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
      action: root.getAttribute('data-volt-' + state + '-action') || null,
      target: root.getAttribute('data-volt-' + state + '-target') || null,
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
    const actionAttribute = directiveAttribute(element, stateDirectiveNames(state, 'action'));
    const targetAttribute = directiveAttribute(element, stateDirectiveNames(state, 'target'));
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

  function stateDirectiveIsActive(element, state, active, shorthandValue, context) {
    if (!active) {
      return false;
    }

    const scope = stateDirectiveScope(element, state, shorthandValue, context);

    return matchesDirectiveScope(scope.actions, context.action) &&
      matchesDirectiveScope(scope.targets, context.target);
  }

  function applyDirectiveVisibility(element, state, active, inverse) {
    const storeKey = state + ':' + (inverse ? 'hide' : 'show');
    const store = runtimeDirectiveStore(element);

    if (!store.visibility[storeKey]) {
      store.visibility[storeKey] = {
        hidden: !!element.hidden,
        ariaHidden: element.getAttribute('aria-hidden'),
      };
    }

    const shouldHide = inverse ? active : !active;
    const initialState = store.visibility[storeKey];

    if (shouldHide) {
      element.hidden = true;
      element.setAttribute('aria-hidden', 'true');
      return;
    }

    element.hidden = initialState.hidden;

    if (initialState.ariaHidden === null) {
      element.removeAttribute('aria-hidden');
      return;
    }

    element.setAttribute('aria-hidden', initialState.ariaHidden);
  }

  function parseDirectiveAttributes(value) {
    if (typeof value !== 'string' || value.trim() === '') {
      return [];
    }

    return value.split(',').map(function (token) {
      const entry = token.trim();

      if (!entry) {
        return null;
      }

      const separator = entry.indexOf('=');

      if (separator === -1) {
        return {
          name: entry,
          value: '',
        };
      }

      return {
        name: entry.slice(0, separator).trim(),
        value: entry.slice(separator + 1).trim(),
      };
    }).filter(function (entry) {
      return entry && entry.name;
    });
  }

  function applyDirectiveAttributes(element, state, active, attributes) {
    if (!Array.isArray(attributes) || attributes.length === 0) {
      return;
    }

    const storeKey = state + ':attr';
    const store = runtimeDirectiveStore(element);

    if (!store.attributes[storeKey]) {
      store.attributes[storeKey] = {};
    }

    attributes.forEach(function (entry) {
      if (!store.attributes[storeKey].hasOwnProperty(entry.name)) {
        store.attributes[storeKey][entry.name] = element.hasAttribute(entry.name)
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

  function applyDirectiveClasses(element, active, value) {
    if (typeof value !== 'string' || value.trim() === '') {
      return;
    }

    value.split(/\s+/).forEach(function (className) {
      if (!className) {
        return;
      }

      element.classList.toggle(className, active);
    });
  }

  function stateDirectiveShorthandValue(element, state) {
    const attribute = directiveAttribute(element, stateDirectiveNames(state));

    return attribute ? attribute.value : '';
  }

  function parseDirectiveTimeout(value) {
    if (typeof value !== 'string' || value.trim() === '') {
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

    const unit = match[2] || 'ms';
    const multiplier = unit === 's' ? 1000 : 1;

    return Math.round(amount * multiplier);
  }

  function matchingStateDirectiveElements(root, state, suffix, active, contextOverride) {
    const context = contextOverride || runtimeStateContext(root, state);
    const names = stateDirectiveNames(state, suffix);

    return collectElementsWithDirectiveAttributes(root, names).filter(function (element) {
      return stateDirectiveIsActive(
        element,
        state,
        active,
        stateDirectiveShorthandValue(element, state),
        context
      );
    });
  }

  function resolveStateDirectiveDuration(root, state, suffix, context) {
    const values = matchingStateDirectiveElements(root, state, suffix, true, context).map(function (element) {
      return parseDirectiveTimeout(directiveValue(element, stateDirectiveNames(state, suffix)));
    }).filter(function (value) {
      return value !== null;
    });

    if (values.length === 0) {
      return null;
    }

    return Math.min.apply(null, values);
  }

  function resolveStateDirectiveTimeout(root, state) {
    return resolveStateDirectiveDuration(root, state, 'timeout');
  }

  function resolveStateDirectiveDelay(root, state, context) {
    return resolveStateDirectiveDuration(root, state, 'delay', context);
  }

  function resolveStateDirectiveMinDuration(root, state, context) {
    return resolveStateDirectiveDuration(root, state, 'min-duration', context);
  }

  function resolveStateDirectiveDebounce(root, state, context) {
    return resolveStateDirectiveDuration(root, state, 'debounce', context);
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

    const detail = meta && typeof meta === 'object' ? meta : {};
    const context = {
      action: detail.action || null,
      target: stateTargetValue(detail),
    };
    const delay = resolveStateDirectiveDelay(root, 'loading', context);

    if (delay === null || delay <= 0) {
      setLoadingState(root, true, trigger, detail);
      return;
    }

    const component = root.getAttribute('data-volt-component') || detail.component || null;
    const requestId = detail.requestId || null;
    const timeoutId = window.setTimeout(function () {
      runtime.loadingDelays.delete(root);

      const activeRoot = resolveRuntimeRoot(root, component);
      const state = componentRequestState(component);

      if (!activeRoot || !state || state.requestId !== requestId) {
        return;
      }

      setLoadingState(activeRoot, true, trigger, Object.assign({}, detail, {
        component: component,
      }));
    }, delay);

    runtime.loadingDelays.set(root, timeoutId);
  }

  function scheduleLoadingMinDurationClear(root, trigger, meta, remaining) {
    if (!root) {
      return;
    }

    clearLoadingMinDuration(root);

    const component = root.getAttribute('data-volt-component') || (meta && meta.component) || null;
    const detail = Object.assign({}, meta || {}, {
      component: component,
      reason: 'min-duration',
    });

    const timeoutId = window.setTimeout(function () {
      runtime.loadingMinClearDelays.delete(root);

      const activeRoot = resolveRuntimeRoot(root, component);

      if (!activeRoot || activeRoot.getAttribute('data-volt-loading') !== 'true') {
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

    const timeout = resolveStateDirectiveTimeout(root, 'success');

    if (timeout === null) {
      return;
    }

    const component = root.getAttribute('data-volt-component') || (meta && meta.component) || null;
    const detail = Object.assign({}, meta || {}, {
      component: component,
      reason: 'timeout',
    });

    const timeoutId = window.setTimeout(function () {
      runtime.successTimeouts.delete(root);

      const activeRoot = resolveRuntimeRoot(root, component);

      if (!activeRoot || activeRoot.getAttribute('data-volt-success') !== 'true') {
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

    const component = root.getAttribute('data-volt-component') || (meta && meta.component) || null;
    const detail = Object.assign({}, meta || {}, {
      component: component,
      reason: 'min-duration',
    });

    const timeoutId = window.setTimeout(function () {
      runtime.successMinClearDelays.delete(root);

      const activeRoot = resolveRuntimeRoot(root, component);

      if (!activeRoot || activeRoot.getAttribute('data-volt-success') !== 'true') {
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

    const timeout = resolveStateDirectiveTimeout(root, 'error');

    if (timeout === null) {
      return;
    }

    const component = root.getAttribute('data-volt-component') || (meta && meta.component) || null;
    const detail = Object.assign({}, meta || {}, {
      component: component,
      reason: 'timeout',
    });

    const timeoutId = window.setTimeout(function () {
      runtime.errorTimeouts.delete(root);

      const activeRoot = resolveRuntimeRoot(root, component);

      if (!activeRoot || activeRoot.getAttribute('data-volt-error') !== 'true') {
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

    const detail = meta && typeof meta === 'object' ? meta : {};
    const context = {
      action: detail.action || null,
      target: stateTargetValue(detail),
    };
    const debounce = resolveStateDirectiveDebounce(root, 'dirty', context);

    if (debounce === null || debounce <= 0) {
      setDirtyState(root, true, detail);
      return;
    }

    const component = root.getAttribute('data-volt-component') || detail.component || null;
    const timeoutId = window.setTimeout(function () {
      runtime.dirtyDebounces.delete(root);

      const activeRoot = resolveRuntimeRoot(root, component);

      if (!activeRoot) {
        return;
      }

      setDirtyState(activeRoot, true, Object.assign({}, detail, {
        component: component,
        reason: 'debounce',
        debounce: debounce,
      }));
    }, debounce);

    runtime.dirtyDebounces.set(root, timeoutId);
  }

  function syncRuntimeStateDirective(root, state, active) {
    const showSelector = directiveSelector(stateDirectiveNames(state));
    const hideSelector = directiveSelector(stateDirectiveNames(state, 'hide'));
    const classSelector = directiveSelector(stateDirectiveNames(state, 'class'));
    const attrSelector = directiveSelector(stateDirectiveNames(state, 'attr'));

    collectDirectiveElements(root, showSelector).forEach(function (element) {
      applyDirectiveVisibility(
        element,
        state,
        stateDirectiveIsActive(element, state, active, stateDirectiveShorthandValue(element, state), runtimeStateContext(root, state)),
        false
      );
    });

    collectDirectiveElements(root, hideSelector).forEach(function (element) {
      applyDirectiveVisibility(
        element,
        state,
        stateDirectiveIsActive(element, state, active, stateDirectiveShorthandValue(element, state), runtimeStateContext(root, state)),
        true
      );
    });

    collectDirectiveElements(root, classSelector).forEach(function (element) {
      applyDirectiveClasses(
        element,
        stateDirectiveIsActive(element, state, active, stateDirectiveShorthandValue(element, state), runtimeStateContext(root, state)),
        directiveValue(element, stateDirectiveNames(state, 'class'))
      );
    });

    collectDirectiveElements(root, attrSelector).forEach(function (element) {
      applyDirectiveAttributes(
        element,
        state,
        stateDirectiveIsActive(element, state, active, stateDirectiveShorthandValue(element, state), runtimeStateContext(root, state)),
        parseDirectiveAttributes(directiveValue(element, stateDirectiveNames(state, 'attr')))
      );
    });
  }

  function syncRuntimeStateDirectives(root) {
    if (!root) {
      return;
    }

    syncRuntimeStateDirective(root, 'loading', root.getAttribute('data-volt-loading') === 'true');
    syncRuntimeStateDirective(root, 'error', root.getAttribute('data-volt-error') === 'true');
    syncRuntimeStateDirective(root, 'dirty', root.getAttribute('data-volt-dirty') === 'true');
    syncRuntimeStateDirective(root, 'success', root.getAttribute('data-volt-success') === 'true');
  }

  function syncAllRuntimeStateDirectives() {
    document.querySelectorAll('[data-volt-root="true"]').forEach(function (root) {
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
    if (!element || typeof element !== 'object') {
      return false;
    }

    if (element.tagName === 'TEXTAREA') {
      return true;
    }

    if (element.tagName !== 'INPUT') {
      return false;
    }

    const type = (element.type || 'text').toLowerCase();

    return [
      'text',
      'search',
      'url',
      'tel',
      'password',
      'email',
      'number',
    ].indexOf(type) !== -1;
  }

  function buildFocusDescriptor(root, element) {
    if (!element) {
      return null;
    }

    if (element.id) {
      return {
        strategy: 'id',
        value: element.id,
      };
    }

    const targetName = element.getAttribute('data-volt-target');

    if (targetName) {
      return {
        strategy: 'target',
        value: targetName,
      };
    }

    const modelName = directiveValue(element, ['volt-model', 'volt:model']);

    if (modelName) {
      const matches = root.querySelectorAll('[volt-model], [volt\\:model]');
      const index = elementIndex(matches, element);

      if (index !== -1) {
        return {
          strategy: 'model',
          value: modelName,
          index: index,
        };
      }
    }

    const fieldName = element.getAttribute('name');

    if (fieldName) {
      const matches = root.querySelectorAll('[name="' + cssEscape(fieldName) + '"]');
      const index = elementIndex(matches, element);

      if (index !== -1) {
        return {
          strategy: 'name',
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
        strategy: 'id',
        value: element.id,
      };
    }

    const targetName = element.getAttribute('data-volt-target');

    if (targetName) {
      return {
        strategy: 'target',
        value: targetName,
      };
    }

    return null;
  }

  function findByDescriptor(root, descriptor) {
    if (!descriptor || !root) {
      return null;
    }

    if (descriptor.strategy === 'id' && descriptor.value) {
      return document.getElementById(descriptor.value);
    }

    if (descriptor.strategy === 'target' && descriptor.value) {
      return root.querySelector('[data-volt-target="' + descriptor.value + '"]');
    }

    if (descriptor.strategy === 'model' && descriptor.value) {
      const matches = root.querySelectorAll(
        '[volt-model="' + descriptor.value + '"], [volt\\:model="' + descriptor.value + '"]'
      );

      return matches[descriptor.index || 0] || null;
    }

    if (descriptor.strategy === 'name' && descriptor.value) {
      const matches = root.querySelectorAll('[name="' + cssEscape(descriptor.value) + '"]');

      return matches[descriptor.index || 0] || null;
    }

    return null;
  }

  function captureSelectionState(element) {
    if (!isTextSelectableElement(element)) {
      return null;
    }

    return {
      start: typeof element.selectionStart === 'number' ? element.selectionStart : null,
      end: typeof element.selectionEnd === 'number' ? element.selectionEnd : null,
      direction: typeof element.selectionDirection === 'string' ? element.selectionDirection : 'none',
      scrollTop: typeof element.scrollTop === 'number' ? element.scrollTop : null,
      scrollLeft: typeof element.scrollLeft === 'number' ? element.scrollLeft : null,
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

    if (typeof selection.start === 'number' && typeof selection.end === 'number' && typeof element.setSelectionRange === 'function') {
      try {
        element.setSelectionRange(selection.start, selection.end, selection.direction || 'none');
      } catch (error) {
      }
    }

    if (typeof selection.scrollTop === 'number') {
      element.scrollTop = selection.scrollTop;
    }

    if (typeof selection.scrollLeft === 'number') {
      element.scrollLeft = selection.scrollLeft;
    }
  }

  function restoreFocusState(root, focusState) {
    if (!root || !focusState || !focusState.descriptor) {
      return;
    }

    const nextElement = findByDescriptor(root, focusState.descriptor);

    if (!nextElement || typeof nextElement.focus !== 'function') {
      return;
    }

    nextElement.focus({
      preventScroll: true,
    });
    restoreSelectionState(nextElement, focusState.selection);
  }

  function isElementScrollRestorable(element) {
    if (!element || typeof element !== 'object') {
      return false;
    }

    if (
      element.hasAttribute('data-volt-preserve-scroll') ||
      element.hasAttribute('volt-preserve-scroll') ||
      element.hasAttribute('volt:preserve-scroll')
    ) {
      return true;
    }

    return !!(
      (typeof element.scrollTop === 'number' && element.scrollTop !== 0) ||
      (typeof element.scrollLeft === 'number' && element.scrollLeft !== 0)
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
        scrollTop: typeof element.scrollTop === 'number' ? element.scrollTop : null,
        scrollLeft: typeof element.scrollLeft === 'number' ? element.scrollLeft : null,
      });
    }

    addCandidate(root);
    root.querySelectorAll('[id], [data-volt-target], [data-volt-preserve-scroll], [volt-preserve-scroll], [volt\\:preserve-scroll]')
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

      if (typeof entry.scrollTop === 'number') {
        element.scrollTop = entry.scrollTop;
      }

      if (typeof entry.scrollLeft === 'number') {
        element.scrollLeft = entry.scrollLeft;
      }
    });
  }

  function emitRuntimeHook(name, detail, target) {
    const hookDetail = detail && typeof detail === 'object' ? detail : {};
    const eventTarget = target || document;

    eventTarget.dispatchEvent(new CustomEvent(name, {
      detail: hookDetail,
      bubbles: true,
    }));
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

    const phaseDuration = transitionConfigValue(effect, phase, 'duration');

    if (typeof phaseDuration === 'number' && phaseDuration >= 0) {
      return phaseDuration;
    }

    if (effect && typeof effect.transitionDuration === 'number' && effect.transitionDuration >= 0) {
      return effect.transitionDuration;
    }

    const phaseAttribute = element.getAttribute('data-volt-transition-' + phase + '-duration');
    const phaseParsed = phaseAttribute ? Number(phaseAttribute) : NaN;

    if (Number.isFinite(phaseParsed) && phaseParsed >= 0) {
      return phaseParsed;
    }

    const attributeValue = element.getAttribute('data-volt-transition-duration');
    const parsed = attributeValue ? Number(attributeValue) : NaN;

    return Number.isFinite(parsed) && parsed >= 0 ? parsed : 180;
  }

  function transitionConfigValue(effect, phase, key) {
    if (!effect || typeof effect !== 'object') {
      return null;
    }

    if (effect.transition && typeof effect.transition === 'object' && effect.transition !== null) {
      const phaseConfig = effect.transition[phase];

      if (phaseConfig && typeof phaseConfig === 'object' && Object.prototype.hasOwnProperty.call(phaseConfig, key)) {
        return phaseConfig[key];
      }

      if (key === 'name' && typeof phaseConfig === 'string') {
        return phaseConfig;
      }
    }

    if (effect.transitions && typeof effect.transitions === 'object' && effect.transitions !== null) {
      const phaseConfig = effect.transitions[phase];

      if (phaseConfig && typeof phaseConfig === 'object' && Object.prototype.hasOwnProperty.call(phaseConfig, key)) {
        return phaseConfig[key];
      }

      if (key === 'name' && typeof phaseConfig === 'string') {
        return phaseConfig;
      }
    }

    return null;
  }

  function transitionVariantFor(element, effect, phase) {
    if (effect && effect.transition === false) {
      return null;
    }

    const phaseVariant = transitionConfigValue(effect, phase, 'name');

    if (typeof phaseVariant === 'string' && phaseVariant !== '') {
      return phaseVariant;
    }

    if (effect && typeof effect.transition === 'string' && effect.transition !== '') {
      return effect.transition;
    }

    if (effect && effect.transition === true) {
      return 'default';
    }

    if (!element) {
      return null;
    }

    const phaseAttribute = element.getAttribute('data-volt-transition-' + phase);

    if (phaseAttribute === '') {
      return 'default';
    }

    if (phaseAttribute) {
      return phaseAttribute;
    }

    const attributeValue = element.getAttribute('data-volt-transition');

    if (attributeValue === '') {
      return 'default';
    }

    return attributeValue || null;
  }

  function transitionClassListFor(element, effect, phase, variant) {
    const classes = ['volt-transition', 'volt-transition-' + phase];

    if (variant) {
      classes.push('volt-transition-' + variant);
    }

    const phaseClasses = [];
    const classConfig = transitionConfigValue(effect, phase, 'className');

    if (typeof classConfig === 'string' && classConfig !== '') {
      phaseClasses.push(classConfig);
    }

    if (element) {
      const phaseAttribute = element.getAttribute('data-volt-transition-' + phase + '-class');

      if (phaseAttribute) {
        phaseClasses.push(phaseAttribute);
      }

      const globalAttribute = element.getAttribute('data-volt-transition-class');

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
    const activeClass = 'volt-transition-' + phase + '-active';
    const classes = transitionClassListFor(element, effect, phase, variant);
    const detail = effectHookDetail(root, effect, element, {
      phase: phase,
      variant: variant,
      duration: duration,
    });

    emitRuntimeHook('volt:before-' + phase, detail, element);
    element.style.setProperty('--volt-transition-duration', duration + 'ms');
    element.classList.add.apply(element.classList, classes);
    await nextFrame();
    element.classList.add(activeClass);
    await wait(duration);
    element.classList.remove(activeClass);
    element.classList.remove.apply(element.classList, classes);
    element.style.removeProperty('--volt-transition-duration');
    emitRuntimeHook('volt:after-' + phase, detail, element);

    return true;
  }

  function fragmentFromHtml(html) {
    if (typeof html !== 'string' || html === '') {
      return null;
    }

    const template = document.createElement('template');
    template.innerHTML = html.trim();

    return template.content;
  }

  function effectHookDetail(root, effect, target, extra) {
    return Object.assign({
      type: effect && effect.type ? effect.type : null,
      target: effect && typeof effect.target === 'string' ? effect.target : null,
      selector: effect && typeof effect.selector === 'string' ? effect.selector : null,
      component: root && typeof root.getAttribute === 'function'
        ? root.getAttribute('data-volt-component')
        : null,
      element: target || null,
    }, extra || {});
  }

  function createEffectResult(root, effect, target, handled, preventsHtmlFallback, extra) {
    emitRuntimeHook('volt:after-effect', effectHookDetail(root, effect, target, Object.assign({
      handled: handled,
      preventsHtmlFallback: preventsHtmlFallback,
    }, extra || {})), target || root || document);

    return {
      handled: handled,
      preventsHtmlFallback: preventsHtmlFallback,
    };
  }

  async function withPreservedUiState(root, callback, meta) {
    const detail = meta && typeof meta === 'object' ? meta : {};
    const focusState = captureFocusState(root);
    const scrollState = captureScrollState(root);
    emitRuntimeHook('volt:before-patch', detail, root);
    const result = await callback();
    const updatedRoot = root && root.isConnected
      ? root
      : root && root.getAttribute
        ? findRootByComponent(root.getAttribute('data-volt-component'))
        : null;

    if (updatedRoot) {
      restoreScrollState(updatedRoot, scrollState);
      restoreFocusState(updatedRoot, focusState);
    }

    emitRuntimeHook('volt:after-patch', Object.assign({}, detail, {
      updatedRoot: updatedRoot || null,
    }), updatedRoot || root || document);

    return result;
  }

  function resolveRuntimeRoot(rootOrComponent, fallbackComponent) {
    if (rootOrComponent && typeof rootOrComponent === 'object' && rootOrComponent.isConnected) {
      return rootOrComponent;
    }

    if (typeof rootOrComponent === 'string' && rootOrComponent !== '') {
      return findRootByComponent(rootOrComponent);
    }

    if (typeof fallbackComponent === 'string' && fallbackComponent !== '') {
      return findRootByComponent(fallbackComponent);
    }

    return null;
  }

  function isAbortError(error) {
    return !!(
      error &&
      typeof error === 'object' &&
      (
        error.name === 'AbortError' ||
        error.code === 20
      )
    );
  }

  function triggerDescriptor(trigger) {
    if (!trigger || typeof trigger.getAttribute !== 'function') {
      return null;
    }

    return {
      tag: trigger.tagName ? String(trigger.tagName).toLowerCase() : null,
      target: trigger.getAttribute('data-volt-target'),
      action: directiveValue(trigger, ['volt-click', 'volt:click', 'volt-submit', 'volt:submit']),
    };
  }

  function requestHookDetail(kind, meta, extra) {
    return Object.assign({
      type: kind,
      component: meta && meta.component ? meta.component : null,
      action: meta && meta.action ? meta.action : null,
      requestId: meta && meta.requestId ? meta.requestId : null,
      trigger: meta && meta.trigger ? meta.trigger : null,
    }, extra || {});
  }

  function responseErrorDetail(response, payload, meta) {
    const payloadError = payload && payload.error && typeof payload.error === 'object'
      ? payload.error
      : {};

    return requestHookDetail('action', meta, {
      status: response.status,
      ok: false,
      message: payloadError.message || ('Request failed with status ' + response.status + '.'),
      error: payloadError,
      outcome: 'error',
    });
  }

  function exceptionErrorDetail(error, meta) {
    return requestHookDetail('action', meta, {
      ok: false,
      message: error && error.message ? error.message : 'Unexpected runtime error.',
      outcome: 'error',
    });
  }

  function stateTargetValue(detail) {
    if (!detail || typeof detail !== 'object') {
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
    if (!element || typeof element.getAttribute !== 'function') {
      return null;
    }

    return directiveValue(element, ['volt-model', 'volt:model']) ||
      element.getAttribute('data-volt-target') ||
      element.getAttribute('name') ||
      element.id ||
      null;
  }

  function syncRequestStatus(root) {
    if (!root) {
      return;
    }

    if (root.getAttribute('data-volt-loading') === 'true') {
      root.setAttribute('data-volt-request-status', 'loading');
      root.setAttribute('aria-busy', 'true');
      return;
    }

    if (root.getAttribute('data-volt-error') === 'true') {
      root.setAttribute('data-volt-request-status', 'error');
      root.setAttribute('aria-busy', 'false');
      return;
    }

    if (root.getAttribute('data-volt-success') === 'true') {
      root.setAttribute('data-volt-request-status', 'success');
      root.setAttribute('aria-busy', 'false');
      return;
    }

    if (root.getAttribute('data-volt-dirty') === 'true') {
      root.setAttribute('data-volt-request-status', 'dirty');
      root.setAttribute('aria-busy', 'false');
      return;
    }

    root.setAttribute('data-volt-request-status', 'idle');
    root.setAttribute('aria-busy', 'false');
  }

  function setLoadingState(rootOrComponent, active, trigger, meta) {
    const detail = meta && typeof meta === 'object' ? meta : {};
    const root = resolveRuntimeRoot(rootOrComponent, detail.component);

    if (root) {
      const previous = root.getAttribute('data-volt-loading') === 'true';
      const context = active
        ? {
            action: detail.action || null,
            target: stateTargetValue(detail),
          }
        : runtimeStateContext(root, 'loading');
      const minDuration = previous ? resolveStateDirectiveMinDuration(root, 'loading', context) : null;
      const activatedAt = runtime.loadingActivatedAt.get(root) || null;
      const elapsed = activatedAt === null ? null : Date.now() - activatedAt;
      const remainingMinDuration = minDuration !== null && elapsed !== null
        ? Math.max(0, minDuration - elapsed)
        : null;

      if (!active && previous && remainingMinDuration !== null && remainingMinDuration > 0 && detail.reason !== 'min-duration') {
        scheduleLoadingMinDurationClear(root, trigger, detail, remainingMinDuration);
        return;
      }

      clearLoadingMinDuration(root);

      if (active) {
        runtime.loadingActivatedAt.set(root, Date.now());
      } else {
        runtime.loadingActivatedAt.delete(root);
      }

      root.setAttribute('data-volt-loading', active ? 'true' : 'false');

      if (active && detail.action) {
        root.setAttribute('data-volt-loading-action', detail.action);
      } else {
        root.removeAttribute('data-volt-loading-action');
      }

      if (active && detail.trigger && detail.trigger.target) {
        root.setAttribute('data-volt-loading-target', detail.trigger.target);
      } else {
        root.removeAttribute('data-volt-loading-target');
      }

      if (active && detail.requestId) {
        root.setAttribute('data-volt-request-id', String(detail.requestId));
      } else {
        root.removeAttribute('data-volt-request-id');
      }

      syncRequestStatus(root);
      syncRuntimeStateDirectives(root);
    }

    if (trigger && 'disabled' in trigger) {
      trigger.disabled = active;
    }
  }

  function setErrorState(rootOrComponent, active, meta) {
    const detail = meta && typeof meta === 'object' ? meta : {};
    const root = resolveRuntimeRoot(rootOrComponent, detail.component);

    if (!root) {
      return;
    }

    const previous = root.getAttribute('data-volt-error') === 'true';
    clearErrorTimeout(root);
    root.setAttribute('data-volt-error', active ? 'true' : 'false');

    if (active) {
      if (detail.action) {
        root.setAttribute('data-volt-error-action', detail.action);
      } else {
        root.removeAttribute('data-volt-error-action');
      }

      if (detail.trigger && detail.trigger.target) {
        root.setAttribute('data-volt-error-target', detail.trigger.target);
      } else {
        root.removeAttribute('data-volt-error-target');
      }

      if (detail.message) {
        root.setAttribute('data-volt-error-message', String(detail.message));
      } else {
        root.removeAttribute('data-volt-error-message');
      }

      syncRequestStatus(root);
      syncRuntimeStateDirectives(root);
      scheduleErrorTimeout(root, detail);
      return;
    }

    root.removeAttribute('data-volt-error-message');
    root.removeAttribute('data-volt-error-action');
    root.removeAttribute('data-volt-error-target');

    syncRequestStatus(root);
    syncRuntimeStateDirectives(root);

    if (previous) {
      emitRuntimeHook('volt:error-cleared', requestHookDetail('error', detail, {
        target: stateTargetValue(detail),
        active: false,
        reason: detail.reason || null,
      }), root);
    }
  }

  function setDirtyState(rootOrComponent, active, meta) {
    const detail = meta && typeof meta === 'object' ? meta : {};
    const root = resolveRuntimeRoot(rootOrComponent, detail.component);

    if (!root) {
      return;
    }

    if (!active) {
      clearDirtyDebounce(root);
    }

    const previous = root.getAttribute('data-volt-dirty') === 'true';
    root.setAttribute('data-volt-dirty', active ? 'true' : 'false');

    if (active) {
      const target = stateTargetValue(detail);

      if (target) {
        root.setAttribute('data-volt-dirty-target', target);
      } else {
        root.removeAttribute('data-volt-dirty-target');
      }
    } else {
      root.removeAttribute('data-volt-dirty-target');
    }

    syncRequestStatus(root);
    syncRuntimeStateDirectives(root);

    if (previous !== active) {
      emitRuntimeHook(active ? 'volt:dirty' : 'volt:clean', requestHookDetail('dirty', detail, {
        target: stateTargetValue(detail),
        active: active,
        reason: detail.reason || null,
        debounce: detail.debounce || null,
      }), root);
    }
  }

  function setSuccessState(rootOrComponent, active, meta) {
    const detail = meta && typeof meta === 'object' ? meta : {};
    const root = resolveRuntimeRoot(rootOrComponent, detail.component);

    if (!root) {
      return;
    }

    const previous = root.getAttribute('data-volt-success') === 'true';
    const context = active
      ? {
          action: detail.action || null,
          target: stateTargetValue(detail),
        }
      : runtimeStateContext(root, 'success');
    const minDuration = previous ? resolveStateDirectiveMinDuration(root, 'success', context) : null;
    const activatedAt = runtime.successActivatedAt.get(root) || null;
    const elapsed = activatedAt === null ? null : Date.now() - activatedAt;
    const remainingMinDuration = minDuration !== null && elapsed !== null
      ? Math.max(0, minDuration - elapsed)
      : null;

    if (!active && previous && remainingMinDuration !== null && remainingMinDuration > 0 && detail.reason !== 'min-duration') {
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

    root.setAttribute('data-volt-success', active ? 'true' : 'false');

    if (active) {
      if (detail.action) {
        root.setAttribute('data-volt-success-action', detail.action);
      } else {
        root.removeAttribute('data-volt-success-action');
      }

      const target = stateTargetValue(detail);

      if (target) {
        root.setAttribute('data-volt-success-target', target);
      } else {
        root.removeAttribute('data-volt-success-target');
      }
    } else {
      root.removeAttribute('data-volt-success-action');
      root.removeAttribute('data-volt-success-target');
    }

    syncRequestStatus(root);
    syncRuntimeStateDirectives(root);

    const timeout = active ? resolveStateDirectiveTimeout(root, 'success') : null;

    if (active) {
      scheduleSuccessTimeout(root, detail);
    }

    if (previous !== active) {
      emitRuntimeHook(active ? 'volt:success' : 'volt:success-cleared', requestHookDetail('success', detail, {
        target: stateTargetValue(detail),
        active: active,
        timeout: timeout,
        minDuration: minDuration,
        reason: detail.reason || null,
      }), root);
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

    if (link.hasAttribute('download')) {
      return false;
    }

    const target = link.getAttribute('target');

    if (target && target !== '' && target.toLowerCase() !== '_self') {
      return false;
    }

    const href = link.getAttribute('href');

    if (!href || href.startsWith('#')) {
      return false;
    }

    const url = new URL(href, window.location.href);

    if (!sameOrigin(url)) {
      return false;
    }

    return true;
  }

  function setNavigationState(active, trigger) {
    document.documentElement.setAttribute('data-volt-navigating', active ? 'true' : 'false');
    document.documentElement.setAttribute('aria-busy', active ? 'true' : 'false');

    if (document.body) {
      document.body.setAttribute('data-volt-navigating', active ? 'true' : 'false');
      document.body.setAttribute('aria-busy', active ? 'true' : 'false');
    }

    if (trigger && 'disabled' in trigger) {
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
      currentBody.setAttribute(name, nextBody.getAttribute(name) || '');
    });
  }

  function applyDocumentPayload(doc) {
    if (doc.title) {
      document.title = doc.title;
    }

    if (doc.body) {
      replaceBodyAttributes(doc.body);
      document.body.innerHTML = doc.body.innerHTML;
      syncAllRuntimeStateDirectives();
    }
  }

  function resolveEffectTarget(root, effect) {
    if (!effect || typeof effect !== 'object') {
      return null;
    }

    if (effect.target === 'root' || effect.target === 'self') {
      return root;
    }

    if (typeof effect.selector === 'string' && effect.selector !== '') {
      return document.querySelector(effect.selector);
    }

    if (typeof effect.target !== 'string' || effect.target === '') {
      return null;
    }

    const escapedTarget = cssEscape(effect.target);
    const scopedTarget = root
      ? root.querySelector('#' + escapedTarget) ||
        root.querySelector('[data-volt-target="' + effect.target + '"]')
      : null;

    if (scopedTarget) {
      return scopedTarget;
    }

    return document.getElementById(effect.target) ||
      document.querySelector('[data-volt-target="' + effect.target + '"]');
  }

  function dispatchRuntimeEvent(effect, target) {
    const name = effect.name || effect.event;

    if (!name) {
      return;
    }

    const eventTarget = target || document;
    eventTarget.dispatchEvent(new CustomEvent(name, {
      detail: effect.payload || effect.detail || {},
      bubbles: true,
    }));
  }

  function applyHtmlReplace(root, target, effect) {
    if (!target) {
      return null;
    }

    const html = typeof effect.html === 'string' ? effect.html : effect.value;

    if (typeof html !== 'string') {
      return null;
    }

    const descriptor = buildStableElementDescriptor(target);

    if (effect.outer === true || effect.mode === 'outer' || target === document.body || target.hasAttribute('data-volt-root')) {
      target.outerHTML = html;

      if (target === document.body) {
        return document.body;
      }

      if (target.hasAttribute('data-volt-root')) {
        const componentName = target.getAttribute('data-volt-component');

        return componentName ? findRootByComponent(componentName) : null;
      }

      return descriptor ? findByDescriptor(root || document.body, descriptor) : null;
    }

    target.innerHTML = html;
    return target;
  }

  function applyClassToggle(target, effect) {
    if (!target) {
      return;
    }

    const className = effect.class || effect.className || effect.value;

    if (typeof className !== 'string' || className === '') {
      return;
    }

    if (typeof effect.force === 'boolean') {
      target.classList.toggle(className, effect.force);
      return;
    }

    target.classList.toggle(className);
  }

  function applyStyleSet(target, effect) {
    if (!target) {
      return;
    }

    if (effect.styles && typeof effect.styles === 'object') {
      Object.keys(effect.styles).forEach(function (property) {
        if (effect.styles[property] === null) {
          target.style.removeProperty(property);
          return;
        }

        target.style.setProperty(property, String(effect.styles[property]));
      });

      return;
    }

    if (typeof effect.property === 'string') {
      if (effect.value === null) {
        target.style.removeProperty(effect.property);
        return;
      }

      if (typeof effect.value !== 'undefined') {
        target.style.setProperty(effect.property, String(effect.value));
      }
    }
  }

  function resolveContainerTarget(root, effect) {
    if (!effect || typeof effect !== 'object') {
      return null;
    }

    if (typeof effect.parentTarget === 'string' && effect.parentTarget !== '') {
      return resolveEffectTarget(root, { target: effect.parentTarget });
    }

    return resolveEffectTarget(root, effect);
  }

  function applyDomInsert(root, effect) {
    const container = resolveContainerTarget(root, effect);
    const fragment = fragmentFromHtml(effect.html);

    if (!container || typeof effect.html !== 'string' || !fragment) {
      return [];
    }

    const insertedNodes = Array.from(fragment.childNodes);
    const insertedElements = insertedNodes.filter(function (node) {
      return node.nodeType === 1;
    });

    if (typeof effect.beforeSelector === 'string' && effect.beforeSelector !== '') {
      const anchor = document.querySelector(effect.beforeSelector);

      if (anchor) {
        anchor.parentNode.insertBefore(fragment, anchor);
        return insertedElements;
      }
    }

    if ((effect.position || 'beforeend') === 'afterbegin' && container.firstChild) {
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

    if (typeof effect.beforeSelector === 'string' && effect.beforeSelector !== '') {
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
    if (!target || typeof name !== 'string') {
      return;
    }

    if (name === 'value' && 'value' in target) {
      target.value = value;
      return;
    }

    if (name === 'checked' && 'checked' in target) {
      target.checked = value !== null;
      return;
    }

    if (name === 'selected' && 'selected' in target) {
      target.selected = value !== null;
      return;
    }

    if (name === 'disabled' && 'disabled' in target) {
      target.disabled = value !== null;
    }
  }

  function applyScroll(target, effect) {
    const behavior = effect.behavior === 'smooth' ? 'smooth' : 'auto';

    if (target && typeof target.scrollIntoView === 'function') {
      target.scrollIntoView({
        behavior: behavior,
        block: effect.block || 'start',
        inline: effect.inline || 'nearest',
      });
      return;
    }

    window.scrollTo({
      top: typeof effect.top === 'number' ? effect.top : 0,
      left: typeof effect.left === 'number' ? effect.left : 0,
      behavior: behavior,
    });
  }

  async function applyEffect(root, effect) {
    if (!effect || typeof effect.type !== 'string') {
      return {
        handled: false,
        preventsHtmlFallback: false,
      };
    }

    const target = resolveEffectTarget(root, effect);
    emitRuntimeHook('volt:before-effect', effectHookDetail(root, effect, target), target || root || document);

    switch (effect.type) {
      case 'text.update':
        if (target && typeof effect.value !== 'undefined') {
          target.textContent = String(effect.value);
          await runElementTransition(root, target, 'update', effect);
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case 'html.replace':
        {
          const replacedTarget = applyHtmlReplace(root, target, effect);

          if (replacedTarget) {
            await runElementTransition(root, replacedTarget, effect.outer === true || effect.mode === 'outer' ? 'enter' : 'update', effect);
          }

          return createEffectResult(root, effect, replacedTarget || target, !!target, !!target);
        }

      case 'dom.append':
        if (target && typeof effect.html === 'string') {
          const insertedElements = applyDomInsert(root, Object.assign({}, effect, {
            beforeSelector: null,
            position: effect.position || 'beforeend',
          }));

          for (let index = 0; index < insertedElements.length; index += 1) {
            await runElementTransition(root, insertedElements[index], 'enter', effect);
          }

          return createEffectResult(root, effect, target, true, true, {
            insertedCount: insertedElements.length,
          });
        }
        break;

      case 'dom.insert':
        {
          const insertedElements = applyDomInsert(root, effect);

          if (insertedElements.length > 0) {
            for (let index = 0; index < insertedElements.length; index += 1) {
              await runElementTransition(root, insertedElements[index], 'enter', effect);
            }

            return createEffectResult(root, effect, target, true, true, {
              insertedCount: insertedElements.length,
            });
          }
        }
        break;

      case 'dom.remove':
        if (target) {
          await runElementTransition(root, target, 'leave', effect);
          target.remove();
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case 'dom.move':
        if (applyDomMove(root, target, effect)) {
          await runElementTransition(root, target, 'move', effect);
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case 'attribute.set':
        if (target && typeof effect.name === 'string') {
          const attributeValue = typeof effect.value === 'undefined' ? '' : String(effect.value);
          target.setAttribute(effect.name, attributeValue);
          syncAttributeProperty(target, effect.name, attributeValue);
          await runElementTransition(root, target, 'update', effect);
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case 'attribute.remove':
        if (target && typeof effect.name === 'string') {
          target.removeAttribute(effect.name);
          syncAttributeProperty(target, effect.name, null);
          await runElementTransition(root, target, 'update', effect);
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case 'class.toggle':
        applyClassToggle(target, effect);
        if (target) {
          await runElementTransition(root, target, 'update', effect);
        }
        return createEffectResult(root, effect, target, !!target, !!target);

      case 'style.set':
        applyStyleSet(target, effect);
        if (target) {
          await runElementTransition(root, target, 'update', effect);
        }
        return createEffectResult(root, effect, target, !!target, !!target);

      case 'focus':
        if (target && typeof target.focus === 'function') {
          target.focus();
          return createEffectResult(root, effect, target, true, false);
        }
        break;

      case 'blur':
        if (target && typeof target.blur === 'function') {
          target.blur();
          return createEffectResult(root, effect, target, true, false);
        }
        break;

      case 'scroll':
        applyScroll(target, effect);
        return createEffectResult(root, effect, target, true, false);

      case 'dispatch.event':
        dispatchRuntimeEvent(effect, target);
        return createEffectResult(root, effect, target, true, false);

      case 'navigate':
        if (typeof effect.url === 'string' && effect.url !== '') {
          await visit(effect.url, {
            historyMode: effect.replace ? 'replace' : 'push',
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
      preventsHtmlFallback = result.preventsHtmlFallback || preventsHtmlFallback;
    }

    return {
      handled: handled,
      preventsHtmlFallback: preventsHtmlFallback,
    };
  }

  async function requestPage(url, signal) {
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'VoltStack',
        'X-Volt-Navigate': 'true',
      },
      credentials: 'same-origin',
      signal: signal,
    });

    if (!response.ok) {
      throw new Error('Navigation request failed with status ' + response.status + '.');
    }

    const html = await response.text();
    const parser = new DOMParser();
    const documentPayload = parser.parseFromString(html, 'text/html');

    return {
      document: documentPayload,
      finalUrl: response.url || url,
    };
  }

  async function visit(url, options) {
    const settings = options || {};
    const requestId = runtime.navigationRequestId + 1;
    runtime.navigationRequestId = requestId;
    const previousController = runtime.navigationController;
    const controller = typeof AbortController === 'function' ? new AbortController() : null;
    const requestMeta = {
      requestId: requestId,
      trigger: triggerDescriptor(settings.trigger || null),
    };
    runtime.navigationController = controller;

    if (previousController) {
      previousController.abort();
    }

    setNavigationState(true, settings.trigger || null);
    emitRuntimeHook('volt:request-start', requestHookDetail('navigation', requestMeta, {
      url: url,
      historyMode: settings.historyMode || 'push',
    }), document);

    let outcome = 'success';

    try {
      const payload = await requestPage(url, controller ? controller.signal : undefined);

      if (runtime.navigationRequestId !== requestId) {
        outcome = 'stale';
        emitRuntimeHook('volt:request-stale', requestHookDetail('navigation', requestMeta, {
          url: url,
          finalUrl: payload.finalUrl,
          outcome: outcome,
        }), document);
        return;
      }

      emitRuntimeHook('volt:before-navigate', {
        url: url,
        finalUrl: payload.finalUrl,
      }, document);

      await withPreservedUiState(document.body, async function () {
        applyDocumentPayload(payload.document);
      }, {
        type: 'navigation',
        url: url,
        finalUrl: payload.finalUrl,
      });

      if (settings.historyMode === 'replace') {
        window.history.replaceState({}, '', payload.finalUrl);
      } else if (settings.updateHistory !== false) {
        window.history.pushState({}, '', payload.finalUrl);
      }

      if (settings.preserveScroll !== true) {
        window.scrollTo(0, 0);
      }

      emitRuntimeHook('volt:navigated', {
        url: url,
        finalUrl: payload.finalUrl,
        historyMode: settings.historyMode || 'push',
      }, document);
    } catch (error) {
      if (isAbortError(error)) {
        outcome = 'aborted';
        emitRuntimeHook('volt:request-abort', requestHookDetail('navigation', requestMeta, {
          url: url,
          outcome: outcome,
        }), document);
        return;
      }

      outcome = 'error';
      emitRuntimeHook('volt:request-error', requestHookDetail('navigation', requestMeta, {
        url: url,
        message: error && error.message ? error.message : 'Navigation failed.',
        outcome: outcome,
      }), document);

      if (settings.fallback !== false) {
        window.location.assign(url);
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

      emitRuntimeHook('volt:request-finish', requestHookDetail('navigation', requestMeta, {
        url: url,
        outcome: outcome,
      }), document);
    }
  }

  async function dispatchAction(root, action, params, updates, trigger) {
    const snapshot = root.getAttribute('data-volt-snapshot');
    const component = root.getAttribute('data-volt-component');
    const endpoint = root.getAttribute('data-volt-endpoint') || '/_volt/action';
    const csrf = root.getAttribute('data-volt-csrf');

    if (!snapshot || !component || !action) {
      return;
    }

    const state = componentRequestState(component);
    const previousController = state && state.controller ? state.controller : null;
    const requestId = state ? state.requestId + 1 : 1;
    const controller = typeof AbortController === 'function' ? new AbortController() : null;
    const requestMeta = {
      component: component,
      action: action,
      requestId: requestId,
      trigger: triggerDescriptor(trigger),
    };

    if (state) {
      state.requestId = requestId;
      state.controller = controller;
    }

    if (previousController) {
      previousController.abort();
    }

    clearDirtyDebounce(root);
    setErrorState(component, false, requestMeta);
    setSuccessState(component, false, Object.assign({}, requestMeta, {
      reason: 'request',
    }));

    if (trigger && 'disabled' in trigger) {
      trigger.disabled = true;
    }

    scheduleLoadingDelay(root, trigger, requestMeta);
    emitRuntimeHook('volt:request-start', requestHookDetail('action', requestMeta), resolveRuntimeRoot(root, component) || document);

    let outcome = 'success';

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'VoltStack',
          'X-CSRF-TOKEN': csrf || '',
        },
        credentials: 'same-origin',
        signal: controller ? controller.signal : undefined,
        body: JSON.stringify({
          component: component,
          action: action,
          params: params || {},
          updates: updates || {},
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
        outcome = 'stale';
        emitRuntimeHook('volt:request-stale', requestHookDetail('action', requestMeta, {
          status: response.status,
          outcome: outcome,
        }), resolveRuntimeRoot(root, component) || document);
        return;
      }

      if (!response.ok) {
        outcome = 'error';
        const errorDetail = responseErrorDetail(response, payload, requestMeta);
        setErrorState(component, true, errorDetail);
        emitRuntimeHook('volt:request-error', errorDetail, resolveRuntimeRoot(root, component) || document);
        return;
      }

      const patchMeta = {
        type: 'action',
        component: component,
        action: action,
        effects: Array.isArray(payload.effects) ? payload.effects.map(function (effect) {
          return effect && effect.type ? effect.type : null;
        }).filter(function (value) {
          return value !== null;
        }) : [],
        usedHtmlFallback: false,
      };

      const patchRoot = resolveRuntimeRoot(root, component) || root;

      await withPreservedUiState(patchRoot, async function () {
        const activeRoot = resolveRuntimeRoot(root, component) || root;
        const result = await applyEffects(activeRoot, payload.effects);

        if (!result.preventsHtmlFallback && payload.html && activeRoot.isConnected) {
          patchMeta.usedHtmlFallback = true;
          activeRoot.outerHTML = payload.html;
        }

        const updatedRoot = resolveRuntimeRoot(activeRoot, component);

        if (payload.snapshot && updatedRoot) {
          updatedRoot.setAttribute('data-volt-snapshot', JSON.stringify(payload.snapshot));
        }

        return result;
      }, patchMeta);

      setDirtyState(component, false, requestMeta);
      setSuccessState(component, true, requestMeta);
    } catch (error) {
      if (isAbortError(error)) {
        outcome = 'aborted';
        emitRuntimeHook('volt:request-abort', requestHookDetail('action', requestMeta, {
          outcome: outcome,
        }), resolveRuntimeRoot(root, component) || document);
        return;
      }

      outcome = 'error';
      const errorDetail = exceptionErrorDetail(error, requestMeta);
      setErrorState(component, true, errorDetail);
      emitRuntimeHook('volt:request-error', errorDetail, resolveRuntimeRoot(root, component) || document);
      throw error;
    } finally {
      if (state && state.requestId === requestId) {
        state.controller = null;
        clearLoadingDelay(resolveRuntimeRoot(root, component) || root);
        setLoadingState(component, false, trigger, requestMeta);
      }

      emitRuntimeHook('volt:request-finish', requestHookDetail('action', requestMeta, {
        outcome: outcome,
      }), resolveRuntimeRoot(root, component) || document);
    }
  }

  document.addEventListener('input', function (event) {
    const element = event.target.closest('input, textarea, select');

    if (!element) {
      return;
    }

    const root = findRoot(element);

    if (!root) {
      return;
    }

    const component = root.getAttribute('data-volt-component');
    const snapshot = readSnapshot(root);
    const key = directiveValue(element, ['volt-model', 'volt:model']);

    if (snapshot && snapshot.state && key) {
      snapshot.state[key] = element.type === 'checkbox' ? !!element.checked : element.value;
      root.setAttribute('data-volt-snapshot', JSON.stringify(snapshot));
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

  document.addEventListener('click', function (event) {
    const actionTrigger = event.target.closest('[volt-click], [volt\\:click]');

    if (actionTrigger) {
      const root = findRoot(actionTrigger);

      if (!root) {
        return;
      }

      event.preventDefault();

      const params = directiveValue(actionTrigger, ['volt-params', 'volt:params']);
      dispatchAction(
        root,
        directiveValue(actionTrigger, ['volt-click', 'volt:click']),
        params ? JSON.parse(params) : {},
        collectModelUpdates(root),
        actionTrigger
      ).catch(function (error) {
        console.error('VoltStack runtime error:', error);
      });

      return;
    }

    const navigationTrigger = event.target.closest('a[volt-navigate], a[volt\\:navigate]');

    if (!shouldHandleNavigation(event, navigationTrigger)) {
      return;
    }

    event.preventDefault();

    const url = new URL(navigationTrigger.href, window.location.href);
    const preserveScroll = navigationTrigger.hasAttribute('volt-preserve-scroll') ||
      navigationTrigger.hasAttribute('volt:preserve-scroll');
    const replace = navigationTrigger.hasAttribute('volt-replace') ||
      navigationTrigger.hasAttribute('volt:replace');

    visit(url.toString(), {
      trigger: navigationTrigger,
      preserveScroll: preserveScroll,
      historyMode: replace ? 'replace' : 'push',
    }).catch(function (error) {
      console.error('VoltStack navigation error:', error);
    });
  });

  document.addEventListener('submit', function (event) {
    const form = event.target.closest('form[volt-submit], form[volt\\:submit]');

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
      directiveValue(form, ['volt-submit', 'volt:submit']),
      collectFormData(form),
      collectModelUpdates(root),
      form
    ).catch(function (error) {
      console.error('VoltStack runtime error:', error);
    });
  });

  window.addEventListener('popstate', function () {
    visit(window.location.href, {
      updateHistory: false,
      historyMode: 'replace',
      preserveScroll: false,
      fallback: false,
    }).catch(function (error) {
      console.error('VoltStack navigation error:', error);
      window.location.reload();
    });
  });

  syncAllRuntimeStateDirectives();
})();
