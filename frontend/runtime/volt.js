(function () {
  function findRoot(element) {
    return element.closest('[data-volt-root="true"]');
  }

  async function dispatchAction(root, action, params) {
    const snapshot = root.getAttribute('data-volt-snapshot');
    const component = root.getAttribute('data-volt-component');
    const endpoint = root.getAttribute('data-volt-endpoint') || '/_volt/action';

    if (!snapshot || !component || !action) {
      return;
    }

    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'VoltStack',
      },
      body: JSON.stringify({
        component: component,
        action: action,
        params: params || {},
        snapshot: JSON.parse(snapshot),
      }),
    });

    const payload = await response.json();

    if (!response.ok || !payload.html) {
      return;
    }

    root.outerHTML = payload.html;
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
      params ? JSON.parse(params) : {}
    ).catch(function (error) {
      console.error('VoltStack runtime error:', error);
    });
  });
})();
