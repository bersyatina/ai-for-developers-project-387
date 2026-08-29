const BASE_URL = '/api'

export async function api(path, { method = 'GET', body } = {}) {
    const response = await fetch(`${BASE_URL}${path}`, {
        method,
        headers: body ? { 'Content-Type': 'application/json', Accept: 'application/json' } : { Accept: 'application/json' },
        body: body ? JSON.stringify(body) : undefined,
    })

    const data = await response.json().catch(() => null)

    if (!response.ok) {
        const error = new Error(data?.message || `HTTP ${response.status}`)
        error.status = response.status
        error.errors = data?.errors
        throw error
    }

    return data
}
