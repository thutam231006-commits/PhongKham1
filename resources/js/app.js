import Alpine from '@alpinejs/csp';

window.Alpine = Alpine;

Alpine.data('heroSlider', () => ({
    active: 0,
    timer: null,
    slides: [
        {
            src: 'https://images.unsplash.com/photo-1573497620053-ea5300f94f21?auto=format&fit=crop&w=1800&q=88',
            alt: 'Chuyên viên tâm lý lắng nghe khách hàng trong phòng tư vấn riêng tư',
        },
        {
            src: 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1800&q=88',
            alt: 'Không gian trò chuyện chuyên nghiệp và thân thiện',
        },
        {
            src: 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1800&q=88',
            alt: 'Không gian phòng khám sáng, hiện đại và yên tĩnh',
        },
    ],

    init() {
        this.start();
    },

    start() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        this.stop();
        this.timer = window.setInterval(() => {
            this.active = (this.active + 1) % this.slides.length;
        }, 5200);
    },

    stop() {
        if (this.timer) {
            window.clearInterval(this.timer);
            this.timer = null;
        }
    },
}));

Alpine.data('confirmModal', (requiresReason = false, useForm = true, eventName = '') => ({
    isOpen: false,
    reason: '',
    submitting: false,

    openModal() {
        this.isOpen = true;
        this.$nextTick(() => this.$refs.cancelBtn?.focus());
    },

    closeModal() {
        this.isOpen = false;
        this.reason = '';
        this.submitting = false;
    },

    confirmHandler() {
        if (requiresReason && this.reason.trim() === '') {
            this.$refs.reasonInput?.focus();
            this.$refs.reasonInput?.setCustomValidity('Vui lòng nhập lý do.');
            this.$refs.reasonInput?.reportValidity();

            return;
        }

        this.$refs.reasonInput?.setCustomValidity('');

        if (useForm) {
            this.submitting = true;
            this.$refs.modalForm?.submit();

            return;
        }

        this.$dispatch(eventName, { reason: this.reason });
        this.closeModal();
    },
}));

Alpine.data('bookingSlots', (config) => ({
    endpoint: config.endpoint,
    serviceId: config.serviceId,
    specialistId: config.specialistId,
    date: config.initialDate,
    slots: config.initialSlots || [],
    minDate: config.minDate,
    maxDate: config.maxDate,
    loading: false,
    error: '',

    get displayDate() {
        const [y, m, d] = this.date.split('-');

        return `${d}/${m}/${y}`;
    },

    async load() {
        if (!this.date) {
            return;
        }

        this.loading = true;
        this.error = '';

        try {
            const url = new URL(this.endpoint, window.location.origin);
            url.searchParams.set('service_id', this.serviceId);
            url.searchParams.set('specialist_id', this.specialistId);
            url.searchParams.set('date', this.date);

            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.error?.message || 'Không tải được khung giờ.');
            }

            this.slots = payload.data.slots;
        } catch (exception) {
            this.error = exception.message || 'Không tải được khung giờ trống.';
            this.slots = [];
        } finally {
            this.loading = false;
        }
    },
}));

Alpine.start();

const revealElements = document.querySelectorAll('.mg-reveal, .mg-stagger');

if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const revealObserver = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        },
        {
            rootMargin: '0px 0px -8% 0px',
            threshold: 0.12,
        },
    );

    revealElements.forEach((element) => revealObserver.observe(element));
} else {
    revealElements.forEach((element) => element.classList.add('is-visible'));
}

const paymentRoot = document.querySelector('[data-payment-status-url]');

if (paymentRoot) {
    const statusUrl = paymentRoot.dataset.paymentStatusUrl;
    const fallbackRedirectUrl = paymentRoot.dataset.paymentRedirectUrl;
    const expiresAt = paymentRoot.dataset.paymentExpiresAt ? new Date(paymentRoot.dataset.paymentExpiresAt) : null;
    const countdown = paymentRoot.querySelector('[data-payment-countdown]');
    const message = paymentRoot.querySelector('[data-payment-message]');
    const checkButton = paymentRoot.querySelector('[data-payment-check]');
    let stopped = false;

    const setMessage = (text, type = 'info') => {
        if (!message) {
            return;
        }

        message.textContent = text;
        message.className = type === 'success'
            ? 'rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-800'
            : type === 'error'
                ? 'rounded-xl border border-red-200 bg-red-50 p-4 text-sm leading-6 text-red-800'
                : 'rounded-xl border border-[#BFDBFE] bg-[#EFF6FF] p-4 text-sm leading-6 text-[#1E40AF]';
    };

    const checkPayment = async () => {
        if (stopped || !statusUrl) {
            return;
        }

        try {
            const response = await fetch(statusUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.error?.message || 'Không thể kiểm tra giao dịch.');
            }

            if (payload.data.payment_status === 'PAID') {
                stopped = true;
                setMessage('Thanh toán thành công. Đang chuyển đến chi tiết lịch hẹn...', 'success');
                window.setTimeout(() => {
                    window.location.assign(payload.data.redirect_url || fallbackRedirectUrl);
                }, 900);
            } else if (payload.data.appointment_status === 'CANCELLED_BY_CLINIC') {
                stopped = true;
                setMessage('Đã hết thời gian giữ chỗ. Vui lòng đặt lại lịch để tạo mã thanh toán mới.', 'error');
                checkButton?.setAttribute('disabled', 'disabled');
            } else {
                setMessage('Chưa nhận được giao dịch. Hệ thống sẽ tiếp tục tự kiểm tra qua SePay.');
            }
        } catch {
            setMessage('Tạm thời chưa kiểm tra được giao dịch. Vui lòng thử lại sau ít phút.', 'error');
        }
    };

    const updateCountdown = () => {
        if (!expiresAt || stopped) {
            return;
        }

        const remainingSeconds = Math.max(0, Math.floor((expiresAt.getTime() - Date.now()) / 1000));
        const minutes = String(Math.floor(remainingSeconds / 60)).padStart(2, '0');
        const seconds = String(remainingSeconds % 60).padStart(2, '0');

        if (countdown) {
            countdown.textContent = `${minutes}:${seconds}`;
        }

        if (remainingSeconds === 0) {
            checkPayment();
        }
    };

    checkButton?.addEventListener('click', checkPayment);
    paymentRoot.querySelectorAll('[data-copy-value]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(button.dataset.copyValue || '');
                const originalLabel = button.textContent;
                button.textContent = 'Đã sao chép';
                window.setTimeout(() => {
                    button.textContent = originalLabel;
                }, 1400);
            } catch {
                setMessage('Trình duyệt không cho phép sao chép tự động. Vui lòng chọn và sao chép nội dung thủ công.', 'error');
            }
        });
    });

    updateCountdown();
    window.setInterval(updateCountdown, 1000);
    window.setInterval(checkPayment, 5000);
}
