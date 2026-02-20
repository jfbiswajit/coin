import { ref } from 'vue';

export type ToastType = 'offline' | 'synced';

const message = ref<string | null>(null);
const type = ref<ToastType>('offline');
let timer: ReturnType<typeof setTimeout> | null = null;

export function useToast() {
    const show = (msg: string, toastType: ToastType = 'offline', duration = 4000) => {
        message.value = msg;
        type.value = toastType;
        if (timer) clearTimeout(timer);
        timer = setTimeout(() => { message.value = null; }, duration);
    };

    const dismiss = () => {
        message.value = null;
        if (timer) clearTimeout(timer);
    };

    return { message, type, show, dismiss };
}
