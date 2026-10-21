<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '../api';
import { auth, deleteAccount, refreshUser } from '../auth';

const route = useRoute();
const router = useRouter();

const justVerified = computed(() => route.query.verified === '1');
const resendMessage = ref('');
const resending = ref(false);

const confirmation = ref('');
const deleteError = ref('');
const deleting = ref(false);

// The page needs an account; guests are sent to sign in and brought back.
watch(
    () => [auth.ready, auth.user],
    ([ready, user]) => {
        if (ready && !user) router.replace({ name: 'sign-in', query: { redirect: route.fullPath } });
    },
    { immediate: true },
);

onMounted(() => {
    if (justVerified.value) refreshUser();
});

async function resend() {
    resending.value = true;
    try {
        resendMessage.value = (await api.resendVerification()).message;
    } catch (e) {
        resendMessage.value = e.message;
    } finally {
        resending.value = false;
    }
}

async function remove() {
    deleting.value = true;
    deleteError.value = '';
    try {
        await deleteAccount(auth.user.has_password ? { password: confirmation.value } : { confirmation: confirmation.value });
        router.push({ name: 'home' });
    } catch (e) {
        deleteError.value = Object.values(e.errors ?? {})[0]?.[0] ?? e.message;
    } finally {
        deleting.value = false;
    }
}
</script>

<template>
    <div v-if="auth.user" class="form-page">
        <h1>Your account</h1>

        <p v-if="justVerified && auth.user.email_verified" class="notice" role="status">Thanks, your email address is confirmed.</p>

        <section class="card" aria-labelledby="profile-heading">
            <h2 id="profile-heading">{{ auth.user.name }}</h2>
            <p>
                {{ auth.user.email }}
                <template v-if="auth.user.github"> · signs in with GitHub</template>
            </p>
            <template v-if="!auth.user.email_verified">
                <p class="notice warning">
                    Please confirm your email address with the link we sent you, so you can reset your password if you
                    ever need to.
                </p>
                <button type="button" class="btn" :disabled="resending" @click="resend">Send the link again</button>
                <p v-if="resendMessage" role="status">{{ resendMessage }}</p>
            </template>
        </section>

        <section class="card" aria-labelledby="export-heading">
            <h2 id="export-heading">Your data</h2>
            <p>Download everything we keep about you: your account, your progress in every lesson and your review schedule.</p>
            <a class="btn" href="/api/account/export" download="learnp-data.json">Download my data</a>
        </section>

        <section class="card danger" aria-labelledby="delete-heading">
            <h2 id="delete-heading">Delete your account</h2>
            <p>
                This deletes your account and all your progress for good. Progress on this browser is removed too. It
                can't be undone.
            </p>
            <form class="field" novalidate @submit.prevent="remove">
                <label for="delete-confirmation">
                    {{ auth.user.has_password ? 'Your password' : 'Type your email address to confirm' }}
                </label>
                <input
                    id="delete-confirmation"
                    v-model="confirmation"
                    :type="auth.user.has_password ? 'password' : 'email'"
                    :autocomplete="auth.user.has_password ? 'current-password' : 'off'"
                    required
                    :aria-invalid="Boolean(deleteError)"
                    aria-describedby="delete-error"
                />
                <p v-if="deleteError" id="delete-error" class="error">{{ deleteError }}</p>
                <button type="submit" class="btn btn-danger" :disabled="deleting || !confirmation">Delete my account</button>
            </form>
        </section>
    </div>
</template>
