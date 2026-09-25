/**
 * Brings the top of a paginated area into view after the page changes,
 * but only if it has scrolled out of sight.
 */
export function scrollToTop(element) {
    if (!element || element.getBoundingClientRect().top >= 0) {
        return;
    }
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    element.scrollIntoView({ block: 'start', behavior: reduceMotion ? 'auto' : 'smooth' });
}
