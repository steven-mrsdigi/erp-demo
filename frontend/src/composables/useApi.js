import { ref } from 'vue'

const API_URL = 'http://localhost:8080/api' // 本地開發用
// const API_URL = 'https://your-railway-app.up.railway.app/api' // 部署後用

export function useApi() {
  const loading = ref(false)
  const error = ref(null)

  async function request(endpoint, options = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}${endpoint}`, {
        headers: {
          'Content-Type': 'application/json',
        },
        ...options
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      return await response.json()
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  function get(endpoint) {
    return request(endpoint, { method: 'GET' })
  }

  function post(endpoint, data) {
    return request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data)
    })
  }

  function put(endpoint, data) {
    return request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data)
    })
  }

  function del(endpoint) {
    return request(endpoint, { method: 'DELETE' })
  }

  return {
    loading,
    error,
    get,
    post,
    put,
    delete: del
  }
}
