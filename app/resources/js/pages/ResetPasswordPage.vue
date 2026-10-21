<script setup>
import { reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import { api } from '../api';

const props = defineProps({ token: { type: String, required: true } });

const route = useRoute();
const form = reactive({
    email: typeof route.query.email === 'string' ? route.query.email : '',
    password: '',
    password_confirmation: '',
});
const errors = ref({});
const done = ref(false);
const busy = ref(false);

async function submit() {
    busy.value = true;
    errors.value = {};
    try {
        await api.resetPassword({ ...form, token: props.token });
        done.value = true;
    } catch (e) {
        errors.value = e.errors ?? { password: [e.message] };
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="form-page">
        <h1>Choose a new password</h1>

        <template v-if="done">
            <p class="notice" role="status">
                Your password has been changed, and every device that was signed in has been signed out.
            </p>
            <RouterLink class="btn btn-primary" :to="{ name: 'sign-in' }">Sign in</RouterLink>
        </template>

        <form v-else class="card" novalidate @submit.prevent="submit">
            <div class="field">
                <label for="email">Email</label>
                <input id="email" v-model="form.email" type="email" autocomplete="email" required :aria-invalid="Boolean(errors.email)" aria-describedby="email-error" />
                <p v-if="errors.email" id="email-error" class="error">{{ errors.email[0] }}</p>
            </div>
            <div class="field">
                <label for="password">New password</label>
                <input id="password" v-model="form.password" type="password" autocomplete="new-password" required :aria-invalid="Boolean(errors.password)" aria-describedby="password-error" />
                <p v-if="errors.password" id="password-error" class="error">{{ errors.password[0] }}</p>
            </div>
            <div class="field">
                <label for="password-confirmation">Confirm the new password</label>
                <input id="password-confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required />
            </div>
            <button type="submit" class="btn btn-primary" :disabled="busy">Change password</button>
        </form>
    </div>
</template>
