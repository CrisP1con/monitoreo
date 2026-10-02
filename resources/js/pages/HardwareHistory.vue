<script setup lang="ts">
import { computed } from 'vue';
import { ArrowLeft, Cpu } from 'lucide-vue-next';
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';

interface TelemetrySample {
    captured_at: string;
    cpu_temperature_c: number | null;
    cpu_package_c: number | null;
    cpu_core_max_c: number | null;
    cpu_cores: Record<string, number>;
    gpu_temperature_c: number | null;
    motherboard_temperature_c: number | null;
    disk_temperature_c: number | null;
    memory_used_percent: number | null;
}

const props = defineProps<{ connection: { id: number; name: string; device_id: string }; telemetry: TelemetrySample[] }>();
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Registros', href: '/records' },
    { title: props.connection.name, href: `/records/${props.connection.id}` },
    { title: 'Histórico de hardware', href: `/records/${props.connection.id}/hardware` },
]);
const coreKeys = computed(() => Array.from(new Set(props.telemetry.flatMap((sample) => Object.keys(sample.cpu_cores ?? {}))).values()).sort((a, b) => Number(a) - Number(b)));
const orderedTelemetry = computed(() => props.telemetry.slice().sort((a, b) => new Date(b.captured_at).getTime() - new Date(a.captured_at).getTime()));
</script>

<template>
    <Head :title="`Hardware · ${connection.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <Link :href="route('records.show', connection.id)" class="mt-1 rounded-lg p-2 hover:bg-muted"><ArrowLeft class="size-5" /></Link>
                    <div><h1 class="text-2xl font-semibold tracking-tight">Histórico de hardware</h1><p class="text-sm text-muted-foreground">{{ connection.name }} · {{ connection.device_id }}</p></div>
                </div>
                <Button variant="outline" as-child><Link :href="route('records.show', connection.id)">Volver al incidente</Link></Button>
            </div>

            <section class="rounded-2xl border bg-card p-5">
                <div class="flex items-center gap-3"><div class="rounded-xl bg-blue-500/10 p-3 text-blue-600"><Cpu class="size-5" /></div><div><h2 class="font-semibold">Histórico de sensores</h2><p class="text-sm text-muted-foreground">{{ telemetry.length }} muestras. Las lecturas se guardan cuando cambian significativamente.</p></div></div>
                <div v-if="telemetry.length" class="mt-5 overflow-x-auto"><table class="w-full min-w-[1080px] text-left text-sm"><thead class="border-b text-xs uppercase text-muted-foreground"><tr><th class="px-3 py-3">Hora</th><th class="px-3 py-3">CPU general</th><th class="px-3 py-3">Package</th><th class="px-3 py-3">Máx. core</th><th v-for="key in coreKeys" :key="key" class="px-3 py-3">Core {{ key }}</th><th class="px-3 py-3">GPU</th><th class="px-3 py-3">Placa madre</th><th class="px-3 py-3">Disco</th><th class="px-3 py-3">RAM</th></tr></thead><tbody><tr v-for="sample in orderedTelemetry" :key="sample.captured_at" class="border-b last:border-0"><td class="whitespace-nowrap px-3 py-3 text-xs">{{ new Date(sample.captured_at).toLocaleString() }}</td><td class="px-3 py-3">{{ sample.cpu_temperature_c !== null ? `${sample.cpu_temperature_c.toFixed(1)} °C` : '-' }}</td><td class="px-3 py-3">{{ sample.cpu_package_c !== null ? `${sample.cpu_package_c.toFixed(1)} °C` : '-' }}</td><td class="px-3 py-3">{{ sample.cpu_core_max_c !== null ? `${sample.cpu_core_max_c.toFixed(1)} °C` : '-' }}</td><td v-for="key in coreKeys" :key="`${sample.captured_at}-${key}`" class="px-3 py-3">{{ sample.cpu_cores?.[key] !== undefined ? `${sample.cpu_cores[key].toFixed(1)} °C` : '-' }}</td><td class="px-3 py-3">{{ sample.gpu_temperature_c !== null ? `${sample.gpu_temperature_c.toFixed(1)} °C` : '-' }}</td><td class="px-3 py-3">{{ sample.motherboard_temperature_c !== null ? `${sample.motherboard_temperature_c.toFixed(1)} °C` : '-' }}</td><td class="px-3 py-3">{{ sample.disk_temperature_c !== null ? `${sample.disk_temperature_c.toFixed(1)} °C` : '-' }}</td><td class="px-3 py-3">{{ sample.memory_used_percent !== null ? `${sample.memory_used_percent.toFixed(1)} %` : '-' }}</td></tr></tbody></table></div>
                <p v-else class="mt-5 rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">Todavía no hay muestras de hardware.</p>
            </section>
        </div>
    </AppLayout>
</template>
