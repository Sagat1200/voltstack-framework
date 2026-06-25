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

