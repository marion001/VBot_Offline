(() => {
  'use strict';
  const hiddenClass = 'page-search-hidden';
  const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('vi').replace(/đ/g, 'd');
  const itemText = item => {
    const values = [...item.querySelectorAll('input:not([type="hidden"]), textarea, select')].map(field => {
      if (field.type === 'checkbox' || field.type === 'radio') return field.checked ? 'đang bật đã chọn có' : 'đang tắt chưa chọn';
      return field.value || '';
    });
    return `${item.innerText || item.textContent || ''} ${values.join(' ')}`;
  };
  function init(toolbar) {
    const input = toolbar.querySelector('.page-search-input');
    const clear = toolbar.querySelector('.page-search-clear');
    const navigation = toolbar.querySelector('.page-search-navigation');
    const empty = toolbar.querySelector('.page-search-empty');
    const selectors = (toolbar.dataset.searchItems || '').split(',').map(x => x.trim()).filter(Boolean);
    const sections = (toolbar.dataset.searchSections || '').split(',').map(x => x.trim()).filter(Boolean);
    const getItems = () => [...new Set(selectors.flatMap(selector => { try { return [...document.querySelectorAll(selector)]; } catch (_) { return []; } }))];
    const getSections = () => sections.flatMap(selector => { try { return [...document.querySelectorAll(selector)]; } catch (_) { return []; } });
    function rebuildNavigation() {
      const selected = navigation.value;
      navigation.replaceChildren(new Option('Đi tới mục hoặc khu vực...', ''));
      getSections().forEach(section => {
        if (!section.id) section.id = `page-search-section-${Math.random().toString(36).slice(2, 9)}`;
        const title = section.dataset.searchLabel || section.querySelector('h1,h2,h3,h4,h5,.card-title')?.textContent?.trim() || 'Khu vực';
        navigation.add(new Option(title, section.id));
      });
      getItems().forEach((item, index) => {
        if (!item.id) item.id = `page-search-item-${index}-${Math.random().toString(36).slice(2, 7)}`;
        const title = item.dataset.searchTitle || item.querySelector('h1,h2,h3,h4,h5,.tuya-device-card-head,.fw-bold,.ir-name')?.textContent?.trim() || item.innerText?.trim().split('\n')[0] || `Mục ${index + 1}`;
        navigation.add(new Option(title.replace(/\s+/g, ' ').slice(0, 100), item.id));
      });
      if ([...navigation.options].some(option => option.value === selected)) navigation.value = selected;
    }
    function applySearch() {
      const query = normalize(input.value.trim());
      let matches = 0;
      getItems().forEach(item => {
        const match = !query || normalize(itemText(item)).includes(query);
        item.classList.toggle(hiddenClass, !match);
        if (match) matches++;
      });
      empty.style.display = query && matches === 0 ? '' : 'none';
      rebuildNavigation();
    }
    input.addEventListener('input', applySearch);
    clear.addEventListener('click', () => { input.value = ''; applySearch(); input.focus(); });
    navigation.addEventListener('change', () => {
      const targetId = navigation.value;
      if (input.value) { input.value = ''; applySearch(); }
      const target = targetId && document.getElementById(targetId);
      if (!target) return;
      target.classList.remove(hiddenClass);
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      target.querySelector('input,textarea,select,button,a')?.focus({ preventScroll: true });
    });
    rebuildNavigation();
    applySearch();
    const observer = new MutationObserver(() => applySearch());
    (toolbar.dataset.searchWatch || '').split(',').map(x => x.trim()).filter(Boolean).forEach(selector => {
      try { document.querySelectorAll(selector).forEach(node => observer.observe(node, { childList: true, subtree: true })); } catch (_) {}
    });
  }
  const style = document.createElement('style');
  style.textContent = `.page-search-toolbar{position:sticky;top:64px;z-index:900;background:rgba(244,248,255,.97);box-shadow:0 .25rem .75rem rgba(18,38,63,.08);backdrop-filter:blur(6px)}.${hiddenClass}{display:none!important}@media(max-width:991.98px){.page-search-toolbar{top:60px}}`;
  document.head.append(style);
  document.querySelectorAll('.page-search-toolbar').forEach(init);
})();
