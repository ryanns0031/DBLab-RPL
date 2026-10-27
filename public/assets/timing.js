(() => {
    const pad = value => String(value).padStart(2, '0');

    document.querySelectorAll('[data-server-countdown]').forEach(timer => {
        const target = Number(timer.dataset.targetTime);
        const serverNow = Number(timer.dataset.serverNow);
        const performanceStart = performance.now();
        const value = timer.querySelector('[data-countdown-value]');
        const status = timer.querySelector('[data-countdown-status]');
        let expiredSent = false;

        if (!Number.isFinite(target) || !Number.isFinite(serverNow) || !value) return;

        const update = () => {
            const elapsed = (performance.now() - performanceStart) / 1000;
            const remaining = Math.max(0, Math.ceil(target - serverNow - elapsed));
            timer.classList.toggle('is-warning', remaining > 60 && remaining <= 300);
            timer.classList.toggle('is-critical', remaining > 0 && remaining <= 60);
            timer.classList.toggle('is-expired', remaining <= 0);

            if (timer.dataset.countdownMode === 'deadline') {
                if (remaining <= 0) {
                    value.textContent = 'Deadline lewat';
                } else {
                    const days = Math.floor(remaining / 86400);
                    const hours = Math.floor((remaining % 86400) / 3600);
                    const minutes = Math.floor((remaining % 3600) / 60);
                    value.textContent = days > 0 ? `${days} hari ${hours} jam` : (hours > 0 ? `${hours} jam ${minutes} menit` : `${minutes} menit`);
                }
                if (status) status.textContent = remaining <= 0 ? 'Deadline telah berakhir' : (remaining <= 86400 ? 'Deadline kurang dari 1 hari lagi' : 'Waktu menuju deadline');
            } else {
                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                value.textContent = `${pad(minutes)}:${pad(seconds)}`;
                if (status) status.textContent = remaining <= 0 ? 'Waktu habis' : (remaining <= 60 ? 'Kritis · kurang dari 1 menit' : (remaining <= 300 ? 'Peringatan · 5 menit terakhir' : 'Waktu tersisa'));
            }

            if (remaining <= 0 && !expiredSent) {
                expiredSent = true;
                timer.dispatchEvent(new CustomEvent('countdown:expired', { bubbles: true }));
            }
        };

        update();
        window.setInterval(update, 1000);
    });
})();
