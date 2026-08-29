import { ref } from 'vue'
import { eventTypesApi } from '@/api/eventTypes'

export function useEventTypes() {
    const eventTypes = ref([])
    const loading = ref(false)
    const error = ref(null)

    async function load() {
        loading.value = true
        error.value = null
        try {
            eventTypes.value = await eventTypesApi.list()
        } catch (e) {
            error.value = e.message
        } finally {
            loading.value = false
        }
    }

    return { eventTypes, loading, error, load }
}
