import { createRouter, createWebHistory } from 'vue-router'
import EventTypes from '@/pages/EventTypes.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'event-types', component: EventTypes },
    {
      path: '/event-types/:id',
      name: 'event-type-booking',
      component: () => import('@/pages/EventTypeBooking.vue'),
    },
    {
      path: '/success',
      name: 'booking-success',
      component: () => import('@/pages/BookingSuccess.vue'),
    },
    // Админ-часть владельца: обычные роуты, без auth (в проекте нет авторизации).
    {
      path: '/admin',
      name: 'admin-create-type',
      component: () => import('@/pages/admin/CreateEventType.vue'),
    },
    {
      path: '/admin/bookings',
      name: 'admin-bookings',
      component: () => import('@/pages/admin/UpcomingBookings.vue'),
    },
  ],
})

export default router
