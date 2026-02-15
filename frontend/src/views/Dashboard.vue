<template>
  <div>
    <h1 class="text-h4 mb-4">Dashboard</h1>
    
    <v-row>
      <v-col cols="12" md="3">
        <v-card color="primary" dark>
          <v-card-text>
            <div class="text-h6">Total Products</div>
            <div class="text-h3">{{ stats.products }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      
      <v-col cols="12" md="3">
        <v-card color="success" dark>
          <v-card-text>
            <div class="text-h6">Total Customers</div>
            <div class="text-h3">{{ stats.customers }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      
      <v-col cols="12" md="3">
        <v-card color="info" dark>
          <v-card-text>
            <div class="text-h6">Total Orders</div>
            <div class="text-h3">{{ stats.orders }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      
      <v-col cols="12" md="3">
        <v-card color="warning" dark>
          <v-card-text>
            <div class="text-h6">Revenue</div>
            <div class="text-h3">${{ stats.revenue }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row class="mt-4">
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title>Recent Orders</v-card-title>
          <v-card-text>
            <v-list>
              <v-list-item v-for="order in recentOrders" :key="order.id">
                <v-list-item-title>{{ order.order_number }}</v-list-item-title>
                <v-list-item-subtitle>
                  {{ order.customer_name }} - ${{ order.total_amount }}
                </v-list-item-subtitle>
                <template v-slot:append>
                  <v-chip :color="getStatusColor(order.status)" size="small">
                    {{ order.status }}
                  </v-chip>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
      
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title>Low Stock Products</v-card-title>
          <v-card-text>
            <v-list>
              <v-list-item v-for="product in lowStockProducts" :key="product.id">
                <v-list-item-title>{{ product.name }}</v-list-item-title>
                <v-list-item-subtitle>SKU: {{ product.sku }}</v-list-item-subtitle>
                <template v-slot:append>
                  <v-chip color="error" size="small">
                    {{ product.stock_quantity }} left
                  </v-chip>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '@/composables/useApi'

const { get } = useApi()

const stats = ref({
  products: 0,
  customers: 0,
  orders: 0,
  revenue: 0
})

const recentOrders = ref([])
const lowStockProducts = ref([])

onMounted(async () => {
  // Load dashboard data
  try {
    const ordersData = await get('/orders')
    const productsData = await get('/products')
    const customersData = await get('/customers')
    
    stats.value.orders = ordersData.data?.length || 0
    stats.value.products = productsData.data?.length || 0
    stats.value.customers = customersData.data?.length || 0
    
    recentOrders.value = ordersData.data?.slice(0, 5) || []
    lowStockProducts.value = productsData.data?.filter(p => p.stock_quantity < 20).slice(0, 5) || []
  } catch (error) {
    console.error('Failed to load dashboard:', error)
  }
})

function getStatusColor(status) {
  const colors = {
    'pending': 'warning',
    'processing': 'info',
    'completed': 'success',
    'cancelled': 'error'
  }
  return colors[status] || 'grey'
}
</script>
