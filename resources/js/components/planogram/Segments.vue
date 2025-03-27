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
        @transfer-layer="handleTransferLayer"
    />
</template>
<script setup lang="ts">
import SegmentVue from './Segment.vue';
import { Gondola, Layer, Segment, Shelf } from './planogram';

const props = defineProps<{
    gondola: Gondola;
    shelf: Shelf;
    segments: Segment[];
    scaleFactor: number;
}>();

interface TransferLayerData {
    layer: Layer;
    segment: Segment;
    fromShelfId: string;
    toShelfId: string;
}

const emit = defineEmits<{
    'update:shelf': [shelf: Shelf | Layer];
    'update:layer': [layer: Layer | Shelf];
    'update:segment': [segment: Segment | Shelf];
    'transfer-layer': [transferData: TransferLayerData];
}>();

// Função para passar atualizações para o componente pai
const updateLayer = (layer: Shelf | Layer) => {
    emit('update:layer', layer);
};

const updateSegment = (segment: Segment | Shelf) => {
    emit('update:segment', segment);
};

// Função para repassar eventos de transferência de layer
const handleTransferLayer = (transferData: TransferLayerData) => { 
    emit('transfer-layer', transferData);
};
</script>
