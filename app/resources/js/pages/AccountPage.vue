<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '../api';
import { register, signIn } from '../auth';

const props = defineProps({
    mode: { type: String, required: true, validator: (value) => ['sign-in', 'register'].includes(value) },
});

const route = useRoute();
const router = useRouter();
const registering = computed(() => props.mode === 'register');

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const errors = ref({});
const message = ref('');
const busy = ref(false);
const github = ref(false);

onMounted(async () => {
    github.value = (await api.authOptions().catch(() => ({ github: false }))).github;
});

// GitHub sign-in sends people back here with ?error= when it can't finish.
const githubErrors = {
    'github-email': 'GitHub did not share an email address with us. Make one visible to apps in your GitHub settings, then try again.',
    'github-linked': 'That email address already belongs to an account linked to a different GitHub account.',
    'github-failed': 'GitHub sign-in did not finish. Please try again.',
};
const githubError = computed(() => githubErrors[route.query.error] ?? '');

// Only paths inside the app, so a crafted link can't send someone elsewhere after signing in.
const destination = computed(() => {
    const redirect = route.query.redirect;
    return typeof redirect === 'string' && redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : '/';
});

async function submit() {
    busy.value = true;
    errors.value = {};
    message.value = '';
    try {
        if (registering.value) {
            await register({ ...form });
        } else {
            await signIn({ email: form.email, password: form.password });
        }
        router.push(destination.value);
    } catch (error) {
        errors.value = error.errors ?? {};
        // Errors for fields the form shows go under them; anything else goes in the summary.
        const shown = ['name', 'email', 'password'];
        const other = Object.entries(errors.value).find(([field]) => !shown.includes(field));
        message.value = other ? other[1][0] : Object.keys(errors.value).length ? '' : error.message;
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="form-page">
        <h1>{{ registering ? 'Create an account' : 'Sign in' }}</h1>
        <p class="lead">
            An account keeps your progress and review schedule in step across browsers and the mobile app. Whatever
            you've done in this browser so far comes with you.
        </p>

        <p v-if="githubError" class="error" role="alert">{{ githubError }}</p>

        <a v-if="github" class="btn" href="/auth/github">Continue with GitHub</a>

        <form class="card" novalidate @submit.prevent="submit">
            <div v-if="registering" class="field">
                <label for="name">Name</label>
                <input id="name" v-model="form.name" type="text" autocomplete="name" required :aria-invalid="Boolean(errors.name)" aria-describedby="name-error" />
                <p v-if="errors.name" id="name-error" class="error">{{ errors.name[0] }}</p>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" v-model="form.email" type="email" autocomplete="email" required :aria-invalid="Boolean(errors.email)" aria-describedby="email-error" />
                <p v-if="errors.email" id="email-error" class="error">{{ errors.email[0] }}</p>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    :autocomplete="registering ? 'new-password' : 'current-password'"
                    required
                    :aria-invalid="Boolean(errors.password)"
                    aria-describedby="password-error"
                />
                <p v-if="errors.password" id="password-error" class="error">{{ errors.password[0] }}</p>
                <RouterLink v-if="!registering" :to="{ name: 'forgot-password' }">Forgot your password?</RouterLink>
            </div>

            <div v-if="registering" class="field">
                <label for="password-confirmation">Confirm password</label>
                <input id="password-confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required />
            </div>

            <p v-if="message" class="error" role="alert">{{ message }}</p>

            <button type="submit" class="btn btn-primary" :disabled="busy">
                {{ registering ? 'Create account' : 'Sign in' }}
            </button>
        </form>

        <p v-if="registering">
            Already have an account? <RouterLink :to="{ name: 'sign-in', query: route.query }">Sign in</RouterLink>
        </p>
        <p v-else>
            New here? <RouterLink :to="{ name: 'register', query: route.query }">Create an account</RouterLink>
        </p>
    </div>
</template>

