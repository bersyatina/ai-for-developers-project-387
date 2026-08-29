<script setup>
import { CalendarDays } from '@lucide/vue'
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="bg-white border-b border-slate-200">
            <nav class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
                <RouterLink to="/" class="flex items-center gap-2 font-semibold">
                    <CalendarDays class="w-6 h-6 text-indigo-600" />
                    Календарь звонков
                </RouterLink>
                <div class="flex items-center gap-4 text-sm">
                    <RouterLink to="/admin" class="text-slate-600 hover:text-slate-900">Создать тип</RouterLink>
                    <RouterLink to="/admin/bookings" class="text-slate-600 hover:text-slate-900">Встречи</RouterLink>
                </div>
            </nav>
        </header>
        <main class="max-w-4xl mx-auto px-4 py-8">
            <RouterView />
        </main>
    </div>
</template>
