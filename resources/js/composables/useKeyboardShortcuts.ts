import { onMounted, onUnmounted } from 'vue';

export function useKeyboardShortcuts(shortcuts: Record<string, () => void>) {
    const handler = (e: KeyboardEvent) => {
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        const target = e.target as HTMLElement;
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) || target.isContentEditable) return;
        shortcuts[e.key]?.();
    };
    onMounted(() => window.addEventListener('keydown', handler));
    onUnmounted(() => window.removeEventListener('keydown', handler));
}
