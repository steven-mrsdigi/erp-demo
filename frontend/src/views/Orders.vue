<template>
  <div>
    <h1 class="text-h4 mb-4">Orders</h1>
    
    <v-btn color="primary" class="mb-4" @click="showAddDialog = true">
      <v-icon left>mdi-plus</v-icon>
      Create Order
    </v-btn>

    <v-data-table
      :headers="headers"
      :items="orders"
      :loading="loading"
      class="elevation-1"
    >
      <template v-slot:item.total_amount="{ item }">
        ${{ item.total_amount }}
      </template>
      
      <template v-slot:item.status="{ item }">
        <v-chip :color="getStatusColor(item.status)" small>
          {{ item.status }}
        </v-chip>
      </template>
      
      <template v-slot:item.payment_status="{ item }">
        <v-chip :color="item.payment_status === 'paid' ? 'success' : 'warning'" small>
          {{ item.payment_status }}
        </v-chip>
      </template>
      
      <template v-slot:item.actions="{ item }">
        <v-icon small class="mr-2" @click="viewItem(item)">mdi-eye</v-icon>
        <v-icon small @click="deleteItem(item)">mdi-delete</v-icon>
      </template>
    </v-data-table>

    <!-- Create Order Dialog -->
    <v-dialog v-model="showAddDialog" max-width="800">
      <v-card>
        <v-card-title>Create Order</v-card-title>
        <v-card-text>
          <v-select
            v-model="newOrder.customer_id"
            :items="customers"
            item-title="name"
            item-value="id"
            label="Select Customer"
            required
          ></v-select>
          
          <v-divider class="my-4"></v-divider>
          
          <div v-for="(item, index) in newOrder.items" :key="index" class="d-flex align-center mb-2">
            <v-select
              v-model="item.product_id"
              :items="products"
              item-title="name"
              item-value="id"
              label="Product"
              class="flex-grow-1 mr-2"
              @update:modelValue="updateItemPrice(item)"
            ></v-select>
            <v-text-field
              v-model="item.quantity"
              label="Qty"
              type="number"
              style="width: 80px"
              class="mr-2"
              @update:modelValue="updateItemTotal(item)"
            ></v-text-field>
            <v-text-field
              v-model="item.unit_price"
              label="Price"
              type="number"
              prefix="$"
              style="width: 120px"
              class="mr-2"
              @update:modelValue="updateItemTotal(item)"
            ></v-text-field>
            <div class="text-subtitle-1">${{ item.total_price }}</div>
            <v-btn icon color="error" class="ml-2" @click="removeItem(index)">
              <v-icon>mdi-delete</v-icon>
            </v-btn>
          </div>
          
          <v-btn text color="primary" @click="addItem">
            <v-icon left>mdi-plus</v-icon>
            Add Item
          </v-btn>
          
          <v-divider class="my-4"></v-divider>
          
          <div class="d-flex justify-end">
            <div class="text-h6">Total: ${{ calculateTotal() }}</div>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="showAddDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="saveOrder">Create Order</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '@/composables/useApi'

const { get, post, loading } = useApi()

const orders = ref([])
const customers = ref([])
const products = ref([])
const showAddDialog = ref(false)

const newOrder = ref({
  customer_id: null,
  items: [{ product_id: null, quantity: 1, unit_price: 0, total_price: 0 }]
})

const headers = [
  { title: 'Order Number', key: 'order_number' },
  { title: 'Customer', key: 'customer_name' },
  { title: 'Total', key: 'total_amount' },
  { title: 'Status', key: 'status' },
  { title: 'Payment', key: 'payment_status' },
  { title: 'Date', key: 'order_date' },
  { title: 'Actions', key: 'actions', sortable: false }
]

onMounted(async () => {
  try {
    const [ordersRes, customersRes, productsRes] = await Promise.all([
      get('/orders'),
      get('/customers'),
      get('/products')
    ])
    orders.value = ordersRes.data || []
    customers.value = customersRes.data || []
    products.value = productsRes.data || []
  } catch (error) {
    console.error('Failed to load data:', error)
  }
})

function addItem() {
  newOrder.value.items.push({ product_id: null, quantity: 1, unit_price: 0, total_price: 0 })
}

function removeItem(index) {
  newOrder.value.items.splice(index, 1)
}

function updateItemPrice(item) {
  const product = products.value.find(p => p.id === item.product_id)
  if (product) {
    item.unit_price = product.price
    updateItemTotal(item)
  }
}

function updateItemTotal(item) {
  item.total_price = (item.quantity || 0) * (item.unit_price || 0)
}

function calculateTotal() {
  return newOrder.value.items.reduce((sum, item) => sum + (item.total_price || 0), 0).toFixed(2)
}

async function saveOrder() {
  try {
    const orderData = {
      customer_id: newOrder.value.customer_id,
      items: newOrder.value.items,
      total_amount: parseFloat(calculateTotal())
    }
    await post('/orders', orderData)
    showAddDialog.value = false
    // Reload orders
    const response = await get('/orders')
    orders.value = response.data || []
    // Reset form
    newOrder.value = { customer_id: null, items: [{ product_id: null, quantity: 1, unit_price: 0, total_price: 0 }] }
  } catch (error) {
    console.error('Failed to save order:', error)
  }
}

function getStatusColor(status) {
  const colors = {
    'pending': 'warning',
    'processing': 'info',
    'completed': 'success',
    'cancelled': 'error'
  }
  return colors[status] || 'grey'
}

function viewItem(item) {
  console.log('View:', item)
}

function deleteItem(item) {
  console.log('Delete:', item)
}
</script>
