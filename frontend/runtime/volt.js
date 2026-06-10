(function () {
  const runtime = {
    navigationRequestId: 0,
  };

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

  function transitionDurationFor(element, effect) {
    if (!element) {
      return 180;
    }

    if (effect && typeof effect.transitionDuration === 'number' && effect.transitionDuration >= 0) {
      return effect.transitionDuration;
    }

    const attributeValue = element.getAttribute('data-volt-transition-duration');
    const parsed = attributeValue ? Number(attributeValue) : NaN;

    return Number.isFinite(parsed) && parsed >= 0 ? parsed : 180;
  }

  function transitionVariantFor(element, effect) {
    if (effect && effect.transition === false) {
      return null;
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

    const attributeValue = element.getAttribute('data-volt-transition');

    if (attributeValue === '') {
      return 'default';
    }

    return attributeValue || null;
  }

  async function runElementTransition(element, phase, effect) {
    const variant = transitionVariantFor(element, effect);

    if (!element || !phase || !variant) {
      return false;
    }

    const duration = transitionDurationFor(element, effect);
    const baseClass = 'volt-transition';
    const phaseClass = 'volt-transition-' + phase;
    const activeClass = phaseClass + '-active';
    const variantClass = 'volt-transition-' + variant;

    element.style.setProperty('--volt-transition-duration', duration + 'ms');
    element.classList.add(baseClass, phaseClass, variantClass);
    await nextFrame();
    element.classList.add(activeClass);
    await wait(duration);
    element.classList.remove(baseClass, phaseClass, activeClass, variantClass);
    element.style.removeProperty('--volt-transition-duration');

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

  function setLoadingState(root, active, trigger) {
    root.setAttribute('data-volt-loading', active ? 'true' : 'false');

    if (trigger && 'disabled' in trigger) {
      trigger.disabled = active;
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

    if (document.body) {
      document.body.setAttribute('data-volt-navigating', active ? 'true' : 'false');
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
          await runElementTransition(target, 'update', effect);
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case 'html.replace':
        {
          const replacedTarget = applyHtmlReplace(root, target, effect);

          if (replacedTarget) {
            await runElementTransition(replacedTarget, effect.outer === true || effect.mode === 'outer' ? 'enter' : 'update', effect);
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
            await runElementTransition(insertedElements[index], 'enter', effect);
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
              await runElementTransition(insertedElements[index], 'enter', effect);
            }

            return createEffectResult(root, effect, target, true, true, {
              insertedCount: insertedElements.length,
            });
          }
        }
        break;

      case 'dom.remove':
        if (target) {
          await runElementTransition(target, 'leave', effect);
          target.remove();
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case 'dom.move':
        if (applyDomMove(root, target, effect)) {
          await runElementTransition(target, 'move', effect);
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case 'attribute.set':
        if (target && typeof effect.name === 'string') {
          const attributeValue = typeof effect.value === 'undefined' ? '' : String(effect.value);
          target.setAttribute(effect.name, attributeValue);
          syncAttributeProperty(target, effect.name, attributeValue);
          await runElementTransition(target, 'update', effect);
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case 'attribute.remove':
        if (target && typeof effect.name === 'string') {
          target.removeAttribute(effect.name);
          syncAttributeProperty(target, effect.name, null);
          await runElementTransition(target, 'update', effect);
          return createEffectResult(root, effect, target, true, true);
        }
        break;

      case 'class.toggle':
        applyClassToggle(target, effect);
        if (target) {
          await runElementTransition(target, 'update', effect);
        }
        return createEffectResult(root, effect, target, !!target, !!target);

      case 'style.set':
        applyStyleSet(target, effect);
        if (target) {
          await runElementTransition(target, 'update', effect);
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

  async function requestPage(url) {
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'VoltStack',
        'X-Volt-Navigate': 'true',
      },
      credentials: 'same-origin',
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

    setNavigationState(true, settings.trigger || null);

    try {
      const payload = await requestPage(url);

      if (runtime.navigationRequestId !== requestId) {
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
      if (settings.fallback !== false) {
        window.location.assign(url);
        return;
      }

      throw error;
    } finally {
      if (runtime.navigationRequestId === requestId) {
        setNavigationState(false, settings.trigger || null);
      }
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

    setLoadingState(root, true, trigger);

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'VoltStack',
          'X-CSRF-TOKEN': csrf || '',
        },
        body: JSON.stringify({
          component: component,
          action: action,
          params: params || {},
          updates: updates || {},
          snapshot: JSON.parse(snapshot),
        }),
      });

      const payload = await response.json();

      if (!response.ok) {
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

      const effectsResult = await withPreservedUiState(root, async function () {
        const result = await applyEffects(root, payload.effects);

        if (!result.preventsHtmlFallback && payload.html && root.isConnected) {
          patchMeta.usedHtmlFallback = true;
          root.outerHTML = payload.html;
        }

        const updatedRoot = root.isConnected ? root : findRootByComponent(component);

        if (payload.snapshot && updatedRoot) {
          updatedRoot.setAttribute('data-volt-snapshot', JSON.stringify(payload.snapshot));
        }

        return result;
      }, patchMeta);
    } finally {
      setLoadingState(root, false, trigger);
    }
  }

  document.addEventListener('input', function (event) {
    const element = event.target.closest('[volt-model], [volt\\:model]');

    if (!element) {
      return;
    }

    const root = findRoot(element);

    if (!root) {
      return;
    }

    const snapshot = readSnapshot(root);

    if (!snapshot || !snapshot.state) {
      return;
    }

    const key = directiveValue(element, ['volt-model', 'volt:model']);

    if (!key) {
      return;
    }

    snapshot.state[key] = element.type === 'checkbox' ? !!element.checked : element.value;
    root.setAttribute('data-volt-snapshot', JSON.stringify(snapshot));
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
})();
