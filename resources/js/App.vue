<script setup lang="ts"> 
import Plannerate from './components/planogram/Plannerate.vue';

const props = defineProps({
    record: {
        type: Object,
        required: true
    },
})

</script>

<template>
    <Plannerate :record="record"  />
</template>