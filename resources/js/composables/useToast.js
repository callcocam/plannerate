import { ref } from 'vue';

export function useToast() {
    const toast = ref({
        show: false,
        message: '',
        type: 'info',
    });

    function showToast(message, type = 'info', duration = 3000) {
        toast.value = {
            show: true,
            message,
            type,
        };

        setTimeout(() => {
            toast.value.show = false;
        }, duration);
    }

    function closeToast() {
        toast.value.show = false;
    }

    return {
        toast,
        showToast,
        closeToast,
    };
} 