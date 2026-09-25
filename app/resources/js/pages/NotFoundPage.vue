<script setup>
import LoadState from '../components/LoadState.vue';

const error = { status: 404 };
</script>

<template>
    <LoadState :error="error" />
</template>
