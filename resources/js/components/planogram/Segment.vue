<template>
    <div class="segment">
        <LayerVue
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
    </div>
</template>
<script setup lang="ts">
import LayerVue from './Layer.vue';
import { Gondola, Layer, Segment, Shelf } from './planogram';

const props = defineProps<{
    gondola: Gondola;
    shelf: Shelf;
    segment: Segment;
    scaleFactor: number;
}>();

interface TransferLayerData {
    layer: Layer;
    segment: Segment;
    fromShelfId: string;
    toShelfId: string;
}

const emit = defineEmits<{
    'update:layer': [layer: Shelf | Layer];
    'update:segment': [segment: Shelf | Segment];
    'transfer-layer': [transferData: TransferLayerData];
}>();

// Função para passar atualizações para o componente pai
const updateLayer = (layer: Shelf | Layer) => {
    emit('update:layer', layer);
};

const updateSegment = (segment: Shelf | Segment) => {
    emit('update:segment', segment);
};

// Função para repassar eventos de transferência de layer
const handleTransferLayer = (transferData: TransferLayerData) => {
    console.log('Segment: repassando transferência de layer', transferData);
    emit('transfer-layer', transferData);
};
</script>
