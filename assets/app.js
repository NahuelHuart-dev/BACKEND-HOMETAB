import './styles/app.css';

const hexAlpha = (hex, alpha) => hex + alpha.toString(16).padStart(2, '0').toUpperCase();

function hexToRgb(hex) {
  const value = hex.replace('#', '');
  return [
    parseInt(value.slice(0, 2), 16),
    parseInt(value.slice(2, 4), 16),
    parseInt(value.slice(4, 6), 16),
  ];
}

function accentVariants(raw) {
  let h = (raw || '#ea580c').trim();
  if (!h.startsWith('#')) h = '#' + h;
  if (!/^#[0-9a-fA-F]{6}$/.test(h)) h = '#EA580C';
  h = h.toUpperCase();
  const [r, g, b] = hexToRgb(h);
  const luminance = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
  const root = document.documentElement;
  root.style.setProperty('--color-accent', h);
  root.style.setProperty('--color-accent-rgb', `${r}, ${g}, ${b}`);
  root.style.setProperty('--color-on-accent', luminance > 0.62 ? '#1c1917' : '#ffffff');
  root.style.setProperty('--accent-bg-subtle', hexAlpha(h, 13));
  root.style.setProperty('--accent-bg', hexAlpha(h, 21));
  root.style.setProperty('--accent-bg-icon', hexAlpha(h, 32));
  root.style.setProperty('--accent-bg-btn', hexAlpha(h, 48));
  root.style.setProperty('--accent-border', hexAlpha(h, 64));
  root.style.setProperty('--accent-border-strong', hexAlpha(h, 96));
  root.style.setProperty('--accent-bg-medium', hexAlpha(h, 153));
  root.style.setProperty('--accent-bg-high', hexAlpha(h, 204));
}

function getCookie(name) {
  const m = document.cookie.match(new RegExp('(?:^|; )' + encodeURIComponent(name) + '=([^;]*)'));
  return m ? decodeURIComponent(m[1]) : null;
}

function setCookie(name, value, days = 365) {
  const d = new Date();
  d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
  document.cookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)};path=/;expires=${d.toUTCString()};SameSite=Lax`;
}

function readConfig() {
  try {
    return JSON.parse(localStorage.getItem('hometab_config') || '{}') || {};
  } catch {
    return {};
  }
}

function writeConfig(patch) {
  const config = {
    darkMode: true,
    tipoPapel: 'cuadricula',
    colorAcento: '#ea580c',
    cursor: 'default',
    ...readConfig(),
    ...patch,
  };
  localStorage.setItem('hometab_config', JSON.stringify(config));
  localStorage.setItem('hometab_cookies', 'true');
  return config;
}

function applyStoredTheme() {
  const config = readConfig();
  const theme = typeof config.darkMode === 'boolean' ? (config.darkMode ? 'dark' : 'light') : (getCookie('ht_theme') === 'light' ? 'light' : 'dark');
  const accent = config.colorAcento || getCookie('ht_accent') || '#EA580C';
  document.documentElement.setAttribute('data-theme', theme);
  document.documentElement.setAttribute('data-paper', config.tipoPapel || 'cuadricula');
  accentVariants(accent);
  document.querySelectorAll('[data-paper]').forEach((button) => button.classList.toggle('selected', button.dataset.paper === (config.tipoPapel || 'cuadricula')));
  document.querySelectorAll('[data-accent]').forEach((button) => button.classList.toggle('selected', button.dataset.accent.toLowerCase() === accent.toLowerCase()));
  document.querySelectorAll('[data-accent-input]').forEach((input) => { input.value = accent; });
  const toggle = document.getElementById('ht-theme-toggle')?.querySelector('.pi');
  if (toggle) toggle.className = `pi ${theme === 'dark' ? 'pi-sun' : 'pi-moon'}`;
}

function initChatWidget() {
  const config = window.HomeTabChat;
  const widget = document.querySelector('[data-chat-widget]');
  if (!config || !widget) return;

  const toggle = widget.querySelector('[data-chat-toggle]');
  const panel = widget.querySelector('[data-chat-panel]');
  const close = widget.querySelector('[data-chat-close]');
  const back = widget.querySelector('[data-chat-back]');
  const housesWrap = widget.querySelector('[data-chat-houses]');
  const room = widget.querySelector('[data-chat-room]');
  const messagesWrap = widget.querySelector('[data-chat-messages]');
  const title = widget.querySelector('[data-chat-title]');
  const subtitle = widget.querySelector('[data-chat-subtitle]');
  const form = widget.querySelector('[data-chat-form]');
  const input = widget.querySelector('[data-chat-input]');
  const empty = widget.querySelector('[data-chat-empty]');
  const typing = widget.querySelector('[data-chat-typing]');
  const dragHandle = widget.querySelector('[data-chat-drag]');
  let currentHouse = null;
  let pollTimer = null;
  let typingTimer = null;
  let sending = false;
  let lastMessageId = 0;
  const renderedIds = new Set();

  const endpointForMessages = (houseId, params = '') => config.endpoints.messages.replace('__HOUSE_ID__', encodeURIComponent(houseId)) + params;
  const endpointForTyping = (houseId) => config.endpoints.typing.replace('__HOUSE_ID__', encodeURIComponent(houseId));
  const headers = (json = false) => ({
    ...(json ? { 'Content-Type': 'application/json' } : {}),
    ...(config.csrfToken ? { 'X-CSRF-Token': config.csrfToken } : {}),
  });

  const formatTime = (raw) => {
    if (!raw) return '';
    return new Intl.DateTimeFormat('es', { hour: '2-digit', minute: '2-digit' }).format(new Date(raw));
  };

  const showEmpty = (message) => {
    if (!empty) return;
    empty.textContent = message;
    empty.hidden = false;
  };

  const hideEmpty = () => {
    if (empty) empty.hidden = true;
  };

  const setView = (view) => {
    const inRoom = view === 'room';
    housesWrap.hidden = inRoom;
    room.hidden = !inRoom;
    back.hidden = !inRoom;
    title.textContent = inRoom && currentHouse ? currentHouse.name : 'Chats';
    subtitle.textContent = inRoom ? 'Chat grupal de la casa' : 'Tus casas';
    hideEmpty();
  };

  const renderMessage = (message) => {
    if (!message?.id || renderedIds.has(Number(message.id))) return;
    renderedIds.add(Number(message.id));
    const own = Number(message.sender?.id) === Number(config.currentUserId);
    const article = document.createElement('article');
    article.className = `ht-chat-message ${own ? 'is-own' : 'is-other'}`;
    article.dataset.messageId = message.id;
    article.innerHTML = `
      <div class="ht-chat-bubble">
        <strong>${message.sender?.fullName || 'Usuario'}</strong>
        <p></p>
        <time>${formatTime(message.createdAt)}</time>
      </div>
    `;
    article.querySelector('p').textContent = message.content;
    messagesWrap.appendChild(article);
    lastMessageId = Math.max(lastMessageId, Number(message.id || 0));
  };

  const scrollMessages = () => {
    messagesWrap.scrollTop = messagesWrap.scrollHeight;
  };

  const loadMessages = async (after = false) => {
    if (!currentHouse) return;
    const url = endpointForMessages(currentHouse.id, after && lastMessageId ? `?afterId=${lastMessageId}` : '?limit=50');
    const response = await fetch(url, { headers: headers() });
    if (!response.ok) throw new Error('No se pudo cargar el chat.');
    const data = await response.json();
    if (!after) {
      messagesWrap.innerHTML = '';
      lastMessageId = 0;
      renderedIds.clear();
    }
    data.forEach(renderMessage);
    if (!after || data.length) scrollMessages();
    if (data.length > 0) hideEmpty();
    if (!after && data.length === 0) showEmpty('Todavía no hay mensajes en esta casa.');
  };

  const openHouse = async (house) => {
    currentHouse = house;
    setView('room');
    messagesWrap.innerHTML = '';
    showEmpty('Cargando mensajes...');
    await loadMessages(false);
    clearInterval(pollTimer);
    clearInterval(typingTimer);
    pollTimer = setInterval(() => loadMessages(true).catch(() => {}), 4000);
    typingTimer = setInterval(() => loadTyping().catch(() => {}), 2500);
    input?.focus();
  };

  const renderHouses = (houses) => {
    housesWrap.innerHTML = '';
    if (!houses.length) {
      showEmpty('No perteneces a ninguna casa con chat disponible.');
      return;
    }
    houses.forEach((house) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'ht-chat-house';
      button.innerHTML = `
        <span class="ht-chat-house__icon pi pi-home"></span>
        <span><strong></strong><small>${house.messageCount || 0} mensajes</small></span>
        <span class="pi pi-chevron-right"></span>
      `;
      button.querySelector('strong').textContent = house.name;
      button.addEventListener('click', () => openHouse(house).catch((error) => showEmpty(error.message)));
      housesWrap.appendChild(button);
    });
  };

  const loadTyping = async () => {
    if (!currentHouse || !typing) return;
    const response = await fetch(endpointForTyping(currentHouse.id), { headers: headers() });
    if (!response.ok) return;
    const data = await response.json();
    const names = (data.typing || []).map((user) => user.fullName).filter(Boolean);
    typing.hidden = names.length === 0;
    typing.textContent = names.length === 1 ? `${names[0]} está escribiendo...` : `${names.join(', ')} están escribiendo...`;
  };

  const markTyping = (() => {
    let lastSent = 0;
    return () => {
      if (!currentHouse) return;
      const now = Date.now();
      if (now - lastSent < 2000) return;
      lastSent = now;
      fetch(endpointForTyping(currentHouse.id), { method: 'POST', headers: headers(true), body: '{}' }).catch(() => {});
    };
  })();

  const makePanelDraggable = () => {
    if (!dragHandle || !panel) return;
    const applyFixedPosition = (left, top) => {
      const maxLeft = window.innerWidth - panel.offsetWidth - 12;
      const maxTop = window.innerHeight - panel.offsetHeight - 12;
      panel.style.position = 'fixed';
      panel.style.left = `${Math.max(12, Math.min(maxLeft, left))}px`;
      panel.style.top = `${Math.max(12, Math.min(maxTop, top))}px`;
      panel.style.right = 'auto';
      panel.style.bottom = 'auto';
    };
    const saved = localStorage.getItem('hometab_chat_panel_pos');
    if (saved) {
      try {
        const pos = JSON.parse(saved);
        if (typeof pos.left === 'number' && typeof pos.top === 'number') {
          applyFixedPosition(pos.left, pos.top);
        } else {
          localStorage.removeItem('hometab_chat_panel_pos');
        }
      } catch {
        localStorage.removeItem('hometab_chat_panel_pos');
      }
    }
    let start = null;
    dragHandle.addEventListener('pointerdown', (event) => {
      if (event.target.closest('button')) return;
      const rect = panel.getBoundingClientRect();
      start = { x: event.clientX, y: event.clientY, left: rect.left, top: rect.top };
      dragHandle.setPointerCapture(event.pointerId);
    });
    dragHandle.addEventListener('pointermove', (event) => {
      if (!start) return;
      applyFixedPosition(start.left + event.clientX - start.x, start.top + event.clientY - start.y);
    });
    dragHandle.addEventListener('pointerup', () => {
      if (!start) return;
      localStorage.setItem('hometab_chat_panel_pos', JSON.stringify({ left: panel.offsetLeft, top: panel.offsetTop }));
      start = null;
    });
  };

  makePanelDraggable();

  const loadHouses = async () => {
    housesWrap.innerHTML = '';
    showEmpty('Cargando tus casas...');
    const response = await fetch(config.endpoints.households, { headers: headers() });
    if (!response.ok) throw new Error('No se pudieron cargar tus chats.');
    const houses = await response.json();
    hideEmpty();
    renderHouses(houses);
  };

  toggle?.addEventListener('click', () => {
    const opening = panel.hidden;
    panel.hidden = !opening;
    toggle.classList.toggle('is-active', opening);
    if (opening) {
      setView('houses');
      loadHouses().catch((error) => showEmpty(error.message));
    } else {
      clearInterval(pollTimer);
      clearInterval(typingTimer);
    }
  });

  close?.addEventListener('click', () => {
    panel.hidden = true;
    toggle.classList.remove('is-active');
    clearInterval(pollTimer);
    clearInterval(typingTimer);
  });

  back?.addEventListener('click', () => {
    currentHouse = null;
    clearInterval(pollTimer);
    clearInterval(typingTimer);
    setView('houses');
    loadHouses().catch((error) => showEmpty(error.message));
  });

  widget.querySelectorAll('[data-emoji]').forEach((button) => {
    button.addEventListener('click', () => {
      input.value += button.dataset.emoji;
      input.focus();
    });
  });

  input?.addEventListener('input', () => {
    input.style.height = 'auto';
    input.style.height = `${Math.min(input.scrollHeight, 120)}px`;
    markTyping();
  });

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const content = input.value.trim();
    if (!currentHouse || !content || sending) return;
    sending = true;
    input.value = '';
    input.style.height = 'auto';
    try {
      const response = await fetch(endpointForMessages(currentHouse.id), {
        method: 'POST',
        headers: headers(true),
        body: JSON.stringify({ content }),
      });
      if (!response.ok) {
        showEmpty('No se pudo enviar el mensaje.');
        input.value = content;
        return;
      }
      hideEmpty();
      const payload = await response.json();
      renderMessage(payload.chatMessage);
      scrollMessages();
    } catch {
      showEmpty('No se pudo enviar el mensaje.');
      input.value = content;
    } finally {
      sending = false;
    }
  });
}

function initAdminComponents() {
  document.querySelectorAll('.ht-admin-table table').forEach((table) => {
    const labels = Array.from(table.querySelectorAll('thead th')).map((th) => th.textContent.trim());
    table.querySelectorAll('tbody tr').forEach((row) => {
      Array.from(row.children).forEach((cell, index) => {
        if (labels[index] && !cell.dataset.label) {
          cell.dataset.label = labels[index];
        }
      });
    });
  });
}

document.documentElement.style.setProperty('color-scheme', 'dark light');

applyStoredTheme();

window.addEventListener('DOMContentLoaded', () => {
  initAdminComponents();
  initChatWidget();

  document.getElementById('ht-theme-toggle')?.addEventListener('click', () => {
    const current = readConfig();
    const darkMode = !(current.darkMode ?? true);
    setCookie('ht_theme', darkMode ? 'dark' : 'light');
    writeConfig({ darkMode });
    applyStoredTheme();
  });

  const drawer = document.getElementById('ht-config-drawer');
  const configButton = document.getElementById('ht-config-open');
  const syncConfigButton = () => configButton?.classList.toggle('is-active', !!drawer && !drawer.hidden);
  syncConfigButton();
  configButton?.addEventListener('click', () => {
    if (drawer) drawer.hidden = !drawer.hidden;
    syncConfigButton();
  });

  document.querySelectorAll('[data-paper]').forEach((button) => {
    button.addEventListener('click', () => {
      writeConfig({ tipoPapel: button.dataset.paper });
      applyStoredTheme();
    });
  });

  document.querySelectorAll('[data-accent]').forEach((button) => {
    button.addEventListener('click', () => {
      setCookie('ht_accent', button.dataset.accent);
      writeConfig({ colorAcento: button.dataset.accent });
      applyStoredTheme();
    });
  });

  document.querySelectorAll('[data-accent-input]').forEach((input) => {
    input.addEventListener('input', () => {
      setCookie('ht_accent', input.value);
      writeConfig({ colorAcento: input.value });
      applyStoredTheme();
    });
  });

  document.querySelector('[data-house-switch] select')?.addEventListener('change', (event) => {
    if (event.target.value) window.location.href = event.target.value;
  });

  document.querySelectorAll('[data-dialog-open]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      const dialog = document.getElementById(trigger.dataset.dialogOpen);
      if (!dialog) return;
      const eventDate = trigger.dataset.eventDate;
      if (eventDate) {
        const start = dialog.querySelector('input[name="startDate"]');
        if (start) start.value = `${eventDate}T09:00`;
      }
      dialog.showModal();
    });
  });

  document.querySelectorAll('[data-dialog-close]').forEach((trigger) => {
    trigger.addEventListener('click', () => trigger.closest('dialog')?.close());
  });

  document.querySelectorAll('dialog.ht-dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) dialog.close();
    });
  });

  const syncExpenseForm = (form) => {
    const periodicity = form.querySelector('[data-periodicity]');
    const paymentType = form.querySelector('[data-payment-type]');
    const selectedPeriod = periodicity?.value || '';
    form.querySelectorAll('[data-recurrence-field]').forEach((field) => {
      const active = field.dataset.recurrenceField === selectedPeriod;
      field.hidden = !active;
      field.querySelectorAll('input, select').forEach((input) => {
        input.disabled = !active;
        input.required = active && input.closest('label')?.querySelector('.ht-required');
        if (!active) input.value = '';
      });
    });

    const sharedMembers = form.querySelector('[data-shared-members]');
    const sharedPaymentStatus = form.querySelector('[data-shared-payment-status]');
    const individualFields = form.querySelectorAll('[data-payment-individual]');
    if (sharedMembers && paymentType) {
      const shared = paymentType.value === 'shared';
      sharedMembers.hidden = !shared;
      sharedMembers.querySelectorAll('input, select').forEach((input) => {
        input.disabled = !shared;
      });
    }
    if (sharedPaymentStatus && paymentType) {
      const shared = paymentType.value === 'shared';
      sharedPaymentStatus.hidden = !shared;
      sharedPaymentStatus.querySelectorAll('input, select').forEach((input) => {
        input.disabled = !shared;
      });
    }
    individualFields.forEach((field) => {
      const individual = paymentType?.value === 'individual';
      field.hidden = !individual;
      field.querySelectorAll('input, select').forEach((input) => {
        input.disabled = !individual;
        input.required = individual && input.closest('label')?.querySelector('.ht-required');
        if (!individual && input.type !== 'checkbox') input.value = '';
        if (!individual && input.type === 'checkbox') input.checked = false;
      });
    });
  };

  document.querySelectorAll('.ht-modal-form').forEach((form) => {
    syncExpenseForm(form);
    form.querySelector('[data-periodicity]')?.addEventListener('change', () => syncExpenseForm(form));
    form.querySelector('[data-payment-type]')?.addEventListener('change', () => syncExpenseForm(form));
  });

  const moveSelectedOptions = (from, to) => {
    Array.from(from.selectedOptions).forEach((option) => {
      option.selected = true;
      to.appendChild(option);
    });
  };

  const syncDualListSelection = (dualList) => {
    dualList.querySelectorAll('[data-dual-available] option').forEach((option) => {
      option.selected = false;
    });
    dualList.querySelectorAll('[data-dual-selected] option').forEach((option) => {
      option.selected = true;
    });
  };

  const syncPaidStatusLists = (form) => {
    if (!form) return;
    const participantValues = new Set();
    form.querySelectorAll('[data-participants-list] [data-dual-selected] option').forEach((option) => {
      participantValues.add(option.value);
    });

    form.querySelectorAll('[data-paid-status-list]').forEach((dualList) => {
      const available = dualList.querySelector('[data-dual-available]');
      const selected = dualList.querySelector('[data-dual-selected]');
      if (!available || !selected) return;

      selected.querySelectorAll('option').forEach((option) => {
        if (!participantValues.has(option.value)) available.appendChild(option);
      });

      available.querySelectorAll('option').forEach((option) => {
        const allowed = participantValues.has(option.value);
        option.hidden = !allowed;
        option.disabled = !allowed;
        if (!allowed) option.selected = false;
      });

      selected.querySelectorAll('option').forEach((option) => {
        option.hidden = false;
        option.disabled = false;
      });

      syncDualListSelection(dualList);
    });
  };

  const syncOptionalDates = (form) => {
    form.querySelectorAll('[data-optional-date-toggle]').forEach((toggle) => {
      const wrap = toggle.closest('.ht-form-grid') || form;
      const field = wrap.querySelector('[data-optional-date-field]');
      if (!field) return;
      const input = field.querySelector('input');
      field.hidden = !toggle.checked;
      if (input) {
        input.disabled = !toggle.checked;
        if (!toggle.checked) input.value = '';
      }
    });
  };

  const syncHouseholdScopedUsers = (form) => {
    const household = form.querySelector('[data-household-filter]')?.value || '';
    const token = household ? `,${household},` : '';

    form.querySelectorAll('[data-household-user-select]').forEach((select) => {
      select.querySelectorAll('option[data-households]').forEach((option) => {
        const allowed = token !== '' && option.dataset.households.includes(token);
        option.hidden = !allowed;
        option.disabled = !allowed;
        if (!allowed && option.selected) select.value = '';
      });
    });

    form.querySelectorAll('[data-household-user-checks] label[data-households]').forEach((label) => {
      const allowed = token !== '' && label.dataset.households.includes(token);
      label.hidden = !allowed;
      label.querySelectorAll('input').forEach((input) => {
        input.disabled = !allowed;
        if (!allowed) input.checked = false;
      });
    });

    form.querySelectorAll('[data-dual-list]').forEach((dualList) => {
      const available = dualList.querySelector('[data-dual-available]');
      const selected = dualList.querySelector('[data-dual-selected]');
      if (!available || !selected) return;

      selected.querySelectorAll('option[data-households]').forEach((option) => {
        const allowed = token !== '' && option.dataset.households.includes(token);
        if (!allowed) available.appendChild(option);
      });

      available.querySelectorAll('option[data-households]').forEach((option) => {
        const allowed = token !== '' && option.dataset.households.includes(token);
        option.hidden = !allowed;
        option.disabled = !allowed;
        if (!allowed) option.selected = false;
      });
      syncDualListSelection(dualList);
    });
    syncPaidStatusLists(form);
  };

  document.querySelectorAll('.ht-modal-form').forEach((form) => {
    syncPaidStatusLists(form);
    syncOptionalDates(form);
    form.querySelectorAll('[data-optional-date-toggle]').forEach((toggle) => {
      toggle.addEventListener('change', () => syncOptionalDates(form));
    });
    if (!form.querySelector('[data-household-filter]')) return;
    syncHouseholdScopedUsers(form);
    form.querySelector('[data-household-filter]')?.addEventListener('change', () => syncHouseholdScopedUsers(form));
  });

  document.querySelectorAll('[data-dual-list]').forEach((dualList) => {
    const available = dualList.querySelector('[data-dual-available]');
    const selected = dualList.querySelector('[data-dual-selected]');
    if (!available || !selected) return;

    dualList.querySelector('[data-dual-add]')?.addEventListener('click', () => {
      moveSelectedOptions(available, selected);
      syncDualListSelection(dualList);
      syncPaidStatusLists(dualList.closest('form'));
    });

    dualList.querySelector('[data-dual-remove]')?.addEventListener('click', () => {
      moveSelectedOptions(selected, available);
      syncDualListSelection(dualList);
      syncPaidStatusLists(dualList.closest('form'));
    });

    available.addEventListener('dblclick', () => {
      moveSelectedOptions(available, selected);
      syncDualListSelection(dualList);
      syncPaidStatusLists(dualList.closest('form'));
    });

    selected.addEventListener('dblclick', () => {
      moveSelectedOptions(selected, available);
      syncDualListSelection(dualList);
      syncPaidStatusLists(dualList.closest('form'));
    });
  });

  document.querySelectorAll('.ht-modal-form').forEach((form) => {
    form.addEventListener('submit', () => {
      form.querySelectorAll('[data-dual-list]').forEach(syncDualListSelection);
    });
  });

  document.querySelectorAll('.ht-avatar-edit').forEach((editor) => {
    const input = editor.querySelector('input[type="file"][name="avatarFile"]');
    const preview = editor.querySelector('[data-avatar-preview]');
    const cropData = editor.querySelector('[data-avatar-crop-data]');
    const zoom = editor.querySelector('[data-avatar-zoom]');
    const zoomWrap = editor.querySelector('[data-avatar-zoom-wrap]');
    let image = null;

    const renderCrop = () => {
      if (!image || !preview || !cropData) return;
      const size = 320;
      const scale = Number(zoom?.value || 1);
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      canvas.width = size;
      canvas.height = size;

      const sourceSize = Math.min(image.naturalWidth, image.naturalHeight) / scale;
      const sx = (image.naturalWidth - sourceSize) / 2;
      const sy = (image.naturalHeight - sourceSize) / 2;
      ctx.drawImage(image, sx, sy, sourceSize, sourceSize, 0, 0, size, size);

      const dataUrl = canvas.toDataURL('image/png');
      cropData.value = dataUrl;
      preview.innerHTML = `<img src="${dataUrl}" alt="">`;
    };

    input?.addEventListener('change', () => {
      const file = input.files?.[0];
      if (!file || !file.type.startsWith('image/')) {
        if (cropData) cropData.value = '';
        return;
      }

      image = new Image();
      image.onload = () => {
        if (zoom) zoom.value = '1';
        if (zoomWrap) zoomWrap.hidden = false;
        renderCrop();
        URL.revokeObjectURL(image.src);
      };
      image.src = URL.createObjectURL(file);
    });

    zoom?.addEventListener('input', renderCrop);
  });
});
