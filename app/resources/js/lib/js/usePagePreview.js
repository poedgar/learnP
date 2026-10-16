import { onBeforeUnmount, ref, shallowRef } from 'vue';

/**
 * Shows the page a JavaScript run built. Bind `preview` as the ref of a <PagePreview>, pass
 * hostElement() to the engine's run(), then call show() with the (reactive) run result: if the run
 * left something on the page, its frame stays open in the preview, and anything it logs later,
 * such as from a click, is added to the result's entries.
 */
export function usePagePreview() {
    const preview = ref(null);
    const frame = shallowRef(null);
    const hostElement = () => preview.value?.element ?? null;

    function clear() {
        frame.value?.close();
        frame.value = null;
    }

    function show(result) {
        clear();
        if (!result?.frame) return;
        frame.value = result.frame;
        result.frame.onMessage((message) => {
            if (message?.type === 'log') {
                result.entries.push(message.entry);
                result.output = result.entries.map((entry) => entry.text).join('\n');
            }
        });
    }

    onBeforeUnmount(clear);

    return { preview, frame, hostElement, show, clear };
}
