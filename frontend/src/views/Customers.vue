<template>
  <div>
    <h1 class="text-h4 mb-4">Customers</h1>
    
    <v-btn color="primary" class="mb-4" @click="showAddDialog = true">
      <v-icon left>mdi-plus</v-icon>
      Add Customer
    </v-btn>

    <v-data-table
      :headers="headers"
      :items="customers"
      :loading="loading"
      class="elevation-1"
    >
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

    <!-- Add Customer Dialog -->
    <v-dialog v-model="showAddDialog" max-width="600">
      <v-card>
        <v-card-title>Add Customer</v-card-title>
        <v-card-text>
          <v-text-field v-model="newCustomer.name" label="Name" required></v-text-field>
          <v-text-field v-model="newCustomer.email" label="Email" type="email"></v-text-field>
          <v-text-field v-model="newCustomer.phone" label="Phone"></v-text-field>
          <v-text-field v-model="newCustomer.company" label="Company"></v-text-field>
          <v-textarea v-model="newCustomer.address" label="Address"></v-textarea>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="showAddDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="saveCustomer">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '@/composables/useApi'

const { get, post, loading } = useApi()

const customers = ref([])
const showAddDialog = ref(false)

const newCustomer = ref({
  name: '',
  email: '',
  phone: '',
  company: '',
  address: ''
})

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Phone', key: 'phone' },
  { title: 'Company', key: 'company' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false }
]

onMounted(async () => {
  try {
    const response = await get('/customers')
    customers.value = response.data || []
  } catch (error) {
    console.error('Failed to load customers:', error)
  }
})

async function saveCustomer() {
  try {
    await post('/customers', newCustomer.value)
    showAddDialog.value = false
    // Reload customers
    const response = await get('/customers')
    customers.value = response.data || []
    // Reset form
    newCustomer.value = { name: '', email: '', phone: '', company: '', address: '' }
  } catch (error) {
    console.error('Failed to save customer:', error)
  }
}

function editItem(item) {
  console.log('Edit:', item)
}

function deleteItem(item) {
  console.log('Delete:', item)
}
</script>
