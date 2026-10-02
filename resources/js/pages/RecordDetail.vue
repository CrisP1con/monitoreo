<script setup lang="ts">
import { computed, ref } from 'vue';
import { ArrowLeft, Cpu, HardDrive, Monitor, Power, Server, ShieldAlert } from 'lucide-vue-next';
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';

interface EventItem { time: string; importance: string; category: string; provider: string; id: number; message: string; }
interface Report { id: number; incident_type: string; collected_at: string; started_at: string; event_count: number; has_unexpected_shutdown: boolean; summary: { title?: string; chain?: string; recommendations?: string[]; counts?: Record<string, number>; }; events: EventItem[]; system_state: Record<string, unknown> | null; }
interface Connection { id: number; name: string; device_id: string; last_seen_at: string | null; revoked_at: string | null; reports_count: number; }
interface TelemetrySample { captured_at: string; cpu_temperature_c: number | null; cpu_package_c: number | null; cpu_core_max_c: number | null; cpu_cores: Record<string, number>; gpu_temperature_c: number | null; motherboard_temperature_c: number | null; disk_temperature_c: number | null; memory_used_percent: number | null; }

const props = defineProps<{ connection: Connection; reports: Report[]; telemetry: TelemetrySample[] }>();
const selectedReportId = ref(props.reports[0]?.id ?? null);
const eventFilter = ref('Todos');
const breadcrumbs = computed<BreadcrumbItem[]>(() => [{ title: 'Registros', href: '/records' }, { title: props.connection.name, href: `/records/${props.connection.id}` }]);
const report = computed(() => props.reports.find((item) => item.id === selectedReportId.value) ?? props.reports[0]);
const categories = computed(() => ['Todos', ...new Set((report.value?.events ?? []).map((event) => event.category))]);
const visibleEvents = computed(() => eventFilter.value === 'Todos' ? (report.value?.events ?? []) : (report.value?.events ?? []).filter((event) => event.category === eventFilter.value));
const latestTelemetry = computed(() => props.telemetry.at(-1));
const temperatureStats = computed(() => {
    const fields = [
        ['CPU', 'cpu_temperature_c'],
        ['GPU', 'gpu_temperature_c'],
        ['Placa madre', 'motherboard_temperature_c'],
        ['Disco', 'disk_temperature_c'],
    ] as const;

    return fields.map(([label, key]) => {
        const values = props.telemetry.map((sample) => sample[key]).filter((value): value is number => value !== null);
        return { label, current: latestTelemetry.value?.[key] ?? null, maximum: values.length ? Math.max(...values) : null, average: values.length ? values.reduce((sum, value) => sum + value, 0) / values.length : null };
    });
});
</script>

