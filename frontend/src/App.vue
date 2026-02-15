<template>
  <v-app>
    <v-app-bar app color="primary" dark>
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
      <v-toolbar-title>ERP System</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn text to="/">Dashboard</v-btn>
      <v-btn text to="/products">Products</v-btn>
      <v-btn text to="/customers">Customers</v-btn>
      <v-btn text to="/orders">Orders</v-btn>
    </v-app-bar>

    <v-navigation-drawer v-model="drawer" app>
      <v-list>
        <v-list-item to="/" prepend-icon="mdi-view-dashboard">
          <v-list-item-title>Dashboard</v-list-item-title>
        </v-list-item>
        <v-list-item to="/products" prepend-icon="mdi-package">
          <v-list-item-title>Products</v-list-item-title>
        </v-list-item>
        <v-list-item to="/customers" prepend-icon="mdi-account-group">
          <v-list-item-title>Customers</v-list-item-title>
        </v-list-item>
        <v-list-item to="/orders" prepend-icon="mdi-cart">
          <v-list-item-title>Orders</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <v-main>
      <v-container fluid>
        <router-view></router-view>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref } from 'vue'

const drawer = ref(false)
</script>
