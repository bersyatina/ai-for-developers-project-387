<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { bookingsApi } from '@/api/bookings'
import { eventTypesApi } from '@/api/eventTypes'
import { useForm } from '@/composables/useForm'
import { useSlots } from '@/composables/useSlots'

const route = useRoute()
const router = useRouter()

const eventType = ref(null)
const selectedDate = ref(todayIso())
const selectedSlot = ref(null)
const { data: formData, errors: formErrors, processing: formProcessing } = useForm({ guestName: '', guestEmail: '' })
const submitError = ref(null)
const submitting = ref(false)

const { slots, loading, error, load } = useSlots(route.params.id)

const dates = computed(() => {
    const list = []
    const now = new Date()
    for (let i = 0; i < 14; i++) {
        const d = new Date(now.getFullYear(), now.getMonth(), now.getDate() + i)
        list.push({
            iso: toIso(d),
            label: i === 0 ? 'Сегодня' : i === 1 ? 'Завтра' : d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' }),
        })
    }
    return list
})

function todayIso() {
    const now = new Date()
    return toIso(new Date(now.getFullYear(), now.getMonth(), now.getDate()))
}

function toIso(d) {
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
}

function formatTime(iso) {
    return new Date(iso).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
}

function formatDate(iso) {
    return new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })
}

async function selectDate(date) {
    selectedDate.value = date
    selectedSlot.value = null
    await load(date)
}

async function book() {
    submitError.value = null
    submitting.value = true
    try {
        await bookingsApi.create({
            eventTypeId: eventType.value.id,
            start: selectedSlot.value.start,
            guestName: formData.guestName,
            guestEmail: formData.guestEmail,
        })
        router.push({
            path: '/success',
            query: {
                guestName: formData.guestName,
                title: eventType.value.title,
                start: selectedSlot.value.start,
            },
        })
    } catch (e) {
        if (e.status === 409) {
            submitError.value = 'Этот слот только что заняли. Пожалуйста, выберите другое время.'
            formErrors.value = {}
            await load(selectedDate.value)
        } else {
            formErrors.value = e.errors || {}
            submitError.value = e.message
        }
    } finally {
        submitting.value = false
    }
}

onMounted(async () => {
    const types = await eventTypesApi.list()
    eventType.value = types.find((t) => t.id === route.params.id) || null
    if (eventType.value) {
        await load(selectedDate.value)
    }
})
</script>

<template>
    <div class="max-w-2xl">
        <button @click="router.back()" class="text-sm text-slate-500 hover:text-slate-800">← Назад</button>

        <div v-if="eventType">
            <h1 class="text-2xl font-bold mt-3" data-testid="booking-title">{{ eventType.title }}</h1>
            <p class="text-slate-500 mb-6">{{ eventType.description }}</p>

            <div class="flex gap-2 overflow-x-auto pb-2 mb-4">
                <button v-for="d in dates" :key="d.iso" type="button" @click="selectDate(d.iso)"
                        data-testid="date-pill"
                        :class="['rounded-lg px-3 py-2 text-sm border whitespace-nowrap',
                                 d.iso === selectedDate
                                     ? 'bg-indigo-600 text-white border-indigo-600'
                                     : 'bg-white text-slate-700 border-slate-200 hover:border-indigo-400']">
                    {{ d.label }}
                </button>
            </div>

            <p v-if="loading" class="text-slate-500">Загружаем слоты…</p>
            <p v-else-if="error" class="text-red-600">{{ error }}</p>
            <p v-else-if="!slots.length" class="text-slate-500">На этот день свободных слотов нет.</p>
            <ul v-else class="grid grid-cols-3 sm:grid-cols-4 gap-2 mb-8">
                <li v-for="slot in slots" :key="slot.start">
                    <button type="button" @click="selectedSlot = slot" data-testid="slot"
                            :class="['w-full rounded-lg border px-3 py-2 text-sm',
                                     selectedSlot?.start === slot.start
                                         ? 'bg-indigo-600 text-white border-indigo-600'
                                         : 'bg-white text-slate-700 border-slate-200 hover:border-indigo-400']">
                        {{ formatTime(slot.start) }}
                    </button>
                </li>
            </ul>

            <div v-if="selectedSlot" class="bg-white border border-slate-200 rounded-xl p-5">
                <h2 class="font-semibold mb-1">
                    Запись на {{ formatDate(selectedSlot.start) }} в {{ formatTime(selectedSlot.start) }}
                </h2>
                <p class="text-slate-500 text-sm mb-4">{{ eventType.durationMinutes }} минут</p>

                <form @submit.prevent="book" novalidate class="grid gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1" for="guest_name">Ваше имя</label>
                        <input id="guest_name" v-model="formData.guestName" name="guest_name" type="text" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        <p v-if="formErrors.guestName" class="text-sm text-red-600 mt-1">{{ formErrors.guestName[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1" for="guest_email">Email</label>
                        <input id="guest_email" v-model="formData.guestEmail" name="guest_email" type="email" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        <p v-if="formErrors.guestEmail" class="text-sm text-red-600 mt-1">{{ formErrors.guestEmail[0] }}</p>
                    </div>

                    <p v-if="submitError" class="text-sm text-red-600">{{ submitError }}</p>

                    <button type="submit" data-testid="book-submit" :disabled="submitting || formProcessing"
                            class="rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                        {{ submitting ? 'Записываем…' : 'Записаться' }}
                    </button>
                </form>
            </div>
        </div>
        <p v-else class="text-slate-500 mt-3">Тип события не найден.</p>
    </div>
</template>
