import { createRouter, createWebHistory } from 'vue-router';
import HomePage from './pages/HomePage.vue';
import CoursePage from './pages/CoursePage.vue';
import NotFoundPage from './pages/NotFoundPage.vue';
import AccountPage from './pages/AccountPage.vue';

export default createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: HomePage },
        { path: '/courses/:course', name: 'course', component: CoursePage, props: true },
        {
            path: '/courses/:course/lessons/:lesson/:step(learn|sandbox|quiz|challenges)?',
            name: 'lesson',
            component: () => import('./pages/LessonPage.vue'),
            props: true,
        },
        { path: '/review', name: 'review', component: () => import('./pages/ReviewPage.vue') },
        { path: '/sign-in', name: 'sign-in', component: AccountPage, props: { mode: 'sign-in' } },
        { path: '/register', name: 'register', component: AccountPage, props: { mode: 'register' } },
        { path: '/forgot-password', name: 'forgot-password', component: () => import('./pages/ForgotPasswordPage.vue') },
        { path: '/reset-password/:token', name: 'reset-password', component: () => import('./pages/ResetPasswordPage.vue'), props: true },
        { path: '/account', name: 'account', component: () => import('./pages/SettingsPage.vue') },
        { path: '/search', name: 'search', component: () => import('./pages/SearchPage.vue') },
        { path: '/courses/:course/certificate', name: 'certificate', component: () => import('./pages/CertificatePage.vue'), props: true },
        { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundPage },
    ],
    scrollBehavior(to, from) {
        // Switching lesson steps keeps the header in view; new pages start at the top.
        if (to.name === 'lesson' && from.name === 'lesson' && to.params.lesson === from.params.lesson) {
            return false;
        }
        return { top: 0 };
    },
});
