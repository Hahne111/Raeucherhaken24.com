(() => {
  if (window.__RH24_EVENT_DELEGATION__) return;
  window.__RH24_EVENT_DELEGATION__ = true;

  const delegatedEvents = ['click', 'input', 'submit', 'keydown'];

  for (const eventName of delegatedEvents) {
    document.addEventListener(eventName, (event) => {
      const source = event.target instanceof Element
        ? event.target.closest(`[data-rh-on${eventName}]`)
        : null;
      if (!source) return;

      const handlerId = source.getAttribute(`data-rh-on${eventName}`);
      const handler = window.RH24EventHandlers?.[handlerId];
      if (typeof handler !== 'function') return;

      const result = handler.call(source, event);
      if (result === false) {
        event.preventDefault();
        event.stopPropagation();
      }
    });
  }
})();
