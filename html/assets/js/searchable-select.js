(function () {
  'use strict';

  var active = null;
  function normalize(value) {
    return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();
  }

  function close() {
    if (!active) return;
    active.panel.remove();
    active.button.setAttribute('aria-expanded', 'false');
    active = null;
  }

  function position(item) {
    if (!item || !item.panel.isConnected) return;
    var rect = item.button.getBoundingClientRect();
    var width = Math.max(rect.width, 260);
    item.panel.style.width = Math.min(width, window.innerWidth - 16) + 'px';
    var left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8));
    var maxHeight = Math.min(340, window.innerHeight - 16);
    var below = window.innerHeight - rect.bottom;
    var top = below >= 180 || below >= rect.top ? rect.bottom + 4 : Math.max(8, rect.top - maxHeight - 4);
    item.panel.style.left = left + 'px';
    item.panel.style.top = Math.max(8, Math.min(top, window.innerHeight - 80)) + 'px';
    item.panel.style.maxHeight = maxHeight + 'px';
  }

  function render(item) {
    var query = normalize(item.search.value).trim().split(/\s+/).filter(Boolean);
    var selected = item.select.value;
    item.list.replaceChildren();
    var choices = Object.create(null);
    Array.prototype.forEach.call(item.select.options, function (option) {
      if (!option.disabled && !option.hidden) choices[option.value] = option.textContent;
    });
    Object.keys(item.select.__vbotExtraOptions || {}).forEach(function (value) {
      choices[value] = item.select.__vbotExtraOptions[value];
    });
    var shown = 0, truncated = false;
    var hasHomeAssistantChoices = Object.keys(choices).some(function (value) {
      return value.indexOf('hass:') === 0 || value.indexOf('toggle:hass:') === 0;
    });
    Object.keys(choices).forEach(function (value) {
      var label = choices[value];
      var haystack = normalize(label + ' ' + value);
      if (query.length && !query.every(function (word) { return haystack.includes(word); })) return;
      if (hasHomeAssistantChoices && shown >= 250) { truncated = true; return; }
      var choice = document.createElement('button');
      choice.type = 'button';
      choice.className = 'vbot-searchable-choice' + (value === selected ? ' selected' : '');
      choice.textContent = label;
      choice.addEventListener('click', function () {
        var nativeOption = Array.prototype.find.call(item.select.options, function (candidate) { return candidate.value === value; });
        if (!nativeOption) item.select.add(new Option(label, value));
        else nativeOption.textContent = label;
        item.select.value = value;
        item.select.dispatchEvent(new Event('change', {bubbles: true}));
        item.button.textContent = label;
        close();
        item.button.focus();
      });
      item.list.appendChild(choice);
      shown++;
    });
    if (!shown) {
      var empty = document.createElement('div');
      empty.className = 'vbot-searchable-empty';
      empty.textContent = 'Không tìm thấy lựa chọn phù hợp';
      item.list.appendChild(empty);
    }
    if (truncated && hasHomeAssistantChoices) {
      var hint = document.createElement('div');
      hint.className = 'vbot-searchable-empty';
      hint.textContent = 'Home Assistant còn nhiều kết quả. Nhập thêm từ khóa để thu hẹp danh sách.';
      item.list.appendChild(hint);
    }
  }

  function open(item) {
    if (active === item) { close(); return; }
    close();
    item.search.value = '';
    render(item);
    var container = item.select.dataset.searchContainer ? document.querySelector(item.select.dataset.searchContainer) : null;
    (container || document.body).appendChild(item.panel);
    item.button.setAttribute('aria-expanded', 'true');
    active = item;
    position(item);
    item.search.focus();
  }

  function bind(select) {
    if (select.dataset.searchableReady) return;
    select.dataset.searchableReady = '1';
    var button = document.createElement('button');
    button.type = 'button';
    var visualClasses = Array.prototype.filter.call(select.classList, function (name) {
      return /^(form-select|form-control|form-select-sm|form-select-lg|border-[a-z]+|w-\d+)$/.test(name);
    });
    button.className = visualClasses.join(' ') + ' vbot-searchable-trigger';
    if (select.id) button.id = select.id + '-trigger';
    button.textContent = select.options[select.selectedIndex] ? select.options[select.selectedIndex].textContent : 'Chọn...';
    button.setAttribute('aria-haspopup', 'listbox');
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-label', select.getAttribute('aria-label') || 'Chọn thao tác');
    select.parentNode.insertBefore(button, select);
    select.classList.add('vbot-searchable-native');

    var panel = document.createElement('div');
    panel.className = 'vbot-searchable-panel';
    panel.setAttribute('role', 'dialog');
    var search = document.createElement('input');
    search.type = 'search';
    search.className = 'form-control vbot-searchable-input';
    search.placeholder = select.dataset.searchPlaceholder || 'Tìm chức năng hoặc thao tác...';
    search.setAttribute('aria-label', search.placeholder);
    search.autocomplete = 'off';
    var list = document.createElement('div');
    list.className = 'vbot-searchable-list';
    list.setAttribute('role', 'listbox');
    panel.append(search, list);
    var item = {select: select, button: button, panel: panel, search: search, list: list};

    button.addEventListener('click', function () { open(item); });
    search.addEventListener('input', function () { render(item); });
    select.addEventListener('change', function () {
      var option = select.options[select.selectedIndex];
      if (option) button.textContent = option.textContent;
      if (active === item) render(item);
    });
    search.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') { close(); button.focus(); }
      if (event.key === 'Enter') {
        var first = list.querySelector('.vbot-searchable-choice');
        if (first) { event.preventDefault(); first.click(); }
      }
    });
    new MutationObserver(function () {
      var option = select.options[select.selectedIndex];
      if (option) button.textContent = option.textContent;
      if (active === item) render(item);
    }).observe(select, {childList: true, subtree: true, attributes: true, attributeFilter: ['hidden', 'disabled']});
  }

  function init(root) {
    var scope = root || document;
    if (scope.matches && scope.matches('select[data-searchable-select]')) bind(scope);
    scope.querySelectorAll('select[data-searchable-select]').forEach(bind);
  }

  function setOptions(select, options) {
    if (!select) return;
    select.__vbotExtraOptions = options && typeof options === 'object' ? options : {};
    var selected = select.value;
    if (selected && Object.prototype.hasOwnProperty.call(select.__vbotExtraOptions, selected)) {
      var nativeOption = Array.prototype.find.call(select.options, function (option) { return option.value === selected; });
      if (nativeOption) nativeOption.textContent = select.__vbotExtraOptions[selected];
      var button = select.previousElementSibling;
      if (button && button.classList.contains('vbot-searchable-trigger')) button.textContent = select.__vbotExtraOptions[selected];
    }
    if (active && active.select === select) render(active);
  }

  document.addEventListener('click', function (event) {
    if (active && !active.panel.contains(event.target) && !active.button.contains(event.target)) close();
  });
  document.addEventListener('hidden.bs.modal', function (event) {
    if (active && event.target.contains(active.select)) close();
  });
  window.addEventListener('resize', function () { position(active); });
  window.addEventListener('scroll', function () { position(active); }, true);
  document.addEventListener('DOMContentLoaded', function () { init(document); });
  window.VBotSearchableSelect = {init: init, setOptions: setOptions};
}());
