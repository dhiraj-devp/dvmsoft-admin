document.addEventListener('alpine:init', () => {
    Alpine.data('toasts', () => ({
        items: [],
        add(event) {
            const detail = event.detail ?? {};
            const item = {
                id: Date.now() + Math.random(),
                type: detail.type || 'success',
                message: detail.message || 'Saved.',
            };
            this.items.push(item);
            setTimeout(() => this.remove(item.id), 4200);
        },
        remove(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    }));
});
