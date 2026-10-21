<script setup>
import { ref } from 'vue';
import { api } from '../api';

const email = ref('');
const sent = ref('');
const error = ref('');
const busy = ref(false);

async function submit() {
    busy.value = true;
    error.value = '';
    try {
        sent.value = (await api.forgotPassword(email.value)).message;
    } catch (e) {
        error.value = e.errors?.email?.[0] ?? e.message;
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="form-page">
        <h1>Forgot your password?</h1>
        <p class="lead">Enter the email address you signed up with, and we'll send you a link to choose a new password.</p>

        <p v-if="sent" class="notice" role="status">{{ sent }}</p>

        <form v-else class="card" novalidate @submit.prevent="submit">
            <div class="field">
                <label for="email">Email</label>
                <input id="email" v-model="email" type="email" autocomplete="email" required :aria-invalid="Boolean(error)" aria-describedby="email-error" />
                <p v-if="error" id="email-error" class="error">{{ error }}</p>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="busy">Send the link</button>
        </form>

        <p><RouterLink :to="{ name: 'sign-in' }">Back to sign in</RouterLink></p>
    </div>
</template>
