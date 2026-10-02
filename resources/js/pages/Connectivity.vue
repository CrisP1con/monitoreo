<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

interface Connection {
    id: number;
    name: string;
    device_id: string;
    last_seen_at: string | null;
    revoked_at: string | null;
    reports_count: number;
}

interface Credentials {
    name: string;
    device_id: string;
    endpoint: string;
    token: string;
}

defineProps<{
    endpoint: string;
    connections: Connection[];
    newConnection?: Credentials | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Conectividad', href: '/connectivity' }];
const form = useForm({ name: '', device_id: '' });
const copied = ref('');

const createConnection = () => {
    form.post(route('connectivity.store'), { preserveScroll: true, onSuccess: () => form.reset() });
};

const copy = async (value: string, label: string) => {
    await navigator.clipboard.writeText(value);
    copied.value = label;
    window.setTimeout(() => copied.value = '', 1800);
};

const agentJson = (connection: Credentials) => JSON.stringify({
    endpoint: connection.endpoint,
    telemetry_endpoint: connection.endpoint.replace('/reports', '/telemetry'),
    token: connection.token,
    device_id: connection.device_id,
    device_name: connection.name,
    agent_version: '1.0.0',
    lookback_minutes: 15,
    telemetry_enabled: true,
    telemetry_sample_interval_seconds: 1,
    telemetry_upload_interval_seconds: 300,
    telemetry_change_threshold_c: 1,
}, null, 2);
</script>

<template>
    <Head title="Conectividad" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-8">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Conectividad</h1>
                <p class="text-sm text-muted-foreground">Generá accesos seguros para que tus agentes envíen diagnósticos.</p>
            </div>

            <div v-if="newConnection?.token" class="rounded-xl border border-emerald-300 bg-emerald-50 p-5 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-100">
                <h2 class="font-semibold">Conexión creada: {{ newConnection.name }}</h2>
                <p class="mt-1 text-sm">El token se muestra una sola vez. Copialo ahora y guardalo en <code>agentsettings.json</code>.</p>
                <div class="mt-4 grid gap-3">
                    <div class="flex gap-2"><Input readonly :model-value="newConnection.endpoint" /><Button type="button" @click="copy(newConnection.endpoint, 'endpoint')">{{ copied === 'endpoint' ? 'Copiado' : 'Copiar endpoint' }}</Button></div>
                    <div class="flex gap-2"><Input readonly :model-value="newConnection.token" /><Button type="button" @click="copy(newConnection.token, 'token')">{{ copied === 'token' ? 'Copiado' : 'Copiar token' }}</Button></div>
                    <div class="relative"><textarea readonly class="min-h-44 w-full rounded-md border bg-background p-3 font-mono text-xs" :value="agentJson(newConnection)" /><Button type="button" class="absolute right-2 top-2" @click="copy(agentJson(newConnection), 'json')">{{ copied === 'json' ? 'Copiado' : 'Copiar JSON' }}</Button></div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,360px)_1fr]">
                <section class="rounded-xl border bg-card p-5 shadow-sm">
                    <h2 class="font-semibold">Nueva conexión</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Creá un token independiente por cada PC.</p>
                    <form class="mt-5 grid gap-4" @submit.prevent="createConnection">
                        <div class="grid gap-2"><Label for="name">Nombre</Label><Input id="name" v-model="form.name" placeholder="Servidor principal" required /><p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p></div>
                        <div class="grid gap-2"><Label for="device_id">ID del equipo</Label><Input id="device_id" v-model="form.device_id" placeholder="SERVER-01" required /><p v-if="form.errors.device_id" class="text-sm text-red-600">{{ form.errors.device_id }}</p></div>
                        <Button type="submit" :disabled="form.processing">Generar endpoint y token</Button>
                    </form>
                </section>

                <section class="rounded-xl border bg-card p-5 shadow-sm">
                    <h2 class="font-semibold">Conexiones registradas</h2>
                    <div v-if="connections.length" class="mt-4 divide-y">
                        <div v-for="connection in connections" :key="connection.id" class="flex flex-wrap items-center justify-between gap-3 py-4">
                            <div><p class="font-medium">{{ connection.name }}</p><p class="text-sm text-muted-foreground">{{ connection.device_id }} · {{ connection.reports_count }} reportes</p><p class="text-xs text-muted-foreground">{{ connection.revoked_at ? 'Revocada' : connection.last_seen_at ? `Último contacto: ${new Date(connection.last_seen_at).toLocaleString()}` : 'Nunca conectada' }}</p></div>
                            <div class="flex gap-2"><Button v-if="!connection.revoked_at" variant="outline" size="sm" as-child><Link :href="route('connectivity.rotate', connection.id)" method="post" as="button">Rotar token</Link></Button><Button v-if="!connection.revoked_at" variant="destructive" size="sm" as-child><Link :href="route('connectivity.revoke', connection.id)" method="post" as="button">Revocar</Link></Button></div>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">Todavía no hay conexiones.</p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
