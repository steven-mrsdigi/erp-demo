import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '@/views/Dashboard.vue'
import Products from '@/views/Products.vue'
import Customers from '@/views/Customers.vue'
import Orders from '@/views/Orders.vue'

const routes = [
  { path: '/', name: 'Dashboard', component: Dashboard },
  { path: '/products', name: 'Products', component: Products },
  { path: '/customers', name: 'Customers', component: Customers },
  { path: '/orders', name: 'Orders', component: Orders },
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
