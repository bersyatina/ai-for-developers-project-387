import { api } from './client'

export const eventTypesApi = {
    list: () => api('/event-types'),
    create: (payload) => api('/event-types', { method: 'POST', body: payload }),
    slots: (id, date, tz) => api(`/event-types/${id}/slots?${new URLSearchParams({ date, tz })}`),
}
