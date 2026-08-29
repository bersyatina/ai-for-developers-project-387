import { api } from './client'

export const bookingsApi = {
    list: () => api('/bookings'),
    create: (payload) => api('/bookings', { method: 'POST', body: payload }),
}
