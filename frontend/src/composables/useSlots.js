import { ref } from 'vue'
import { eventTypesApi } from '@/api/eventTypes'

export function useSlots(eventTypeId) {
    const slots = ref([])
    const loading = ref(false)
    const error = ref(null)

    async function load(date) {
        loading.value = true
        error.value = null
        try {
            slots.value = await eventTypesApi.slots(eventTypeId, date)
        } catch (e) {
            error.value = e.message
            slots.value = []
        } finally {
            loading.value = false
        }
    }

    return { slots, loading, error, load }
}
