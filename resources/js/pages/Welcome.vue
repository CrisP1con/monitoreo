<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
</script>

<template>
    <Head title="System Monitor" />
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
            <AppLogo />
            <nav class="flex items-center gap-3 text-sm">
                <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="rounded-lg bg-cyan-400 px-4 py-2 font-semibold text-slate-950 hover:bg-cyan-300">Abrir monitor</Link>
                <template v-else>
                    <Link :href="route('login')" class="rounded-lg px-4 py-2 text-slate-300 hover:bg-slate-800 hover:text-white">Ingresar</Link>
                    <Link :href="route('register')" class="rounded-lg bg-cyan-400 px-4 py-2 font-semibold text-slate-950 hover:bg-cyan-300">Crear cuenta</Link>
                </template>
            </nav>
        </header>
        <main class="mx-auto grid max-w-6xl gap-12 px-6 pb-20 pt-16 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
            <section class="space-y-7">
                <div class="inline-flex items-center gap-2 rounded-full border border-cyan-400/30 bg-cyan-400/10 px-3 py-1 text-xs font-medium text-cyan-300"><span class="size-2 rounded-full bg-emerald-400" /> Monitoreo activo</div>
                <h1 class="max-w-3xl text-5xl font-semibold tracking-tight sm:text-6xl">Entendé qué pasa en tus equipos.</h1>
                <p class="max-w-2xl text-lg leading-8 text-slate-400">Centralizá los eventos importantes de Windows, detectá fallas gráficas, hardware y apagados inesperados desde un solo lugar.</p>
                <div class="flex flex-wrap gap-3"><Link :href="route('register')" class="rounded-lg bg-cyan-400 px-5 py-3 font-semibold text-slate-950 hover:bg-cyan-300">Empezar a monitorear</Link><Link :href="route('login')" class="rounded-lg border border-slate-700 px-5 py-3 font-semibold text-slate-200 hover:bg-slate-800">Ingresar</Link></div>
            </section>
            <section class="rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl shadow-cyan-950/30">
                <div class="mb-5 flex items-center justify-between"><div><p class="text-sm text-slate-400">Estado del sistema</p><p class="mt-1 text-2xl font-semibold">Operativo</p></div><div class="rounded-xl bg-emerald-400/10 px-3 py-2 text-sm font-medium text-emerald-300">Salud 98%</div></div>
                <div class="grid gap-3 sm:grid-cols-2"><div class="rounded-2xl bg-slate-800 p-4"><p class="text-sm text-slate-400">Equipos conectados</p><p class="mt-2 text-3xl font-semibold">12</p></div><div class="rounded-2xl bg-slate-800 p-4"><p class="text-sm text-slate-400">Eventos críticos</p><p class="mt-2 text-3xl font-semibold text-amber-300">2</p></div></div>
                <div class="mt-4 rounded-2xl bg-slate-800 p-4"><div class="flex items-center justify-between text-sm"><span class="text-slate-400">Último análisis</span><span class="text-slate-200">hace 2 minutos</span></div><div class="mt-4 h-2 rounded-full bg-slate-700"><div class="h-2 w-4/5 rounded-full bg-cyan-400" /></div></div>
            </section>
        </main>
    </div>
</template>
