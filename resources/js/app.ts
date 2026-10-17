import { createInertiaApp, router } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import FlixReadAuthLayout from '@/layouts/auth/FlixReadAuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Watoon';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
            case name === 'dashboard/User':
            case name.startsWith('films/'):
            case name.startsWith('messages/'):
            case name === 'settings/UserSettings':
            case name.startsWith('subscription/'):
                return null;
            case name === 'auth/Login':
            case name === 'auth/Register':
                return FlixReadAuthLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    withApp: (app) => {
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

// Prevent overscroll bounce / white canvas flash on macOS and iOS, and keep viewport canvas in sync
const syncPageCanvas = (component?: string) => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.style.overscrollBehavior = 'none';
    document.documentElement.style.setProperty('overscroll-behavior-y', 'none');
    document.documentElement.style.setProperty('overscroll-behavior-x', 'none');
    document.body.style.overscrollBehavior = 'none';
    document.body.style.setProperty('overscroll-behavior-y', 'none');
    document.body.style.setProperty('overscroll-behavior-x', 'none');

    const isLightPage =
        component === 'Welcome' ||
        component === 'auth/Login' ||
        component === 'auth/Register';
    const bgColor = isLightPage ? '#FAFAF7' : '#0E0F11';

    document.documentElement.style.backgroundColor = bgColor;
    document.body.style.backgroundColor = bgColor;

    const themeColorMeta = document.querySelector('meta[name="theme-color"]');
    if (themeColorMeta) {
        themeColorMeta.setAttribute('content', bgColor);
    }
};

syncPageCanvas();

router.on('navigate', (event) => {
    syncPageCanvas(event.detail.page.component);
});

