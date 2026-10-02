<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Server, ShieldAlert, Wifi, WifiOff } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Connection {
    id: number;
    name: string;
    device_id: string;
    last_seen_at: string | null;
    revoked_at: string | null;
    reports_count: number;
    latest_report_at: string | null;
    latest_title: string | null;
    latest_event_count: number;
    has_unexpected_shutdown: boolean;
}

defineProps<{ connections: Connection[] }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Registros', href: '/records' }];

const status = (connection: Connection) => {
    if (connection.revoked_at) return 'Revocada';
    if (!connection.last_seen_at) return 'Sin contacto';
    return Date.now() - new Date(connection.last_seen_at).getTime() < 30 * 60 * 1000 ? 'Online' : 'Sin contacto reciente';
};

const statusClass = (connection: Connection) => status(connection) === 'Online' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400';
</script>

<template>
    <Head title="Registros" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-8">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div><h1 class="text-2xl font-semibold tracking-tight">Registros</h1><p class="text-sm text-muted-foreground">Una vista rápida del estado y los eventos de cada PC o servidor.</p></div>
                <Link :href="route('connectivity.index')" class="text-sm font-medium text-primary hover:underline">Administrar conexiones</Link>
            </div>

            <div v-if="connections.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Link v-for="connection in connections" :key="connection.id" :href="route('records.show', connection.id)" class="group rounded-2xl border bg-card p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3"><div class="flex items-center gap-3"><div class="rounded-xl bg-primary/10 p-3 text-primary"><Server class="size-5" /></div><div><h2 class="font-semibold group-hover:text-primary">{{ connection.name }}</h2><p class="text-xs text-muted-foreground">{{ connection.device_id }}</p></div></div><span v-if="status(connection) === 'Online'"><Wifi class="size-5 text-emerald-500" /></span><span v-else><WifiOff class="size-5 text-muted-foreground" /></span></div>
                    <div class="mt-5 flex items-center justify-between text-sm"><span :class="statusClass(connection)">{{ status(connection) }}</span><span class="text-muted-foreground">{{ connection.reports_count }} reportes</span></div>
                    <div class="mt-4 grid grid-cols-2 gap-3"><div class="rounded-xl bg-muted/60 p-3"><p class="text-xs text-muted-foreground">Último evento</p><p class="mt-1 truncate text-sm font-medium">{{ connection.latest_title || 'Sin reportes' }}</p></div><div class="rounded-xl bg-muted/60 p-3"><p class="text-xs text-muted-foreground">Eventos detectados</p><p class="mt-1 text-lg font-semibold">{{ connection.latest_event_count }}</p></div></div>
                    <div v-if="connection.has_unexpected_shutdown" class="mt-4 flex items-center gap-2 rounded-lg bg-red-500/10 px-3 py-2 text-xs font-medium text-red-700 dark:text-red-300"><ShieldAlert class="size-4" /> Apagado o reinicio inesperado</div>
                    <p class="mt-4 text-xs text-muted-foreground">{{ connection.latest_report_at ? `Último reporte: ${new Date(connection.latest_report_at).toLocaleString()}` : 'Esperando el primer reporte' }}</p>
                </Link>
            </div>
            <div v-else class="rounded-2xl border border-dashed p-10 text-center"><Server class="mx-auto size-10 text-muted-foreground" /><h2 class="mt-4 font-semibold">Todavía no hay equipos registrados</h2><p class="mt-1 text-sm text-muted-foreground">Creá una conexión en Conectividad y configurá el agente en la PC.</p><Link :href="route('connectivity.index')" class="mt-4 inline-flex text-sm font-medium text-primary hover:underline">Ir a Conectividad</Link></div>
        </div>
    </AppLayout>
</template>
