(function () {
  function findRoot(element) {
    return element.closest('[data-volt-root="true"]');
  }

  function readSnapshot(root) {
    const snapshot = root.getAttribute('data-volt-snapshot');

    return snapshot ? JSON.parse(snapshot) : null;
  }

  function collectModelUpdates(root) {
    const updates = {};

    root.querySelectorAll('[volt-model]').forEach(function (element) {
      const key = element.getAttribute('volt-model');

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

  function setLoadingState(root, active, trigger) {
    root.setAttribute('data-volt-loading', active ? 'true' : 'false');

    if (trigger && 'disabled' in trigger) {
      trigger.disabled = active;
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

      if (!response.ok || !payload.html) {
        return;
      }

      root.outerHTML = payload.html;
    } finally {
      setLoadingState(root, false, trigger);
    }
  }

  document.addEventListener('input', function (event) {
    const element = event.target.closest('[volt-model]');

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

    const key = element.getAttribute('volt-model');

    if (!key) {
      return;
    }

    snapshot.state[key] = element.type === 'checkbox' ? !!element.checked : element.value;
    root.setAttribute('data-volt-snapshot', JSON.stringify(snapshot));
  }

  document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[volt-click]');

    if (!trigger) {
      return;
    }

    const root = findRoot(trigger);

    if (!root) {
      return;
    }

    event.preventDefault();

    const params = trigger.getAttribute('volt-params');
    dispatchAction(
      root,
      trigger.getAttribute('volt-click'),
      params ? JSON.parse(params) : {},
      collectModelUpdates(root),
      trigger
    ).catch(function (error) {
      console.error('VoltStack runtime error:', error);
    });
  });

  document.addEventListener('submit', function (event) {
    const form = event.target.closest('form[volt-submit]');

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
      form.getAttribute('volt-submit'),
      collectFormData(form),
      collectModelUpdates(root),
      form
    ).catch(function (error) {
      console.error('VoltStack runtime error:', error);
    });
  });
})();
