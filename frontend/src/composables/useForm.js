import { reactive, ref } from 'vue'

export function useForm(initial = {}) {
    const data = reactive({ ...initial })
    const errors = ref({})
    const processing = ref(false)

    async function submit(fn) {
        processing.value = true
        errors.value = {}
        try {
            return await fn(data)
        } catch (e) {
            if (e.status === 422) errors.value = e.errors || {}
            throw e
        } finally {
            processing.value = false
        }
    }

    return { data, errors, processing, submit }
}
