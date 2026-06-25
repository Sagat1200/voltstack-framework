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

  function parseDocumentContract(value, source) {
    const normalized =
      typeof value === "string" ? value.trim().toLowerCase() : "";

    if (
      normalized === "reload" ||
      normalized === "reload-only" ||
      normalized === "static" ||
      normalized === "non-spa" ||
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
      normalized === "interactive" ||
      normalized === "reactive"
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

  function documentContractForDocument(doc) {
    if (!doc || typeof doc !== "object") {
      return parseDocumentContract("", "default");
    }

    const declaredMeta = firstDocumentMetaValue(doc, DOCUMENT_CONTRACT_META_NAMES);

    if (declaredMeta !== null) {
      return parseDocumentContract(declaredMeta, "document");
    }

    if (doc.body && typeof doc.body.getAttribute === "function") {
      const attribute = directiveAttribute(doc.body, [
        "data-volt-document",
        "volt-document",
        "volt:document",
      ]);

      if (attribute) {
        return parseDocumentContract(attribute.value || "", "body");
      }
    }

    if (doc.documentElement && typeof doc.documentElement.getAttribute === "function") {
      const attribute = directiveAttribute(doc.documentElement, [
        "data-volt-document",
        "volt-document",
        "volt:document",
      ]);

      if (attribute) {
        return parseDocumentContract(attribute.value || "", "html");
      }
    }

    return parseDocumentContract("", "default");
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

