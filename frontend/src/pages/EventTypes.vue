<script setup>
import { onMounted } from 'vue'
import { useEventTypes } from '@/composables/useEventTypes'
import { ArrowRight, CalendarClock } from '@lucide/vue'

const { eventTypes, loading, error, load } = useEventTypes()

onMounted(load)
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold mb-1">Выберите тип звонка</h1>
        <p class="text-slate-500 mb-6">Запись на 30-минутный звонок в ближайшие 14 дней.</p>

        <p v-if="loading" class="text-slate-500">Загрузка…</p>
        <p v-else-if="error" class="text-red-600">{{ error }}</p>
        <p v-else-if="!eventTypes.length" class="text-slate-500">Пока нет доступных типов звонков.</p>

        <ul v-else class="grid gap-4 sm:grid-cols-2">
            <li v-for="type in eventTypes" :key="type.id"
                class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col gap-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 class="font-semibold text-lg" data-testid="event-type-title">{{ type.title }}</h2>
                        <p class="text-slate-500 text-sm mt-1">{{ type.description }}</p>
                    </div>
                    <CalendarClock class="w-5 h-5 text-indigo-500 shrink-0" />
                </div>
                <div class="flex items-center justify-between mt-auto">
                    <span class="text-sm text-slate-500">{{ type.durationMinutes }} мин</span>
                    <RouterLink :to="`/event-types/${type.id}`"
                                class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700"
                                data-testid="book-link">
                        Забронировать
                        <ArrowRight class="w-4 h-4" />
                    </RouterLink>
                </div>
            </li>
        </ul>
    </div>
</template>
