/* NEO Website Check — front-end flow (vanilla JS, no dependencies).
   Loads async; everything waits for DOMContentLoaded. One instance per element. */
(function () {
  'use strict';

  function ready(fn) { document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn) : fn(); }
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function init(root) {
    var cfg, i18n;
    try { cfg = JSON.parse(root.getAttribute('data-config') || '{}'); i18n = JSON.parse(root.getAttribute('data-i18n') || '{}'); } catch (e) { return; }
    var $ = function (s, c) { return (c || root).querySelector(s); };
    var $$ = function (s, c) { return Array.prototype.slice.call((c || root).querySelectorAll(s)); };
    var panes = { idle: $('[data-nwc-pane="idle"]'), run: $('[data-nwc-pane="run"]'), gate: $('[data-nwc-pane="gate"]'), result: $('[data-nwc-pane="result"]') };
    var urlForm = panes.idle, leadForm = $('[data-nwc-lead]');
    var statusEl = $('[data-nwc-status]'), urlEl = $('[data-nwc-url]'), view = $('[data-nwc-view]'), shot = $('[data-nwc-shot]'), pinsEl = $('[data-nwc-pins]');
    var etaEl = $('[data-nwc-eta]'), bar = $('.nwc__progress'), runErr = $('[data-nwc-run-err]');
    var job = null, domain = '', pollTimer = 0, etaTimer = 0, etaLeft = 0, lensRaf = 0, lensT = 0, lensLast = 0, shotShown = false, shotTries = 0, pinsShown = {};

    function fmt(s, v) { return String(s || '').replace('%s', v).replace('%d', v); }
    function track(name, params) {
      params = params || {};
      try { document.dispatchEvent(new CustomEvent('neo_websitecheck', { detail: { name: name, params: params, element: root } })); } catch (e) {}
      if (cfg.dataLayer && window.dataLayer && typeof window.dataLayer.push === 'function') {
        var o = { event: name }; for (var k in params) o[k] = params[k]; window.dataLayer.push(o);
      }
    }
    function setState(state) {
      for (var k in panes) if (panes[k]) panes[k].hidden = k !== state;
      root.setAttribute('data-state', state);
      statusEl.textContent = state === 'idle' ? i18n.status_idle : state === 'run' ? i18n.status_run : i18n.status_done;
      if (state === 'run' && !reduce && !lensRaf) { lensLast = 0; lensRaf = requestAnimationFrame(lensLoop); }
    }
    function lensLoop(now) {
      if (root.getAttribute('data-state') !== 'run') { lensRaf = 0; return; }
      var dt = Math.min(0.05, (now - (lensLast || now)) / 1000); lensLast = now; lensT += dt;
      var w = view.clientWidth, h = view.clientHeight;
      root.style.setProperty('--lx', (w / 2 + Math.cos(lensT * 1.4) * w * 0.33).toFixed(1) + 'px');
      root.style.setProperty('--ly', (h / 2 + Math.sin(lensT * 2.1) * h * 0.3).toFixed(1) + 'px');
      lensRaf = requestAnimationFrame(lensLoop);
    }
    function showErr(el, msg) { el.textContent = msg || ''; el.hidden = !msg; }
    function norm(v) { v = v.trim().toLowerCase().replace(/^https?:\/\//, '').replace(/\/.*$/, '').replace(/^www\./, ''); return /^([a-z0-9äöüß-]+\.)+[a-z]{2,}$/.test(v) ? v : ''; }
    function api(method, url, body) {
      return fetch(url, { method: method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: body ? JSON.stringify(body) : undefined, credentials: 'same-origin' })
        .then(function (r) { return r.json().then(function (j) { j.__status = r.status; return j; }); });
    }
    function errText(j) {
      if (!j) return i18n.err_generic;
      if (j.error === 'invalid_url') return i18n.err_url;
      if (j.error === 'rate_limited') return j.window === 'hour' ? (i18n.err_limit_hour || i18n.err_limit) : i18n.err_limit;
      if (j.error === 'spam_check') return i18n.err_spam;
      if (j.error === 'failed') return i18n.err_failed;
      if (j.error === 'unavailable') return i18n.err_unavailable || i18n.err_generic;
      return i18n.err_generic;
    }
    function altchaSolution() {
      var input = urlForm.querySelector('input[name="altcha"]');
      return input && input.value ? input.value : '';
    }
    // A solution is accepted once (replay protection): after every start request the
    // widget solves a fresh challenge, so a second check works without reloading.
    function renewAltcha() {
      var w = urlForm.querySelector('altcha-widget');
      if (!w || typeof w.reset !== 'function') return;
      w.reset();
      if (typeof w.verify === 'function') w.verify();
    }
    function waitForAltcha(ms) {
      if (!cfg.challenge) return Promise.resolve('');
      return new Promise(function (resolve) {
        var started = Date.now();
        (function tick() {
          var v = altchaSolution();
          if (v || Date.now() - started > ms) return resolve(v);
          setTimeout(tick, 150);
        })();
      });
    }

    /* ---- ETA countdown ---- */
    function setEta(seconds) {
      etaLeft = Math.max(0, seconds | 0);
      renderEta();
      clearInterval(etaTimer);
      etaTimer = setInterval(function () { etaLeft = Math.max(0, etaLeft - 1); renderEta(); }, 1000);
    }
    function renderEta() { etaEl.textContent = etaLeft > 3 ? fmt(i18n.eta, etaLeft) : i18n.eta_soon; }

    /* ---- progress rendering ---- */
    function renderProgress(j) {
      var pct = j.progress || 0;
      bar.style.setProperty('--sp', pct + '%'); bar.setAttribute('aria-valuenow', pct);
      (j.steps || []).forEach(function (s) {
        var li = panes.run.querySelector('[data-step="' + s.key + '"]');
        if (!li) return;
        li.className = s.state === 'done' ? 'is-done' : s.state === 'run' ? 'is-run' : '';
      });
      // shown only once loaded: a failed load (file not there yet) never shows a broken image, the next poll retries
      if (j.screenshot && !shotShown && shotTries < 5) {
        shotShown = true; shotTries++;
        shot.onload = function () { shot.hidden = false; };
        shot.onerror = function () { shot.hidden = true; shot.removeAttribute('src'); shotShown = false; };
        shot.src = j.screenshot;
      }
      (j.pins || []).forEach(function (p, idx) {
        if (pinsShown[idx]) return;
        pinsShown[idx] = true;
        var el = document.createElement('span');
        el.className = 'nwc-pin' + (p.kind === 'good' ? ' is-good' : '');
        el.style.setProperty('--x', p.x + '%'); el.style.setProperty('--y', p.y + '%');
        el.innerHTML = '<b>' + (p.kind === 'good' ? '✓' : '!') + '</b><em></em>';
        el.querySelector('em').textContent = p.label || '';
        pinsEl.appendChild(el);
        setTimeout(function () { el.classList.add('is-on'); }, 400 + idx * 900);
      });
    }

    /* ---- polling ---- */
    function poll() {
      if (!job) return;
      var t0 = Date.now();
      api('GET', cfg.status.replace(/0(\/status)$/, job.id + '$1') + '?token=' + encodeURIComponent(job.token)).then(function (j) {
        if (j.error && j.__status === 404) { showErr(runErr, i18n.err_generic); return; }
        renderProgress(j);
        if (typeof j.eta === 'number') setEta(j.eta);
        if (j.status === 'done') { clearInterval(etaTimer); finishRun(); return; }
        if (j.status === 'failed') { clearInterval(etaTimer); showErr(runErr, i18n.err_failed || ''); /* the technical reason stays in the back end */ addResetButton(); return; }
        // A long response means this request executed a step: poll again right away.
        var took = Date.now() - t0;
        pollTimer = setTimeout(poll, took > 3000 ? 300 : 2000);
      }).catch(function () { pollTimer = setTimeout(poll, 4000); });
    }
    function addResetButton() {
      if (runErr.nextElementSibling && runErr.nextElementSibling.hasAttribute('data-nwc-reset')) return;
      // a copy of the result's reset button, so a customised template keeps its markup
      var b = $('[data-nwc-reset]', panes.result).cloneNode(true); b.style.marginTop = '12px';
      b.addEventListener('click', reset); runErr.parentNode.insertBefore(b, runErr.nextSibling);
    }
    function finishRun() {
      $$('.nwc__steps li', panes.run).forEach(function (li) { li.className = 'is-done'; });
      bar.style.setProperty('--sp', '100%'); bar.setAttribute('aria-valuenow', 100);
      setTimeout(function () { setState('gate'); track('check_gate', { domain: domain }); leadForm.focus(); }, 500);
    }

    /* ---- start ---- */
    urlForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var input = urlForm.elements.url, err = $('#' + input.getAttribute('aria-describedby'));
      domain = norm(input.value);
      input.setAttribute('aria-invalid', domain ? 'false' : 'true');
      showErr(err, domain ? '' : i18n.err_url);
      if (!domain) { input.focus(); return; }
      var btn = urlForm.querySelector('button[type="submit"]'); btn.disabled = true;
      waitForAltcha(8000).then(function (altcha) {
        return api('POST', cfg.start, { url: input.value.trim(), altcha: altcha, lang: cfg.lang, config: cfg.config || 0, website: urlForm.elements.website ? urlForm.elements.website.value : '', started: urlForm.elements.started.value });
      }).then(function (j) {
        btn.disabled = false;
        renewAltcha();
        if (!j || j.error) { showErr(err, errText(j)); return; }
        job = { id: j.id, token: j.token };
        domain = j.domain || domain;
        shotShown = false; shotTries = 0; pinsShown = {}; pinsEl.innerHTML = ''; shot.hidden = true; shot.removeAttribute('src');
        $$('[data-nwc-domain]').forEach(function (x) { x.textContent = domain; });
        urlEl.textContent = domain;
        showErr(runErr, '');
        $$('.nwc__steps li', panes.run).forEach(function (li) { li.className = ''; });
        track('check_start', { domain: domain, cached: !!j.cached });
        setState('run');
        setEta(j.eta || 90);
        renderProgress(j);
        if (j.status === 'done') { finishRun(); } else { pollTimer = setTimeout(poll, 300); }
      }).catch(function () { btn.disabled = false; renewAltcha(); showErr(err, i18n.err_generic); });
    });
    urlForm.elements.started.value = String(Date.now());

    /* ---- claim ---- */
    leadForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var first = null;
      ['firstname', 'lastname', 'email'].forEach(function (n) {
        var f = leadForm.elements[n], ok = f.checkValidity() && f.value.trim() !== '';
        f.setAttribute('aria-invalid', ok ? 'false' : 'true');
        var msg = document.getElementById(f.getAttribute('aria-describedby')); if (msg) msg.hidden = ok;
        if (!ok && !first) first = f;
      });
      if (first) { first.focus(); return; }
      var btn = leadForm.querySelector('button[type="submit"]'), leadErr = $('[data-nwc-lead-err]');
      btn.disabled = true; showErr(leadErr, '');
      var nl = leadForm.elements.newsletter && leadForm.elements.newsletter.checked ? 1 : 0;
      var ct = leadForm.elements.contact && leadForm.elements.contact.checked ? 1 : 0;
      api('POST', cfg.claim.replace(/0(\/claim)$/, job.id + '$1'), { token: job.token, firstname: leadForm.elements.firstname.value.trim(), lastname: leadForm.elements.lastname.value.trim(), email: leadForm.elements.email.value.trim(), newsletter: nl, contact: ct })
        .then(function (j) {
          btn.disabled = false;
          if (!j || j.error) { showErr(leadErr, errText(j)); return; }
          track('check_lead', { domain: domain, newsletter: nl, contact: ct });
          renderResult(j.result, j.mailSent, leadForm.elements.email.value.trim());
        }).catch(function () { btn.disabled = false; showErr(leadErr, i18n.err_generic); });
    });

    /* ---- result ---- */
    function renderResult(r, mailSent, email) {
      var box = $('[data-nwc-gradebox]');
      $('[data-nwc-grade]').textContent = r.grade || '–';
      $('[data-nwc-gradeword]').textContent = fmt(i18n.grade_label, r.grade) + (r.gradeWord ? ' · ' + r.gradeWord : '');
      var compact = panes.result.getAttribute('data-nwc-mode') !== 'full';
      // short view: at most two sentences of the summary, the PDF has the rest
      var summary = r.summary || r.verdict || '';
      if (compact) {
        // one sentence; a second one only if both together stay short
        var parts = summary.match(/[^.!?]+[.!?]+(\s|$)/g);
        if (parts && parts.length > 1) summary = (parts[0] + parts[1]).length <= 170 ? (parts[0] + parts[1]).trim() : parts[0].trim();
      }
      $('[data-nwc-verdict]').textContent = summary;
      box.className = 'nwc__grade is-' + (r.verdictKey || 'mid');
      var sent = $('[data-nwc-sent]');
      sent.textContent = mailSent ? fmt(i18n.mail_sent, email) : i18n.mail_failed;
      sent.className = 'nwc__sent' + (mailSent ? '' : ' is-failed');
      $$('.nwc__gauge').forEach(function (g) {
        var key = g.getAttribute('data-g'), v = r.scores ? r.scores[key] : null, b = g.querySelector('b');
        g.classList.remove('is-low', 'is-mid', 'is-na');
        if (v === null || v === undefined) { g.classList.add('is-na'); b.textContent = '–'; g.style.setProperty('--p', 0); return; }
        g.classList.toggle('is-low', v < 50); g.classList.toggle('is-mid', v >= 50 && v < 90);
        b.textContent = v;
        if (reduce) g.style.setProperty('--p', v); else { g.style.setProperty('--p', 0); requestAnimationFrame(function () { requestAnimationFrame(function () { g.style.setProperty('--p', v); }); }); }
      });
      var areas = $('[data-nwc-areas]'); if (areas) areas.innerHTML = '';
      (areas ? r.areas || [] : []).forEach(function (a) {
        var li = document.createElement('li');
        li.innerHTML = '<span class="nwc__chip is-' + (a.grade || 0) + '">' + (a.grade || '–') + '</span><div><b></b><span></span></div>';
        li.querySelector('b').textContent = a.label; li.querySelector('div span').textContent = a.summary;
        areas.appendChild(li);
      });
      var mw = $('[data-nwc-measures-wrap]'), ml = $('[data-nwc-measures]'); if (ml) ml.innerHTML = '';
      (ml ? r.measures || [] : []).forEach(function (m, idx) {
        var li = document.createElement('li');
        li.innerHTML = '<i aria-hidden="true">' + (idx + 1) + '</i><div><b></b><span></span><br><small></small></div>';
        li.querySelector('b').textContent = m.title; li.querySelector('span').textContent = m.text;
        li.querySelector('small').textContent = i18n['effort_' + (m.effort || 'small')] || '';
        ml.appendChild(li);
      });
      if (mw) mw.hidden = !(r.measures && r.measures.length);
      var sw = $('[data-nwc-strengths-wrap]'), sl = $('[data-nwc-strengths]'); if (sl) sl.innerHTML = '';
      (sl ? r.strengths || [] : []).forEach(function (s) {
        var li = document.createElement('li');
        li.innerHTML = '<i aria-hidden="true">✓</i><div><b></b><span></span></div>';
        li.querySelector('b').textContent = s.title; li.querySelector('span').textContent = s.text;
        sl.appendChild(li);
      });
      if (sw) sw.hidden = !(r.strengths && r.strengths.length);
      setState('result');
      track('check_result', { domain: domain, grade: r.grade });
      panes.result.focus({ preventScroll: true });
      // Prefill a contact form on the same page, if there is one.
      var cf = document.querySelector('form [name="firstname"], form [name="vorname"]');
      if (cf && cf.form && cf.form !== leadForm) {
        var map = { firstname: ['firstname', 'vorname'], lastname: ['lastname', 'nachname'], email: ['email'] };
        for (var k in map) map[k].forEach(function (n) { var f = cf.form.elements[n]; if (f && !f.value) f.value = leadForm.elements[k].value.trim(); });
      }
    }

    function reset() {
      clearTimeout(pollTimer); clearInterval(etaTimer); job = null;
      urlForm.elements.url.value = ''; urlForm.elements.started.value = String(Date.now());
      showErr(runErr, ''); urlEl.textContent = urlForm.elements.url.placeholder;
      var extra = panes.run.querySelector('[data-nwc-reset]'); if (extra) extra.remove();
      setState('idle'); urlForm.elements.url.focus();
    }
    $$('[data-nwc-reset]').forEach(function (b) { b.addEventListener('click', reset); });
    window.addEventListener('pagehide', function () { if (root.getAttribute('data-state') === 'gate') track('check_abandon', { domain: domain }); });
    setState('idle');
  }

  ready(function () { Array.prototype.forEach.call(document.querySelectorAll('[data-nwc]'), init); });
})();
