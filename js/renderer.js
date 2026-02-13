export function render(containerSelector, prop, renderFn) {
  const container = document.querySelector(containerSelector);
  if (container) {
    container.innerHTML = renderFn(prop);
  }
}