<template>
    <Head :title="connection.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4"><div class="flex items-start gap-3"><Link :href="route('records.index')" class="mt-1 rounded-lg p-2 hover:bg-muted"><ArrowLeft class="size-5" /></Link><div><h1 class="text-2xl font-semibold tracking-tight">{{ connection.name }}</h1><p class="text-sm text-muted-foreground">{{ connection.device_id }} · {{ connection.reports_count }} reportes recibidos</p></div></div><div class="rounded-full px-3 py-1 text-sm" :class="connection.revoked_at ? 'bg-red-500/10 text-red-700' : 'bg-emerald-500/10 text-emerald-700'">{{ connection.revoked_at ? 'Conexión revocada' : 'Conexión activa' }}</div></div>

            <div v-if="report" class="grid gap-4 md:grid-cols-3"><div class="rounded-2xl border bg-card p-5 md:col-span-2"><div class="flex items-start gap-3"><div class="rounded-xl bg-red-500/10 p-3 text-red-600"><ShieldAlert class="size-5" /></div><div><p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Diagnóstico principal</p><h2 class="mt-1 text-xl font-semibold">{{ report.summary.title || 'Eventos importantes para revisar' }}</h2><p class="mt-3 text-sm text-muted-foreground">{{ report.summary.chain }}</p></div></div><div v-if="report.summary.recommendations?.length" class="mt-5 border-t pt-4"><p class="text-sm font-semibold">Acciones recomendadas</p><ul class="mt-2 list-inside list-disc space-y-1 text-sm text-muted-foreground"><li v-for="recommendation in report.summary.recommendations" :key="recommendation">{{ recommendation }}</li></ul></div></div><div class="grid gap-4"><div class="rounded-2xl border bg-card p-5"><div class="flex items-center gap-2 text-sm text-muted-foreground"><Power class="size-4" /> Estado del último reporte</div><p class="mt-3 text-2xl font-semibold">{{ report.has_unexpected_shutdown ? 'Reinicio inesperado' : 'Sin apagado inesperado' }}</p></div><div class="rounded-2xl border bg-card p-5"><div class="flex items-center gap-2 text-sm text-muted-foreground"><Monitor class="size-4" /> Eventos</div><p class="mt-3 text-2xl font-semibold">{{ report.event_count }}</p></div></div></div>
            <div v-else class="rounded-2xl border border-dashed p-10 text-center"><HardDrive class="mx-auto size-10 text-muted-foreground" /><h2 class="mt-4 font-semibold">Todavía no hay reportes</h2><p class="mt-1 text-sm text-muted-foreground">Cuando el agente envíe información, el diagnóstico aparecerá acá.</p></div>

            <section class="rounded-2xl border bg-card p-5">
                <div class="flex flex-wrap items-center justify-between gap-3"><div class="flex items-start gap-3"><div class="rounded-xl bg-blue-500/10 p-3 text-blue-600"><Cpu class="size-5" /></div><div><h2 class="font-semibold">Hardware</h2><p class="text-sm text-muted-foreground">Último estado recibido y evolución de los sensores.</p></div></div><div class="flex items-center gap-3"><span class="text-xs text-muted-foreground">{{ telemetry.length }} muestras</span><Button variant="outline" size="sm" as-child><Link :href="route('records.hardware', connection.id)">Histórico de hardware</Link></Button></div></div>
                <div class="mt-4 rounded-xl bg-muted/40 p-4"><p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Última lectura</p><div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-5"><div v-for="stat in temperatureStats" :key="`current-${stat.label}`"><p class="text-xs text-muted-foreground">{{ stat.label }}</p><p class="mt-1 font-semibold">{{ stat.current !== null ? `${stat.current.toFixed(1)} °C` : 'Sin dato' }}</p></div><div><p class="text-xs text-muted-foreground">Memoria</p><p class="mt-1 font-semibold">{{ latestTelemetry?.memory_used_percent !== null && latestTelemetry?.memory_used_percent !== undefined ? `${latestTelemetry.memory_used_percent.toFixed(1)} %` : 'Sin dato' }}</p></div></div></div>
                <div v-if="telemetry.length" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"><div v-for="stat in temperatureStats" :key="stat.label" class="rounded-xl border p-4"><p class="text-xs uppercase tracking-wide text-muted-foreground">{{ stat.label }}</p><p class="mt-2 text-2xl font-semibold">{{ stat.current !== null ? `${stat.current.toFixed(1)} °C` : 'Sin dato' }}</p><p class="mt-1 text-xs text-muted-foreground">Máx. {{ stat.maximum !== null ? `${stat.maximum.toFixed(1)} °C` : '-' }} · Prom. {{ stat.average !== null ? `${stat.average.toFixed(1)} °C` : '-' }}</p></div></div>
                <p v-if="telemetry.length" class="mt-5 rounded-xl border border-dashed p-4 text-center text-sm text-muted-foreground">La lista completa está en el histórico de hardware. Los eventos del sistema permanecen más abajo.</p>
                <p v-else class="mt-5 rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground">Todavía no hay muestras. Activá la telemetría en el agente para comenzar a registrar sensores.</p>
            </section>

            <div v-if="reports.length" class="grid gap-6 lg:grid-cols-[280px_1fr]"><section class="rounded-2xl border bg-card p-4"><div class="flex items-center gap-2 font-semibold"><Server class="size-4" /> Historial</div><div class="mt-3 grid gap-2"><button v-for="item in reports" :key="item.id" class="rounded-xl border p-3 text-left transition hover:border-primary/50" :class="item.id === report?.id ? 'border-primary bg-primary/5' : ''" @click="selectedReportId = item.id"><p class="text-sm font-medium">{{ item.summary.title || item.incident_type }}</p><p class="mt-1 text-xs text-muted-foreground">{{ new Date(item.started_at).toLocaleString() }}</p><p class="mt-1 text-xs text-muted-foreground">{{ item.event_count }} eventos</p></button></div></section><section class="min-w-0 rounded-2xl border bg-card p-4"><div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="font-semibold">Eventos del incidente</h2><p class="text-xs text-muted-foreground">Ventana iniciada {{ report ? new Date(report.started_at).toLocaleString() : '-' }}</p></div><select v-model="eventFilter" class="rounded-lg border bg-background px-3 py-2 text-sm"><option v-for="category in categories" :key="category">{{ category }}</option></select></div><div class="mt-4 overflow-x-auto"><table class="w-full min-w-[720px] text-left text-sm"><thead class="border-b text-xs uppercase text-muted-foreground"><tr><th class="px-3 py-3">Hora</th><th class="px-3 py-3">Importancia</th><th class="px-3 py-3">Categoría</th><th class="px-3 py-3">Origen / ID</th><th class="px-3 py-3">Detalle</th></tr></thead><tbody><tr v-for="event in visibleEvents" :key="`${event.time}-${event.provider}-${event.id}`" class="border-b last:border-0"><td class="whitespace-nowrap px-3 py-3 text-xs">{{ new Date(event.time).toLocaleString() }}</td><td class="px-3 py-3"><span class="rounded-full px-2 py-1 text-xs" :class="event.importance === 'Alta' ? 'bg-red-500/10 text-red-700' : 'bg-amber-500/10 text-amber-700'">{{ event.importance }}</span></td><td class="px-3 py-3">{{ event.category }}</td><td class="px-3 py-3">{{ event.provider }} / {{ event.id }}</td><td class="max-w-[520px] px-3 py-3 text-xs text-muted-foreground">{{ event.message }}</td></tr><tr v-if="!visibleEvents.length"><td colspan="5" class="px-3 py-8 text-center text-muted-foreground">No hay eventos para este filtro.</td></tr></tbody></table></div></section></div>
        </div>
    </AppLayout>
</template>
