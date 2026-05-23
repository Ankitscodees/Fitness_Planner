// ============================================================
// FitPro — main.js
// ============================================================

(function () {
    'use strict';

    // ── Mobile Nav Toggle ──
    const navToggle = document.getElementById('navToggle');
    const navLinks  = document.getElementById('navLinks');

    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => {
            navLinks.classList.toggle('open');
            const open = navLinks.classList.contains('open');
            navToggle.setAttribute('aria-expanded', open);
        });

        // Close nav when link clicked on mobile
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => navLinks.classList.remove('open'));
        });
    }

    // ── Auto-dismiss Flash Messages ──
    const flashMsg = document.getElementById('flashMsg');
    if (flashMsg) {
        setTimeout(() => {
            flashMsg.style.opacity = '0';
            flashMsg.style.transform = 'translateX(-50%) translateY(-10px)';
            flashMsg.style.transition = '0.4s ease';
            setTimeout(() => flashMsg.remove(), 400);
        }, 4500);
    }

    // ── Filter Buttons (workouts/meals page) ──
    const filterBtns = document.querySelectorAll('[data-filter]');
    if (filterBtns.length) {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.dataset.filter;
                const group  = btn.dataset.group || 'default';

                // Update active state within same group
                document.querySelectorAll(`[data-filter][data-group="${group}"]`).forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Show/hide cards
                const cards = document.querySelectorAll('[data-tags]');
                cards.forEach(card => {
                    if (filter === 'all') {
                        card.closest('.card-wrapper')?.classList.remove('hidden');
                        card.classList.remove('hidden');
                    } else {
                        const tags = card.dataset.tags || '';
                        const show = tags.includes(filter);
                        card.closest('.card-wrapper')
                            ? card.closest('.card-wrapper').classList.toggle('hidden', !show)
                            : card.classList.toggle('hidden', !show);
                    }
                });

                updateEmptyState();
            });
        });
    }

    function updateEmptyState() {
        const grid  = document.querySelector('.cards-grid');
        const empty = document.getElementById('emptyState');
        if (!grid || !empty) return;
        const visible = grid.querySelectorAll('.card:not(.hidden), .card-wrapper:not(.hidden)').length;
        empty.classList.toggle('hidden', visible > 0);
    }

    // ── Scroll animation (Intersection Observer) ──
    const animateEls = document.querySelectorAll('.card, .hero-card, .stat-card, .result-item');
    if ('IntersectionObserver' in window && animateEls.length) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    entry.target.style.animationDelay = `${i * 0.05}s`;
                    entry.target.classList.add('animate-in');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        animateEls.forEach(el => io.observe(el));
    }

    // ── BMI Calculator ──
    const calcForm = document.getElementById('calcForm');
    if (calcForm) {
        calcForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const weight   = parseFloat(document.getElementById('c_weight')?.value);
            const height   = parseFloat(document.getElementById('c_height')?.value);
            const age      = parseInt(document.getElementById('c_age')?.value);
            const gender   = document.getElementById('c_gender')?.value;
            const activity = document.getElementById('c_activity')?.value;

            if (!weight || !height || !age || !gender || !activity) {
                showCalcError('Please fill in all fields.');
                return;
            }
            if (weight < 20 || weight > 500) { showCalcError('Please enter a valid weight (20–500 kg).'); return; }
            if (height < 100 || height > 250) { showCalcError('Please enter a valid height (100–250 cm).'); return; }
            if (age < 10 || age > 120) { showCalcError('Please enter a valid age.'); return; }

            // BMI
            const hM  = height / 100;
            const bmi = +(weight / (hM * hM)).toFixed(1);

            // BMR (Mifflin-St Jeor)
            let bmr = (10 * weight) + (6.25 * height) - (5 * age);
            bmr = gender === 'female' ? bmr - 161 : bmr + 5;
            bmr = Math.round(bmr);

            // TDEE
            const mult = { sedentary: 1.2, lightly_active: 1.375, moderately_active: 1.55, very_active: 1.725, extra_active: 1.9 };
            const tdee = Math.round(bmr * (mult[activity] || 1.55));

            // BMI Category
            let cat, catColor;
            if (bmi < 18.5)      { cat = 'Underweight'; catColor = '#60a5fa'; }
            else if (bmi < 25)   { cat = 'Normal Weight'; catColor = '#4ade80'; }
            else if (bmi < 30)   { cat = 'Overweight'; catColor = '#fbbf24'; }
            else                 { cat = 'Obese'; catColor = '#f87171'; }

            // Populate results
            setResult('res_bmi',  bmi, catColor);
            setResult('res_bmr',  bmr);
            setResult('res_tdee', tdee);
            setResult('res_fat',  Math.round(tdee - 500));
            setResult('res_gain', Math.round(tdee + 400));

            document.getElementById('bmi_cat').textContent  = cat;
            document.getElementById('bmi_cat').style.color  = catColor;

            // BMI bar indicator
            const indicator = document.getElementById('bmiIndicator');
            if (indicator) {
                const pct = Math.min(Math.max((bmi - 10) / 35 * 100, 2), 98);
                indicator.style.left = pct + '%';
            }

            // Show results section
            const resultsEl = document.getElementById('calcResults');
            if (resultsEl) {
                resultsEl.classList.remove('hidden');
                resultsEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            // Show AI form if user is not logged in
            hiddenFields('c_bmi_val', bmi);
            hiddenFields('c_bmr_val', bmr);
            hiddenFields('c_tdee_val', tdee);

            clearCalcError();
        });
    }

    function setResult(id, value, color) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value.toLocaleString();
            if (color) el.style.color = color;
        }
    }
    function hiddenFields(id, val) {
        const el = document.getElementById(id);
        if (el) el.value = val;
    }
    function showCalcError(msg) {
        const el = document.getElementById('calcError');
        if (el) { el.textContent = msg; el.classList.remove('hidden'); }
    }
    function clearCalcError() {
        const el = document.getElementById('calcError');
        if (el) el.classList.add('hidden');
    }

    // ── Password Strength ──
    const passInput = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    if (passInput && strengthBar) {
        passInput.addEventListener('input', function () {
            const val = this.value;
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const colors = ['', '#f87171', '#fbbf24', '#60a5fa', '#4ade80'];
            const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const widths = ['0%', '25%', '50%', '75%', '100%'];

            const fill = strengthBar.querySelector('.strength-fill');
            const label = document.getElementById('strengthLabel');
            if (fill) { fill.style.width = widths[score]; fill.style.background = colors[score]; }
            if (label) { label.textContent = score > 0 ? labels[score] : ''; label.style.color = colors[score]; }
        });
    }

    // ── Confirm Delete ──
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // ── Admin Search Filter ──
    const adminSearch = document.getElementById('adminSearch');
    if (adminSearch) {
        adminSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.admin-table tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    // ── Nutrition Progress Bars (animate on load) ──
    document.querySelectorAll('.nutrition-fill').forEach(el => {
        const target = el.dataset.width || '0%';
        el.style.width = '0%';
        setTimeout(() => { el.style.width = target; }, 200);
    });

    // ── Smooth reveal for detail page content ──
    document.querySelectorAll('.instruction-item').forEach((el, i) => {
        el.style.opacity = '0';
        el.style.transform = 'translateX(-10px)';
        setTimeout(() => {
            el.style.transition = '0.3s ease';
            el.style.opacity = '1';
            el.style.transform = 'translateX(0)';
        }, 100 + i * 60);
    });

    // ── Weight chart (mini bar chart on dashboard) ──
    const chartCanvas = document.getElementById('weightChart');
    if (chartCanvas) {
        const labels = JSON.parse(chartCanvas.dataset.labels || '[]');
        const values = JSON.parse(chartCanvas.dataset.values || '[]');
        drawBarChart(chartCanvas, labels, values);
    }

    function drawBarChart(canvas, labels, values) {
        const ctx = canvas.getContext('2d');
        const W = canvas.width = canvas.offsetWidth;
        const H = canvas.height = 160;
        const pad = { top: 20, right: 16, bottom: 36, left: 40 };
        const innerW = W - pad.left - pad.right;
        const innerH = H - pad.top - pad.bottom;

        if (!values.length) return;

        const min = Math.floor(Math.min(...values) - 2);
        const max = Math.ceil(Math.max(...values) + 2);
        const barW = (innerW / values.length) * 0.6;
        const barGap = innerW / values.length;

        ctx.clearRect(0, 0, W, H);

        // Grid lines
        ctx.strokeStyle = '#2a2a38';
        ctx.lineWidth = 1;
        for (let i = 0; i <= 4; i++) {
            const y = pad.top + (innerH / 4) * i;
            ctx.beginPath();
            ctx.moveTo(pad.left, y);
            ctx.lineTo(W - pad.right, y);
            ctx.stroke();
            const val = (max - (max - min) * (i / 4)).toFixed(1);
            ctx.fillStyle = '#55556a';
            ctx.font = '10px DM Sans, sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(val, pad.left - 6, y + 4);
        }

        // Bars and line
        ctx.beginPath();
        values.forEach((v, i) => {
            const x = pad.left + barGap * i + barGap * 0.2;
            const barH = ((v - min) / (max - min)) * innerH;
            const y = pad.top + innerH - barH;

            // Bar
            ctx.fillStyle = 'rgba(232,255,0,0.15)';
            ctx.fillRect(x, y, barW, barH);

            // Outline
            ctx.strokeStyle = 'rgba(232,255,0,0.4)';
            ctx.lineWidth = 1;
            ctx.strokeRect(x, y, barW, barH);

            // Label
            ctx.fillStyle = '#55556a';
            ctx.font = '9px DM Sans, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(labels[i] || '', x + barW / 2, H - 8);

            // Line point
            if (i === 0) ctx.moveTo(x + barW / 2, y);
            else ctx.lineTo(x + barW / 2, y);
        });

        // Line
        ctx.strokeStyle = '#e8ff00';
        ctx.lineWidth = 2;
        ctx.stroke();

        // Points
        values.forEach((v, i) => {
            const x = pad.left + barGap * i + barGap * 0.2 + barW / 2;
            const barH = ((v - min) / (max - min)) * innerH;
            const y = pad.top + innerH - barH;
            ctx.beginPath();
            ctx.arc(x, y, 3, 0, Math.PI * 2);
            ctx.fillStyle = '#e8ff00';
            ctx.fill();
        });
    }

    // ── Table sort ──
    document.querySelectorAll('[data-sort]').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
            const table = this.closest('table');
            const col   = [...this.parentElement.children].indexOf(this);
            const asc   = this.dataset.sortDir !== 'asc';
            this.dataset.sortDir = asc ? 'asc' : 'desc';
            const tbody = table.querySelector('tbody');
            const rows  = [...tbody.querySelectorAll('tr')];
            rows.sort((a, b) => {
                const A = a.children[col]?.textContent.trim() || '';
                const B = b.children[col]?.textContent.trim() || '';
                const nA = parseFloat(A), nB = parseFloat(B);
                if (!isNaN(nA) && !isNaN(nB)) return asc ? nA - nB : nB - nA;
                return asc ? A.localeCompare(B) : B.localeCompare(A);
            });
            rows.forEach(r => tbody.appendChild(r));
        });
    });

})();
