<script setup>
import { ref } from 'vue'
import { eventTypesApi } from '@/api/eventTypes'
import { useForm } from '@/composables/useForm'

const { data: formData, errors: formErrors } = useForm({ title: '', description: '', durationMinutes: 30 })
const success = ref(null)
const serverError = ref(null)

async function submit() {
    success.value = null
    serverError.value = null
    try {
        const created = await eventTypesApi.create({
            title: formData.title,
            description: formData.description,
            durationMinutes: Number(formData.durationMinutes),
        })
        success.value = `Тип «${created.title}» создан.`
        formData.title = ''
        formData.description = ''
    } catch (e) {
        if (e.status === 422) formErrors.value = e.errors || {}
        else serverError.value = e.message
    }
}
</script>

<template>
    <div class="max-w-lg">
        <h1 class="text-2xl font-bold mb-6">Создать тип звонка</h1>

        <form @submit.prevent="submit" novalidate class="bg-white border border-slate-200 rounded-xl p-5 grid gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1" for="title">Название</label>
                <input id="title" v-model="formData.title" name="title" type="text" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <p v-if="formErrors.title" class="text-sm text-red-600 mt-1">{{ formErrors.title[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1" for="description">Описание</label>
                <textarea id="description" v-model="formData.description" name="description" rows="2"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1" for="durationMinutes">Длительность, мин</label>
                <select id="durationMinutes" v-model="formData.durationMinutes" name="durationMinutes"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option :value="30">30</option>
                    <option :value="60">60</option>
                    <option :value="90">90</option>
                    <option :value="120">120</option>
                </select>
                <p v-if="formErrors.durationMinutes" class="text-sm text-red-600 mt-1">{{ formErrors.durationMinutes[0] }}</p>
            </div>

            <p v-if="success" class="text-sm text-emerald-600">{{ success }}</p>
            <p v-if="serverError" class="text-sm text-red-600">{{ serverError }}</p>

            <button type="submit" data-testid="create-type-submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700">
                Создать
            </button>
        </form>
    </div>
</template>
