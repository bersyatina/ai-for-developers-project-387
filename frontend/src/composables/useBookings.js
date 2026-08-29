import { ref } from 'vue'
import { bookingsApi } from '@/api/bookings'

export function useBookings() {
    const bookings = ref([])
    const loading = ref(false)
    const error = ref(null)

    async function load() {
        loading.value = true
        error.value = null
        try {
            bookings.value = await bookingsApi.list()
        } catch (e) {
            error.value = e.message
        } finally {
            loading.value = false
        }
    }

    return { bookings, loading, error, load }
}
