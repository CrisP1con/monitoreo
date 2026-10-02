<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" :class="className" v-bind="$attrs">
        <rect x="4" y="5" width="32" height="24" rx="4" fill="none" stroke="currentColor" stroke-width="3" />
        <path d="M10 35h20M20 29v6M10 20h5l3-7 4 12 3-7h5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" />
    </svg>
</template>
