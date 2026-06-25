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
        html: {},
        bindings: {},
        modelLocal: null,
        portal: {},
        focus: {},
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

