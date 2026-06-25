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

  function htmlDirectiveNames(suffix) {
    return storeDirectiveNames("html", suffix);
  }

  function bindDirectiveNames() {
    return ["volt:bind", "volt-bind", "data-volt-bind"];
  }

  function modelLocalDirectiveNames() {
    return [
      "volt:model.local",
      "volt-model-local",
      "data-volt-model-local",
    ];
  }

  function modelSyncDirectiveNames() {
    return [
      "volt:model.sync",
      "volt-model-sync",
      "data-volt-model-sync",
    ];
  }

  function portalDirectiveNames() {
    return storeDirectiveNames("portal");
  }

  function focusDirectiveNames(suffix) {
    return storeDirectiveNames("focus", suffix);
  }

  function autofocusDirectiveNames(suffix) {
    return storeDirectiveNames("autofocus", suffix);
  }

  function autofocusWhenDirectiveNames() {
    return autofocusDirectiveNames("when");
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

  function formatStoreDirectiveHtmlValue(value) {
    if (value === null || typeof value === "undefined") {
      return "";
    }

    if (typeof value === "string") {
      return value;
    }

    if (typeof value === "number" || typeof value === "boolean") {
      return String(value);
    }

    try {
      return JSON.stringify(value);
    } catch (error) {
      return String(value);
    }
  }

  function formatBindDirectiveValue(value) {
    if (value === null || typeof value === "undefined") {
      return "";
    }

    if (typeof value === "string") {
      return value;
    }

    if (typeof value === "number" || typeof value === "boolean") {
      return String(value);
    }

    try {
      return JSON.stringify(value);
    } catch (error) {
      return String(value);
    }
  }

  function valuesAreSame(left, right) {
    if (left === right) {
      return true;
    }

    if (
      typeof left === "number" &&
      typeof right === "number" &&
      Number.isNaN(left) &&
      Number.isNaN(right)
    ) {
      return true;
    }

    return false;
  }

  function normalizeBindDirectivePropertyName(name) {
    if (typeof name !== "string" || name.trim() === "") {
      return null;
    }

    const normalized = name
      .trim()
      .replace(/-([a-z])/g, function (_, character) {
        return character.toUpperCase();
      });

    if (normalized.toLowerCase() === "readonly") {
      return "readOnly";
    }

    return normalized;
  }

  function bindDirectiveReflectAttributeName(propertyName, rawName) {
    if (typeof rawName === "string" && rawName.trim() !== "") {
      return rawName
        .trim()
        .replace(/[A-Z]/g, function (character) {
          return "-" + character.toLowerCase();
        })
        .toLowerCase();
    }

    if (typeof propertyName !== "string" || propertyName === "") {
      return null;
    }

    return propertyName
      .replace(/[A-Z]/g, function (character) {
        return "-" + character.toLowerCase();
      })
      .toLowerCase();
  }

  function isBooleanBindDirectiveProperty(propertyName) {
    return [
      "checked",
      "disabled",
      "hidden",
      "required",
      "readOnly",
      "selected",
    ].indexOf(propertyName) !== -1;
  }

  function bindDirectiveEntries(element) {
    if (!element || !element.attributes) {
      return [];
    }

    return Array.from(element.attributes)
      .map(function (attribute) {
        const name = attribute && attribute.name ? attribute.name : "";
        let match = name.match(/^volt:bind:([A-Za-z0-9_-]+)$/);

        if (!match) {
          match = name.match(/^volt-bind-([A-Za-z0-9_-]+)$/);
        }

        if (!match) {
          match = name.match(/^data-volt-bind-([A-Za-z0-9_-]+)$/);
        }

        if (!match) {
          return null;
        }

        const rawProperty = match[1];
        const propertyName = normalizeBindDirectivePropertyName(rawProperty);

        if (!propertyName) {
          return null;
        }

        return {
          attributeName: name,
          rawProperty: rawProperty,
          propertyName: propertyName,
          expression: attribute.value || "",
          reflectAttributeName: bindDirectiveReflectAttributeName(
            propertyName,
            rawProperty,
          ),
        };
      })
      .filter(function (entry) {
        return !!entry;
      });
  }

