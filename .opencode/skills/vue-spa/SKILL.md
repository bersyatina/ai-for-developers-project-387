---
name: vue-spa
description: Use ONLY when building or modifying Vue 3 SPA pages, components, composables, forms, routing, or API integration in the frontend/ of this project. Covers frontend/src structure, Vue Router (history), fetch-based API client, composables pattern, Tailwind 4 styling, and lucide-vue-next icons. This is a separate SPA — NOT Inertia.
---

# Vue 3 SPA Frontend (frontend/)

**Сверяйся с `AGENTS.md` для правил разработки, структуры проекта и контракта API.** Этот навык — о фронтенд-паттернах в `frontend/`. Фронтенд — независимое SPA-приложение (Vite + Vue 3 + Vue Router + Tailwind), данные берёт из backend через HTTP API по контракту `spec/openapi.yaml`.

---

## 1. Структура `frontend/src`

```
frontend/src/
├── main.js              # точка входа: createApp + router
├── App.vue              # корневой компонент
├── router/index.js      # Vue Router, history mode, lazy-import страниц
├── api/client.js        # fetch-обёртка (baseURL /api, JSON, обработка ошибок)
├── api/eventTypes.js    # функции API по сущностям
├── api/bookings.js
├── api/slots.js
├── pages/               # страницы (маршруты)
│   ├── EventTypes.vue        # список типов (гость)
│   ├── EventTypeBooking.vue  # выбор даты + свободного слота (гость)
│   ├── BookingSuccess.vue    # подтверждение брони (гость)
│   └── admin/                # админ-часть владельца (без авторизации)
│       ├── CreateEventType.vue
│       └── UpcomingBookings.vue
├── components/          # переиспользуемые UI-компоненты
└── composables/         # useApi, useEventTypes, useSlots, useBookings
```

## 2. Роутер

```js
// router/index.js
import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    { path: '/', component: () => import('@/pages/EventTypes.vue') },
    { path: '/event-types/:id', component: () => import('@/pages/EventTypeBooking.vue') },
    { path: '/success', component: () => import('@/pages/BookingSuccess.vue') },
    // админ-часть владельца — обычные роуты, без auth (в проекте нет авторизации)
    { path: '/admin', component: () => import('@/pages/admin/CreateEventType.vue') },
    { path: '/admin/bookings', component: () => import('@/pages/admin/UpcomingBookings.vue') },
];
```

- Тяжёлые страницы лениво грузим через динамический `import`.
- Админ-часть — просто отдельные маршруты `/admin*`, без guard'ов.

## 3. API-клиент (fetch-обёртка)

Вместо Inertia `useForm`/`router.post` — обычный fetch к `/api`:

```js
// api/client.js
const BASE_URL = '/api';

export async function api(path, { method = 'GET', body } = {}) {
    const res = await fetch(`${BASE_URL}${path}`, {
        method,
        headers: body ? { 'Content-Type': 'application/json' } : undefined,
        body: body ? JSON.stringify(body) : undefined,
    });

    const data = await res.json().catch(() => null);

    if (!res.ok) {
        const error = new Error(data?.message || `HTTP ${res.status}`);
        error.status = res.status;
        error.errors = data?.errors;
        throw error;
    }

    return data;
}
```

```js
// api/eventTypes.js
import { api } from './client';

export const eventTypesApi = {
    list: () => api('/event-types'),
    create: (payload) => api('/event-types', { method: 'POST', body: payload }),
    slots: (id, date) => api(`/event-types/${id}/slots?date=${date}`),
};
```

- **Обработка ошибок:** 422 — ошибки валидации (`error.errors`), 409 — слот занят. Показывать их в форме (см. раздел 4).
- В dev-режиме Vite проксирует `/api` на backend (`server.proxy` в `vite.config.js`).

## 4. Формы и отправка

Стандартный паттерн Vue 3 + composable `useForm` (свой, не Inertia):

```js
import { ref } from 'vue';

export function useForm(initial = {}) {
    const data = ref({ ...initial });
    const errors = ref({});
    const processing = ref(false);

    async function submit(fn) {
        processing.value = true;
        errors.value = {};
        try {
            return await fn(data.value);
        } catch (e) {
            if (e.status === 422) errors.value = e.errors || {};
            throw e;
        } finally {
            processing.value = false;
        }
    }

    return { data, errors, processing, submit };
}
```

В компоненте показываем `errors.value.field` под инпутами. Блокируем кнопку при `processing`.

## 5. Composables

Все в `frontend/src/composables/`. Паттерн:

```js
// composables/useSlots.js
export function useSlots(eventTypeId) {
    const slots = ref([]);
    const loading = ref(false);

    async function load(date) {
        loading.value = true;
        try {
            slots.value = await eventTypesApi.slots(eventTypeId, date);
        } finally {
            loading.value = false;
        }
    }

    return { slots, loading, load };
}
```

Существующие по смыслу: `useApi.js`, `useForm.js`, `useEventTypes.js`, `useSlots.js`, `useBookings.js`.

## 6. Стиль (Tailwind 4)

- Tailwind 4 через плагин `@tailwindcss/vite`; единственная директива `@import "tailwindcss";` в `src/main.css`.
- **Без scoped styles** — классы инлайн в template.
- Тёмная тема через `dark:` префикс: `class="bg-white dark:bg-slate-900"` (опционально).
- Утилиты-классы, не кастомный CSS.

## 7. Иконки

Lucide Vue: `import { CalendarDays, Clock, Trash2 } from 'lucide-vue-next'`

## 8. Сборка и dev

```bash
# из корня проекта
make build   # npm --prefix frontend run build  → dist/ (раздаётся nginx в Docker)
make dev     # vite dev-сервер (проксирует /api на backend)
```
