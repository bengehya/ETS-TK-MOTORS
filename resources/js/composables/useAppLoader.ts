import { router } from '@inertiajs/vue3';
import { computed, readonly, ref } from 'vue';

const pending = ref(0);
const visible = ref(false);
const APPEAR_DELAY_MS = 250;

let showTimer: ReturnType<typeof setTimeout> | null = null;
let installed = false;

const clearShowTimer = () => {
    if (showTimer !== null) {
        clearTimeout(showTimer);
        showTimer = null;
    }
};

const shouldSkipVisit = (): boolean => {
    const path = window.location.pathname;

    return path === '/' || path === '';
};

export const beginLoader = (): void => {
    pending.value += 1;

    if (pending.value === 1 && !visible.value) {
        clearShowTimer();
        showTimer = setTimeout(() => {
            showTimer = null;
            if (pending.value > 0) {
                visible.value = true;
            }
        }, APPEAR_DELAY_MS);
    }
};

export const endLoader = (): void => {
    pending.value = Math.max(0, pending.value - 1);

    if (pending.value === 0) {
        clearShowTimer();
        visible.value = false;
    }
};

export const trackLoader = async <T>(operation: () => Promise<T>): Promise<T> => {
    beginLoader();

    try {
        return await operation();
    } finally {
        endLoader();
    }
};

export const installAppLoader = (): void => {
    if (installed) {
        return;
    }

    installed = true;

    router.on('start', (event) => {
        const headers = event.detail.visit.headers;
        const skipHeader = headers['X-Tk-Skip-Loader'] ?? headers['x-tk-skip-loader'];

        if (shouldSkipVisit() || skipHeader === '1') {
            return;
        }

        beginLoader();
    });

    router.on('finish', () => endLoader());
};

export const useAppLoader = () => ({
    visible: readonly(visible),
    busy: computed(() => pending.value > 0),
    begin: beginLoader,
    end: endLoader,
    track: trackLoader,
});
