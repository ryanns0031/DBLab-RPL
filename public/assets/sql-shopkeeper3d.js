(() => {
    'use strict';

    const root = document.getElementById('shopkeeper-game');
    const canvas = document.getElementById('shop-world');
    if (!root || !canvas) return;

    const ctx = canvas.getContext('2d', { alpha: false });
    const $ = (selector) => root.querySelector(selector);
    const state = JSON.parse(root.dataset.initialState || '{}');
    const products = JSON.parse(root.dataset.products || '{}');
    const missions = JSON.parse(root.dataset.missions || '{}');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const player = { x: 0, z: 14.5, facing: 0, moving: false, step: 0 };
    const keys = new Set();
    const joystick = { active: false, pointer: null, x: 0, y: 0 };
    const selected = { itemId: 1, interaction: null, running: false, toastTimer: 0, hint: 0 };
    const productsList = Object.entries(products).map(([id, item]) => ({ id: Number(id), ...item }));
    const objects = [
        { id: 'rack-left', type: 'rack', label: 'Rak minuman', x: -6, z: 6, w: 2.6, d: 1.35, h: 2.35 },
        { id: 'rack-mid-left', type: 'rack', label: 'Rak makanan', x: -2.4, z: 6, w: 2.6, d: 1.35, h: 2.35 },
        { id: 'rack-mid', type: 'rack', label: 'Rak produk', x: 1.2, z: 6, w: 2.6, d: 1.35, h: 2.35 },
        { id: 'fridge', type: 'fridge', label: 'Lemari pendingin', x: 5.8, z: 5.5, w: 2.2, d: 1.6, h: 2.65 },
        { id: 'counter', type: 'cashier', label: 'Meja kasir', x: 4.8, z: 12.1, w: 3.3, d: 1.5, h: 1.25 },
        { id: 'terminal', type: 'terminal', label: 'Terminal SQL', x: -7.5, z: 13.1, w: 1.3, d: 1.1, h: 1.5 },
        { id: 'stockroom', type: 'stockroom', label: 'Gudang stok', x: 8.5, z: 3.2, w: 2.7, d: 2, h: 2.6 },
    ];
    const customers = [
        { name: 'Naya', x: 3.3, z: 9.2, color: '#ff8f70' },
        { name: 'Bimo', x: 5.6, z: 8.1, color: '#61d8c7' },
        { name: 'Salsa', x: 7.7, z: 9.4, color: '#f5c35b' },
    ];
    let width = 0;
    let height = 0;
    let lastFrame = 0;
    let simulationSeconds = 8 * 60 * 60;

    function resize() {
        const rect = canvas.getBoundingClientRect();
        const ratio = Math.min(window.devicePixelRatio || 1, 1.75);
        width = Math.max(1, rect.width);
        height = Math.max(1, rect.height);
        canvas.width = Math.round(width * ratio);
        canvas.height = Math.round(height * ratio);
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    }

    function project(x, y, z) {
        const focal = Math.min(width * 0.88, height * 1.48);
        const cameraZ = player.z + 6.3;
        const depth = Math.max(2.2, cameraZ - z);
        return {
            x: width * 0.5 + ((x - player.x) * focal) / depth,
            y: height * 0.11 + ((3.15 - y) * focal) / depth,
            scale: focal / depth,
        };
    }

    function polygon(points, fill, stroke = null, lineWidth = 1) {
        ctx.beginPath();
        points.forEach((point, index) => index ? ctx.lineTo(point.x, point.y) : ctx.moveTo(point.x, point.y));
        ctx.closePath();
        ctx.fillStyle = fill;
        ctx.fill();
        if (stroke) {
            ctx.strokeStyle = stroke;
            ctx.lineWidth = lineWidth;
            ctx.stroke();
        }
    }

    function box(x, z, w, d, h, color, options = {}) {
        const y = options.y || 0;
        const pts = [
            project(x - w / 2, y, z - d / 2), project(x + w / 2, y, z - d / 2),
            project(x + w / 2, y, z + d / 2), project(x - w / 2, y, z + d / 2),
            project(x - w / 2, y + h, z - d / 2), project(x + w / 2, y + h, z - d / 2),
            project(x + w / 2, y + h, z + d / 2), project(x - w / 2, y + h, z + d / 2),
        ];
        polygon([pts[0], pts[1], pts[5], pts[4]], shade(color, 1.16), options.stroke || '#ffffff16');
        polygon([pts[1], pts[2], pts[6], pts[5]], shade(color, 0.74), options.stroke || '#ffffff12');
        polygon([pts[3], pts[2], pts[6], pts[7]], shade(color, 0.88), options.stroke || '#ffffff12');
        polygon([pts[4], pts[5], pts[6], pts[7]], shade(color, 1.32), options.stroke || '#ffffff24');
        return pts;
    }

    function shade(hex, factor) {
        const color = hex.replace('#', '');
        const parts = color.match(/.{2}/g).map((part) => Math.max(0, Math.min(255, Math.round(parseInt(part, 16) * factor))));
        return `rgb(${parts.join(',')})`;
    }

    function textAt(text, x, y, z, color = '#d7e5ff', size = 10) {
        const point = project(x, y, z);
        ctx.save();
        ctx.font = `700 ${Math.max(8, Math.min(size, point.scale * 0.2))}px Inter, system-ui, sans-serif`;
        ctx.textAlign = 'center';
        ctx.fillStyle = color;
        ctx.shadowColor = '#071020';
        ctx.shadowBlur = 5;
        ctx.fillText(text, point.x, point.y);
        ctx.restore();
    }

    function drawFloor() {
        const corners = [[-10, 0], [10, 0], [10, 19], [-10, 19]].map(([x, z]) => project(x, 0, z));
        const gradient = ctx.createLinearGradient(0, height * 0.2, 0, height);
        gradient.addColorStop(0, '#46516e');
        gradient.addColorStop(0.55, '#303b58');
        gradient.addColorStop(1, '#222b43');
        polygon(corners, gradient, '#7787a455', 1);

        for (let z = 1; z < 19; z += 1) {
            const a = project(-10, 0.012, z);
            const b = project(10, 0.012, z);
            ctx.strokeStyle = z % 3 === 0 ? '#aab9df1b' : '#aab9df0c';
            ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
        }
        for (let x = -9; x <= 9; x += 3) {
            const a = project(x, 0.012, 0);
            const b = project(x, 0.012, 19);
            ctx.strokeStyle = '#aab9df10';
            ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
        }
        // Rear wall, side walls, and a bright shop sign establish a room instead of a flat backdrop.
        box(0, -0.3, 20, 0.7, 4.3, '#28334f');
        box(-10.15, 9.5, 0.55, 19, 4.3, '#293552');
        box(10.15, 9.5, 0.55, 19, 4.3, '#293552');
        box(0, 0.1, 5.2, 0.2, 0.75, '#124f79', { y: 2.8, stroke: '#4ecfff75' });
        textAt('RPL MART', 0, 3.02, 0.18, '#a5efff', 16);
        box(-8.6, 3.7, 1.05, 0.6, 0.08, '#6d5bd0', { y: 0.04 });
        textAt('GUDANG', -8.6, 0.25, 3.7, '#c6baff', 8);
    }

    function drawShelf(item, index) {
        const base = project(item.x, 0, item.z + 0.72);
        box(item.x, item.z, item.w, item.d, item.h, index === 3 ? '#294f69' : '#43516e');
        for (let level = 1; level <= 3; level += 1) {
            const y = level * 0.52;
            const left = project(item.x - item.w * 0.46, y, item.z + item.d * 0.51);
            const right = project(item.x + item.w * 0.46, y, item.z + item.d * 0.51);
            ctx.strokeStyle = '#d4def45c'; ctx.lineWidth = Math.max(1, base.scale * 0.045);
            ctx.beginPath(); ctx.moveTo(left.x, left.y); ctx.lineTo(right.x, right.y); ctx.stroke();
            for (let p = 0; p < 5; p += 1) {
                const px = item.x - item.w * 0.37 + p * item.w * 0.18;
                const product = productsList[(index + p + level) % productsList.length];
                const color = ['#57c6ef', '#f4ae54', '#d97eab', '#85d99d', '#b29cff'][product.id - 1];
                box(px, item.z + item.d * 0.54, 0.24, 0.2, 0.34 + (p % 2) * 0.08, color, { y });
            }
        }
        textAt(index === 3 ? 'MINUMAN DINGIN' : 'PRODUK', item.x, item.h + 0.2, item.z, '#d4e8ff', 9);
    }

    function drawFridge(item) {
        box(item.x, item.z, item.w, item.d, item.h, '#247293');
        const pane = [project(item.x - 0.78, 0.26, item.z + 0.84), project(item.x + 0.78, 0.26, item.z + 0.84), project(item.x + 0.78, 2.36, item.z + 0.84), project(item.x - 0.78, 2.36, item.z + 0.84)];
        polygon(pane, '#84def044', '#b4f3ff75', 1.4);
        for (let i = 1; i < 4; i += 1) {
            const a = project(item.x - 0.7, i * 0.48, item.z + 0.86);
            const b = project(item.x + 0.7, i * 0.48, item.z + 0.86);
            ctx.strokeStyle = '#d5fbff55'; ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
        }
        textAt('FRESH', item.x, 2.8, item.z, '#b7f5ff', 9);
    }

    function drawCounter(item) {
        box(item.x, item.z, item.w, item.d, item.h, '#8b5b45');
        box(item.x, item.z - 0.05, item.w + 0.16, item.d + 0.12, 0.14, '#e2aa69', { y: item.h });
        box(item.x + 0.22, item.z - 0.22, 0.72, 0.48, 0.4, '#27344e', { y: item.h + 0.14, stroke: '#71defc55' });
        textAt('KASIR', item.x, item.h + 0.7, item.z, '#ffe3b8', 10);
    }

    function drawCustomer(customer, index) {
        const base = project(customer.x, 0, customer.z);
        const size = Math.max(0.65, Math.min(1.25, base.scale / 45));
        ctx.save();
        ctx.fillStyle = '#09101e66';
        ctx.beginPath(); ctx.ellipse(base.x, base.y + 2, size * 13, size * 4, 0, 0, Math.PI * 2); ctx.fill();
        const bob = Math.sin(performance.now() / 450 + index) * 1.5;
        ctx.fillStyle = customer.color;
        ctx.beginPath(); ctx.roundRect(base.x - size * 7, base.y - size * 26 + bob, size * 14, size * 22, size * 5); ctx.fill();
        ctx.fillStyle = '#f3caa9'; ctx.beginPath(); ctx.arc(base.x, base.y - size * 32 + bob, size * 6.2, 0, Math.PI * 2); ctx.fill();
        ctx.fillStyle = '#263049'; ctx.beginPath(); ctx.arc(base.x - size * 1.8, base.y - size * 33 + bob, size * 0.75, 0, Math.PI * 2); ctx.fill();
        ctx.restore();
    }

    function drawPlayer() {
        const base = project(player.x, 0, player.z);
        const scale = Math.max(0.8, Math.min(1.5, base.scale / 42));
        const sway = player.moving ? Math.sin(player.step * 11) * 3 : Math.sin(performance.now() / 700) * 0.7;
        ctx.save();
        ctx.fillStyle = '#070d1a88'; ctx.beginPath(); ctx.ellipse(base.x, base.y + 2, 16 * scale, 5 * scale, 0, 0, Math.PI * 2); ctx.fill();
        // Procedural low-poly avatar: idle breathing and alternating walk legs/arms.
        ctx.strokeStyle = '#14213a'; ctx.lineWidth = 5 * scale; ctx.lineCap = 'round';
        for (const side of [-1, 1]) {
            ctx.beginPath(); ctx.moveTo(base.x + side * 4 * scale, base.y - 8 * scale);
            ctx.lineTo(base.x + side * 5 * scale + sway * side, base.y - (2 + Math.max(0, sway * side)) * scale); ctx.stroke();
        }
        ctx.fillStyle = '#5269e8';
        ctx.beginPath(); ctx.moveTo(base.x - 9 * scale, base.y - 28 * scale); ctx.lineTo(base.x + 8 * scale, base.y - 28 * scale); ctx.lineTo(base.x + 7 * scale, base.y - 8 * scale); ctx.lineTo(base.x - 7 * scale, base.y - 8 * scale); ctx.closePath(); ctx.fill();
        ctx.strokeStyle = '#f0c8a7'; ctx.lineWidth = 4 * scale;
        for (const side of [-1, 1]) {
            ctx.beginPath(); ctx.moveTo(base.x + side * 8 * scale, base.y - 25 * scale); ctx.lineTo(base.x + side * (11 + sway * 0.4) * scale, base.y - 13 * scale); ctx.stroke();
        }
        ctx.fillStyle = '#f2c7a6'; ctx.beginPath(); ctx.arc(base.x, base.y - 34 * scale, 7 * scale, 0, Math.PI * 2); ctx.fill();
        ctx.fillStyle = '#382d4b'; ctx.beginPath(); ctx.arc(base.x, base.y - 37 * scale, 7 * scale, Math.PI, Math.PI * 2); ctx.fill();
        ctx.fillStyle = '#dceaff'; ctx.font = `700 ${Math.max(8, 10 * scale)}px system-ui`; ctx.textAlign = 'center'; ctx.fillText('KAMU', base.x, base.y - 47 * scale);
        ctx.restore();
    }

    function drawWorld() {
        const gradient = ctx.createLinearGradient(0, 0, 0, height);
        gradient.addColorStop(0, '#121a2c'); gradient.addColorStop(0.46, '#202943'); gradient.addColorStop(1, '#141c2d');
        ctx.fillStyle = gradient; ctx.fillRect(0, 0, width, height);
        drawFloor();
        const queue = state.highestLevel >= 2 && state.waiting ? [{ ...customers[0], name: state.waiting.name }] : [];
        const renderables = [...objects.map((object) => ({ type: 'object', object, depth: object.z })), ...queue.map((customer, i) => ({ type: 'customer', customer, depth: customer.z, index: i }))];
        renderables.sort((a, b) => a.depth - b.depth);
        for (const item of renderables) {
            if (item.type === 'customer') { drawCustomer(item.customer, item.index); continue; }
            const object = item.object;
            if (object.type === 'rack') drawShelf(object, objects.filter((entry) => entry.type === 'rack').indexOf(object));
            else if (object.type === 'fridge') drawFridge(object);
            else if (object.type === 'cashier') drawCounter(object);
            else if (object.type === 'terminal') {
                box(object.x, object.z, object.w, object.d, object.h, '#33466d');
                box(object.x, object.z + 0.12, 1.05, 0.08, 0.46, '#071826', { y: 1.32, stroke: '#47d9ff99' });
                textAt('SQL', object.x, 1.9, object.z, '#75e9ff', 10);
            } else {
                box(object.x, object.z, object.w, object.d, object.h, '#574d76');
                textAt('STOK', object.x, 2.85, object.z, '#e1d5ff', 11);
            }
        }
        drawPlayer();
        drawMiniMap();
    }

    function drawMiniMap() {
        const mapButton = $('[data-toggle-map]');
        if (!mapButton?.classList.contains('is-open')) return;
        const mapW = Math.min(150, width * 0.28); const mapH = mapW * 0.62; const x = width - mapW - 18; const y = 54;
        ctx.save(); ctx.fillStyle = '#091222db'; ctx.strokeStyle = '#73dfff66'; ctx.lineWidth = 1; ctx.beginPath(); ctx.roundRect(x, y, mapW, mapH, 10); ctx.fill(); ctx.stroke();
        for (const object of objects) {
            const mx = x + mapW * ((object.x + 10) / 20); const my = y + mapH * ((object.z + 1) / 21);
            ctx.fillStyle = object.type === 'cashier' ? '#ffc265' : object.type === 'terminal' ? '#6ce8ff' : '#8798c1';
            ctx.fillRect(mx - 2.5, my - 2.5, 5, 5);
        }
        const px = x + mapW * ((player.x + 10) / 20); const py = y + mapH * ((player.z + 1) / 21);
        ctx.fillStyle = '#9c86ff'; ctx.beginPath(); ctx.arc(px, py, 4, 0, Math.PI * 2); ctx.fill(); ctx.restore();
    }

    function nearestInteraction() {
        let nearest = null; let distance = Infinity;
        for (const object of objects) {
            const d = Math.hypot(player.x - object.x, player.z - object.z);
            if (d < distance) { nearest = object; distance = d; }
        }
        if (distance > 3.2) return null;
        return { ...nearest, distance };
    }

    function frame(time) {
        const delta = Math.min(0.04, Math.max(0, (time - lastFrame) / 1000)); lastFrame = time;
        const inputX = (keys.has('d') || keys.has('arrowright') ? 1 : 0) - (keys.has('a') || keys.has('arrowleft') ? 1 : 0) + joystick.x;
        const inputY = (keys.has('s') || keys.has('arrowdown') ? 1 : 0) - (keys.has('w') || keys.has('arrowup') ? 1 : 0) + joystick.y;
        const length = Math.hypot(inputX, inputY);
        simulationSeconds += delta * 30;
        const clockMinutes = Math.floor(simulationSeconds / 60);
        $('#shop-clock').textContent = `${String(Math.floor(clockMinutes / 60) % 24).padStart(2, '0')}:${String(clockMinutes % 60).padStart(2, '0')}`;
        player.moving = length > 0.08;
        if (player.moving) {
            const run = keys.has('shift') ? 1.55 : 1;
            player.x = Math.max(-9.1, Math.min(9.1, player.x + (inputX / Math.max(1, length)) * 4.2 * run * delta));
            player.z = Math.max(1.1, Math.min(17.5, player.z + (inputY / Math.max(1, length)) * 4.2 * run * delta));
            player.facing = Math.atan2(inputX, -inputY); player.step += delta;
        }
        drawWorld();
        updateInteraction(nearestInteraction());
        requestAnimationFrame(frame);
    }

    function updateInteraction(interaction) {
        selected.interaction = interaction;
        const text = $('#world-interaction-text'); const key = $('#world-interaction-key'); const button = $('#world-interaction-button');
        if (!interaction) { text.textContent = 'Dekati rak, kasir, atau terminal'; key.textContent = 'E'; button.hidden = true; return; }
        text.textContent = `${interaction.label} · tekan E`;
        key.textContent = 'E';
        button.hidden = false;
        button.textContent = interaction.type === 'terminal' ? 'Buka terminal' : interaction.type === 'cashier' ? 'Layani pelanggan' : 'Pilih rak';
    }

    function formatMoney(amount) { return `Rp ${new Intl.NumberFormat('id-ID').format(amount || 0)}`; }

    function renderState(next) {
        Object.assign(state, next);
        $('#shop-balance').textContent = formatMoney(state.balance);
        $('#shop-reputation').textContent = state.reputation;
        $('#shop-xp').textContent = state.xp;
        $('#customer-progress').style.width = `${Math.min(100, state.servedCount * 20)}%`;
        $('#customer-mission-text').textContent = `${state.servedCount}/5 pelanggan dilayani`;
        $('#queue-count').textContent = `${state.queueCount} menunggu`;
        const waiting = $('#waiting-customer');
        if (state.waiting) {
            waiting.innerHTML = `<span class="customer-avatar"></span><span><b id="customer-name"></b><small id="customer-order"></small></span>`;
            waiting.querySelector('.customer-avatar').textContent = state.waiting.name.slice(0, 1);
            waiting.querySelector('#customer-name').textContent = state.waiting.name;
            const item = products[state.waiting.item_id];
            waiting.querySelector('#customer-order').textContent = `Mencari ${item?.nama_barang || 'produk'} · ${state.waiting.qty} unit`;
        } else waiting.innerHTML = '<span class="customer-empty">Antrean pelanggan hari ini selesai. Kerja bagus!</span>';
        const serve = $('[data-serve-customer]'); serve.disabled = !state.waiting;
        for (const [id, qty] of Object.entries(state.inventory || {})) {
            const stock = $(`[data-stock-for="${id}"]`); if (stock) stock.textContent = qty;
            const item = products[id]; const row = $(`[data-item-id="${id}"]`);
            if (row && item) row.classList.toggle('is-low', qty < item.stok_minimum);
        }
        const progress = $('#shopkeeper-game');
        progress.dataset.initialState = JSON.stringify(state);
        if (next.highestLevel) updateMission(next.highestLevel, next.completedLevels || []);
    }

    function updateMission(level, completed) {
        const mission = missions[level];
        if (!mission) return;
        const previous = Number($('#terminal-level').textContent.match(/\d+/)?.[0] || 1);
        $('#terminal-level').textContent = `LEVEL ${level} / 5`;
        $('#terminal-concept').textContent = mission.concept;
        $('#mission-title').textContent = mission.title;
        $('#mission-brief').textContent = mission.brief;
        $('.mission-card .side-card-heading small').textContent = `LEVEL ${level} · ${level < 4 ? 'PEMULA' : 'MENENGAH'}`;
        $('.mission-card .side-card-heading h2').textContent = `Misi Toko · ${mission.title}`;
        $('.mission-action b').textContent = mission.action;
        $('.mission-badge').textContent = `${level}/5`;
        for (const link of root.querySelectorAll('[data-mission-level]')) {
            const n = Number(link.dataset.missionLevel);
            link.classList.toggle('is-complete', completed.includes(n) || n < level);
            link.classList.toggle('is-current', n === level);
            link.classList.toggle('is-locked', n > level);
            link.textContent = completed.includes(n) || n < level ? '✓' : n === level ? String(n) : '⌑';
        }
        if (level !== previous) {
            $('#shop-query').value = mission.starter;
            $('#shop-hint').dataset.hint = '0';
        }
    }

    function toast(message, kind = '') {
        const element = $('#shop-toast');
        element.textContent = message; element.className = `world-toast is-visible ${kind}`;
        clearTimeout(selected.toastTimer);
        selected.toastTimer = setTimeout(() => element.classList.remove('is-visible'), 3800);
    }

    async function send(url, payload) {
        const response = await fetch(url, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || `Permintaan gagal (${response.status}).`);
        return data;
    }

    function busy(flag) {
        selected.running = flag;
        $('#shop-run').disabled = flag; $('#shop-submit').disabled = flag;
        $('[data-serve-customer]').disabled = flag || !state.waiting;
        $('#shop-run').classList.toggle('is-loading', flag);
    }

    function showMessage(message, success = false) {
        const element = $('#terminal-message'); element.textContent = message;
        element.className = `terminal-message is-visible ${success ? 'is-success' : 'is-error'}`;
        toast(message, success ? 'is-success' : '');
    }

    async function runQuery(submit) {
        if (selected.running) return;
        busy(true);
        $('#terminal-message').classList.remove('is-visible');
        try {
            const start = performance.now();
            const result = await send(root.dataset.queryUrl, { query: $('#shop-query').value, level: state.highestLevel, submit });
            const elapsed = result.elapsed ?? Math.round(performance.now() - start);
            renderResults(result.columns || [], result.rows || [], elapsed);
            if (result.progress) renderState(result.progress);
            showMessage(result.message, result.missionComplete || (submit && result.success && state.highestLevel > Number($('#terminal-level').textContent.match(/\d+/)?.[0] || 0)));
            if (result.success && (state.highestLevel === 1 || state.highestLevel === 5)) {
                selected.interaction = nearestInteraction();
                updateInteraction(selected.interaction);
            }
        } catch (error) { showMessage(error.message, false); }
        finally { busy(false); }
    }

    function renderResults(columns, rows, elapsed) {
        const results = $('#terminal-results'); results.hidden = false;
        $('#result-count').textContent = `${rows.length} baris · ${elapsed} ms`;
        const head = $('#result-head'); const body = $('#result-body');
        head.replaceChildren(); body.replaceChildren();
        const headerRow = document.createElement('tr');
        columns.forEach((column) => { const th = document.createElement('th'); th.textContent = column; headerRow.append(th); }); head.append(headerRow);
        rows.forEach((row) => {
            const tr = document.createElement('tr');
            columns.forEach((column) => { const td = document.createElement('td'); td.textContent = row[column] === null ? 'NULL' : String(row[column] ?? ''); tr.append(td); });
            body.append(tr);
        });
        if (!rows.length) { const tr = document.createElement('tr'); const td = document.createElement('td'); td.colSpan = Math.max(1, columns.length); td.textContent = 'Query tidak menemukan baris.'; tr.append(td); body.append(tr); }
    }

    async function gameAction(action, itemId = null) {
        if (selected.running) return;
        busy(true);
        try {
            const result = await send(root.dataset.actionUrl, { action, item_id: itemId });
            if (result.progress) renderState(result.progress);
            showMessage(result.message, result.success || result.missionComplete);
            if (result.success || result.missionComplete) toast(result.message, 'is-success');
        } catch (error) { toast(error.message); showMessage(error.message, false); }
        finally { busy(false); }
    }

    function interact() {
        const object = selected.interaction || nearestInteraction();
        if (!object) { toast('Dekati rak, kasir, atau terminal untuk berinteraksi.'); return; }
        if (object.type === 'terminal') { openTerminal(); return; }
        if (object.type === 'cashier') { gameAction('serve_customer'); return; }
        const item = productsList.find((product) => product.id === selected.itemId) || productsList[0];
        if ((state.highestLevel === 1 || state.highestLevel === 5) && state.queryPassed?.[String(state.highestLevel)]) {
            gameAction('restock', item.id);
        } else {
            toast(`Rak ${item.nama_barang}: ${state.inventory[item.id]} unit. Jalankan dan submit query misi dahulu.`);
        }
    }

    function openTerminal() {
        const terminal = $('#shop-terminal');
        terminal.classList.add('is-open');
        $('[data-open-terminal]').setAttribute('aria-expanded', 'true');
        if (window.matchMedia('(max-width: 900px)').matches) document.body.classList.add('shop-terminal-open');
        terminal.scrollIntoView({ behavior: 'smooth', block: window.matchMedia('(max-width: 900px)').matches ? 'start' : 'nearest' });
        setTimeout(() => $('#shop-query').focus({ preventScroll: true }), 250);
    }

    function closeTerminal() {
        $('#shop-terminal').classList.remove('is-open'); document.body.classList.remove('shop-terminal-open');
        $('[data-open-terminal]').setAttribute('aria-expanded', 'false');
    }

    function setJoystick(event) {
        const ring = $('#shop-joystick').querySelector('.joystick-ring'); const rect = ring.getBoundingClientRect();
        const radius = rect.width * 0.34; const dx = event.clientX - (rect.left + rect.width / 2); const dy = event.clientY - (rect.top + rect.height / 2);
        const length = Math.hypot(dx, dy); const scale = length > radius ? radius / length : 1;
        joystick.x = (dx * scale) / radius; joystick.y = (dy * scale) / radius;
        const thumb = ring.querySelector('.joystick-thumb'); thumb.style.transform = `translate(${joystick.x * radius}px, ${joystick.y * radius}px)`;
    }

    function releaseJoystick() {
        joystick.active = false; joystick.pointer = null; joystick.x = 0; joystick.y = 0;
        $('#shop-joystick').querySelector('.joystick-thumb').style.transform = 'translate(0, 0)';
    }

    $('#shop-run').addEventListener('click', () => runQuery(false));
    $('#shop-submit').addEventListener('click', () => runQuery(true));
    $('[data-open-terminal]').addEventListener('click', openTerminal);
    $('[data-close-terminal]').addEventListener('click', closeTerminal);
    $('[data-serve-customer]').addEventListener('click', () => gameAction('serve_customer'));
    $('#world-interaction-button').addEventListener('click', interact);
    $('[data-touch-interact]').addEventListener('click', interact);
    $('#shop-hint').addEventListener('click', (event) => {
        const mission = missions[state.highestLevel]; const hints = (mission.hint || '').split(/(?<=\.)\s+/).filter(Boolean);
        selected.hint = Math.min(Number(event.currentTarget.dataset.hint || 0), Math.max(0, hints.length - 1));
        toast(hints[selected.hint] || mission.hint); event.currentTarget.dataset.hint = String(Math.min(selected.hint + 1, Math.max(0, hints.length - 1)));
    });
    $('[data-toggle-map]').addEventListener('click', (event) => event.currentTarget.classList.toggle('is-open'));
    $('[data-fullscreen]').addEventListener('click', () => root.requestFullscreen?.());
    $('#schema-toggle').addEventListener('click', (event) => {
        const list = $('#schema-list'); list.hidden = !list.hidden; event.currentTarget.setAttribute('aria-expanded', String(!list.hidden)); event.currentTarget.textContent = list.hidden ? 'Lihat tabel⌄' : 'Tutup tabel⌃';
    });
    $('#shop-query').addEventListener('input', (event) => {
        $('#terminal-lines').textContent = Array.from({ length: event.target.value.split('\n').length }, (_, index) => index + 1).join('\n');
    });
    $('#shop-query').addEventListener('keydown', (event) => {
        if (event.key === 'Tab') { event.preventDefault(); const start = event.target.selectionStart; const end = event.target.selectionEnd; event.target.setRangeText('    ', start, end, 'end'); event.target.dispatchEvent(new Event('input')); }
        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') { event.preventDefault(); runQuery(false); }
    });
    root.querySelectorAll('[data-item-id]').forEach((button) => button.addEventListener('click', () => {
        selected.itemId = Number(button.dataset.itemId);
        root.querySelectorAll('[data-item-id]').forEach((row) => row.classList.toggle('is-selected', row === button));
        const item = products[selected.itemId]; toast(`${item.nama_barang} dipilih. Dekati rak lalu tekan E untuk berinteraksi.`);
    }));
    $('#shop-joystick').addEventListener('pointerdown', (event) => {
        event.preventDefault(); joystick.active = true; joystick.pointer = event.pointerId; event.currentTarget.setPointerCapture(event.pointerId); setJoystick(event);
    });
    $('#shop-joystick').addEventListener('pointermove', (event) => { if (joystick.active && joystick.pointer === event.pointerId) { event.preventDefault(); setJoystick(event); } });
    $('#shop-joystick').addEventListener('pointerup', (event) => { if (joystick.pointer === event.pointerId) releaseJoystick(); });
    $('#shop-joystick').addEventListener('pointercancel', releaseJoystick);
    const runButton = $('[data-touch-run]');
    runButton.addEventListener('pointerdown', (event) => { event.preventDefault(); keys.add('shift'); runButton.setPointerCapture(event.pointerId); });
    ['pointerup', 'pointercancel', 'lostpointercapture'].forEach((type) => runButton.addEventListener(type, () => keys.delete('shift')));
    document.addEventListener('keydown', (event) => {
        if (!root.isConnected) return;
        const target = event.target;
        const typing = target instanceof HTMLElement && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));
        if (event.key === 'Escape') { closeTerminal(); return; }
        if (typing) return;
        const key = event.key.toLowerCase();
        if (['w', 'a', 's', 'd', 'arrowup', 'arrowdown', 'arrowleft', 'arrowright', 'shift'].includes(key)) { event.preventDefault(); keys.add(key); }
        if (key === 'e' && !event.repeat) { event.preventDefault(); interact(); }
    });
    document.addEventListener('keyup', (event) => keys.delete(event.key.toLowerCase()));
    window.addEventListener('blur', () => { keys.clear(); releaseJoystick(); });
    window.addEventListener('resize', resize);
    root.querySelectorAll('[data-mission-level]').forEach((link) => link.addEventListener('click', (event) => event.preventDefault()));
    resize();
    updateMission(state.highestLevel, state.completedLevels || []);
    requestAnimationFrame(frame);
})();
