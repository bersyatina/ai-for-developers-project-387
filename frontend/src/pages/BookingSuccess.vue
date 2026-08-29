<script setup>
import { useRoute } from 'vue-router'
import { CheckCircle2 } from '@lucide/vue'

const route = useRoute()

function formatDate(iso) {
    return new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })
}

function formatTime(iso) {
    return new Date(iso).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
    <div class="max-w-lg mx-auto text-center bg-white border border-slate-200 rounded-xl p-8">
        <CheckCircle2 class="w-12 h-12 text-emerald-500 mx-auto mb-4" data-testid="success-icon" />
        <h1 class="text-2xl font-bold mb-2" data-testid="success-title">Запись подтверждена</h1>
        <p class="text-slate-600">
            {{ route.query.guestName }}, вы записаны на звонок «{{ route.query.title }}».
        </p>
        <p class="text-slate-600 mt-2">
            <span data-testid="success-date">{{ formatDate(route.query.start) }}</span> в
            <span data-testid="success-time">{{ formatTime(route.query.start) }}</span>
        </p>
        <RouterLink to="/" class="inline-block mt-6 text-indigo-600 hover:text-indigo-700 font-medium">← На главную</RouterLink>
    </div>
</template>
