/**
 * Space Weather Dashboard — Application Logic
 * Fetches and visualizes NOAA SWPC space weather data.
 */

(function () {
    'use strict';

    // ================================================================
    // Configuration
    // ================================================================
    const API_BASE = 'api.php?endpoint=';
    const SWPC_DIRECT = 'https://services.swpc.noaa.gov/json/';
    const SWPC_IMAGES = 'https://services.swpc.noaa.gov/images/';
    const REFRESH_INTERVAL = 120000; // 2 minutes
    let refreshTimer = null;

    // Chart instances cache
    const charts = {};

    // Data cache
    const dataCache = {};

    // ================================================================
    // Utility Functions
    // ================================================================

    /**
     * Fetch JSON from the PHP proxy or directly from SWPC.
     */
    async function fetchData(endpoint) {
        const cacheKey = endpoint;
        try {
            // Try PHP proxy first
            let url = API_BASE + encodeURIComponent(endpoint);
            let res = await fetch(url);
            if (!res.ok) throw new Error(`Proxy error: ${res.status}`);
            const data = await res.json();
            dataCache[cacheKey] = { data, timestamp: Date.now() };
            return data;
        } catch (proxyErr) {
            // Fallback: direct fetch (may be blocked by CORS)
            try {
                let res = await fetch(SWPC_DIRECT + endpoint);
                if (!res.ok) throw new Error(`Direct error: ${res.status}`);
                const data = await res.json();
                dataCache[cacheKey] = { data, timestamp: Date.now() };
                return data;
            } catch (directErr) {
                console.warn(`Failed to fetch ${endpoint}:`, directErr.message);
                throw directErr;
            }
        }
    }

    function formatDateTime(str) {
        if (!str) return '—';
        const d = new Date(str);
        if (isNaN(d)) return str;
        return d.toLocaleString();
    }

    function formatNumber(n, decimals) {
        if (n === null || n === undefined || n === '') return '—';
        const num = parseFloat(n);
        if (isNaN(num)) return '—';
        if (decimals !== undefined) return num.toFixed(decimals);
        if (Math.abs(num) < 0.01) return num.toExponential(2);
        return num.toLocaleString(undefined, { maximumFractionDigits: 2 });
    }

    function getKpColor(kp) {
        const v = parseFloat(kp);
        if (isNaN(v)) return '#64748b';
        if (v < 2) return '#10b981';
        if (v < 4) return '#06b6d4';
        if (v < 5) return '#f59e0b';
        if (v < 6) return '#f97316';
        if (v < 8) return '#ef4444';
        return '#ff2d55';
    }

    function getKpClass(kp) {
        const v = Math.round(parseFloat(kp));
        if (isNaN(v)) return '';
        return `kp-${Math.min(9, Math.max(0, v))}`;
    }

    function setLoading(elementId, loading) {
        const el = document.getElementById(elementId);
        if (!el) return;
        if (loading) {
            el.innerHTML = '<div class="loading-spinner">Loading data...</div>';
        }
    }

    function showError(elementId, msg) {
        const el = document.getElementById(elementId);
        if (!el) return;
        el.innerHTML = `<div class="error-message">⚠️ ${msg}</div>`;
    }

    function destroyChart(id) {
        if (charts[id]) {
            charts[id].destroy();
            delete charts[id];
        }
    }

    function createTimeSeriesChart(canvasId, datasets, options = {}) {
        destroyChart(canvasId);
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        const cfg = {
            type: 'line',
            data: { datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        labels: { color: '#94a3b8', font: { size: 11 } }
                    },
                    tooltip: {
                        backgroundColor: '#1a2332',
                        titleColor: '#e2e8f0',
                        bodyColor: '#94a3b8',
                        borderColor: '#1e293b',
                        borderWidth: 1
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: { tooltipFormat: 'MMM d, yyyy HH:mm' },
                        grid: { color: 'rgba(30, 41, 59, 0.5)' },
                        ticks: { color: '#64748b', maxTicksLimit: 10, font: { size: 10 } }
                    },
                    y: {
                        grid: { color: 'rgba(30, 41, 59, 0.5)' },
                        ticks: { color: '#64748b', font: { size: 10 } },
                        ...(options.yAxis || {})
                    }
                },
                ...options.chartOptions
            }
        };

        if (options.yScaleType === 'logarithmic') {
            cfg.options.scales.y.type = 'logarithmic';
        }

        charts[canvasId] = new Chart(ctx, cfg);
        return charts[canvasId];
    }

    // ================================================================
    // Navigation
    // ================================================================
    function switchSection(sectionId) {
        document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.nav-link[data-section]').forEach(l => l.classList.remove('active'));

        const panel = document.getElementById('section-' + sectionId);
        if (panel) panel.classList.add('active');

        const link = document.querySelector(`.nav-link[data-section="${sectionId}"]`);
        if (link) link.classList.add('active');

        // Load data for section on first visit
        loadSectionData(sectionId);
    }

    // Make switchSection globally accessible
    window.switchSection = switchSection;

    document.querySelectorAll('.nav-link[data-section]').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            switchSection(link.dataset.section);
        });
    });

    // Sub-tab handling
    document.querySelectorAll('.sub-tab-btn[data-subtab]').forEach(btn => {
        btn.addEventListener('click', () => {
            const group = btn.closest('.section-panel');
            const prefix = btn.dataset.subtab.split('-')[0];
            group.querySelectorAll(`.sub-tab-btn[data-subtab^="${prefix}-"]`).forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            group.querySelectorAll(`[id^="subtab-${prefix}-"]`).forEach(el => el.style.display = 'none');
            const target = document.getElementById('subtab-' + btn.dataset.subtab);
            if (target) target.style.display = '';
        });
    });

    // ================================================================
    // Section Data Loading
    // ================================================================
    const loadedSections = new Set();

    function loadSectionData(section) {
        if (loadedSections.has(section)) return;
        loadedSections.add(section);

        switch (section) {
            case 'dashboard': loadDashboard(); break;
            case 'solar-wind': loadSolarWind(); break;
            case 'geomagnetic': loadGeomagnetic(); break;
            case 'xray': loadXrayParticles(); break;
            case 'solar-cycle': loadSolarCycle(); break;
            case 'alerts': loadAlerts(); break;
            case 'imagery': loadImagery('aurora'); break;
            case 'explorer': loadExplorer(); break;
        }
    }

    // ================================================================
    // DASHBOARD
    // ================================================================
    async function loadDashboard() {
        try {
            const [kpData, windData, magData, alertData] = await Promise.allSettled([
                fetchData('planetary_k_index_1m.json'),
                fetchData('rtsw/rtsw_wind_1m.json'),
                fetchData('rtsw/rtsw_mag_1m.json'),
                fetchData('alerts.json')
            ]);

            // Kp metric
            if (kpData.status === 'fulfilled' && kpData.value.length > 0) {
                const latest = kpData.value[kpData.value.length - 1];
                const kpVal = latest.estimated_kp || latest.kp_index || latest.Kp || '—';
                const metricEl = document.getElementById('metric-kp');
                metricEl.textContent = formatNumber(kpVal, 1);
                metricEl.className = 'metric-value ' + getKpClass(kpVal);
                renderKpBars('dashKpBars', kpData.value.slice(-24));
            }

            // Solar wind metrics
            if (windData.status === 'fulfilled' && windData.value.length > 0) {
                const latest = windData.value[windData.value.length - 1];
                document.getElementById('metric-wind-speed').textContent = formatNumber(latest.proton_speed, 0);
                document.getElementById('metric-wind-density').textContent = formatNumber(latest.proton_density, 1);
                renderDashWindChart(windData.value);
            }

            // Bz metric
            if (magData.status === 'fulfilled' && magData.value.length > 0) {
                const latest = magData.value[magData.value.length - 1];
                const bzEl = document.getElementById('metric-bz');
                const bzVal = parseFloat(latest.bz_gsm);
                bzEl.textContent = formatNumber(bzVal, 1);
                if (!isNaN(bzVal)) {
                    bzEl.style.color = bzVal < -5 ? '#ef4444' : bzVal < 0 ? '#f59e0b' : '#10b981';
                }
            }

            // Alerts metric + preview
            if (alertData.status === 'fulfilled') {
                document.getElementById('metric-alerts').textContent = alertData.value.length;
                renderAlertPreview(alertData.value.slice(0, 5));
            }

            // X-ray metric
            try {
                const xrayData = await fetchData('goes/primary/xrays-1-day.json');
                if (xrayData && xrayData.length > 0) {
                    const latest = xrayData[xrayData.length - 1];
                    const flux = latest.flux || latest.current_intflux;
                    document.getElementById('metric-xray').textContent = flux ? parseFloat(flux).toExponential(1) : '—';
                }
            } catch (e) { /* non-critical */ }

            updateStatus(true);
        } catch (err) {
            updateStatus(false);
            console.error('Dashboard load error:', err);
        }
    }

    function renderKpBars(containerId, data) {
        const container = document.getElementById(containerId);
        if (!container || !data || data.length === 0) return;

        container.innerHTML = '';
        const maxH = container.clientHeight || 120;

        data.forEach(item => {
            const kp = parseFloat(item.estimated_kp || item.kp_index || item.Kp || 0);
            const bar = document.createElement('div');
            bar.className = 'kp-bar';
            bar.style.height = Math.max(4, (kp / 9) * maxH) + 'px';
            bar.style.backgroundColor = getKpColor(kp);
            const time = item.time_tag || item.date || '';
            bar.setAttribute('data-tooltip', `Kp: ${kp.toFixed(1)} | ${formatDateTime(time)}`);
            container.appendChild(bar);
        });
    }

    function renderDashWindChart(data) {
        const sampled = sampleData(data, 200);
        const points = sampled.map(d => ({
            x: new Date(d.time_tag),
            y: parseFloat(d.proton_speed)
        })).filter(p => !isNaN(p.y));

        createTimeSeriesChart('dashWindChart', [{
            label: 'Speed (km/s)',
            data: points,
            borderColor: '#06b6d4',
            backgroundColor: 'rgba(6, 182, 212, 0.1)',
            borderWidth: 1.5,
            pointRadius: 0,
            fill: true
        }]);
    }

    function renderAlertPreview(alerts) {
        const container = document.getElementById('dashAlerts');
        if (!alerts || alerts.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-3">No recent alerts</p>';
            return;
        }
        container.innerHTML = alerts.map(a => renderAlertItem(a)).join('');
    }

    // ================================================================
    // SOLAR WIND
    // ================================================================
    async function loadSolarWind() {
        try {
            const [windData, magData] = await Promise.all([
                fetchData('rtsw/rtsw_wind_1m.json'),
                fetchData('rtsw/rtsw_mag_1m.json')
            ]);

            // Plasma charts
            const windSampled = sampleData(windData, 300);

            const speedPoints = windSampled.map(d => ({ x: new Date(d.time_tag), y: parseFloat(d.proton_speed) })).filter(p => !isNaN(p.y));
            createTimeSeriesChart('swSpeedChart', [{
                label: 'Speed (km/s)',
                data: speedPoints,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 1.5,
                pointRadius: 0,
                fill: true
            }]);

            const densityPoints = windSampled.map(d => ({ x: new Date(d.time_tag), y: parseFloat(d.proton_density) })).filter(p => !isNaN(p.y));
            createTimeSeriesChart('swDensityChart', [{
                label: 'Density (p/cm³)',
                data: densityPoints,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 1.5,
                pointRadius: 0,
                fill: true
            }]);

            const tempPoints = windSampled.map(d => ({ x: new Date(d.time_tag), y: parseFloat(d.proton_temperature) })).filter(p => !isNaN(p.y));
            createTimeSeriesChart('swTempChart', [{
                label: 'Temperature (K)',
                data: tempPoints,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                borderWidth: 1.5,
                pointRadius: 0,
                fill: true
            }]);

            // Magnetic field charts
            const magSampled = sampleData(magData, 300);

            const btPoints = magSampled.map(d => ({ x: new Date(d.time_tag), y: parseFloat(d.bt) })).filter(p => !isNaN(p.y));
            createTimeSeriesChart('swBtChart', [{
                label: 'Bt (nT)',
                data: btPoints,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                borderWidth: 1.5,
                pointRadius: 0,
                fill: true
            }]);

            const bzPoints = magSampled.map(d => ({ x: new Date(d.time_tag), y: parseFloat(d.bz_gsm) })).filter(p => !isNaN(p.y));
            createTimeSeriesChart('swBzChart', [{
                label: 'Bz GSM (nT)',
                data: bzPoints,
                borderColor: '#06b6d4',
                backgroundColor: 'rgba(6, 182, 212, 0.1)',
                borderWidth: 1.5,
                pointRadius: 0,
                fill: true
            }], {
                chartOptions: {
                    plugins: {
                        annotation: {
                            annotations: {
                                zeroline: {
                                    type: 'line', yMin: 0, yMax: 0,
                                    borderColor: 'rgba(255,255,255,0.2)', borderWidth: 1
                                }
                            }
                        }
                    }
                }
            });

            // Data table
            renderSolarWindTable(windData, magData);
        } catch (err) {
            console.error('Solar wind load error:', err);
        }
    }

    function renderSolarWindTable(windData, magData) {
        const wrapper = document.getElementById('swTableWrapper');
        if (!windData || windData.length === 0) {
            wrapper.innerHTML = '<p class="text-muted p-3">No data available</p>';
            return;
        }

        const recent = windData.slice(-100).reverse();
        let html = `<table class="data-table">
            <thead><tr>
                <th>Time (UTC)</th>
                <th>Speed (km/s)</th>
                <th>Density (p/cm³)</th>
                <th>Temperature (K)</th>
            </tr></thead><tbody>`;

        recent.forEach(row => {
            html += `<tr>
                <td>${formatDateTime(row.time_tag)}</td>
                <td>${formatNumber(row.proton_speed, 0)}</td>
                <td>${formatNumber(row.proton_density, 1)}</td>
                <td>${formatNumber(row.proton_temperature, 0)}</td>
            </tr>`;
        });

        html += '</tbody></table>';
        wrapper.innerHTML = html;
    }

    // ================================================================
    // GEOMAGNETIC
    // ================================================================
    async function loadGeomagnetic() {
        try {
            const data = await fetchData('planetary_k_index_1m.json');
            if (!data || data.length === 0) return;

            // Kp bars
            renderKpBars('geoKpBars', data.slice(-48));

            // Kp time series
            const sampled = sampleData(data, 300);
            const points = sampled.map(d => ({
                x: new Date(d.time_tag),
                y: parseFloat(d.estimated_kp || d.kp_index || d.Kp || 0)
            })).filter(p => !isNaN(p.y));

            createTimeSeriesChart('geoKpChart', [{
                label: 'Kp Index',
                data: points,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 2,
                pointRadius: 0,
                fill: true,
                segment: {
                    borderColor: ctx => {
                        const v = ctx.p1.parsed.y;
                        if (v >= 7) return '#ff2d55';
                        if (v >= 5) return '#ef4444';
                        if (v >= 4) return '#f97316';
                        return '#f59e0b';
                    }
                }
            }], {
                yAxis: { min: 0, max: 9, title: { display: true, text: 'Kp', color: '#64748b' } }
            });

            // Data table
            const recent = data.slice(-50).reverse();
            let html = `<table class="data-table">
                <thead><tr>
                    <th>Time (UTC)</th>
                    <th>Kp Index</th>
                    <th>Status</th>
                </tr></thead><tbody>`;

            recent.forEach(row => {
                const kp = parseFloat(row.estimated_kp || row.kp_index || row.Kp || 0);
                let status = 'Quiet';
                if (kp >= 7) status = '🔴 Extreme Storm';
                else if (kp >= 6) status = '🟠 Severe Storm';
                else if (kp >= 5) status = '🟡 Storm';
                else if (kp >= 4) status = '🟡 Active';
                else if (kp >= 3) status = 'Unsettled';

                html += `<tr>
                    <td>${formatDateTime(row.time_tag)}</td>
                    <td><span class="${getKpClass(kp)}" style="font-weight:700">${kp.toFixed(1)}</span></td>
                    <td>${status}</td>
                </tr>`;
            });

            html += '</tbody></table>';
            document.getElementById('geoTableWrapper').innerHTML = html;

        } catch (err) {
            console.error('Geomagnetic load error:', err);
            showError('geoTableWrapper', 'Failed to load geomagnetic data');
        }
    }

    // ================================================================
    // X-RAY / PARTICLES
    // ================================================================
    async function loadXrayParticles() {
        loadXrayChart('1');
        loadProtonChart('1');
        loadGoesMagChart('1');
    }

    async function loadXrayChart(days) {
        try {
            const endpoint = `goes/primary/xrays-${days}-day.json`;
            const data = await fetchData(endpoint);
            if (!data || data.length === 0) return;

            const sampled = sampleData(data, 400);
            const shortPoints = [];
            const longPoints = [];

            sampled.forEach(d => {
                const t = new Date(d.time_tag);
                const flux = parseFloat(d.flux || d.current_intflux || 0);
                if (isNaN(flux) || flux <= 0) return;
                const energy = d.energy || '';
                if (energy.includes('0.05') || energy.includes('short') || d.channel === 'A') {
                    shortPoints.push({ x: t, y: flux });
                } else {
                    longPoints.push({ x: t, y: flux });
                }
            });

            // If no separation by energy, use all data as one series
            const datasets = [];
            if (shortPoints.length > 0) {
                datasets.push({
                    label: 'Short (0.05-0.4 nm)',
                    data: shortPoints,
                    borderColor: '#3b82f6',
                    borderWidth: 1,
                    pointRadius: 0,
                    fill: false
                });
            }
            if (longPoints.length > 0) {
                datasets.push({
                    label: 'Long (0.1-0.8 nm)',
                    data: longPoints,
                    borderColor: '#ef4444',
                    borderWidth: 1,
                    pointRadius: 0,
                    fill: false
                });
            }
            if (datasets.length === 0) {
                // Fallback: all data as single series
                const allPoints = sampled.map(d => ({
                    x: new Date(d.time_tag),
                    y: parseFloat(d.flux || d.current_intflux || 0)
                })).filter(p => !isNaN(p.y) && p.y > 0);
                datasets.push({
                    label: 'X-Ray Flux (W/m²)',
                    data: allPoints,
                    borderColor: '#ef4444',
                    borderWidth: 1.5,
                    pointRadius: 0,
                    fill: false
                });
            }

            createTimeSeriesChart('xrayChart', datasets, { yScaleType: 'logarithmic' });
        } catch (err) {
            console.error('X-ray load error:', err);
        }
    }

    async function loadProtonChart(days) {
        try {
            const endpoint = `goes/primary/integral-protons-${days}-day.json`;
            const data = await fetchData(endpoint);
            if (!data || data.length === 0) return;

            // Group by energy channel
            const channels = {};
            data.forEach(d => {
                const key = d.energy || d.channel || 'default';
                if (!channels[key]) channels[key] = [];
                channels[key].push({
                    x: new Date(d.time_tag),
                    y: parseFloat(d.flux || 0)
                });
            });

            const colors = ['#3b82f6', '#ef4444', '#f59e0b', '#10b981', '#8b5cf6', '#06b6d4'];
            let i = 0;
            const datasets = Object.entries(channels).map(([key, points]) => ({
                label: key,
                data: sampleData(points, 200),
                borderColor: colors[i++ % colors.length],
                borderWidth: 1,
                pointRadius: 0,
                fill: false
            }));

            createTimeSeriesChart('protonChart', datasets, { yScaleType: 'logarithmic' });
        } catch (err) {
            console.error('Proton load error:', err);
        }
    }

    async function loadGoesMagChart(days) {
        try {
            const endpoint = `goes/primary/magnetometers-${days}-day.json`;
            const data = await fetchData(endpoint);
            if (!data || data.length === 0) return;

            const sampled = sampleData(data, 300);
            const hePoints = sampled.map(d => ({
                x: new Date(d.time_tag),
                y: parseFloat(d.He || d.arcjet_flag || 0)
            })).filter(p => !isNaN(p.y));

            const datasets = [];
            if (hePoints.length > 0) {
                datasets.push({
                    label: 'He (nT)',
                    data: hePoints,
                    borderColor: '#8b5cf6',
                    borderWidth: 1,
                    pointRadius: 0,
                    fill: false
                });
            }

            // Try other components
            ['Hp', 'Hn'].forEach((comp, idx) => {
                const pts = sampled.map(d => ({
                    x: new Date(d.time_tag),
                    y: parseFloat(d[comp] || 0)
                })).filter(p => !isNaN(p.y) && p.y !== 0);
                if (pts.length > 0) {
                    datasets.push({
                        label: `${comp} (nT)`,
                        data: pts,
                        borderColor: ['#06b6d4', '#f59e0b'][idx],
                        borderWidth: 1,
                        pointRadius: 0,
                        fill: false
                    });
                }
            });

            if (datasets.length > 0) {
                createTimeSeriesChart('goesMagChart', datasets);
            }
        } catch (err) {
            console.error('GOES mag load error:', err);
        }
    }

    // X-ray range switcher
    document.querySelectorAll('[data-xray-range]').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.parentElement.querySelectorAll('.sub-tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadXrayChart(btn.dataset.xrayRange);
        });
    });
    document.querySelectorAll('[data-proton-range]').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.parentElement.querySelectorAll('.sub-tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadProtonChart(btn.dataset.protonRange);
        });
    });
    document.querySelectorAll('[data-goesmag-range]').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.parentElement.querySelectorAll('.sub-tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadGoesMagChart(btn.dataset.goesmagRange);
        });
    });

    // ================================================================
    // SOLAR CYCLE
    // ================================================================
    async function loadSolarCycle() {
        try {
            const [predicted, observed] = await Promise.allSettled([
                fetchData('solar-cycle/predicted-solar-cycle.json'),
                fetchData('solar-cycle/observed-solar-cycle-indices.json')
            ]);

            const datasets = [];

            // Observed sunspot numbers
            if (observed.status === 'fulfilled' && observed.value.length > 0) {
                const pts = observed.value.map(d => ({
                    x: new Date(d['time-tag'] || `${d.year || d['Year']}-${String(d.month || d['Month'] || 1).padStart(2, '0')}-01`),
                    y: parseFloat(d.ssn || d['Sunspot Number'] || d['smoothed_ssn'] || 0)
                })).filter(p => !isNaN(p.y) && !isNaN(p.x.getTime()));

                datasets.push({
                    label: 'Observed SSN',
                    data: sampleData(pts, 500),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    borderWidth: 1.5,
                    pointRadius: 0,
                    fill: true
                });
            }

            // Predicted sunspot numbers
            if (predicted.status === 'fulfilled' && predicted.value.length > 0) {
                const predPts = predicted.value.map(d => ({
                    x: new Date(d['time-tag'] || `${d.year || d['Year']}-${String(d.month || d['Month'] || 1).padStart(2, '0')}-01`),
                    y: parseFloat(d['predicted_ssn'] || d.ssn || 0)
                })).filter(p => !isNaN(p.y) && !isNaN(p.x.getTime()));

                datasets.push({
                    label: 'Predicted SSN',
                    data: predPts,
                    borderColor: '#f59e0b',
                    borderDash: [5, 5],
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: false
                });

                // High/low bounds
                const highPts = predicted.value.map(d => ({
                    x: new Date(d['time-tag'] || `${d.year}-${String(d.month || 1).padStart(2, '0')}-01`),
                    y: parseFloat(d['high_ssn'] || 0)
                })).filter(p => !isNaN(p.y) && p.y > 0 && !isNaN(p.x.getTime()));

                const lowPts = predicted.value.map(d => ({
                    x: new Date(d['time-tag'] || `${d.year}-${String(d.month || 1).padStart(2, '0')}-01`),
                    y: parseFloat(d['low_ssn'] || 0)
                })).filter(p => !isNaN(p.y) && !isNaN(p.x.getTime()));

                if (highPts.length > 0) {
                    datasets.push({
                        label: 'High Bound',
                        data: highPts,
                        borderColor: 'rgba(245, 158, 11, 0.3)',
                        borderWidth: 1,
                        pointRadius: 0,
                        fill: false
                    });
                }
                if (lowPts.length > 0) {
                    datasets.push({
                        label: 'Low Bound',
                        data: lowPts,
                        borderColor: 'rgba(245, 158, 11, 0.3)',
                        borderWidth: 1,
                        pointRadius: 0,
                        fill: '-1'
                    });
                }
            }

            if (datasets.length > 0) {
                createTimeSeriesChart('solarCycleChart', datasets, {
                    yAxis: { min: 0, title: { display: true, text: 'Sunspot Number', color: '#64748b' } }
                });
            }

            // F10.7 chart
            const f107Datasets = [];
            if (observed.status === 'fulfilled') {
                const f107Obs = observed.value.map(d => ({
                    x: new Date(d['time-tag'] || `${d.year || d['Year']}-${String(d.month || d['Month'] || 1).padStart(2, '0')}-01`),
                    y: parseFloat(d['f10.7'] || d['observed_f10.7'] || 0)
                })).filter(p => !isNaN(p.y) && p.y > 0 && !isNaN(p.x.getTime()));

                if (f107Obs.length > 0) {
                    f107Datasets.push({
                        label: 'Observed F10.7',
                        data: sampleData(f107Obs, 500),
                        borderColor: '#06b6d4',
                        backgroundColor: 'rgba(6, 182, 212, 0.05)',
                        borderWidth: 1.5,
                        pointRadius: 0,
                        fill: true
                    });
                }
            }
            if (predicted.status === 'fulfilled') {
                const f107Pred = predicted.value.map(d => ({
                    x: new Date(d['time-tag'] || `${d.year}-${String(d.month || 1).padStart(2, '0')}-01`),
                    y: parseFloat(d['predicted_f10.7'] || d['f10.7'] || 0)
                })).filter(p => !isNaN(p.y) && p.y > 0 && !isNaN(p.x.getTime()));

                if (f107Pred.length > 0) {
                    f107Datasets.push({
                        label: 'Predicted F10.7',
                        data: f107Pred,
                        borderColor: '#f97316',
                        borderDash: [5, 5],
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: false
                    });
                }
            }

            if (f107Datasets.length > 0) {
                createTimeSeriesChart('f107Chart', f107Datasets, {
                    yAxis: { title: { display: true, text: 'F10.7 (sfu)', color: '#64748b' } }
                });
            }

        } catch (err) {
            console.error('Solar cycle load error:', err);
        }
    }

    // ================================================================
    // ALERTS
    // ================================================================
    async function loadAlerts() {
        try {
            const data = await fetchData('alerts.json');
            renderAlertsList(data, 'all');

            // Filter buttons
            document.querySelectorAll('[data-alert-filter]').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('[data-alert-filter]').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    renderAlertsList(data, btn.dataset.alertFilter);
                });
            });
        } catch (err) {
            showError('alertsList', 'Failed to load alerts data');
        }
    }

    function renderAlertsList(data, filter) {
        const container = document.getElementById('alertsList');
        if (!data || data.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-3">No alerts available</p>';
            return;
        }

        let filtered = data;
        if (filter !== 'all') {
            filtered = data.filter(a => {
                const msg = (a.message || a.product_id || '').toLowerCase();
                if (filter === 'alert') return msg.includes('alert');
                if (filter === 'warning') return msg.includes('warning');
                if (filter === 'watch') return msg.includes('watch');
                if (filter === 'summary') return msg.includes('summary');
                return true;
            });
        }

        if (filtered.length === 0) {
            container.innerHTML = `<p class="text-muted text-center py-3">No ${filter} alerts found</p>`;
            return;
        }

        container.innerHTML = filtered.map(a => renderAlertItem(a)).join('');
    }

    function renderAlertItem(alert) {
        const msg = (alert.message || '').trim();
        const productId = alert.product_id || '';
        const issueTime = alert.issue_datetime || alert.issue_time || '';

        let typeClass = 'alert-summary-type';
        const lower = (productId + ' ' + msg).toLowerCase();
        if (lower.includes('warning')) typeClass = 'alert-warning-type';
        else if (lower.includes('watch')) typeClass = 'alert-watch-type';
        else if (lower.includes('alert')) typeClass = 'alert-alert-type';

        // Extract first line as title
        const lines = msg.split('\n').filter(l => l.trim());
        const title = lines[0] || productId || 'Space Weather Notification';

        return `<div class="alert-item ${typeClass}">
            <div class="alert-title">${escapeHtml(title)}</div>
            <div class="alert-meta">${escapeHtml(productId)} • ${formatDateTime(issueTime)}</div>
            <details><summary style="cursor:pointer;color:#94a3b8;font-size:0.82rem;">Show full message</summary>
            <div class="alert-body">${escapeHtml(msg)}</div>
            </details>
        </div>`;
    }

    // ================================================================
    // IMAGERY
    // ================================================================
    const IMAGERY_CATALOG = {
        aurora: [
            { src: 'animations/ovation/north/latest.jpg', title: 'Aurora Forecast — North', desc: 'OVATION model northern hemisphere' },
            { src: 'animations/ovation/south/latest.jpg', title: 'Aurora Forecast — South', desc: 'OVATION model southern hemisphere' },
            { src: 'aurora-forecast-northern-hemisphere.jpg', title: 'Aurora Map — Northern', desc: 'Probability map' },
            { src: 'aurora-forecast-southern-hemisphere.jpg', title: 'Aurora Map — Southern', desc: 'Probability map' },
        ],
        solar: [
            { src: 'animations/sdo/latest_1024_0193.jpg', title: 'SDO AIA 193Å', desc: 'Solar corona in extreme UV' },
            { src: 'animations/sdo/latest_1024_0304.jpg', title: 'SDO AIA 304Å', desc: 'Solar chromosphere' },
            { src: 'animations/sdo/latest_1024_0171.jpg', title: 'SDO AIA 171Å', desc: 'Solar corona — quiet regions' },
            { src: 'animations/sdo/latest_1024_0211.jpg', title: 'SDO AIA 211Å', desc: 'Active regions and flares' },
            { src: 'animations/sdo/latest_1024_0094.jpg', title: 'SDO AIA 94Å', desc: 'Solar flare plasma' },
            { src: 'animations/sdo/latest_1024_0131.jpg', title: 'SDO AIA 131Å', desc: 'Flare regions' },
            { src: 'animations/sdo/latest_1024_HMIIF.jpg', title: 'SDO HMI Intensitygram', desc: 'Visible light sunspot image' },
            { src: 'animations/sdo/latest_1024_HMIB.jpg', title: 'SDO HMI Magnetogram', desc: 'Solar magnetic field' },
        ],
        coronagraph: [
            { src: 'animations/lasco/c2/latest.jpg', title: 'SOHO LASCO C2', desc: 'Inner coronagraph' },
            { src: 'animations/lasco/c3/latest.jpg', title: 'SOHO LASCO C3', desc: 'Outer coronagraph' },
        ],
        magnetosphere: [
            { src: 'animations/geospace/geospace_1_day.png', title: 'Geospace — 1 Day', desc: 'Magnetosphere model output' },
            { src: 'animations/geospace/geospace_6_hour.png', title: 'Geospace — 6 Hour', desc: 'Short-range magnetosphere' },
        ]
    };

    function loadImagery(category) {
        const gallery = document.getElementById('imageGallery');
        const items = IMAGERY_CATALOG[category] || [];

        if (items.length === 0) {
            gallery.innerHTML = '<p class="text-muted text-center py-3">No imagery available for this category</p>';
            return;
        }

        gallery.innerHTML = items.map(img => `
            <div class="image-card">
                <img src="${SWPC_IMAGES}${img.src}" alt="${escapeHtml(img.title)}"
                     loading="lazy" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 320 240%22><rect fill=%22%23111827%22 width=%22320%22 height=%22240%22/><text fill=%22%2364748b%22 x=%22160%22 y=%22120%22 text-anchor=%22middle%22 font-size=%2214%22>Image unavailable</text></svg>'">
                <div class="caption">
                    ${escapeHtml(img.title)}
                    <small>${escapeHtml(img.desc)}</small>
                </div>
            </div>
        `).join('');
    }

    document.querySelectorAll('[data-img-cat]').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.parentElement.querySelectorAll('.sub-tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadImagery(btn.dataset.imgCat);
        });
    });

    // ================================================================
    // EXPLORER
    // ================================================================
    const EXPLORER_ENDPOINTS = {
        'Solar Wind': [
            { id: 'rtsw/rtsw_wind_1m.json', name: 'Real-Time Wind (1m)' },
            { id: 'rtsw/rtsw_wind_5m.json', name: 'Real-Time Wind (5m)' },
            { id: 'rtsw/rtsw_mag_1m.json', name: 'IMF Magnetic Field (1m)' },
            { id: 'rtsw/rtsw_mag_5m.json', name: 'IMF Magnetic Field (5m)' },
        ],
        'Geomagnetic': [
            { id: 'planetary_k_index_1m.json', name: 'Planetary Kp (1m)' },
            { id: 'boulder_k_index_1m.json', name: 'Boulder K-Index (1m)' },
            { id: 'estimated_kp.json', name: 'Estimated Kp' },
        ],
        'GOES X-Ray': [
            { id: 'goes/primary/xrays-1-day.json', name: 'X-Rays (1 day)' },
            { id: 'goes/primary/xrays-3-day.json', name: 'X-Rays (3 day)' },
            { id: 'goes/primary/xrays-7-day.json', name: 'X-Rays (7 day)' },
        ],
        'GOES Protons': [
            { id: 'goes/primary/integral-protons-1-day.json', name: 'Protons (1 day)' },
            { id: 'goes/primary/integral-protons-3-day.json', name: 'Protons (3 day)' },
            { id: 'goes/primary/integral-protons-7-day.json', name: 'Protons (7 day)' },
        ],
        'GOES Magnetometer': [
            { id: 'goes/primary/magnetometers-1-day.json', name: 'Magnetometer (1 day)' },
            { id: 'goes/primary/magnetometers-3-day.json', name: 'Magnetometer (3 day)' },
            { id: 'goes/primary/magnetometers-7-day.json', name: 'Magnetometer (7 day)' },
        ],
        'Solar Activity': [
            { id: 'solar-cycle/predicted-solar-cycle.json', name: 'Predicted Solar Cycle' },
            { id: 'solar-cycle/observed-solar-cycle-indices.json', name: 'Observed Solar Cycle' },
            { id: 'f107_cm_flux.json', name: 'F10.7 cm Flux' },
            { id: 'sunspot_report.json', name: 'Sunspot Report' },
        ],
        'Alerts': [
            { id: 'alerts.json', name: 'All Alerts' },
        ],
        'Aurora': [
            { id: 'ovation_aurora_latest.json', name: 'Aurora Forecast' },
        ],
        'Models': [
            { id: 'enlil_time_series.json', name: 'ENLIL Time Series' },
        ]
    };

    function loadExplorer() {
        const sidebar = document.getElementById('explorerSidebar');
        let html = '';

        Object.entries(EXPLORER_ENDPOINTS).forEach(([category, endpoints]) => {
            html += `<div class="endpoint-category">${escapeHtml(category)}</div>`;
            html += '<ul class="endpoint-list">';
            endpoints.forEach(ep => {
                html += `<li data-endpoint="${escapeHtml(ep.id)}">
                    <span class="ep-name">${escapeHtml(ep.name)}</span>
                    <span style="color:var(--text-muted);font-size:0.7rem;">▶</span>
                </li>`;
            });
            html += '</ul>';
        });

        sidebar.innerHTML = html;

        // Click handler
        sidebar.querySelectorAll('li[data-endpoint]').forEach(li => {
            li.addEventListener('click', () => {
                sidebar.querySelectorAll('li').forEach(l => l.style.background = '');
                li.style.background = 'var(--bg-card-hover)';
                loadExplorerEndpoint(li.dataset.endpoint);
            });
        });
    }

    async function loadExplorerEndpoint(endpoint) {
        const content = document.getElementById('explorerContent');
        content.innerHTML = `<h5>/${endpoint}</h5><div class="loading-spinner">Loading...</div>`;

        try {
            const data = await fetchData(endpoint);

            let html = `<h5>/${escapeHtml(endpoint)}</h5>`;
            html += `<p style="color:var(--text-muted);font-size:0.82rem;">
                ${Array.isArray(data) ? data.length + ' records' : 'Object'} •
                <button class="btn-refresh" onclick="document.getElementById('explorerJsonToggle').style.display = document.getElementById('explorerJsonToggle').style.display === 'none' ? '' : 'none'">Toggle Raw JSON</button>
            </p>`;

            // Render as table if array of objects
            if (Array.isArray(data) && data.length > 0 && typeof data[0] === 'object') {
                const keys = Object.keys(data[0]);
                const displayRows = data.slice(-100).reverse();

                html += '<div class="data-table-wrapper"><table class="data-table"><thead><tr>';
                keys.forEach(k => { html += `<th>${escapeHtml(k)}</th>`; });
                html += '</tr></thead><tbody>';

                displayRows.forEach(row => {
                    html += '<tr>';
                    keys.forEach(k => {
                        const v = row[k];
                        html += `<td>${escapeHtml(String(v ?? ''))}</td>`;
                    });
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
            }

            // Raw JSON
            const jsonStr = JSON.stringify(data, null, 2);
            const truncated = jsonStr.length > 50000 ? jsonStr.substring(0, 50000) + '\n\n... (truncated)' : jsonStr;
            html += `<div id="explorerJsonToggle" style="display:none;margin-top:1rem;">
                <div class="json-viewer">${escapeHtml(truncated)}</div>
            </div>`;

            content.innerHTML = html;

        } catch (err) {
            content.innerHTML = `<h5>/${escapeHtml(endpoint)}</h5>
                <div class="error-message">Failed to load data: ${escapeHtml(err.message)}</div>`;
        }
    }

    // ================================================================
    // Status & Refresh
    // ================================================================
    function updateStatus(connected) {
        const dot = document.getElementById('connectionDot');
        const status = document.getElementById('connectionStatus');
        const update = document.getElementById('lastUpdate');

        if (connected) {
            dot.className = 'status-dot green pulse';
            status.textContent = 'Connected to SWPC';
            update.textContent = 'Last update: ' + new Date().toLocaleTimeString();
        } else {
            dot.className = 'status-dot red';
            status.textContent = 'Connection error';
        }
    }

    document.getElementById('refreshAll').addEventListener('click', () => {
        loadedSections.clear();
        Object.keys(charts).forEach(id => destroyChart(id));
        const activePanel = document.querySelector('.section-panel.active');
        if (activePanel) {
            const section = activePanel.id.replace('section-', '');
            loadSectionData(section);
        }
    });

    // Auto-refresh
    function startAutoRefresh() {
        if (refreshTimer) clearInterval(refreshTimer);
        refreshTimer = setInterval(() => {
            const activePanel = document.querySelector('.section-panel.active');
            if (activePanel) {
                const section = activePanel.id.replace('section-', '');
                loadedSections.delete(section);
                loadSectionData(section);
            }
        }, REFRESH_INTERVAL);
    }

    // ================================================================
    // Helpers
    // ================================================================
    function sampleData(arr, maxPoints) {
        if (!Array.isArray(arr) || arr.length <= maxPoints) return arr;
        const step = Math.ceil(arr.length / maxPoints);
        const result = [];
        for (let i = 0; i < arr.length; i += step) {
            result.push(arr[i]);
        }
        // Always include last element
        if (result[result.length - 1] !== arr[arr.length - 1]) {
            result.push(arr[arr.length - 1]);
        }
        return result;
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ================================================================
    // Initialize
    // ================================================================
    function init() {
        loadSectionData('dashboard');
        startAutoRefresh();
    }

    init();

})();
