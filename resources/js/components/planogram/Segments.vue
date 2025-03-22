<template>
    <SegmentVue
        v-for="segment in segments"
        :key="segment.id"
        :shelf="shelf"
        :segment="segment"
        :layer="segment.layer"
        :scale-factor="scaleFactor"
        :gondola="gondola"
        @update:layer="updateLayer"
        @update:segment="updateSegment"
    />
</template>
<script setup lang="ts">
import SegmentVue from './Segment.vue';
import { Gondola, Layer, Segment, Shelf } from './planogram';

const props = defineProps<{ gondola: Gondola; shelf: Shelf; segments: Segment[]; scaleFactor: number }>();
const emit = defineEmits(['update:shelf', 'update:layer', 'update:segment']);

// Função para passar atualizações para o componente pai
const updateLayer = (layer: Layer) => {
    emit('update:layer', layer);
};

const updateSegment = (segment: Segment) => {
    emit('update:segment', segment);
};
</script> 
