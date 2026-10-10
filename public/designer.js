/* NEO Website Check — visual designer for the e-mail and the PDF report (Contao back end).
   Vanilla JS, no dependencies. Reads its configuration (block schema, labels, endpoints)
   from the <script data-nwd-config> the widget renders, keeps the design as JSON in the
   hidden textarea and renders a live preview through the back-end preview endpoint. */
(function () {
  'use strict';

  function init(root) {
    var cfgEl = root.querySelector('[data-nwd-config]');
    var field = root.querySelector('.nwd-value');
    if (!cfgEl || !field) return;
    var cfg = JSON.parse(cfgEl.textContent);
    var ui = cfg.ui || {};
    var schema = cfg.schema;
    var kind = cfg.kind;
    var design = parse(field.value);
    var selected = null;
    var tab = 'blocks';
    var previewTimer = null;
    var previewReq = 0;
    var uid = 0;

    // ---------- helpers -------------------------------------------------
    function parse(v) {
      try { var d = JSON.parse(v || 'null'); if (d && d.blocks) return d; } catch (e) {}
      return null;
    }
    function el(tag, attrs, children) {
      var n = document.createElement(tag);
      if (attrs) Object.keys(attrs).forEach(function (k) {
        if (k === 'class') n.className = attrs[k];
        else if (k === 'text') n.textContent = attrs[k];
        else if (k === 'html') n.innerHTML = attrs[k];
        else if (k.indexOf('on') === 0) n.addEventListener(k.slice(2), attrs[k]);
        else n.setAttribute(k, attrs[k]);
      });
      (children || []).forEach(function (c) { if (c) n.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
      return n;
    }
    function t(key, fallback) { return ui[key] || fallback || key; }
    function newId() { return 'b' + Date.now().toString(36) + (uid++).toString(36); }
    function blockDef(type) { return schema.blocks[type]; }
    function defaults(type) {
      var props = {}, def = blockDef(type);
      Object.keys(def.props).forEach(function (k) { props[k] = def.props[k].default; });
      return props;
    }
    function language() {
      var sel = document.getElementById(cfg.languageField);
      return (sel && sel.value) || cfg.language || 'en';
    }
    function emptyDesign() {
      var theme = {};
      Object.keys(schema.theme).forEach(function (k) { theme[k] = schema.theme[k].default; });
      return { v: 1, theme: theme, blocks: [] };
    }
    function excerpt(block) {
      var p = block.props, keys = ['title', 'text', 'eyebrow', 'label', 'html', 'caption', 'tagline'];
      for (var i = 0; i < keys.length; i++) {
        if (typeof p[keys[i]] === 'string' && p[keys[i]].trim()) {
          var s = p[keys[i]].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
          return s.length > 40 ? s.slice(0, 38) + '…' : s;
        }
      }
      if (block.type === 'area_detail' && schema.areas[p.area]) return schema.areas[p.area];
      return '';
    }
    function commit() {
      field.value = JSON.stringify(design);
      schedulePreview();
      renderStructure();
    }
    function postJson(url, body) {
      return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': '*/*', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) });
    }
    function draft() { return { kind: kind, design: design, lang: language(), config: cfg.recordId }; }

    // ---------- layout --------------------------------------------------
    var shell = el('div', { class: 'nwd-shell' });
    var left = el('div', { class: 'nwd-pane' });
    var leftHead = el('div', { class: 'nwd-pane__head' });
    var leftBody = el('div', { class: 'nwd-pane__body' });
    var center = el('div', { class: 'nwd-pane' });
    var centerHead = el('div', { class: 'nwd-pane__head' }, [el('span', { text: t('preview') })]);
    var previewWrap = el('div', { class: 'nwd-preview' });
    var iframe = el('iframe', { title: t('preview'), sandbox: 'allow-scripts allow-same-origin' });
    var status = el('span', { class: 'nwd-preview__status' });
    var right = el('div', { class: 'nwd-pane' });
    var rightHead = el('div', { class: 'nwd-pane__head' }, [el('span', { text: t('properties') })]);
    var rightBody = el('div', { class: 'nwd-pane__body nwd-props' });

    var tabs = el('div', { class: 'nwd-tabs' });
    [['blocks', t('blocks')], ['structure', t('structure')], ['theme', t('theme')]].forEach(function (pair) {
      tabs.appendChild(el('button', { type: 'button', class: 'nwd-tab' + (tab === pair[0] ? ' is-active' : ''), text: pair[1], 'data-tab': pair[0], onclick: function () { tab = pair[0]; renderLeft(); } }));
    });
    leftHead.appendChild(tabs);
    left.appendChild(leftHead); left.appendChild(leftBody);
    previewWrap.appendChild(iframe); previewWrap.appendChild(status);
    center.appendChild(centerHead); center.appendChild(previewWrap);
    right.appendChild(rightHead); right.appendChild(rightBody);
    shell.appendChild(left); shell.appendChild(center); shell.appendChild(right);

    // toolbar above the shell: presets, preview buttons, twig export
    var toolbar = el('div', { class: 'nwd-toolbar' });
    var presetSel = el('select', {});
    Object.keys(cfg.presets || {}).forEach(function (name) { presetSel.appendChild(el('option', { value: name, text: cfg.presets[name] })); });
    toolbar.appendChild(presetSel);
    toolbar.appendChild(el('button', { type: 'button', class: 'nwd-btn', text: t('presets'), onclick: function () {
      if (design.blocks.length && !window.confirm(t('preset_confirm'))) return;
      loadPreset(presetSel.value);
    } }));
    toolbar.appendChild(el('button', { type: 'button', class: 'nwd-btn nwd-btn--primary', text: kind === 'pdf' ? t('pdf_preview') : t('mail_preview'), onclick: openFull }));
    if (cfg.isAdmin) {
      toolbar.appendChild(el('button', { type: 'button', class: 'nwd-btn', text: t('export_twig'), onclick: exportTwig }));
    }
    toolbar.appendChild(el('span', { class: 'nwd-hint', text: t('demo_hint') }));
    var warn = el('div', { class: 'nwd-warn', text: t('twig_active') });
    warn.hidden = true;
    root.appendChild(toolbar);
    root.appendChild(warn);
    root.appendChild(shell);
    checkTwigActive();

    // ---------- left pane -----------------------------------------------
    function renderLeft() {
      Array.prototype.forEach.call(tabs.children, function (b) { b.classList.toggle('is-active', b.getAttribute('data-tab') === tab); });
      leftBody.innerHTML = '';
      if (tab === 'blocks') renderPalette();
      else if (tab === 'structure') renderStructure();
      else renderThemeForm();
    }
    function renderPalette() {
      var groups = schema.groups;
      Object.keys(groups).forEach(function (g) {
        var items = Object.keys(schema.blocks).filter(function (type) { return schema.blocks[type].group === g; });
        if (!items.length) return;
        leftBody.appendChild(el('div', { class: 'nwd-group', text: groups[g] }));
        items.forEach(function (type) {
          var def = schema.blocks[type];
          var item = el('button', { type: 'button', class: 'nwd-palette__item', draggable: 'true', title: t('add'), onclick: function () { addBlock(type); } }, [def.label, def.desc ? el('small', { text: def.desc }) : null]);
          item.addEventListener('dragstart', function (e) { e.dataTransfer.setData('text/nwd-type', type); e.dataTransfer.effectAllowed = 'copy'; });
          leftBody.appendChild(item);
        });
      });
    }
    function renderStructure() {
      if (tab !== 'structure') return;
      leftBody.innerHTML = '';
      var list = el('ul', { class: 'nwd-struct' });
      if (!design.blocks.length) {
        leftBody.appendChild(el('div', { class: 'nwd-empty', text: t('empty') }));
      }
      design.blocks.forEach(function (block, index) {
        var def = blockDef(block.type);
        var item = el('li', { class: 'nwd-struct__item' + (selected === block.id ? ' is-selected' : '') + (block.type === 'page_break' ? ' is-break' : ''), draggable: 'true', 'data-id': block.id, onclick: function () { select(block.id, true); } }, [
          el('span', { class: 'nwd-grip', text: '⋮⋮', title: t('drag') }),
          el('span', { class: 'nwd-text' }, [
            el('span', { class: 'nwd-label', text: def ? def.label : block.type }),
            excerpt(block) ? el('span', { class: 'nwd-sub', text: excerpt(block) }) : null
          ])
        ]);
        item.addEventListener('dragstart', function (e) { e.dataTransfer.setData('text/nwd-id', block.id); e.dataTransfer.effectAllowed = 'move'; });
        item.addEventListener('dragover', function (e) { e.preventDefault(); item.classList.add('is-dragover'); });
        item.addEventListener('dragleave', function () { item.classList.remove('is-dragover'); });
        item.addEventListener('drop', function (e) { e.preventDefault(); item.classList.remove('is-dragover'); handleDrop(e, index); });
        list.appendChild(item);
      });
      var tail = el('li', { class: 'nwd-empty', text: '＋' });
      tail.addEventListener('dragover', function (e) { e.preventDefault(); });
      tail.addEventListener('drop', function (e) { e.preventDefault(); handleDrop(e, design.blocks.length); });
      list.appendChild(tail);
      leftBody.appendChild(list);
    }
    function handleDrop(e, index) {
      var type = e.dataTransfer.getData('text/nwd-type');
      var id = e.dataTransfer.getData('text/nwd-id');
      if (type && schema.blocks[type]) { insertBlock(type, index); return; }
      if (id) {
        var from = design.blocks.findIndex(function (b) { return b.id === id; });
        if (from < 0) return;
        var moved = design.blocks.splice(from, 1)[0];
        if (from < index) index--;
        design.blocks.splice(index, 0, moved);
        commit();
      }
    }
    function renderThemeForm() {
      leftBody.appendChild(el('div', { class: 'nwd-group', text: t('theme') }));
      var form = el('div', { class: 'nwd-props' });
      Object.keys(schema.theme).forEach(function (name) {
        form.appendChild(propEditor(schema.theme[name], design.theme[name], function (v) { design.theme[name] = v; commit(); }));
      });
      leftBody.appendChild(form);
    }

    // ---------- blocks ----------------------------------------------------
    function addBlock(type) {
      var index = design.blocks.length;
      if (selected) { var i = design.blocks.findIndex(function (b) { return b.id === selected; }); if (i >= 0) index = i + 1; }
      insertBlock(type, index);
    }
    function insertBlock(type, index) {
      var block = { id: newId(), type: type, props: defaults(type) };
      design.blocks.splice(index, 0, block);
      selected = block.id;
      tab = 'structure';
      renderLeft();
      renderProps();
      commit();
    }
    function select(id, scroll) {
      selected = id;
      renderStructure();
      renderProps();
      if (iframe.contentWindow) iframe.contentWindow.postMessage({ nwd: 'select', id: id, scroll: !!scroll }, '*');
    }
    function current() { return design.blocks.find(function (b) { return b.id === selected; }) || null; }
    function removeBlock(block) {
      design.blocks = design.blocks.filter(function (b) { return b.id !== block.id; });
      selected = null; renderProps(); commit();
    }
    function duplicateBlock(block) {
      var copy = JSON.parse(JSON.stringify(block)); copy.id = newId();
      var i = design.blocks.indexOf(block); design.blocks.splice(i + 1, 0, copy);
      selected = copy.id; renderProps(); commit();
    }
    function moveBlock(block, dir) {
      var i = design.blocks.indexOf(block), j = i + dir;
      if (j < 0 || j >= design.blocks.length) return;
      design.blocks.splice(i, 1); design.blocks.splice(j, 0, block); commit();
    }

    // ---------- properties pane ---------------------------------------------
    function renderProps() {
      rightBody.innerHTML = '';
      var block = current();
      if (!block) { rightBody.appendChild(el('p', { class: 'nwd-hint', text: t('select_hint') })); return; }
      var def = blockDef(block.type);
      rightBody.appendChild(el('p', { class: 'nwd-block-title', text: def.label }));
      if (def.desc) rightBody.appendChild(el('p', { class: 'nwd-block-desc', text: def.desc }));
      Object.keys(def.props).forEach(function (name) {
        var prop = def.props[name];
        rightBody.appendChild(propEditor(prop, block.props[name], function (v) { block.props[name] = v; commit(); }));
      });
      var actions = el('div', { class: 'nwd-actions' }, [
        el('button', { type: 'button', class: 'nwd-btn nwd-btn--sm', text: '↑ ' + t('up'), onclick: function () { moveBlock(block, -1); } }),
        el('button', { type: 'button', class: 'nwd-btn nwd-btn--sm', text: '↓ ' + t('down'), onclick: function () { moveBlock(block, 1); } }),
        el('button', { type: 'button', class: 'nwd-btn nwd-btn--sm', text: t('duplicate'), onclick: function () { duplicateBlock(block); } }),
        el('button', { type: 'button', class: 'nwd-btn nwd-btn--sm nwd-btn--danger', text: t('remove'), onclick: function () { removeBlock(block); } })
      ]);
      rightBody.appendChild(actions);
    }

    function propEditor(prop, value, onChange) {
      var wrap = el('div', {});
      var label = el('label', { text: prop.label });
      var input;
      switch (prop.type) {
        case 'bool':
          var cb = el('input', { type: 'checkbox' }); cb.checked = !!value;
          cb.addEventListener('change', function () { onChange(cb.checked); });
          wrap.appendChild(el('label', { class: 'nwd-check' }, [cb, prop.label]));
          return wrap;
        case 'select':
          input = el('select', {});
          Object.keys(prop.options).forEach(function (v) { input.appendChild(el('option', { value: v, text: prop.options[v] })); });
          input.value = value == null ? '' : String(value);
          input.addEventListener('change', function () { onChange(input.value); });
          break;
        case 'align':
          input = el('div', { class: 'nwd-align' });
          [['left', '⇤'], ['center', '↔'], ['right', '⇥']].forEach(function (pair) {
            var b = el('button', { type: 'button', text: pair[1], title: pair[0], class: value === pair[0] ? 'is-active' : '', onclick: function () {
              Array.prototype.forEach.call(input.children, function (c) { c.classList.remove('is-active'); }); b.classList.add('is-active'); onChange(pair[0]);
            } });
            input.appendChild(b);
          });
          break;
        case 'number':
          input = el('input', { type: 'number', min: prop.min != null ? prop.min : '', max: prop.max != null ? prop.max : '', step: '1' });
          input.value = value == null ? '' : value;
          input.addEventListener('input', debounce(function () { onChange(input.value === '' ? prop.default : Number(input.value)); }, 300));
          if (prop.unit) label.appendChild(el('span', { class: 'nwd-unit', text: prop.unit }));
          break;
        case 'color':
          input = el('div', { class: 'nwd-color' });
          var picker = el('input', { type: 'color' }); var txt = el('input', { type: 'text', placeholder: '#rrggbb' });
          picker.value = /^#[0-9a-f]{6}$/i.test(value || '') ? value : '#888888'; txt.value = value || '';
          picker.addEventListener('input', function () { txt.value = picker.value; onChange(picker.value); });
          txt.addEventListener('input', debounce(function () { var v = txt.value.trim(); if (v === '' || /^#?[0-9a-f]{6}$/i.test(v)) { if (v && v[0] !== '#') v = '#' + v; if (v) picker.value = v; onChange(v); } }, 300));
          var clear = el('button', { type: 'button', class: 'nwd-btn nwd-btn--sm', text: '×', title: t('remove'), onclick: function () { txt.value = ''; onChange(''); } });
          input.appendChild(picker); input.appendChild(txt); input.appendChild(clear);
          break;
        case 'textarea':
          input = el('textarea', {}); input.value = value || '';
          input.addEventListener('input', debounce(function () { onChange(input.value); }, 350));
          wrap.appendChild(label); wrap.appendChild(input); wrap.appendChild(placeholderBar(function (ph) { insertAtCursor(input, '{{' + ph + '}}'); onChange(input.value); }));
          return wrap;
        case 'richtext':
          wrap.appendChild(label); wrap.appendChild(richEditor(value || '', onChange));
          return wrap;
        case 'image':
          wrap.appendChild(label); wrap.appendChild(imageEditor(value || '', onChange));
          return wrap;
        default:
          input = el('input', { type: 'text' }); input.value = value == null ? '' : value;
          input.addEventListener('input', debounce(function () { onChange(input.value); }, 350));
          wrap.appendChild(label); wrap.appendChild(input);
          if (prop.type === 'text') wrap.appendChild(placeholderBar(function (ph) { insertAtCursor(input, '{{' + ph + '}}'); onChange(input.value); }));
          return wrap;
      }
      wrap.appendChild(label); wrap.appendChild(input);
      return wrap;
    }
    function placeholderBar(onPick) {
      var sel = el('select', { class: 'nwd-ph' });
      sel.appendChild(el('option', { value: '', text: '{{…}} ' + t('insert_placeholder') }));
      (cfg.placeholders || []).forEach(function (p) { sel.appendChild(el('option', { value: p, text: '{{' + p + '}}' })); });
      sel.addEventListener('change', function () { if (sel.value) { onPick(sel.value); sel.value = ''; } });
      sel.style.marginTop = '3px'; sel.style.fontSize = '11px';
      return sel;
    }
    function insertAtCursor(input, text) {
      var s = input.selectionStart || input.value.length, e = input.selectionEnd || s;
      input.value = input.value.slice(0, s) + text + input.value.slice(e);
      input.focus(); input.selectionStart = input.selectionEnd = s + text.length;
    }
    function richEditor(html, onChange) {
      var box = el('div', { class: 'nwd-rich' });
      var bar = el('div', { class: 'nwd-rich__bar' });
      var edit = el('div', { class: 'nwd-rich__edit', contenteditable: 'true', html: html });
      function cmd(name, arg) { edit.focus(); document.execCommand(name, false, arg || null); push(); }
      var push = debounce(function () { onChange(edit.innerHTML); }, 350);
      bar.appendChild(el('button', { type: 'button', html: '<b>B</b>', title: t('bold'), onmousedown: function (e) { e.preventDefault(); cmd('bold'); } }));
      bar.appendChild(el('button', { type: 'button', html: '<i>I</i>', title: t('italic'), onmousedown: function (e) { e.preventDefault(); cmd('italic'); } }));
      bar.appendChild(el('button', { type: 'button', text: '• ' + t('list'), onmousedown: function (e) { e.preventDefault(); cmd('insertUnorderedList'); } }));
      bar.appendChild(el('button', { type: 'button', text: t('link'), onmousedown: function (e) { e.preventDefault(); var url = window.prompt(t('link_prompt'), 'https://'); if (url) cmd('createLink', url); } }));
      var ph = placeholderBar(function (p) { edit.focus(); document.execCommand('insertText', false, '{{' + p + '}}'); push(); });
      bar.appendChild(ph);
      edit.addEventListener('input', push);
      edit.addEventListener('paste', function (e) { e.preventDefault(); var txt = (e.clipboardData || window.clipboardData).getData('text/plain'); document.execCommand('insertText', false, txt); });
      box.appendChild(bar); box.appendChild(edit);
      return box;
    }
    function imageEditor(path, onChange) {
      var box = el('div', { class: 'nwd-image' });
      var thumb = el('div', { class: 'nwd-image__thumb' });
      var pathEl = el('div', { class: 'nwd-image__path' });
      function show(p) {
        thumb.innerHTML = ''; pathEl.textContent = p || '';
        if (p) thumb.appendChild(el('img', { src: (p.indexOf('files/') === 0 ? '/' : '') + p, alt: '' })); else thumb.appendChild(el('span', { class: 'nwd-hint', text: '–' }));
      }
      show(path);
      var fileInput = el('input', { type: 'file', accept: 'image/*' }); fileInput.hidden = true;
      fileInput.addEventListener('change', function () {
        if (!fileInput.files[0]) return;
        var fd = new FormData(); fd.append('file', fileInput.files[0]);
        fetch(cfg.endpoints.upload, { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
          .then(function (r) { return r.json(); })
          .then(function (j) { if (j.path) { show(j.path); onChange(j.path); } else window.alert(t('error')); })
          .catch(function () { window.alert(t('error')); });
        fileInput.value = '';
      });
      var row = el('div', { class: 'nwd-row' }, [
        el('button', { type: 'button', class: 'nwd-btn nwd-btn--sm', text: t('choose_image'), onclick: function () { pickFile(function (p) { show(p); onChange(p); }); } }),
        el('button', { type: 'button', class: 'nwd-btn nwd-btn--sm', text: t('upload_image'), onclick: function () { fileInput.click(); } }),
        el('button', { type: 'button', class: 'nwd-btn nwd-btn--sm nwd-btn--danger', text: t('remove_image'), onclick: function () { show(''); onChange(''); } })
      ]);
      box.appendChild(thumb); box.appendChild(pathEl); box.appendChild(row); box.appendChild(fileInput);
      return box;
    }
    function pickFile(cb) {
      if (!window.Backend || !Backend.openModalSelector) { var p = window.prompt('files/…'); if (p) cb(p); return; }
      Backend.openModalSelector({
        id: 'tl_listing',
        title: t('choose_image'),
        url: cfg.pickerUrl,
        callback: function (table, value) { var v = Array.isArray(value) ? value[0] : value; if (v) cb(String(v)); }
      });
    }

    // ---------- preview ---------------------------------------------------
    function schedulePreview() {
      clearTimeout(previewTimer);
      previewTimer = setTimeout(renderPreview, 450);
    }
    function renderPreview() {
      var req = ++previewReq;
      status.textContent = t('loading'); status.className = 'nwd-preview__status is-on';
      postJson(cfg.endpoints.preview, draft())
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
        .then(function (html) {
          if (req !== previewReq) return;
          iframe.srcdoc = html;
          status.className = 'nwd-preview__status';
        })
        .catch(function () { if (req === previewReq) { status.textContent = t('error'); status.className = 'nwd-preview__status is-on is-error'; } });
    }
    window.addEventListener('message', function (e) {
      if (e.source !== iframe.contentWindow || !e.data || !e.data.nwd) return;
      if (e.data.nwd === 'select') { selected = e.data.id; renderStructure(); renderProps(); }
      if (e.data.nwd === 'ready' && selected) iframe.contentWindow.postMessage({ nwd: 'select', id: selected }, '*');
    });
    function openFull() {
      var url = kind === 'pdf' ? cfg.endpoints.pdf : cfg.endpoints.preview;
      postJson(url, draft()).then(function (r) { return r.blob(); }).then(function (blob) {
        var u = URL.createObjectURL(blob); window.open(u, '_blank'); setTimeout(function () { URL.revokeObjectURL(u); }, 60000);
      }).catch(function () { window.alert(t('error')); });
    }
    function loadPreset(name) {
      fetch(cfg.endpoints.preset + '&kind=' + encodeURIComponent(kind) + '&name=' + encodeURIComponent(name) + '&lang=' + encodeURIComponent(language()), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (!d || !d.blocks) throw new Error('preset'); design = d; selected = null; renderLeft(); renderProps(); commit(); })
        .catch(function () { window.alert(t('error')); });
    }

    // ---------- Twig export (admins) ------------------------------------
    function twigTextarea() { return document.getElementById(cfg.twigField); }
    function checkTwigActive() {
      var ta = twigTextarea();
      warn.hidden = !(ta && ta.value.trim());
    }
    function exportTwig() {
      var ta = twigTextarea();
      if (!ta) return;
      if (ta.value.trim() && !window.confirm(t('export_confirm'))) return;
      postJson(cfg.endpoints.twig, draft()).then(function (r) { return r.text(); }).then(function (src) {
        ta.value = src;
        try { if (window.ace) { var ed = ace.edit(document.getElementById(cfg.twigField + '_div') || document.getElementById(cfg.twigField.replace('ctrl_', '') + '_div')); if (ed) ed.setValue(src, -1); } } catch (e) {}
        checkTwigActive();
        var legend = ta.closest('fieldset'); if (legend) { legend.classList.remove('collapsed'); legend.scrollIntoView({ behavior: 'smooth' }); }
      }).catch(function () { window.alert(t('error')); });
    }
    var twigTa = twigTextarea();
    if (twigTa) twigTa.addEventListener('input', checkTwigActive);

    function debounce(fn, ms) { var h; return function () { var a = arguments, s = this; clearTimeout(h); h = setTimeout(function () { fn.apply(s, a); }, ms); }; }

    // ---------- boot -----------------------------------------------------
    if (!design) {
      design = emptyDesign();
      renderLeft(); renderProps();
      loadPreset('standard');
    } else {
      renderLeft(); renderProps(); commit();
    }
  }

  function boot() { document.querySelectorAll('[data-nwd]').forEach(init); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
