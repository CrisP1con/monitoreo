<script setup lang="ts">
import { computed } from 'vue';
import { Activity, AlertTriangle, CheckCircle2, Monitor, Server } from 'lucide-vue-next';
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Stats { total_connections: number; active_connections: number; online_connections: number; reports_last_24h: number; unexpected_shutdowns: number; events_last_24h: number; }
interface Connection { id: number; name: string; device_id: string; last_seen_at: string | null; revoked_at: string | null; reports_count: number; status: 'online' | 'offline' | 'revoked'; latest_report: { collected_at: string | null; title: string; event_count: number; has_unexpected_shutdown: boolean } | null; telemetry: { captured_at: string | null; cpu_temperature_c: number | null; gpu_temperature_c: number | null; memory_used_percent: number | null } | null; }
interface Alert { id: number; connection_id: number; connection_name: string; collected_at: string | null; title: string; event_count: number; has_unexpected_shutdown: boolean; }

const props = defineProps<{ stats: Stats; connections: Connection[]; recent_alerts: Alert[]; generated_at: string }>();
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];
const onlinePercentage = computed(() => props.stats.active_connections ? Math.round((props.stats.online_connections / props.stats.active_connections) * 100) : 0);
const statusLabel = (status: Connection['status']): string => ({ online: 'En línea', offline: 'Sin contacto', revoked: 'Revocada' })[status];
const formatDate = (value: string | null): string => value ? new Date(value).toLocaleString() : 'Sin datos';
const formatTemperature = (value: number | null): string => value === null ? '-' : `${value.toFixed(1)} °C`;
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-8">
            <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm text-muted-foreground">Monitoreo general</p><h1 class="text-2xl font-semibold tracking-tight">Estado de tus equipos</h1><p class="mt-1 text-sm text-muted-foreground">Una vista rápida de conexiones, diagnósticos y alertas de las últimas 24 horas.</p></div><p class="text-xs text-muted-foreground">Actualizado {{ formatDate(generated_at) }}</p></div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border bg-card p-5"><div class="flex items-center justify-between"><span class="text-sm text-muted-foreground">Equipos registrados</span><Server class="size-5 text-blue-600" /></div><p class="mt-3 text-3xl font-semibold">{{ stats.total_connections }}</p><p class="mt-1 text-xs text-muted-foreground">{{ stats.active_connections }} conexiones activas</p></div>
                <div class="rounded-2xl border bg-card p-5"><div class="flex items-center justify-between"><span class="text-sm text-muted-foreground">Conectividad</span><Activity class="size-5 text-emerald-600" /></div><p class="mt-3 text-3xl font-semibold">{{ stats.online_connections }} <span class="text-base font-normal text-muted-foreground">en línea</span></p><p class="mt-1 text-xs text-muted-foreground">{{ onlinePercentage }}% de las conexiones activas</p></div>
                <div class="rounded-2xl border bg-card p-5"><div class="flex items-center justify-between"><span class="text-sm text-muted-foreground">Reportes recientes</span><Monitor class="size-5 text-violet-600" /></div><p class="mt-3 text-3xl font-semibold">{{ stats.reports_last_24h }}</p><p class="mt-1 text-xs text-muted-foreground">{{ stats.events_last_24h }} eventos analizados</p></div>
                <div class="rounded-2xl border bg-card p-5"><div class="flex items-center justify-between"><span class="text-sm text-muted-foreground">Apagados inesperados</span><AlertTriangle class="size-5" :class="stats.unexpected_shutdowns ? 'text-red-600' : 'text-emerald-600'" /></div><p class="mt-3 text-3xl font-semibold">{{ stats.unexpected_shutdowns }}</p><p class="mt-1 text-xs text-muted-foreground">En las últimas 24 horas</p></div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
                <section class="rounded-2xl border bg-card p-5"><div class="flex items-center justify-between gap-3"><div><h2 class="font-semibold">Equipos monitoreados</h2><p class="text-sm text-muted-foreground">Estado actual y última telemetría recibida.</p></div><Link :href="route('records.index')" class="text-sm font-medium text-primary hover:underline">Ver registros</Link></div>
                    <div v-if="connections.length" class="mt-4 grid gap-3"><Link v-for="connection in connections" :key="connection.id" :href="route('records.show', connection.id)" class="rounded-xl border p-4 transition hover:border-primary/50 hover:bg-muted/30"><div class="flex flex-wrap items-start justify-between gap-3"><div class="flex items-start gap-3"><div class="rounded-lg bg-muted p-2"><Server class="size-4" /></div><div><p class="font-medium">{{ connection.name }}</p><p class="text-xs text-muted-foreground">{{ connection.device_id }}</p></div></div><span class="rounded-full px-2.5 py-1 text-xs" :class="connection.status === 'online' ? 'bg-emerald-500/10 text-emerald-700' : connection.status === 'revoked' ? 'bg-red-500/10 text-red-700' : 'bg-amber-500/10 text-amber-700'"><span class="mr-1">●</span>{{ statusLabel(connection.status) }}</span></div><div class="mt-4 grid gap-3 text-sm sm:grid-cols-4"><div><p class="text-xs text-muted-foreground">CPU</p><p class="mt-1 font-medium">{{ formatTemperature(connection.telemetry?.cpu_temperature_c ?? null) }}</p></div><div><p class="text-xs text-muted-foreground">GPU</p><p class="mt-1 font-medium">{{ formatTemperature(connection.telemetry?.gpu_temperature_c ?? null) }}</p></div><div><p class="text-xs text-muted-foreground">Memoria</p><p class="mt-1 font-medium">{{ connection.telemetry?.memory_used_percent !== null && connection.telemetry?.memory_used_percent !== undefined ? `${connection.telemetry.memory_used_percent.toFixed(1)} %` : '-' }}</p></div><div><p class="text-xs text-muted-foreground">Último contacto</p><p class="mt-1 truncate font-medium">{{ formatDate(connection.last_seen_at) }}</p></div></div></Link></div>
                    <div v-else class="mt-4 rounded-xl border border-dashed p-8 text-center"><Server class="mx-auto size-8 text-muted-foreground" /><p class="mt-3 font-medium">Todavía no hay equipos registrados</p><p class="mt-1 text-sm text-muted-foreground">Creá una conexión para empezar a recibir diagnósticos.</p><Link :href="route('connectivity.index')" class="mt-3 inline-block text-sm font-medium text-primary hover:underline">Configurar conexión</Link></div>
                </section>

                <section class="rounded-2xl border bg-card p-5"><div class="flex items-center justify-between gap-3"><div><h2 class="font-semibold">Actividad reciente</h2><p class="text-sm text-muted-foreground">Últimos diagnósticos recibidos.</p></div><CheckCircle2 v-if="!recent_alerts.length" class="size-5 text-emerald-600" /><AlertTriangle v-else class="size-5 text-amber-600" /></div><div v-if="recent_alerts.length" class="mt-4 space-y-3"><Link v-for="alert in recent_alerts" :key="alert.id" :href="route('records.show', alert.connection_id)" class="block rounded-xl border p-3 transition hover:border-primary/50"><div class="flex items-start gap-3"><AlertTriangle v-if="alert.has_unexpected_shutdown" class="mt-0.5 size-4 shrink-0 text-red-600" /><Activity v-else class="mt-0.5 size-4 shrink-0 text-amber-600" /><div class="min-w-0"><p class="truncate text-sm font-medium">{{ alert.connection_name }}</p><p class="mt-1 truncate text-xs text-muted-foreground">{{ alert.title }}</p><p class="mt-1 text-xs text-muted-foreground">{{ alert.event_count }} eventos · {{ formatDate(alert.collected_at) }}</p></div></div></Link></div><div v-else class="mt-8 text-center"><CheckCircle2 class="mx-auto size-9 text-emerald-600" /><p class="mt-3 font-medium">Sin alertas recientes</p><p class="mt-1 text-sm text-muted-foreground">No se recibieron incidentes en las últimas 24 horas.</p></div></section>
            </div>
        </div>
    </AppLayout>
</template>
