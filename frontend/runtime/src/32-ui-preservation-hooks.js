  function elementIndex(elements, target) {
    for (let index = 0; index < elements.length; index += 1) {
      if (elements[index] === target) {
        return index;
      }
    }

    return -1;
  }

  function modelDescriptorMatches(root, modelName) {
    if (!root || !modelName) {
      return [];
    }

    return root.querySelectorAll(
      '[volt-model="' +
        modelName +
        '"], [volt\\:model="' +
        modelName +
        '"], [volt-model-local="' +
        modelName +
        '"], [volt\\:model\\.local="' +
        modelName +
        '"], [data-volt-model-local="' +
        modelName +
        '"], [volt-model-sync="' +
        modelName +
        '"], [volt\\:model\\.sync="' +
        modelName +
        '"], [data-volt-model-sync="' +
        modelName +
        '"]',
    );
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

    const modelName =
      directiveValue(element, ["volt-model", "volt:model"]) ||
      directiveValue(element, modelLocalDirectiveNames()) ||
      directiveValue(element, modelSyncDirectiveNames());

    if (modelName) {
      const matches = modelDescriptorMatches(root, modelName);
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
      const matches = modelDescriptorMatches(root, descriptor.value);

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

