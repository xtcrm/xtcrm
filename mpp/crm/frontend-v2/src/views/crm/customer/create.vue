<template>
  <CustomerForm mode="page" @ok="router.push('/crm/customer/index')" @update:open="router.push('/crm/customer/index')" />
</template>

<script setup>
import { useRouter } from 'vue-router'
import CustomerForm from './components/CustomerForm.vue'

const router = useRouter()
</script>
