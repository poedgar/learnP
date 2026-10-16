import { Platform } from 'react-native';

/**
 * The `dom` prop for an Expo DOM component, with a workaround for Android.
 *
 * On Android, react-native-webview defines injectedObjectJson racing the page's own scripts, so
 * Expo's page can end up without the host OS or initial props and crash ("Top OS
 * ($$EXPO_DOM_HOST_OS) is not defined"). Provide them ourselves, and ignore the page setting them
 * to undefined. Expo re-sends the live props once the page is ready.
 *
 * `functionNames` are the component's function props (they travel by name); `props` are the
 * others, as sent across.
 */
export function domProps(functionNames: string[], props: Record<string, unknown>, style: object = { flex: 1 }) {
  return {
    style,
    useExpoDOMWebView: false,
    injectedJavaScriptBeforeContentLoaded: `
      [['$$EXPO_DOM_HOST_OS', ${JSON.stringify(Platform.OS)}],
       ['$$EXPO_INITIAL_PROPS', ${JSON.stringify({ names: functionNames, props })}]
      ].forEach(function (entry) {
        var current = window[entry[0]] !== undefined ? window[entry[0]] : entry[1];
        Object.defineProperty(window, entry[0], {
          configurable: true,
          get: function () { return current; },
          set: function (next) { if (next !== undefined) current = next; },
        });
      });
      true;`,
  };
}
