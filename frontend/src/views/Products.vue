<template>
  <div>
    <h1 class="text-h4 mb-4">Products</h1>
    
    <v-btn color="primary" class="mb-4" @click="showAddDialog = true">
      <v-icon left>mdi-plus</v-icon>
      Add Product
    </v-btn>

    <v-data-table
      :headers="headers"
      :items="products"
      :loading="loading"
      class="elevation-1"
    >
      <template v-slot:item.price="{ item }">
        ${{ item.price }}
      </template>
      
      <template v-slot:item.stock_quantity="{ item }">
        <v-chip :color="item.stock_quantity < 20 ? 'error' : 'success'" small>
          {{ item.stock_quantity }}
        </v-chip>
      </template>
      
      <template v-slot:item.status="{ item }">
        <v-chip :color="item.status === 'active' ? 'success' : 'grey'" small>
          {{ item.status }}
        </v-chip>
      </template>
      
      <template v-slot:item.actions="{ item }">
        <v-icon small class="mr-2" @click="editItem(item)">mdi-pencil</v-icon>
        <v-icon small @click="deleteItem(item)">mdi-delete</v-icon>
      </template>
    </v-data-table>

    <!-- Add Product Dialog -->
    <v-dialog v-model="showAddDialog" max-width="600">
      <v-card>
        <v-card-title>Add Product</v-card-title>
        <v-card-text>
          <v-text-field v-model="newProduct.name" label="Product Name" required></v-text-field>
          <v-text-field v-model="newProduct.sku" label="SKU" required></v-text-field>
          <v-textarea v-model="newProduct.description" label="Description"></v-textarea>
          <v-text-field v-model="newProduct.price" label="Price" type="number" prefix="$"></v-text-field>
          <v-text-field v-model="newProduct.stock_quantity" label="Stock Quantity" type="number"></v-text-field>
          <v-select v-model="newProduct.category" label="Category" :items="categories"></v-select>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="showAddDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="saveProduct">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '@/composables/useApi'

const { get, post, loading } = useApi()

const products = ref([])
const showAddDialog = ref(false)
const categories = ['Electronics', 'Accessories', 'Software', 'Services']

const newProduct = ref({
  name: '',
  sku: '',
  description: '',
  price: 0,
  stock_quantity: 0,
  category: ''
})

const headers = [
  { title: 'SKU', key: 'sku' },
  { title: 'Name', key: 'name' },
  { title: 'Price', key: 'price' },
  { title: 'Stock', key: 'stock_quantity' },
  { title: 'Category', key: 'category' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false }
]

onMounted(async () => {
  try {
    const response = await get('/products')
    products.value = response.data || []
  } catch (error) {
    console.error('Failed to load products:', error)
  }
})

async function saveProduct() {
  try {
    await post('/products', newProduct.value)
    showAddDialog.value = false
    // Reload products
    const response = await get('/products')
    products.value = response.data || []
    // Reset form
    newProduct.value = { name: '', sku: '', description: '', price: 0, stock_quantity: 0, category: '' }
  } catch (error) {
    console.error('Failed to save product:', error)
  }
}

function editItem(item) {
  // Implement edit
  console.log('Edit:', item)
}

function deleteItem(item) {
  // Implement delete
  console.log('Delete:', item)
}
</script>
