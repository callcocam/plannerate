<script setup lang="ts">
import { ref } from 'vue';
import Gondola from './Gondola.vue';

const props = defineProps({
    record: {
        type: Object,
        required: true
    },
})

const sections = ref(props.record.sections);
</script>

<template>
    <Gondola :record="record" :sections="sections" />
</template>