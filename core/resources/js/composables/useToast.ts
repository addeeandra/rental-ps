import { reactive } from 'vue';

export type ToastType = 'success' | 'error' | 'info';

export interface Toast {
    id: string;
    message: string;
    type: ToastType;
}

const toasts = reactive<Toast[]>([]);

export function useToast() {
    const showToast = (message: string, type: ToastType = 'info') => {
        const id = `toast-${Date.now()}-${Math.random()}`;
        const toast: Toast = { id, message, type };
        
        toasts.push(toast);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            removeToast(id);
        }, 5000);
    };
    
    const removeToast = (id: string) => {
        const index = toasts.findIndex((t) => t.id === id);
        if (index !== -1) {
            toasts.splice(index, 1);
        }
    };
    
    return {
        toasts,
        showToast,
        removeToast,
    };
}
