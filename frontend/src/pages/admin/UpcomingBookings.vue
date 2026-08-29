<script setup>
import { onMounted } from 'vue'
import { useBookings } from '@/composables/useBookings'

const { bookings, loading, error, load } = useBookings()

onMounted(load)

function formatDate(iso) {
    return new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })
}

function formatTime(iso) {
    return new Date(iso).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold mb-6">Предстоящие встречи</h1>

        <p v-if="loading" class="text-slate-500">Загрузка…</p>
        <p v-else-if="error" class="text-red-600">{{ error }}</p>
        <p v-else-if="!bookings.length" class="text-slate-500">Встреч пока нет.</p>

        <ul v-else class="bg-white border border-slate-200 rounded-xl divide-y divide-slate-100">
            <li v-for="booking in bookings" :key="booking.id" data-testid="upcoming-booking"
                class="flex items-center justify-between gap-4 p-4">
                <div>
                    <p class="font-medium">{{ booking.guestName }}</p>
                    <p class="text-sm text-slate-500">{{ booking.guestEmail }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-slate-700">{{ formatDate(booking.start) }}</p>
                    <p class="text-sm text-slate-700 font-medium">{{ formatTime(booking.start) }}–{{ formatTime(booking.end) }}</p>
                </div>
            </li>
        </ul>
    </div>
</template>
